<?php

/**
 * Weekly Analysis Schedule Configuration
 *
 * Controls what gets dispatched by `app:run-weekly-analysis`.
 * Stage 4 is fully automatic (discovers models via $statisticalAnalysisColumns).
 * Stage 6 must be configured explicitly because scoring weights are domain-specific.
 *
 * Run manually:
 *   php artisan app:run-weekly-analysis              # all stages
 *   php artisan app:run-weekly-analysis --stage2     # Stage 2 only
 *   php artisan app:run-weekly-analysis --stage4     # Stage 4 only
 *   php artisan app:run-weekly-analysis --stage6     # Stage 6 only
 *   php artisan app:run-weekly-analysis --dry-run    # preview without dispatching
 *   php artisan app:run-weekly-analysis --fresh      # force new data exports
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Stage 2 — Yearly Count Comparison
    |--------------------------------------------------------------------------
    | Runs for ALL auto-discoverable models (any model with $statisticalAnalysisColumns).
    | baseline_year is the reference year all other years are compared against.
    */
    'stage2' => [
        'enabled'       => true,
        'baseline_year' => 2025,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stage 4 — H3 Anomaly & Trend Analysis
    |--------------------------------------------------------------------------
    | Runs for ALL auto-discoverable models (any model with $statisticalAnalysisColumns).
    | Adding a new model to the system automatically includes it here.
    */
    'stage4' => [
        'enabled'         => true,
        'resolutions'     => [9, 8, 7, 6, 5],
        'p_anomaly'       => 0.05,
        'p_trend'         => 0.05,
        'trend_weeks'     => [4, 26, 52],
        'anomaly_weeks'   => 4,
        'export_timespan' => 108,   // weeks of history to include in CSV exports
    ],

    /*
    |--------------------------------------------------------------------------
    | Stage 6 — Historical Neighbourhood Scoring
    |--------------------------------------------------------------------------
    | Configure one entry per model+column you want scored.
    | Each job can override the top-level defaults (resolutions, analysis_weeks, etc.).
    |
    | Required per job:
    |   model   — Fully-qualified model class
    |   column  — The $statisticalAnalysisColumns field to group by
    |
    | Optional per job (falls back to top-level defaults if omitted):
    |   resolutions      — H3 resolutions to generate reports for
    |   analysis_weeks   — Weeks of history for the baseline average
    |   default_weight   — Weight for categories not listed in 'weights'
    |   export_timespan  — Weeks to export (0 = all data)
    |   weights          — ['Category Name' => float, ...] scoring weights
    */
    'stage6' => [
        'enabled'         => true,
        'resolutions'     => [8, 9, 10],
        'analysis_weeks'  => 52,
        'default_weight'  => 1.0,   // equal weight for all unlisted categories
        'export_timespan' => 0,     // 0 = all available data

        'jobs' => [
            // Add entries here. Example:
            // [
            //     'model'          => \App\Models\CrimeData::class,
            //     'column'         => 'offense_description',
            //     'weights'        => [
            //         'ASSAULT - SIMPLE'       => 3.0,
            //         'ROBBERY'                => 2.5,
            //         'LARCENY FROM MOTOR VEH' => 1.5,
            //     ],
            // ],
            // [
            //     'model'          => \App\Models\CrimeData::class,
            //     'column'         => 'offense_description',
            //     'resolutions'    => [7, 8],
            //     'analysis_weeks' => 104,
            //     'default_weight' => 0.5,
            // ],
        ],
    ],

];
