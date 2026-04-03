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
        $snapshot = $snapshotBuilder->build(
            $location,
            (float) $request->query('radius', 0.25),
            (int) $request->query('days', 2),
            (int) $request->query('limit', 4)
        );

        return view('reports.location_snapshot', [
            'location' => $location,
            'snapshot' => $snapshot,
        ]);
    }
}
