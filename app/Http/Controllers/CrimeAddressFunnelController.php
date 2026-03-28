<?php

namespace App\Http\Controllers;

use App\Jobs\SendLocationReportEmail;
use App\Models\Location;
use App\Services\AddressServiceabilityService;
use App\Services\CrimeAddressPreviewBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrimeAddressFunnelController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('CrimeAddress/Index', [
            'initialAddress' => $request->query('address'),
            'initialLatitude' => $request->query('lat') !== null ? (float) $request->query('lat') : null,
            'initialLongitude' => $request->query('lng') !== null ? (float) $request->query('lng') : null,
        ]);
    }

    public function preview(
        Request $request,
        AddressServiceabilityService $serviceabilityService,
        CrimeAddressPreviewBuilder $previewBuilder
    ): JsonResponse {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.01|max:0.5',
        ]);

        $serviceability = $serviceabilityService->determineSupport(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['address'],
        );

        if (!$serviceability['supported']) {
            return response()->json([
                'supported' => false,
                'message' => 'We do not serve your address yet. We will look into adding your area and notify you if we do.',
                'serviceability' => $serviceability,
            ]);
        }

        return response()->json(
            $previewBuilder->build(
                $serviceability,
                $validated['address'],
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                (float) ($validated['radius'] ?? 0.25),
            )
        );
    }

    public function context(
        Request $request,
        AddressServiceabilityService $serviceabilityService,
        CrimeAddressPreviewBuilder $previewBuilder
    ): JsonResponse {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $serviceability = $serviceabilityService->determineSupport(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['address'],
        );

        if (!$serviceability['supported']) {
            return response()->json([
                'supported' => false,
                'message' => 'We do not serve your address yet. We will look into adding your area and notify you if we do.',
                'serviceability' => $serviceability,
            ]);
        }

        return response()->json(
            $previewBuilder->buildDeferredContext($serviceability)
        );
    }

    public function startTrial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        $effectiveTier = $user->getEffectiveTierDetails()['tier'];

        if (in_array($effectiveTier, ['basic', 'pro'], true)) {
            return response()->json([
                'active' => true,
                'message' => 'Your paid plan already supports recurring reports.',
            ]);
        }

        if ($user->hasUsedCrimeAddressTrial() && !$user->hasActiveCrimeAddressTrial()) {
            return response()->json([
                'message' => 'Your free trial has ended. Choose a plan to continue receiving reports.',
            ], 409);
        }

        $alreadyActive = $user->hasActiveCrimeAddressTrial();

        $location = $user->crime_address_trial_location_id
            ? $user->locations()->find($user->crime_address_trial_location_id)
            : null;

        if (!$location) {
            $location = new Location();
            $location->user_id = $user->id;
        }

        $location->fill([
            'name' => 'Crime Address Trial',
            'address' => $validated['address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'report' => 'daily',
            'language' => 'en',
        ]);
        $location->save();

        $startedAt = $user->crime_address_trial_started_at ?? now();
        $endsAt = $user->crime_address_trial_ends_at ?? now()->addDays(7);

        $user->forceFill([
            'crime_address_trial_started_at' => $startedAt,
            'crime_address_trial_ends_at' => $endsAt,
            'crime_address_trial_location_id' => $location->id,
        ])->save();

        SendLocationReportEmail::dispatch($location);

        return response()->json([
            'active' => true,
            'message' => $alreadyActive
                ? "Your free trial is active through {$endsAt->toFormattedDateString()}."
                : 'Your 7-day free trial has started.',
            'trial_ends_at' => $endsAt->toDateString(),
            'location' => [
                'id' => $location->id,
                'address' => $location->address,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
            ],
        ]);
    }
}
