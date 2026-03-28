<?php

namespace App\Http\Controllers;

use App\Models\CoverageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CoverageRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requested_address' => 'required|string|max:255',
            'normalized_address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'email' => 'required|email|max:255',
            'source_page' => 'nullable|string|max:255',
        ]);

        $normalizedAddress = Str::squish($validated['normalized_address'] ?? $validated['requested_address']);

        $existingRequest = CoverageRequest::query()
            ->where('email', $validated['email'])
            ->where(function ($query) use ($normalizedAddress, $validated) {
                $query->where('normalized_address', $normalizedAddress)
                    ->orWhere('requested_address', $validated['requested_address']);
            })
            ->first();

        if ($existingRequest) {
            $existingRequest->fill([
                'user_id' => $existingRequest->user_id ?? Auth::id(),
                'requested_address' => $validated['requested_address'],
                'normalized_address' => $normalizedAddress,
                'latitude' => $validated['latitude'] ?? $existingRequest->latitude,
                'longitude' => $validated['longitude'] ?? $existingRequest->longitude,
                'source_page' => $validated['source_page'] ?? $existingRequest->source_page,
                'status' => $existingRequest->status ?: 'pending',
                'request_count' => $existingRequest->request_count + 1,
            ])->save();

            return response()->json([
                'message' => 'We will look into adding your area and notify you if we do.',
                'coverage_request_id' => $existingRequest->id,
                'created' => false,
            ], 200);
        }

        $coverageRequest = CoverageRequest::create([
            'user_id' => Auth::id(),
            'email' => $validated['email'],
            'requested_address' => $validated['requested_address'],
            'normalized_address' => $normalizedAddress,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'source_page' => $validated['source_page'] ?? null,
            'status' => 'pending',
            'request_count' => 1,
        ]);

        return response()->json([
            'message' => 'We will look into adding your area and notify you if we do.',
            'coverage_request_id' => $coverageRequest->id,
            'created' => true,
        ], 201);
    }
}
