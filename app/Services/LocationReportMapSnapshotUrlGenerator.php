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
        ?CarbonInterface $expiresAt = null
    ): string {
        return URL::temporarySignedRoute(
            'reports.location-snapshot',
            $expiresAt ?? Carbon::now()->addMinutes((int) config('services.reports.snapshot_url_ttl_minutes', 15)),
            [
                'location' => $location->getKey(),
                'radius' => min(max($radius, 0.01), 0.50),
                'days' => min(max($days, 1), 7),
                'limit' => min(max($limit, 1), 6),
            ]
        );
    }
}
