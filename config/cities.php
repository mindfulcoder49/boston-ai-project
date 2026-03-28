<?php

/**
 * City Configuration for BostonScope Multi-City Platform
 *
 * Each city entry defines:
 * - latitude/longitude: City center coordinates for proximity detection
 * - data_points_table: The aggregated data points table for this city
 * - db_connection: Laravel database connection name
 * - models: Array of Mappable model classes for this city
 *
 * The system uses Haversine formula to find the nearest city to the user's
 * requested location and routes queries to the appropriate database.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default City
    |--------------------------------------------------------------------------
    |
    | The default city to use when no location is provided or when the
    | requested location doesn't match any configured city.
    |
    */
    'default' => 'boston',

    /*
    |--------------------------------------------------------------------------
    | City Configurations
    |--------------------------------------------------------------------------
    |
    | Each city has its own database connection, data points table, and
    | set of models. Cities are matched by geographic proximity.
    |
    */
    'cities' => [
        'boston' => [
            'name' => 'Boston',
            'latitude' => 42.3601,
            'longitude' => -71.0589,
            'data_points_table' => 'data_points',
            'db_connection' => 'mysql',
            'serviceability' => [
                'crime_address_funnel_enabled' => true,
                'radius_miles' => 12,
                'supported_regions' => ['MA'],
                'crime_model_locality_map' => [
                    'cambridge' => \App\Models\CambridgeCrimeReportData::class,
                    'boston' => \App\Models\CrimeData::class,
                ],
            ],
            'models' => [
                \App\Models\CrimeData::class,
                \App\Models\ThreeOneOneCase::class,
                \App\Models\PropertyViolation::class,
                \App\Models\ConstructionOffHour::class,
                \App\Models\BuildingPermit::class,
                \App\Models\FoodInspection::class,
                \App\Models\CambridgeThreeOneOneCase::class,
                \App\Models\CambridgeBuildingPermitData::class,
                \App\Models\CambridgeCrimeReportData::class,
                \App\Models\CambridgeHousingViolationData::class,
                \App\Models\CambridgeSanitaryInspectionData::class,
                \App\Models\PersonCrashData::class,
            ],
        ],

        'everett' => [
            'name' => 'Everett',
            'latitude' => 42.4084,
            'longitude' => -71.0537,
            'data_points_table' => 'data_points',
            'db_connection' => 'mysql',
            'serviceability' => [
                'crime_address_funnel_enabled' => true,
                'radius_miles' => 4,
                'supported_regions' => ['MA'],
            ],
            'models' => [
                \App\Models\EverettCrimeData::class,
            ],
        ],

        'chicago' => [
            'name' => 'Chicago',
            'latitude' => 41.8781,
            'longitude' => -87.6298,
            'data_points_table' => 'chicago_data_points',
            'db_connection' => 'chicago_db',
            'serviceability' => [
                'crime_address_funnel_enabled' => true,
                'radius_miles' => 16,
                'supported_regions' => ['IL'],
            ],
            'models' => [
                \App\Models\ChicagoCrime::class,
            ],
        ],

        'san_francisco' => [
            'name' => 'San Francisco',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'data_points_table' => 'san_francisco_data_points',
            'db_connection' => 'san_francisco_db',
            'serviceability' => [
                'crime_address_funnel_enabled' => true,
                'radius_miles' => 8,
                'supported_regions' => ['CA'],
            ],
            'models' => [
                \App\Models\SanFranciscoCrime::class,
            ],
        ],

        // Add new cities below this line

        'new_york' => [
            'name' => 'New York',
            'latitude' => 40.7128,
            'longitude' => -74.006,
            'data_points_table' => 'new_york_data_points',
            'db_connection' => 'new_york_db',
            'serviceability' => [
                'crime_address_funnel_enabled' => false,
                'radius_miles' => 15,
                'supported_regions' => ['NY'],
            ],
            'models' => [
                \App\Models\NewYork311::class,
            ],
        ],

        'montgomery_county_md' => [
            'name' => 'Montgomery County, MD',
            'latitude' => 39.154,
            'longitude' => -77.24,
            'data_points_table' => 'montgomery_county_md_data_points',
            'db_connection' => 'montgomery_county_md_db',
            'serviceability' => [
                'crime_address_funnel_enabled' => true,
                'radius_miles' => 18,
                'supported_regions' => ['MD'],
            ],
            'models' => [
                \App\Models\MontgomeryCountyMdCrime::class,
            ],
        ],

        'seattle' => [
            'name' => 'Seattle',
            'latitude' => 47.6062,
            'longitude' => -122.3321,
            'data_points_table' => 'seattle_data_points',
            'db_connection' => 'seattle_db',
            'serviceability' => [
                'crime_address_funnel_enabled' => true,
                'radius_miles' => 15,
                'supported_regions' => ['WA'],
            ],
            'models' => [
                \App\Models\SeattleCrime::class,
            ],
        ],
        // The city-generator tool will append new cities here automatically
    ],
];
