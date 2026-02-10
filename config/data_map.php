<?php

/**
 * Data Map Configuration for BostonScope Platform
 *
 * This config defines the model registry for the DataMapController,
 * mapping data type keys to their corresponding Eloquent model classes.
 *
 * Each entry in 'models' maps a URL-friendly key to a model class.
 * Each entry in 'city_contexts' defines map centering for data type prefixes.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Model Registry
    |--------------------------------------------------------------------------
    |
    | Maps data type keys (used in URLs and API) to their Eloquent model classes.
    | All models must use the App\Models\Concerns\Mappable trait.
    |
    */
    'models' => [
        // Boston & Greater Boston Area
        '311_cases' => \App\Models\ThreeOneOneCase::class,
        'property_violations' => \App\Models\PropertyViolation::class,
        'food_inspections' => \App\Models\FoodInspection::class,
        'construction_off_hours' => \App\Models\ConstructionOffHour::class,
        'building_permits' => \App\Models\BuildingPermit::class,
        'crime' => \App\Models\CrimeData::class,
        'person_crash_data' => \App\Models\PersonCrashData::class,

        // Cambridge
        'cambridge_311_cases' => \App\Models\CambridgeThreeOneOneCase::class,
        'cambridge_housing_violations' => \App\Models\CambridgeHousingViolationData::class,
        'cambridge_sanitary_inspections' => \App\Models\CambridgeSanitaryInspectionData::class,
        'cambridge_building_permits' => \App\Models\CambridgeBuildingPermitData::class,
        'cambridge_crime_reports' => \App\Models\CambridgeCrimeReportData::class,

        // Everett
        'everett_crime' => \App\Models\EverettCrimeData::class,

        // Chicago
        'chicago_crime' => \App\Models\ChicagoCrime::class,

        // San Francisco
        'san_francisco_crime' => \App\Models\SanFranciscoCrime::class,

        'seattle_crime' => \App\Models\SeattleCrime::class,

        'montgomery_county_md_crime' => \App\Models\MontgomeryCountyMdCrime::class,

        // Add new data types below this line
        // The city-generator tool will append new entries here automatically
    ],

    /*
    |--------------------------------------------------------------------------
    | City Contexts
    |--------------------------------------------------------------------------
    |
    | Defines map center coordinates and zoom levels for different cities.
    | The key is a prefix that matches data type keys (e.g., 'chicago_' matches 'chicago_crime').
    |
    */
    'city_contexts' => [
        'chicago_' => [
            'city' => 'chicago',
            'center' => [41.8781, -87.6298],
            'zoom' => 11,
        ],
        'san_francisco_' => [
            'city' => 'san_francisco',
            'center' => [37.7749, -122.4194],
            'zoom' => 12,
        ],
        'seattle_' => [
            'city' => 'seattle',
            'center' => [47.6062, -122.3321],
            'zoom' => 12,
        ],
        'montgomery_county_md_' => [
            'city' => 'montgomery_county_md',
            'center' => [39.154, -77.24],
            'zoom' => 12,
        ],
        // Add new city contexts below this line
        // The city-generator tool will append new entries here automatically
    ],

    /*
    |--------------------------------------------------------------------------
    | Default City Context
    |--------------------------------------------------------------------------
    |
    | Used when a data type doesn't match any city prefix.
    | This covers Boston, Cambridge, Everett, and other Greater Boston area data.
    |
    */
    'default_city_context' => [
        'city' => 'boston',
        'center' => [42.3601, -71.0589],
        'zoom' => 12,
    ],
];
