<?php

return [
    'base_url' => 'http://data.boston.gov/datastore/dump',
    'datasets' => [
        [
            //311 Service Requests 2024 (assuming this was the original name, adjust if needed)
            'name' => '311-service-requests-2025', // Or original '311-service-requests-2024'
            'resource_id' => '9d7c2214-4709-478a-a2e8-fb2020a5bb94',
            'format' => 'csv',
        ],
        [
            'name' => 'construction-off-hours',
            'resource_id' => 'c66524ea-36f5-43b1-aa9c-da36d7cb8744',
            'format' => 'csv',
        ],
        [
            'name' => 'building-permits',
            'resource_id' => '6ddcd912-32a0-43df-9908-63574f8c7e77',
            'format' => 'csv',
        ],
        [
            'name' => 'crime-incident-reports',
            'resource_id' => 'b973d8cb-eeb2-4e7e-99da-c92938efc9c0',
            'format' => 'csv',
        ],
        [
            'name' => 'trash-schedules-by-address',
            'resource_id' => 'fee8ee07-b8b5-4ee5-b540-5162590ba5c1',
            'format' => 'csv',
        ],
        [
            'name' => 'property-violations',
            'resource_id' => '800a2663-1d6a-46e7-9356-bedb70f5332c',
            'format' => 'csv',
        ],
        [
            'name' => 'food-inspections',
            'resource_id' => '4582bec6-2b4f-4f9e-bc55-cbaa73117f4c',
            'format' => 'csv',
        ],
        // Add more Boston datasets as needed, following this original structure
    ],
];
