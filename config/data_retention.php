<?php

return [
    'file_targets' => [
        [
            'slug' => 'logs',
            'name' => 'All Logs',
            'default' => true,
            'paths' => [
                storage_path('logs'),
            ],
        ],
        [
            'slug' => 'pipeline-runs',
            'name' => 'Pipeline Run Logs',
            'default' => false,
            'paths' => [
                storage_path('logs/pipeline_runs'),
            ],
        ],
        [
            'slug' => 'datasets',
            'name' => 'All Downloaded Datasets',
            'default' => true,
            'paths' => [
                storage_path('app/datasets'),
            ],
        ],
        [
            'slug' => 'boston-datasets',
            'name' => 'Boston Full Snapshot Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets'),
            ],
            'include_patterns' => [
                '311-service-requests-2025_*.csv',
                '311-service-requests-2026_*.csv',
                'construction-off-hours_*.csv',
                'building-permits_*.csv',
                'crime-incident-reports_*.csv',
                'trash-schedules-by-address_*.csv',
                'property-violations_*.csv',
                'food-inspections_*.csv',
            ],
        ],
        [
            'slug' => 'cambridge-datasets',
            'name' => 'Cambridge Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/cambridge'),
            ],
        ],
        [
            'slug' => 'cambridge-socrata-datasets',
            'name' => 'Cambridge Socrata Snapshots',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/cambridge'),
            ],
            'exclude_paths' => [
                storage_path('app/datasets/cambridge/logs'),
            ],
        ],
        [
            'slug' => 'cambridge-logs',
            'name' => 'Cambridge Daily Police Logs',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/cambridge/logs'),
            ],
        ],
        [
            'slug' => 'chicago-datasets',
            'name' => 'Chicago Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/chicago'),
            ],
        ],
        [
            'slug' => 'montgomery-county-md-datasets',
            'name' => 'Montgomery County MD Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/montgomery_county_md'),
            ],
        ],
        [
            'slug' => 'massachusetts-datasets',
            'name' => 'Massachusetts Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/massachusetts'),
            ],
        ],
        [
            'slug' => 'san-francisco-datasets',
            'name' => 'San Francisco Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/san_francisco'),
            ],
        ],
        [
            'slug' => 'seattle-datasets',
            'name' => 'Seattle Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/seattle'),
            ],
        ],
        [
            'slug' => 'new-york-datasets',
            'name' => 'New York Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/new_york'),
            ],
        ],
        [
            'slug' => 'everett-datasets',
            'name' => 'Everett Datasets',
            'default' => false,
            'paths' => [
                storage_path('app/datasets/everett'),
            ],
        ],
    ],

    'database_rules' => [
        [
            'name' => 'Shared Data Points',
            'group' => 'aggregated_data_points',
            'connection' => 'mysql',
            'table' => 'data_points',
            'date_field' => 'alcivartech_date',
            'retention_days' => 183,
            'sample_columns' => ['type', 'generic_foreign_id', 'alcivartech_date'],
        ],
        [
            'name' => 'Chicago Data Points',
            'group' => 'aggregated_data_points',
            'connection' => 'chicago_db',
            'table' => 'chicago_data_points',
            'date_field' => 'alcivartech_date',
            'retention_days' => 183,
            'sample_columns' => ['type', 'generic_foreign_id', 'alcivartech_date'],
        ],
        [
            'name' => 'San Francisco Data Points',
            'group' => 'aggregated_data_points',
            'connection' => 'san_francisco_db',
            'table' => 'san_francisco_data_points',
            'date_field' => 'alcivartech_date',
            'retention_days' => 183,
            'sample_columns' => ['type', 'generic_foreign_id', 'alcivartech_date'],
        ],
        [
            'name' => 'Seattle Data Points',
            'group' => 'aggregated_data_points',
            'connection' => 'seattle_db',
            'table' => 'seattle_data_points',
            'date_field' => 'alcivartech_date',
            'retention_days' => 183,
            'sample_columns' => ['type', 'generic_foreign_id', 'alcivartech_date'],
        ],
        [
            'name' => 'Montgomery County MD Data Points',
            'group' => 'aggregated_data_points',
            'connection' => 'montgomery_county_md_db',
            'table' => 'montgomery_county_md_data_points',
            'date_field' => 'alcivartech_date',
            'retention_days' => 183,
            'sample_columns' => ['type', 'generic_foreign_id', 'alcivartech_date'],
        ],
        [
            'name' => 'New York Data Points',
            'group' => 'aggregated_data_points',
            'connection' => 'new_york_db',
            'table' => 'new_york_data_points',
            'date_field' => 'alcivartech_date',
            'retention_days' => 183,
            'sample_columns' => ['type', 'generic_foreign_id', 'alcivartech_date'],
        ],
    ],
];
