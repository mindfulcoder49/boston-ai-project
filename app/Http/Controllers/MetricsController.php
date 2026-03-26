<?php

namespace App\Http\Controllers;

use App\Support\MetricsSnapshotStore;
use Inertia\Inertia;

class MetricsController extends Controller
{
    public function index(MetricsSnapshotStore $metricsSnapshotStore)
    {
        $snapshot = $metricsSnapshotStore->currentPayload();
        $allMetrics = $snapshot['data'] ?? [];
        $pageLastUpdatedTimestamp = $snapshot['last_updated'] ?? null;

        if (!is_array($allMetrics)) {
            $allMetrics = [];
        }

        return Inertia::render('DataMetrics', [
            'metricsData' => $allMetrics,
            'lastUpdated' => $pageLastUpdatedTimestamp,
        ]);
    }
}
