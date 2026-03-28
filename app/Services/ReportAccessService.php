<?php

namespace App\Services;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportAccessService
{
    public function resolveRecurringReportAccess(User $user): array
    {
        $effectiveTier = $user->getEffectiveTierDetails()['tier'];

        if ($effectiveTier === 'pro') {
            return [
                'eligible' => true,
                'mode' => 'pro',
                'location_ids' => $user->locations->pluck('id')->all(),
            ];
        }

        if ($effectiveTier === 'basic') {
            $locationId = $user->locations->sortBy('id')->first()?->id;

            return [
                'eligible' => $locationId !== null,
                'mode' => 'basic',
                'location_ids' => $locationId ? [$locationId] : [],
            ];
        }

        if ($user->hasActiveCrimeAddressTrial() && $user->crime_address_trial_location_id) {
            return [
                'eligible' => true,
                'mode' => 'trial',
                'location_ids' => [$user->crime_address_trial_location_id],
            ];
        }

        return [
            'eligible' => false,
            'mode' => 'none',
            'location_ids' => [],
        ];
    }

    public function filterEligibleLocations(Collection $locations, array $access, bool $force = false): Collection
    {
        $eligibleLocations = $locations
            ->filter(function (Location $location) use ($force) {
                if ($force) {
                    return true;
                }

                return $location->report === 'daily' || (now()->isSunday() && $location->report === 'weekly');
            });

        if ($access['mode'] === 'trial') {
            return $eligibleLocations->whereIn('id', $access['location_ids']);
        }

        if ($access['mode'] === 'basic') {
            return $eligibleLocations->whereIn('id', $access['location_ids'])->sortBy('id')->take(1);
        }

        if ($access['mode'] === 'pro') {
            return $eligibleLocations->sortBy('id')->values();
        }

        return collect();
    }
}
