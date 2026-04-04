<?php

namespace App\Services;

use App\Models\Location;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class LocationReportMapSnapshotUrlGenerator
{
    public function generate(
        Location $location,
        float $radius = 0.25,
        int $days = 2,
        int $limit = 4,
        ?CarbonInterface $expiresAt = null,
        ?CarbonInterface $date = null
    ): string {
        $parameters = [
            'location' => $location->getKey(),
            'radius' => min(max($radius, 0.01), 0.50),
            'days' => min(max($days, 1), 7),
            'limit' => min(max($limit, 1), 12),
        ];

        if ($date) {
            $parameters['date'] = $date->toDateString();
        }

        return URL::temporarySignedRoute(
            'reports.location-snapshot',
            $expiresAt ?? Carbon::now()->addMinutes((int) config('services.reports.snapshot_url_ttl_minutes', 15)),
            $parameters
        );
    }

    public function generateForDate(
        Location $location,
        float $radius,
        CarbonInterface|string $date,
        int $limit = 8,
        ?CarbonInterface $expiresAt = null
    ): string {
        $resolvedDate = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse((string) $date);

        return $this->generate(
            $location,
            $radius,
            1,
            $limit,
            $expiresAt,
            $resolvedDate->copy()->startOfDay()
        );
    }
}
