<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\LocationReportMapSnapshotBuilder;
use Illuminate\Contracts\View\View;

class LocationReportDailyMapsPageController extends Controller
{
    public function show(Location $location, LocationReportMapSnapshotBuilder $snapshotBuilder): View
    {
        $radius = (float) config('services.reports.email_map_radius', 0.25);
        $days = (int) config('services.reports.email_map_days', 7);
        $limit = (int) config('services.reports.email_map_limit', 8);

        return view('reports.location_daily_maps', [
            'location' => $location,
            'snapshots' => $snapshotBuilder->buildDailySeries($location, $radius, $days, $limit),
            'radius' => $radius,
            'days' => $days,
        ]);
    }
}
