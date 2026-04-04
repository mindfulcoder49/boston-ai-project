<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\LocationReportMapSnapshotBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LocationReportSnapshotController extends Controller
{
    public function show(Request $request, Location $location, LocationReportMapSnapshotBuilder $snapshotBuilder): View
    {
        $radius = (float) $request->query('radius', 0.25);
        $limit = (int) $request->query('limit', 4);
        $date = $request->query('date');

        $snapshot = is_string($date) && trim($date) !== ''
            ? $snapshotBuilder->buildForDate($location, $radius, $date, $limit)
            : $snapshotBuilder->build(
                $location,
                $radius,
                (int) $request->query('days', 2),
                $limit
            );

        return view('reports.location_snapshot', [
            'location' => $location,
            'snapshot' => $snapshot,
        ]);
    }
}
