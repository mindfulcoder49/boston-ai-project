<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Config; // Changed from DB, Carbon, Cache, Log
// Removed Model Imports as they are no longer needed here

class MetricsController extends Controller
{
    // Removed $mappableModels property

    public function index()
    {
        // Load metrics data and last updated timestamp from the configuration file
        $allMetrics = Config::get('metrics.data', []); // Correctly get the 'data' array
        $pageLastUpdatedTimestamp = Config::get('metrics.last_updated', now()->toDateTimeString());

        // Ensure $allMetrics is an array (this check is now more relevant to the actual data)
        if (!is_array($allMetrics)) {
            $allMetrics = [];
            // Optionally log an error or warning here if metrics.data was expected but not an array
        }
        
        return Inertia::render('DataMetrics', [
            'metricsData' => $allMetrics,
            'lastUpdated' => $pageLastUpdatedTimestamp,
        ]);
    }
}
