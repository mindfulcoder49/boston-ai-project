<?php

return [
    // This file is for datasets using the newer, more flexible configuration
    // (e.g., multiple cities, varied URL patterns).
    // Boston datasets are now in config/boston_datasets.php
    'datasets' => [
        // Add the Cambridge dataset (or other non-Boston datasets) 
        
        [
            'name' => 'cambridge-311-service-requests',
            'city' => 'cambridge',
            'base_url' => 'https://data.cambridgema.gov/resource', // Base URL for Cambridge Socrata API
            'resource_id' => '2z9k-mv9g', // Resource ID from the URL
            'format' => 'csv',
            'url_pattern_type' => 'extension', // Cambridge uses /resource_id.format
            'pagination_type' => 'socrata_offset', // Indicates Socrata-style pagination
            'page_size' => 10000, // Number of records to fetch per page (max 50000 for SODA 2.0)
            'order_by_field' => ':id', // Field to ensure stable ordering for pagination
        ], 
        [
            'name' => 'cambridge-building-permits',
            'city' => 'cambridge', // This will be used to create the 'datasets/cambridge/' subdirectory
            'base_url' => 'https://data.cambridgema.gov/resource',
            'resource_id' => 'qu2z-8suj',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_offset',
            'page_size' => 10000,
            'order_by_field' => ':id', // Or ':id' if 'permit_num' isn't reliable for ordering
        ],
        [
            'name' => 'cambridge-sanitary-inspections',
            'city' => 'cambridge',
            'base_url' => 'https://data.cambridgema.gov/resource',
            'resource_id' => 'ryb9-qzmw',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_offset',
            'page_size' => 10000,
            'order_by_field' => ':id',
        ], 
        [
            'name' => 'cambridge-housing-code-violations',
            'city' => 'cambridge',
            'base_url' => 'https://data.cambridgema.gov/resource',
            'resource_id' => 'f8su-kv88',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_offset',
            'page_size' => 10000,
            'order_by_field' => ':id',
        ], 
        [
            'name' => 'cambridge-crime-reports',
            'city' => 'cambridge',
            'base_url' => 'https://data.cambridgema.gov/resource',
            'resource_id' => 'xuad-73uj',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_offset',
            'page_size' => 10000,
            'order_by_field' => ':id',
        ],
        [
            'name' => 'cambridge-master-addresses-list',
            'city' => 'cambridge',
            'base_url' => 'https://data.cambridgema.gov/resource',
            'resource_id' => 'vup6-kpwv',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_offset',
            'page_size' => 10000,
            'order_by_field' => ':id',
        ],
        [
            'name' => 'cambridge-master-intersections-list',
            'city' => 'cambridge',
            'base_url' => 'https://data.cambridgema.gov/resource',
            'resource_id' => '7g3f-rtpe',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_offset',
            'page_size' => 10000,
            'order_by_field' => ':id',
        ], 
        [
            'name' => 'chicago-crimes-2001-to-present',
            'city' => 'chicago',
            'base_url' => 'https://data.cityofchicago.org/resource',
            'resource_id' => 'ijzp-q8t2',
            'format' => 'csv',
            'url_pattern_type' => 'extension',
            'pagination_type' => 'socrata_by_year', // Use the new year-by-year strategy
            'page_size' => 50000,
            'order_by_field' => 'date', // Order by date within each year's query
            'order_by_direction' => 'DESC',
            'year_field' => 'year', // The field name for the year in the dataset
            'start_year' => 2010, // The first year of data to fetch
            // 'end_year' will default to the current year if not specified
            'download_type' => 'incremental', // 'full' or 'incremental'
            'model' => 'App\Models\ChicagoCrime', // Corresponding Eloquent model
            'date_field' => 'date', // The date field in the dataset for incremental checks
        ],
        // Add more datasets from Cambridge or other cities (not Boston) as needed
    ],
];
