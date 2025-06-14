<?php

return [
    'App\\Models\\PropertyViolation' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'case_no',
                'label' => 'Case No',
                'type' => 'text',
                'placeholder' => 'Enter Case No',
            ],
            2 => [
                'name' => 'ap_case_defn_key',
                'label' => 'Ap Case Defn Key',
                'type' => 'text',
                'placeholder' => 'Enter Ap Case Defn Key',
            ],
            3 => [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'text',
                'placeholder' => 'Enter Status',
            ],
            4 => [
                'name' => 'code',
                'label' => 'Code',
                'type' => 'text',
                'placeholder' => 'Enter Code',
            ],
            5 => [
                'name' => 'value',
                'label' => 'Value',
                'type' => 'text',
                'placeholder' => 'Enter Value',
            ],
            6 => [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'text',
                'placeholder' => 'Enter Description',
            ],
            7 => [
                'name' => 'violation_stno',
                'label' => 'Violation Stno',
                'type' => 'text',
                'placeholder' => 'Enter Violation Stno',
            ],
            8 => [
                'name' => 'violation_sthigh',
                'label' => 'Violation Sthigh',
                'type' => 'text',
                'placeholder' => 'Enter Violation Sthigh',
            ],
            9 => [
                'name' => 'violation_street',
                'label' => 'Violation Street',
                'type' => 'text',
                'placeholder' => 'Enter Violation Street',
            ],
            10 => [
                'name' => 'violation_suffix',
                'label' => 'Violation Suffix',
                'type' => 'text',
                'placeholder' => 'Enter Violation Suffix',
            ],
            11 => [
                'name' => 'violation_city',
                'label' => 'Violation City',
                'type' => 'text',
                'placeholder' => 'Enter Violation City',
            ],
            12 => [
                'name' => 'violation_state',
                'label' => 'Violation State',
                'type' => 'text',
                'placeholder' => 'Enter Violation State',
            ],
            13 => [
                'name' => 'violation_zip',
                'label' => 'Violation Zip',
                'type' => 'text',
                'placeholder' => 'Enter Violation Zip',
            ],
            14 => [
                'name' => 'ward',
                'label' => 'Ward',
                'type' => 'text',
                'placeholder' => 'Enter Ward',
            ],
            15 => [
                'name' => 'contact_addr1',
                'label' => 'Contact Addr1',
                'type' => 'text',
                'placeholder' => 'Enter Contact Addr1',
            ],
            16 => [
                'name' => 'contact_addr2',
                'label' => 'Contact Addr2',
                'type' => 'text',
                'placeholder' => 'Enter Contact Addr2',
            ],
            17 => [
                'name' => 'contact_city',
                'label' => 'Contact City',
                'type' => 'text',
                'placeholder' => 'Enter Contact City',
            ],
            18 => [
                'name' => 'contact_state',
                'label' => 'Contact State',
                'type' => 'text',
                'placeholder' => 'Enter Contact State',
            ],
            19 => [
                'name' => 'contact_zip',
                'label' => 'Contact Zip',
                'type' => 'text',
                'placeholder' => 'Enter Contact Zip',
            ],
            20 => [
                'name' => 'sam_id',
                'label' => 'Sam Id',
                'type' => 'text',
                'placeholder' => 'Enter Sam Id',
            ],
            21 => [
                'name' => 'latitude_min',
                'label' => 'Latitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Latitude',
            ],
            22 => [
                'name' => 'latitude_max',
                'label' => 'Latitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Latitude',
            ],
            23 => [
                'name' => 'longitude_min',
                'label' => 'Longitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Longitude',
            ],
            24 => [
                'name' => 'longitude_max',
                'label' => 'Longitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Longitude',
            ],
            25 => [
                'name' => 'location',
                'label' => 'Location',
                'type' => 'text',
                'placeholder' => 'Enter Location',
            ],
            26 => [
                'name' => 'language_code',
                'label' => 'Language Code',
                'type' => 'text',
                'placeholder' => 'Enter Language Code',
            ],
        ],
        'contextData' => 'Dataset of Property Violations. Filter by attributes like case no, ap case defn key, date (Status Dttm), status.',
        'searchableColumns' => [
            0 => 'case_no',
            1 => 'status',
            2 => 'code',
            3 => 'description',
            4 => 'violation_stno',
            5 => 'violation_street',
            6 => 'violation_zip',
            7 => 'ward',
            8 => 'contact_city',
            9 => 'contact_state',
            10 => 'sam_id',
            11 => 'latitude',
            12 => 'longitude',
            13 => 'language_code',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Status Dttm\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Status Dttm\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'case_no' => [
                'type' => 'string',
                'description' => 'Filter by Case No.',
            ],
            'ap_case_defn_key' => [
                'type' => 'string',
                'description' => 'Filter by Ap Case Defn Key.',
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Filter by Status.',
            ],
            'code' => [
                'type' => 'string',
                'description' => 'Filter by Code.',
            ],
            'value' => [
                'type' => 'string',
                'description' => 'Filter by Value.',
            ],
            'description' => [
                'type' => 'string',
                'description' => 'Filter by Description.',
            ],
            'violation_stno' => [
                'type' => 'string',
                'description' => 'Filter by Violation Stno.',
            ],
            'violation_sthigh' => [
                'type' => 'string',
                'description' => 'Filter by Violation Sthigh.',
            ],
            'violation_street' => [
                'type' => 'string',
                'description' => 'Filter by Violation Street.',
            ],
            'violation_suffix' => [
                'type' => 'string',
                'description' => 'Filter by Violation Suffix.',
            ],
            'violation_city' => [
                'type' => 'string',
                'description' => 'Filter by Violation City.',
            ],
            'violation_state' => [
                'type' => 'string',
                'description' => 'Filter by Violation State.',
            ],
            'violation_zip' => [
                'type' => 'string',
                'description' => 'Filter by Violation Zip.',
            ],
            'ward' => [
                'type' => 'string',
                'description' => 'Filter by Ward.',
            ],
            'contact_addr1' => [
                'type' => 'string',
                'description' => 'Filter by Contact Addr1.',
            ],
            'contact_addr2' => [
                'type' => 'string',
                'description' => 'Filter by Contact Addr2.',
            ],
            'contact_city' => [
                'type' => 'string',
                'description' => 'Filter by Contact City.',
            ],
            'contact_state' => [
                'type' => 'string',
                'description' => 'Filter by Contact State.',
            ],
            'contact_zip' => [
                'type' => 'string',
                'description' => 'Filter by Contact Zip.',
            ],
            'sam_id' => [
                'type' => 'string',
                'description' => 'Filter by Sam Id.',
            ],
            'latitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Latitude.',
            ],
            'latitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Latitude.',
            ],
            'longitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Longitude.',
            ],
            'longitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Longitude.',
            ],
            'location' => [
                'type' => 'string',
                'description' => 'Filter by Location.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
        ],
    ],
    'App\\Models\\CambridgeBuildingPermitData' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'permit_id_external',
                'label' => 'Permit Id External',
                'type' => 'text',
                'placeholder' => 'Enter Permit Id External',
            ],
            2 => [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'text',
                'placeholder' => 'Enter Address',
            ],
            3 => [
                'name' => 'address_geocoded',
                'label' => 'Address Geocoded',
                'type' => 'text',
                'placeholder' => 'Enter Address Geocoded',
            ],
            4 => [
                'name' => 'latitude',
                'label' => 'Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Latitude',
            ],
            5 => [
                'name' => 'longitude',
                'label' => 'Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Longitude',
            ],
            6 => [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'text',
                'placeholder' => 'Enter Status',
            ],
            7 => [
                'name' => 'applicant_submit_date_start',
                'label' => 'Applicant Submit Date Start',
                'type' => 'date',
                'placeholder' => 'Start date for Applicant Submit Date',
            ],
            8 => [
                'name' => 'applicant_submit_date_end',
                'label' => 'Applicant Submit Date End',
                'type' => 'date',
                'placeholder' => 'End date for Applicant Submit Date',
            ],
            9 => [
                'name' => 'number_of_residential_units_min',
                'label' => 'Number Of Residential Units Min',
                'type' => 'number',
                'placeholder' => 'Min value for Number Of Residential Units',
            ],
            10 => [
                'name' => 'number_of_residential_units_max',
                'label' => 'Number Of Residential Units Max',
                'type' => 'number',
                'placeholder' => 'Max value for Number Of Residential Units',
            ],
            11 => [
                'name' => 'current_property_use',
                'label' => 'Current Property Use',
                'type' => 'text',
                'placeholder' => 'Enter Current Property Use',
            ],
            12 => [
                'name' => 'proposed_property_use',
                'label' => 'Proposed Property Use',
                'type' => 'text',
                'placeholder' => 'Enter Proposed Property Use',
            ],
            13 => [
                'name' => 'total_cost_of_construction',
                'label' => 'Total Cost Of Construction',
                'type' => 'text',
                'placeholder' => 'Enter Total Cost Of Construction',
            ],
            14 => [
                'name' => 'detailed_description_of_work',
                'label' => 'Detailed Description Of Work',
                'type' => 'text',
                'placeholder' => 'Enter Detailed Description Of Work',
            ],
            15 => [
                'name' => 'gross_square_footage_min',
                'label' => 'Gross Square Footage Min',
                'type' => 'number',
                'placeholder' => 'Min value for Gross Square Footage',
            ],
            16 => [
                'name' => 'gross_square_footage_max',
                'label' => 'Gross Square Footage Max',
                'type' => 'number',
                'placeholder' => 'Max value for Gross Square Footage',
            ],
            17 => [
                'name' => 'building_use',
                'label' => 'Building Use',
                'type' => 'text',
                'placeholder' => 'Enter Building Use',
            ],
            18 => [
                'name' => 'maplot_number',
                'label' => 'Maplot Number',
                'type' => 'text',
                'placeholder' => 'Enter Maplot Number',
            ],
            19 => [
                'name' => 'raw_data',
                'label' => 'Raw Data',
                'type' => 'text',
                'placeholder' => 'Enter Raw Data',
            ],
        ],
        'contextData' => 'Dataset of Cambridge Building Permits. Filter by attributes like permit id external, address, address geocoded.',
        'searchableColumns' => [
            0 => 'id',
            1 => 'permit_id_external',
            2 => 'address',
            3 => 'address_geocoded',
            4 => 'latitude',
            5 => 'longitude',
            6 => 'status',
            7 => 'number_of_residential_units',
            8 => 'current_property_use',
            9 => 'proposed_property_use',
            10 => 'total_cost_of_construction',
            11 => 'detailed_description_of_work',
            12 => 'gross_square_footage',
            13 => 'building_use',
            14 => 'maplot_number',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Issue Date\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Issue Date\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'permit_id_external' => [
                'type' => 'string',
                'description' => 'Filter by Permit Id External.',
            ],
            'address' => [
                'type' => 'string',
                'description' => 'Filter by Address.',
            ],
            'address_geocoded' => [
                'type' => 'string',
                'description' => 'Filter by Address Geocoded.',
            ],
            'latitude' => [
                'type' => 'string',
                'description' => 'Filter by Latitude.',
            ],
            'longitude' => [
                'type' => 'string',
                'description' => 'Filter by Longitude.',
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Filter by Status.',
            ],
            'applicant_submit_date_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Applicant Submit Date (YYYY-MM-DD)',
            ],
            'applicant_submit_date_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Applicant Submit Date (YYYY-MM-DD)',
            ],
            'number_of_residential_units_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Number Of Residential Units.',
            ],
            'number_of_residential_units_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Number Of Residential Units.',
            ],
            'current_property_use' => [
                'type' => 'string',
                'description' => 'Filter by Current Property Use.',
            ],
            'proposed_property_use' => [
                'type' => 'string',
                'description' => 'Filter by Proposed Property Use.',
            ],
            'total_cost_of_construction' => [
                'type' => 'string',
                'description' => 'Filter by Total Cost Of Construction.',
            ],
            'detailed_description_of_work' => [
                'type' => 'string',
                'description' => 'Filter by Detailed Description Of Work.',
            ],
            'gross_square_footage_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Gross Square Footage.',
            ],
            'gross_square_footage_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Gross Square Footage.',
            ],
            'building_use' => [
                'type' => 'string',
                'description' => 'Filter by Building Use.',
            ],
            'maplot_number' => [
                'type' => 'string',
                'description' => 'Filter by Maplot Number.',
            ],
            'raw_data' => [
                'type' => 'string',
                'description' => 'Filter by Raw Data.',
            ],
        ],
    ],
    'App\\Models\\FoodInspection' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'external_id_min',
                'label' => 'External Id Min',
                'type' => 'number',
                'placeholder' => 'Min value for External Id',
            ],
            2 => [
                'name' => 'external_id_max',
                'label' => 'External Id Max',
                'type' => 'number',
                'placeholder' => 'Max value for External Id',
            ],
            3 => [
                'name' => 'businessname',
                'label' => 'Businessname',
                'type' => 'text',
                'placeholder' => 'Enter Businessname',
            ],
            4 => [
                'name' => 'dbaname',
                'label' => 'Dbaname',
                'type' => 'text',
                'placeholder' => 'Enter Dbaname',
            ],
            5 => [
                'name' => 'legalowner',
                'label' => 'Legalowner',
                'type' => 'text',
                'placeholder' => 'Enter Legalowner',
            ],
            6 => [
                'name' => 'namelast',
                'label' => 'Namelast',
                'type' => 'text',
                'placeholder' => 'Enter Namelast',
            ],
            7 => [
                'name' => 'namefirst',
                'label' => 'Namefirst',
                'type' => 'text',
                'placeholder' => 'Enter Namefirst',
            ],
            8 => [
                'name' => 'licenseno',
                'label' => 'Licenseno',
                'type' => 'text',
                'placeholder' => 'Enter Licenseno',
            ],
            9 => [
                'name' => 'issdttm_start',
                'label' => 'Issdttm Start',
                'type' => 'date',
                'placeholder' => 'Start date for Issdttm',
            ],
            10 => [
                'name' => 'issdttm_end',
                'label' => 'Issdttm End',
                'type' => 'date',
                'placeholder' => 'End date for Issdttm',
            ],
            11 => [
                'name' => 'expdttm_start',
                'label' => 'Expdttm Start',
                'type' => 'date',
                'placeholder' => 'Start date for Expdttm',
            ],
            12 => [
                'name' => 'expdttm_end',
                'label' => 'Expdttm End',
                'type' => 'date',
                'placeholder' => 'End date for Expdttm',
            ],
            13 => [
                'name' => 'licstatus',
                'label' => 'Licstatus',
                'type' => 'text',
                'placeholder' => 'Enter Licstatus',
            ],
            14 => [
                'name' => 'licensecat',
                'label' => 'Licensecat',
                'type' => 'text',
                'placeholder' => 'Enter Licensecat',
            ],
            15 => [
                'name' => 'descript',
                'label' => 'Descript',
                'type' => 'text',
                'placeholder' => 'Enter Descript',
            ],
            16 => [
                'name' => 'result',
                'label' => 'Result',
                'type' => 'text',
                'placeholder' => 'Enter Result',
            ],
            17 => [
                'name' => 'violation',
                'label' => 'Violation',
                'type' => 'text',
                'placeholder' => 'Enter Violation',
            ],
            18 => [
                'name' => 'viol_level',
                'label' => 'Viol Level',
                'type' => 'text',
                'placeholder' => 'Enter Viol Level',
            ],
            19 => [
                'name' => 'violdesc',
                'label' => 'Violdesc',
                'type' => 'text',
                'placeholder' => 'Enter Violdesc',
            ],
            20 => [
                'name' => 'violdttm_start',
                'label' => 'Violdttm Start',
                'type' => 'date',
                'placeholder' => 'Start date for Violdttm',
            ],
            21 => [
                'name' => 'violdttm_end',
                'label' => 'Violdttm End',
                'type' => 'date',
                'placeholder' => 'End date for Violdttm',
            ],
            22 => [
                'name' => 'viol_status',
                'label' => 'Viol Status',
                'type' => 'text',
                'placeholder' => 'Enter Viol Status',
            ],
            23 => [
                'name' => 'status_date_start',
                'label' => 'Status Date Start',
                'type' => 'date',
                'placeholder' => 'Start date for Status Date',
            ],
            24 => [
                'name' => 'status_date_end',
                'label' => 'Status Date End',
                'type' => 'date',
                'placeholder' => 'End date for Status Date',
            ],
            25 => [
                'name' => 'comments',
                'label' => 'Comments',
                'type' => 'text',
                'placeholder' => 'Enter Comments',
            ],
            26 => [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'text',
                'placeholder' => 'Enter Address',
            ],
            27 => [
                'name' => 'city',
                'label' => 'City',
                'type' => 'text',
                'placeholder' => 'Enter City',
            ],
            28 => [
                'name' => 'state',
                'label' => 'State',
                'type' => 'text',
                'placeholder' => 'Enter State',
            ],
            29 => [
                'name' => 'zip',
                'label' => 'Zip',
                'type' => 'text',
                'placeholder' => 'Enter Zip',
            ],
            30 => [
                'name' => 'property_id',
                'label' => 'Property Id',
                'type' => 'text',
                'placeholder' => 'Enter Property Id',
            ],
            31 => [
                'name' => 'latitude_min',
                'label' => 'Latitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Latitude',
            ],
            32 => [
                'name' => 'latitude_max',
                'label' => 'Latitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Latitude',
            ],
            33 => [
                'name' => 'longitude_min',
                'label' => 'Longitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Longitude',
            ],
            34 => [
                'name' => 'longitude_max',
                'label' => 'Longitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Longitude',
            ],
            35 => [
                'name' => 'language_code',
                'label' => 'Language Code',
                'type' => 'text',
                'placeholder' => 'Enter Language Code',
            ],
        ],
        'contextData' => 'Dataset of Food Inspections. Filter by attributes like businessname, dbaname, legalowner.',
        'searchableColumns' => [
            0 => 'external_id',
            1 => 'businessname',
            2 => 'dbaname',
            3 => 'licenseno',
            4 => 'licstatus',
            5 => 'licensecat',
            6 => 'result',
            7 => 'viol_level',
            8 => 'viol_status',
            9 => 'address',
            10 => 'city',
            11 => 'zip',
            12 => 'property_id',
            13 => 'language_code',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Resultdttm\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Resultdttm\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'external_id_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for External Id.',
            ],
            'external_id_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for External Id.',
            ],
            'businessname' => [
                'type' => 'string',
                'description' => 'Filter by Businessname.',
            ],
            'dbaname' => [
                'type' => 'string',
                'description' => 'Filter by Dbaname.',
            ],
            'legalowner' => [
                'type' => 'string',
                'description' => 'Filter by Legalowner.',
            ],
            'namelast' => [
                'type' => 'string',
                'description' => 'Filter by Namelast.',
            ],
            'namefirst' => [
                'type' => 'string',
                'description' => 'Filter by Namefirst.',
            ],
            'licenseno' => [
                'type' => 'string',
                'description' => 'Filter by Licenseno.',
            ],
            'issdttm_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Issdttm (YYYY-MM-DD)',
            ],
            'issdttm_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Issdttm (YYYY-MM-DD)',
            ],
            'expdttm_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Expdttm (YYYY-MM-DD)',
            ],
            'expdttm_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Expdttm (YYYY-MM-DD)',
            ],
            'licstatus' => [
                'type' => 'string',
                'description' => 'Filter by Licstatus.',
            ],
            'licensecat' => [
                'type' => 'string',
                'description' => 'Filter by Licensecat.',
            ],
            'descript' => [
                'type' => 'string',
                'description' => 'Filter by Descript.',
            ],
            'result' => [
                'type' => 'string',
                'description' => 'Filter by Result.',
            ],
            'violation' => [
                'type' => 'string',
                'description' => 'Filter by Violation.',
            ],
            'viol_level' => [
                'type' => 'string',
                'description' => 'Filter by Viol Level.',
            ],
            'violdesc' => [
                'type' => 'string',
                'description' => 'Filter by Violdesc.',
            ],
            'violdttm_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Violdttm (YYYY-MM-DD)',
            ],
            'violdttm_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Violdttm (YYYY-MM-DD)',
            ],
            'viol_status' => [
                'type' => 'string',
                'description' => 'Filter by Viol Status.',
            ],
            'status_date_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Status Date (YYYY-MM-DD)',
            ],
            'status_date_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Status Date (YYYY-MM-DD)',
            ],
            'comments' => [
                'type' => 'string',
                'description' => 'Filter by Comments.',
            ],
            'address' => [
                'type' => 'string',
                'description' => 'Filter by Address.',
            ],
            'city' => [
                'type' => 'string',
                'description' => 'Filter by City.',
            ],
            'state' => [
                'type' => 'string',
                'description' => 'Filter by State.',
            ],
            'zip' => [
                'type' => 'string',
                'description' => 'Filter by Zip.',
            ],
            'property_id' => [
                'type' => 'string',
                'description' => 'Filter by Property Id.',
            ],
            'latitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Latitude.',
            ],
            'latitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Latitude.',
            ],
            'longitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Longitude.',
            ],
            'longitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Longitude.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
        ],
    ],
    'App\\Models\\CambridgeHousingViolationData' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'record_id_external',
                'label' => 'Record Id External',
                'type' => 'text',
                'placeholder' => 'Enter Record Id External',
            ],
            2 => [
                'name' => 'full_address',
                'label' => 'Full Address',
                'type' => 'text',
                'placeholder' => 'Enter Full Address',
            ],
            3 => [
                'name' => 'parcel_number',
                'label' => 'Parcel Number',
                'type' => 'text',
                'placeholder' => 'Enter Parcel Number',
            ],
            4 => [
                'name' => 'code',
                'label' => 'Code',
                'type' => 'text',
                'placeholder' => 'Enter Code',
            ],
            5 => [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'text',
                'placeholder' => 'Enter Description',
            ],
            6 => [
                'name' => 'corrective_action',
                'label' => 'Corrective Action',
                'type' => 'text',
                'placeholder' => 'Enter Corrective Action',
            ],
            7 => [
                'name' => 'correction_required_by',
                'label' => 'Correction Required By',
                'type' => 'text',
                'placeholder' => 'Enter Correction Required By',
            ],
            8 => [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'text',
                'placeholder' => 'Enter Status',
            ],
            9 => [
                'name' => 'application_submit_date',
                'label' => 'Application Submit Date',
                'type' => 'text',
                'placeholder' => 'Enter Application Submit Date',
            ],
            10 => [
                'name' => 'longitude',
                'label' => 'Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Longitude',
            ],
            11 => [
                'name' => 'latitude',
                'label' => 'Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Latitude',
            ],
            12 => [
                'name' => 'point_text',
                'label' => 'Point Text',
                'type' => 'text',
                'placeholder' => 'Enter Point Text',
            ],
        ],
        'contextData' => 'Dataset of Cambridge Housing Violations. Filter by attributes like record id external, full address, parcel number.',
        'searchableColumns' => [
            0 => 'id',
            1 => 'record_id_external',
            2 => 'full_address',
            3 => 'parcel_number',
            4 => 'code',
            5 => 'description',
            6 => 'corrective_action',
            7 => 'correction_required_by',
            8 => 'status',
            9 => 'application_submit_date',
            10 => 'issue_date',
            11 => 'longitude',
            12 => 'latitude',
            13 => 'point_text',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Issue Date\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Issue Date\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'record_id_external' => [
                'type' => 'string',
                'description' => 'Filter by Record Id External.',
            ],
            'full_address' => [
                'type' => 'string',
                'description' => 'Filter by Full Address.',
            ],
            'parcel_number' => [
                'type' => 'string',
                'description' => 'Filter by Parcel Number.',
            ],
            'code' => [
                'type' => 'string',
                'description' => 'Filter by Code.',
            ],
            'description' => [
                'type' => 'string',
                'description' => 'Filter by Description.',
            ],
            'corrective_action' => [
                'type' => 'string',
                'description' => 'Filter by Corrective Action.',
            ],
            'correction_required_by' => [
                'type' => 'string',
                'description' => 'Filter by Correction Required By.',
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Filter by Status.',
            ],
            'application_submit_date' => [
                'type' => 'string',
                'description' => 'Filter by Application Submit Date.',
            ],
            'longitude' => [
                'type' => 'string',
                'description' => 'Filter by Longitude.',
            ],
            'latitude' => [
                'type' => 'string',
                'description' => 'Filter by Latitude.',
            ],
            'point_text' => [
                'type' => 'string',
                'description' => 'Filter by Point Text.',
            ],
        ],
    ],
    'App\\Models\\BuildingPermit' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'permitnumber',
                'label' => 'Permitnumber',
                'type' => 'text',
                'placeholder' => 'Enter Permitnumber',
            ],
            2 => [
                'name' => 'worktype',
                'label' => 'Worktype',
                'type' => 'text',
                'placeholder' => 'Enter Worktype',
            ],
            3 => [
                'name' => 'permittypedescr',
                'label' => 'Permittypedescr',
                'type' => 'text',
                'placeholder' => 'Enter Permittypedescr',
            ],
            4 => [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'text',
                'placeholder' => 'Enter Description',
            ],
            5 => [
                'name' => 'comments',
                'label' => 'Comments',
                'type' => 'text',
                'placeholder' => 'Enter Comments',
            ],
            6 => [
                'name' => 'applicant',
                'label' => 'Applicant',
                'type' => 'text',
                'placeholder' => 'Enter Applicant',
            ],
            7 => [
                'name' => 'declared_valuation',
                'label' => 'Declared Valuation',
                'type' => 'text',
                'placeholder' => 'Enter Declared Valuation',
            ],
            8 => [
                'name' => 'total_fees',
                'label' => 'Total Fees',
                'type' => 'text',
                'placeholder' => 'Enter Total Fees',
            ],
            9 => [
                'name' => 'expiration_date_start',
                'label' => 'Expiration Date Start',
                'type' => 'date',
                'placeholder' => 'Start date for Expiration Date',
            ],
            10 => [
                'name' => 'expiration_date_end',
                'label' => 'Expiration Date End',
                'type' => 'date',
                'placeholder' => 'End date for Expiration Date',
            ],
            11 => [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'text',
                'placeholder' => 'Enter Status',
            ],
            12 => [
                'name' => 'language_code',
                'label' => 'Language Code',
                'type' => 'text',
                'placeholder' => 'Enter Language Code',
            ],
            13 => [
                'name' => 'occupancytype',
                'label' => 'Occupancytype',
                'type' => 'text',
                'placeholder' => 'Enter Occupancytype',
            ],
            14 => [
                'name' => 'sq_feet_min',
                'label' => 'Sq Feet Min',
                'type' => 'number',
                'placeholder' => 'Min value for Sq Feet',
            ],
            15 => [
                'name' => 'sq_feet_max',
                'label' => 'Sq Feet Max',
                'type' => 'number',
                'placeholder' => 'Max value for Sq Feet',
            ],
            16 => [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'text',
                'placeholder' => 'Enter Address',
            ],
            17 => [
                'name' => 'city',
                'label' => 'City',
                'type' => 'text',
                'placeholder' => 'Enter City',
            ],
            18 => [
                'name' => 'state',
                'label' => 'State',
                'type' => 'text',
                'placeholder' => 'Enter State',
            ],
            19 => [
                'name' => 'zip',
                'label' => 'Zip',
                'type' => 'text',
                'placeholder' => 'Enter Zip',
            ],
            20 => [
                'name' => 'property_id',
                'label' => 'Property Id',
                'type' => 'text',
                'placeholder' => 'Enter Property Id',
            ],
            21 => [
                'name' => 'parcel_id',
                'label' => 'Parcel Id',
                'type' => 'text',
                'placeholder' => 'Enter Parcel Id',
            ],
            22 => [
                'name' => 'gpsy_min',
                'label' => 'Gpsy Min',
                'type' => 'number',
                'placeholder' => 'Min value for Gpsy',
            ],
            23 => [
                'name' => 'gpsy_max',
                'label' => 'Gpsy Max',
                'type' => 'number',
                'placeholder' => 'Max value for Gpsy',
            ],
            24 => [
                'name' => 'gpsx_min',
                'label' => 'Gpsx Min',
                'type' => 'number',
                'placeholder' => 'Min value for Gpsx',
            ],
            25 => [
                'name' => 'gpsx_max',
                'label' => 'Gpsx Max',
                'type' => 'number',
                'placeholder' => 'Max value for Gpsx',
            ],
            26 => [
                'name' => 'y_latitude_min',
                'label' => 'Y Latitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Y Latitude',
            ],
            27 => [
                'name' => 'y_latitude_max',
                'label' => 'Y Latitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Y Latitude',
            ],
            28 => [
                'name' => 'x_longitude_min',
                'label' => 'X Longitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for X Longitude',
            ],
            29 => [
                'name' => 'x_longitude_max',
                'label' => 'X Longitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for X Longitude',
            ],
        ],
        'contextData' => 'Dataset of Building Permits. Filter by attributes like permitnumber, worktype, permittypedescr.',
        'searchableColumns' => [
            0 => 'permitnumber',
            1 => 'worktype',
            2 => 'permittypedescr',
            3 => 'status',
            4 => 'occupancytype',
            5 => 'address',
            6 => 'city',
            7 => 'state',
            8 => 'zip',
            9 => 'property_id',
            10 => 'parcel_id',
            11 => 'language_code',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Issued Date\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Issued Date\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'permitnumber' => [
                'type' => 'string',
                'description' => 'Filter by Permitnumber.',
            ],
            'worktype' => [
                'type' => 'string',
                'description' => 'Filter by Worktype.',
            ],
            'permittypedescr' => [
                'type' => 'string',
                'description' => 'Filter by Permittypedescr.',
            ],
            'description' => [
                'type' => 'string',
                'description' => 'Filter by Description.',
            ],
            'comments' => [
                'type' => 'string',
                'description' => 'Filter by Comments.',
            ],
            'applicant' => [
                'type' => 'string',
                'description' => 'Filter by Applicant.',
            ],
            'declared_valuation' => [
                'type' => 'string',
                'description' => 'Filter by Declared Valuation.',
            ],
            'total_fees' => [
                'type' => 'string',
                'description' => 'Filter by Total Fees.',
            ],
            'expiration_date_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Expiration Date (YYYY-MM-DD)',
            ],
            'expiration_date_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Expiration Date (YYYY-MM-DD)',
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Filter by Status.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
            'occupancytype' => [
                'type' => 'string',
                'description' => 'Filter by Occupancytype.',
            ],
            'sq_feet_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Sq Feet.',
            ],
            'sq_feet_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Sq Feet.',
            ],
            'address' => [
                'type' => 'string',
                'description' => 'Filter by Address.',
            ],
            'city' => [
                'type' => 'string',
                'description' => 'Filter by City.',
            ],
            'state' => [
                'type' => 'string',
                'description' => 'Filter by State.',
            ],
            'zip' => [
                'type' => 'string',
                'description' => 'Filter by Zip.',
            ],
            'property_id' => [
                'type' => 'string',
                'description' => 'Filter by Property Id.',
            ],
            'parcel_id' => [
                'type' => 'string',
                'description' => 'Filter by Parcel Id.',
            ],
            'gpsy_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Gpsy.',
            ],
            'gpsy_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Gpsy.',
            ],
            'gpsx_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Gpsx.',
            ],
            'gpsx_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Gpsx.',
            ],
            'y_latitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Y Latitude.',
            ],
            'y_latitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Y Latitude.',
            ],
            'x_longitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for X Longitude.',
            ],
            'x_longitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for X Longitude.',
            ],
        ],
    ],
    'App\\Models\\CambridgeThreeOneOneCase' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'ticket_id_external',
                'label' => 'Ticket Id External',
                'type' => 'text',
                'placeholder' => 'Enter Ticket Id External',
            ],
            2 => [
                'name' => 'city',
                'label' => 'City',
                'type' => 'text',
                'placeholder' => 'Enter City',
            ],
            3 => [
                'name' => 'issue_type',
                'label' => 'Issue Type',
                'type' => 'text',
                'placeholder' => 'Enter Issue Type',
            ],
            4 => [
                'name' => 'issue_category',
                'label' => 'Issue Category',
                'type' => 'text',
                'placeholder' => 'Enter Issue Category',
            ],
            5 => [
                'name' => 'ticket_status',
                'label' => 'Ticket Status',
                'type' => 'text',
                'placeholder' => 'Enter Ticket Status',
            ],
            6 => [
                'name' => 'issue_description',
                'label' => 'Issue Description',
                'type' => 'text',
                'placeholder' => 'Enter Issue Description',
            ],
            7 => [
                'name' => 'ticket_closed_date_time_start',
                'label' => 'Ticket Closed Date Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Ticket Closed Date Time',
            ],
            8 => [
                'name' => 'ticket_closed_date_time_end',
                'label' => 'Ticket Closed Date Time End',
                'type' => 'date',
                'placeholder' => 'End date for Ticket Closed Date Time',
            ],
            9 => [
                'name' => 'ticket_last_updated_date_time_start',
                'label' => 'Ticket Last Updated Date Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Ticket Last Updated Date Time',
            ],
            10 => [
                'name' => 'ticket_last_updated_date_time_end',
                'label' => 'Ticket Last Updated Date Time End',
                'type' => 'date',
                'placeholder' => 'End date for Ticket Last Updated Date Time',
            ],
            11 => [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'text',
                'placeholder' => 'Enter Address',
            ],
            12 => [
                'name' => 'latitude',
                'label' => 'Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Latitude',
            ],
            13 => [
                'name' => 'longitude',
                'label' => 'Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Longitude',
            ],
            14 => [
                'name' => 'location_text',
                'label' => 'Location Text',
                'type' => 'text',
                'placeholder' => 'Enter Location Text',
            ],
            15 => [
                'name' => 'image_url',
                'label' => 'Image Url',
                'type' => 'text',
                'placeholder' => 'Enter Image Url',
            ],
            16 => [
                'name' => 'acknowledged_at_start',
                'label' => 'Acknowledged At Start',
                'type' => 'date',
                'placeholder' => 'Start date for Acknowledged At',
            ],
            17 => [
                'name' => 'acknowledged_at_end',
                'label' => 'Acknowledged At End',
                'type' => 'date',
                'placeholder' => 'End date for Acknowledged At',
            ],
            18 => [
                'name' => 'html_url',
                'label' => 'Html Url',
                'type' => 'text',
                'placeholder' => 'Enter Html Url',
            ],
        ],
        'contextData' => 'Dataset of Cambridge 311 Service Requests. Filter by attributes like ticket id external, city, issue type.',
        'searchableColumns' => [
            0 => 'id',
            1 => 'ticket_id_external',
            2 => 'city',
            3 => 'issue_type',
            4 => 'issue_category',
            5 => 'ticket_status',
            6 => 'issue_description',
            7 => 'address',
            8 => 'latitude',
            9 => 'longitude',
            10 => 'location_text',
            11 => 'image_url',
            12 => 'html_url',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Ticket Created Date Time\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Ticket Created Date Time\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'ticket_id_external' => [
                'type' => 'string',
                'description' => 'Filter by Ticket Id External.',
            ],
            'city' => [
                'type' => 'string',
                'description' => 'Filter by City.',
            ],
            'issue_type' => [
                'type' => 'string',
                'description' => 'Filter by Issue Type.',
            ],
            'issue_category' => [
                'type' => 'string',
                'description' => 'Filter by Issue Category.',
            ],
            'ticket_status' => [
                'type' => 'string',
                'description' => 'Filter by Ticket Status.',
            ],
            'issue_description' => [
                'type' => 'string',
                'description' => 'Filter by Issue Description.',
            ],
            'ticket_closed_date_time_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Ticket Closed Date Time (YYYY-MM-DD)',
            ],
            'ticket_closed_date_time_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Ticket Closed Date Time (YYYY-MM-DD)',
            ],
            'ticket_last_updated_date_time_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Ticket Last Updated Date Time (YYYY-MM-DD)',
            ],
            'ticket_last_updated_date_time_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Ticket Last Updated Date Time (YYYY-MM-DD)',
            ],
            'address' => [
                'type' => 'string',
                'description' => 'Filter by Address.',
            ],
            'latitude' => [
                'type' => 'string',
                'description' => 'Filter by Latitude.',
            ],
            'longitude' => [
                'type' => 'string',
                'description' => 'Filter by Longitude.',
            ],
            'location_text' => [
                'type' => 'string',
                'description' => 'Filter by Location Text.',
            ],
            'image_url' => [
                'type' => 'string',
                'description' => 'Filter by Image Url.',
            ],
            'acknowledged_at_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Acknowledged At (YYYY-MM-DD)',
            ],
            'acknowledged_at_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Acknowledged At (YYYY-MM-DD)',
            ],
            'html_url' => [
                'type' => 'string',
                'description' => 'Filter by Html Url.',
            ],
        ],
    ],
    'App\\Models\\EverettCrimeData' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'case_number',
                'label' => 'Case Number',
                'type' => 'text',
                'placeholder' => 'Enter Case Number',
            ],
            2 => [
                'name' => 'incident_log_file_date',
                'label' => 'Incident Log File Date',
                'type' => 'text',
                'placeholder' => 'Enter Incident Log File Date',
            ],
            3 => [
                'name' => 'incident_entry_date_parsed',
                'label' => 'Incident Entry Date Parsed',
                'type' => 'text',
                'placeholder' => 'Enter Incident Entry Date Parsed',
            ],
            4 => [
                'name' => 'incident_time_parsed_start',
                'label' => 'Incident Time Parsed Start',
                'type' => 'date',
                'placeholder' => 'Start date for Incident Time Parsed',
            ],
            5 => [
                'name' => 'incident_time_parsed_end',
                'label' => 'Incident Time Parsed End',
                'type' => 'date',
                'placeholder' => 'End date for Incident Time Parsed',
            ],
            6 => [
                'name' => 'year_min',
                'label' => 'Year Min',
                'type' => 'number',
                'placeholder' => 'Min value for Year',
            ],
            7 => [
                'name' => 'year_max',
                'label' => 'Year Max',
                'type' => 'number',
                'placeholder' => 'Max value for Year',
            ],
            8 => [
                'name' => 'month_min',
                'label' => 'Month Min',
                'type' => 'number',
                'placeholder' => 'Min value for Month',
            ],
            9 => [
                'name' => 'month_max',
                'label' => 'Month Max',
                'type' => 'number',
                'placeholder' => 'Max value for Month',
            ],
            10 => [
                'name' => 'day_of_week',
                'label' => 'Day Of Week',
                'type' => 'text',
                'placeholder' => 'Enter Day Of Week',
            ],
            11 => [
                'name' => 'hour_min',
                'label' => 'Hour Min',
                'type' => 'number',
                'placeholder' => 'Min value for Hour',
            ],
            12 => [
                'name' => 'hour_max',
                'label' => 'Hour Max',
                'type' => 'number',
                'placeholder' => 'Max value for Hour',
            ],
            13 => [
                'name' => 'incident_type',
                'label' => 'Incident Type',
                'type' => 'text',
                'placeholder' => 'Enter Incident Type',
            ],
            14 => [
                'name' => 'incident_address',
                'label' => 'Incident Address',
                'type' => 'text',
                'placeholder' => 'Enter Incident Address',
            ],
            15 => [
                'name' => 'incident_latitude',
                'label' => 'Incident Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Incident Latitude',
            ],
            16 => [
                'name' => 'incident_longitude',
                'label' => 'Incident Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Incident Longitude',
            ],
            17 => [
                'name' => 'incident_description',
                'label' => 'Incident Description',
                'type' => 'text',
                'placeholder' => 'Enter Incident Description',
            ],
            18 => [
                'name' => 'arrest_name',
                'label' => 'Arrest Name',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Name',
            ],
            19 => [
                'name' => 'arrest_address',
                'label' => 'Arrest Address',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Address',
            ],
            20 => [
                'name' => 'arrest_age_min',
                'label' => 'Arrest Age Min',
                'type' => 'number',
                'placeholder' => 'Min value for Arrest Age',
            ],
            21 => [
                'name' => 'arrest_age_max',
                'label' => 'Arrest Age Max',
                'type' => 'number',
                'placeholder' => 'Max value for Arrest Age',
            ],
            22 => [
                'name' => 'arrest_date_parsed',
                'label' => 'Arrest Date Parsed',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Date Parsed',
            ],
            23 => [
                'name' => 'arrest_charges',
                'label' => 'Arrest Charges',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Charges',
            ],
            24 => [
                'name' => 'crime_details_concatenated',
                'label' => 'Crime Details Concatenated',
                'type' => 'text',
                'placeholder' => 'Enter Crime Details Concatenated',
            ],
            25 => [
                'name' => 'source_city',
                'label' => 'Source City',
                'type' => 'text',
                'placeholder' => 'Enter Source City',
            ],
        ],
        'contextData' => 'Dataset of Everett Crimes. Filter by attributes like case number, incident log file date, incident entry date parsed.',
        'searchableColumns' => [
            0 => 'case_number',
            1 => 'incident_type',
            2 => 'incident_address',
            3 => 'incident_description',
            4 => 'arrest_name',
            5 => 'arrest_charges',
            6 => 'crime_details_concatenated',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Occurred On Datetime\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Occurred On Datetime\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'case_number' => [
                'type' => 'string',
                'description' => 'Filter by Case Number.',
            ],
            'incident_log_file_date' => [
                'type' => 'string',
                'description' => 'Filter by Incident Log File Date.',
            ],
            'incident_entry_date_parsed' => [
                'type' => 'string',
                'description' => 'Filter by Incident Entry Date Parsed.',
            ],
            'incident_time_parsed_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Incident Time Parsed (YYYY-MM-DD)',
            ],
            'incident_time_parsed_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Incident Time Parsed (YYYY-MM-DD)',
            ],
            'year_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Year.',
            ],
            'year_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Year.',
            ],
            'month_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Month.',
            ],
            'month_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Month.',
            ],
            'day_of_week' => [
                'type' => 'string',
                'description' => 'Filter by Day Of Week.',
            ],
            'hour_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Hour.',
            ],
            'hour_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Hour.',
            ],
            'incident_type' => [
                'type' => 'string',
                'description' => 'Filter by Incident Type.',
            ],
            'incident_address' => [
                'type' => 'string',
                'description' => 'Filter by Incident Address.',
            ],
            'incident_latitude' => [
                'type' => 'string',
                'description' => 'Filter by Incident Latitude.',
            ],
            'incident_longitude' => [
                'type' => 'string',
                'description' => 'Filter by Incident Longitude.',
            ],
            'incident_description' => [
                'type' => 'string',
                'description' => 'Filter by Incident Description.',
            ],
            'arrest_name' => [
                'type' => 'string',
                'description' => 'Filter by Arrest Name.',
            ],
            'arrest_address' => [
                'type' => 'string',
                'description' => 'Filter by Arrest Address.',
            ],
            'arrest_age_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Arrest Age.',
            ],
            'arrest_age_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Arrest Age.',
            ],
            'arrest_date_parsed' => [
                'type' => 'string',
                'description' => 'Filter by Arrest Date Parsed.',
            ],
            'arrest_charges' => [
                'type' => 'string',
                'description' => 'Filter by Arrest Charges.',
            ],
            'crime_details_concatenated' => [
                'type' => 'string',
                'description' => 'Filter by Crime Details Concatenated.',
            ],
            'source_city' => [
                'type' => 'string',
                'description' => 'Filter by Source City.',
            ],
        ],
    ],
    'App\\Models\\PersonCrashData' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'crash_numb_min',
                'label' => 'Crash Number Min',
                'type' => 'number',
                'placeholder' => 'Min value for Crash Number',
            ],
            2 => [
                'name' => 'crash_numb_max',
                'label' => 'Crash Number Max',
                'type' => 'number',
                'placeholder' => 'Max value for Crash Number',
            ],
            3 => [
                'name' => 'city_town_name',
                'label' => 'City Town Name',
                'type' => 'text',
                'placeholder' => 'Enter City Town Name',
            ],
            4 => [
                'name' => 'crash_hour',
                'label' => 'Crash Hour',
                'type' => 'multiselect',
                'placeholder' => 'Select Crash Hour',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    2 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    3 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    4 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    5 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    6 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                    7 => [
                        'value' => '7',
                        'label' => '7',
                    ],
                    8 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    9 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                    10 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    11 => [
                        'value' => '11',
                        'label' => '11',
                    ],
                    12 => [
                        'value' => '12',
                        'label' => '12',
                    ],
                    13 => [
                        'value' => '13',
                        'label' => '13',
                    ],
                    14 => [
                        'value' => '14',
                        'label' => '14',
                    ],
                    15 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    16 => [
                        'value' => '16',
                        'label' => '16',
                    ],
                    17 => [
                        'value' => '17',
                        'label' => '17',
                    ],
                    18 => [
                        'value' => '18',
                        'label' => '18',
                    ],
                    19 => [
                        'value' => '19',
                        'label' => '19',
                    ],
                    20 => [
                        'value' => '20',
                        'label' => '20',
                    ],
                    21 => [
                        'value' => '21',
                        'label' => '21',
                    ],
                    22 => [
                        'value' => '22',
                        'label' => '22',
                    ],
                    23 => [
                        'value' => '23',
                        'label' => '23',
                    ],
                ],
            ],
            5 => [
                'name' => 'crash_status',
                'label' => 'Crash Status',
                'type' => 'multiselect',
                'placeholder' => 'Select Crash Status',
                'options' => [
                    0 => [
                        'value' => 'Open',
                        'label' => 'Open',
                    ],
                    1 => [
                        'value' => 'Open Fatal',
                        'label' => 'Open Fatal',
                    ],
                ],
            ],
            6 => [
                'name' => 'crash_severity_descr',
                'label' => 'Crash Severity',
                'type' => 'multiselect',
                'placeholder' => 'Select Crash Severity',
                'options' => [
                    0 => [
                        'value' => 'Fatal injury',
                        'label' => 'Fatal injury',
                    ],
                    1 => [
                        'value' => 'Non-fatal injury',
                        'label' => 'Non-fatal injury',
                    ],
                    2 => [
                        'value' => 'Not Reported',
                        'label' => 'Not Reported',
                    ],
                    3 => [
                        'value' => 'Property damage only (none injured)',
                        'label' => 'Property damage only (none injured)',
                    ],
                    4 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            7 => [
                'name' => 'max_injr_svrty_cl',
                'label' => 'Max Injury Severity Reported',
                'type' => 'multiselect',
                'placeholder' => 'Select Max Injury Severity Reported',
                'options' => [
                    0 => [
                        'value' => 'Deceased not caused by crash',
                        'label' => 'Deceased not caused by crash',
                    ],
                    1 => [
                        'value' => 'Fatal injury (K)',
                        'label' => 'Fatal injury (K)',
                    ],
                    2 => [
                        'value' => 'No Apparent Injury (O)',
                        'label' => 'No Apparent Injury (O)',
                    ],
                    3 => [
                        'value' => 'No injury',
                        'label' => 'No injury',
                    ],
                    4 => [
                        'value' => 'Non-fatal injury - Possible',
                        'label' => 'Non-fatal injury - Possible',
                    ],
                    5 => [
                        'value' => 'Not Applicable',
                        'label' => 'Not Applicable',
                    ],
                    6 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    7 => [
                        'value' => 'Possible Injury (C)',
                        'label' => 'Possible Injury (C)',
                    ],
                    8 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    9 => [
                        'value' => 'Suspected Minor Injury (B)',
                        'label' => 'Suspected Minor Injury (B)',
                    ],
                    10 => [
                        'value' => 'Suspected Serious Injury (A)',
                        'label' => 'Suspected Serious Injury (A)',
                    ],
                    11 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            8 => [
                'name' => 'numb_vehc',
                'label' => 'Number of Vehicles',
                'type' => 'multiselect',
                'placeholder' => 'Select Number of Vehicles',
                'options' => [
                    0 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    1 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    2 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    3 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    4 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    5 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                    6 => [
                        'value' => '7',
                        'label' => '7',
                    ],
                    7 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    8 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                    9 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    10 => [
                        'value' => '11',
                        'label' => '11',
                    ],
                    11 => [
                        'value' => '13',
                        'label' => '13',
                    ],
                    12 => [
                        'value' => '14',
                        'label' => '14',
                    ],
                ],
            ],
            9 => [
                'name' => 'numb_nonfatal_injr',
                'label' => 'Total NonFatal Injuries',
                'type' => 'multiselect',
                'placeholder' => 'Select Total NonFatal Injuries',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    2 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    3 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    4 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    5 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    6 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                    7 => [
                        'value' => '7',
                        'label' => '7',
                    ],
                    8 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    9 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                    10 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    11 => [
                        'value' => '11',
                        'label' => '11',
                    ],
                    12 => [
                        'value' => '12',
                        'label' => '12',
                    ],
                ],
            ],
            10 => [
                'name' => 'numb_fatal_injr',
                'label' => 'Total Fatal Injuries',
                'type' => 'multiselect',
                'placeholder' => 'Select Total Fatal Injuries',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    2 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    3 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                ],
            ],
            11 => [
                'name' => 'polc_agncy_type_descr',
                'label' => 'Police Agency Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Police Agency Type',
                'options' => [
                    0 => [
                        'value' => 'Campus police',
                        'label' => 'Campus police',
                    ],
                    1 => [
                        'value' => 'Local police',
                        'label' => 'Local police',
                    ],
                    2 => [
                        'value' => 'MBTA police',
                        'label' => 'MBTA police',
                    ],
                    3 => [
                        'value' => 'State police',
                        'label' => 'State police',
                    ],
                ],
            ],
            12 => [
                'name' => 'year',
                'label' => 'Year',
                'type' => 'multiselect',
                'placeholder' => 'Select Year',
                'options' => [
                    0 => [
                        'value' => '2023',
                        'label' => '2023',
                    ],
                    1 => [
                        'value' => '2024',
                        'label' => '2024',
                    ],
                    2 => [
                        'value' => '2025',
                        'label' => '2025',
                    ],
                ],
            ],
            13 => [
                'name' => 'crash_person_id',
                'label' => 'Crash Person Id',
                'type' => 'text',
                'placeholder' => 'Enter Crash Person Id',
            ],
            14 => [
                'name' => 'manr_coll_descr',
                'label' => 'Manner of Collision',
                'type' => 'multiselect',
                'placeholder' => 'Select Manner of Collision',
                'options' => [
                    0 => [
                        'value' => 'Angle',
                        'label' => 'Angle',
                    ],
                    1 => [
                        'value' => 'Front to Front',
                        'label' => 'Front to Front',
                    ],
                    2 => [
                        'value' => 'Front to Rear',
                        'label' => 'Front to Rear',
                    ],
                    3 => [
                        'value' => 'Head-on',
                        'label' => 'Head-on',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Rear to Side',
                        'label' => 'Rear to Side',
                    ],
                    6 => [
                        'value' => 'Rear-end',
                        'label' => 'Rear-end',
                    ],
                    7 => [
                        'value' => 'Rear-to-rear',
                        'label' => 'Rear-to-rear',
                    ],
                    8 => [
                        'value' => 'Sideswipe, opposite direction',
                        'label' => 'Sideswipe, opposite direction',
                    ],
                    9 => [
                        'value' => 'Sideswipe, same direction',
                        'label' => 'Sideswipe, same direction',
                    ],
                    10 => [
                        'value' => 'Single vehicle crash',
                        'label' => 'Single vehicle crash',
                    ],
                    11 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            15 => [
                'name' => 'vehc_mnvr_actn_cl',
                'label' => 'Vehicle Actions Prior to Crash (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Actions Prior to Crash (All Vehicles)',
            ],
            16 => [
                'name' => 'vehc_trvl_dirc_cl',
                'label' => 'Vehicle Travel Direction (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Travel Direction (All Vehicles)',
            ],
            17 => [
                'name' => 'vehc_seq_events_cl',
                'label' => 'Vehicle Sequence of Events (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Sequence of Events (All Vehicles)',
            ],
            18 => [
                'name' => 'ambnt_light_descr',
                'label' => 'Light Condition',
                'type' => 'multiselect',
                'placeholder' => 'Select Light Condition',
                'options' => [
                    0 => [
                        'value' => 'Dark - lighted roadway',
                        'label' => 'Dark - lighted roadway',
                    ],
                    1 => [
                        'value' => 'Dark - roadway not lighted',
                        'label' => 'Dark - roadway not lighted',
                    ],
                    2 => [
                        'value' => 'Dark - unknown roadway lighting',
                        'label' => 'Dark - unknown roadway lighting',
                    ],
                    3 => [
                        'value' => 'Dawn',
                        'label' => 'Dawn',
                    ],
                    4 => [
                        'value' => 'Daylight',
                        'label' => 'Daylight',
                    ],
                    5 => [
                        'value' => 'Dusk',
                        'label' => 'Dusk',
                    ],
                    6 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    7 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    8 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    9 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            19 => [
                'name' => 'weath_cond_descr',
                'label' => 'Weather Condition',
                'type' => 'text',
                'placeholder' => 'Enter Weather Condition',
            ],
            20 => [
                'name' => 'road_surf_cond_descr',
                'label' => 'Road Surface Condition',
                'type' => 'multiselect',
                'placeholder' => 'Select Road Surface Condition',
                'options' => [
                    0 => [
                        'value' => 'Dry',
                        'label' => 'Dry',
                    ],
                    1 => [
                        'value' => 'Ice',
                        'label' => 'Ice',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    4 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    5 => [
                        'value' => 'Sand, mud, dirt, oil, gravel',
                        'label' => 'Sand, mud, dirt, oil, gravel',
                    ],
                    6 => [
                        'value' => 'Slush',
                        'label' => 'Slush',
                    ],
                    7 => [
                        'value' => 'Snow',
                        'label' => 'Snow',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    9 => [
                        'value' => 'Water (standing, moving)',
                        'label' => 'Water (standing, moving)',
                    ],
                    10 => [
                        'value' => 'Wet',
                        'label' => 'Wet',
                    ],
                ],
            ],
            21 => [
                'name' => 'first_hrmf_event_descr',
                'label' => 'First Harmful Event',
                'type' => 'multiselect',
                'placeholder' => 'Select First Harmful Event',
                'options' => [
                    0 => [
                        'value' => 'Collision with animal - deer',
                        'label' => 'Collision with animal - deer',
                    ],
                    1 => [
                        'value' => 'Collision with animal - other',
                        'label' => 'Collision with animal - other',
                    ],
                    2 => [
                        'value' => 'Collision with bridge',
                        'label' => 'Collision with bridge',
                    ],
                    3 => [
                        'value' => 'Collision with bridge overhead structure',
                        'label' => 'Collision with bridge overhead structure',
                    ],
                    4 => [
                        'value' => 'Collision with curb',
                        'label' => 'Collision with curb',
                    ],
                    5 => [
                        'value' => 'Collision with cyclist',
                        'label' => 'Collision with cyclist',
                    ],
                    6 => [
                        'value' => 'Collision with ditch',
                        'label' => 'Collision with ditch',
                    ],
                    7 => [
                        'value' => 'Collision with embankment',
                        'label' => 'Collision with embankment',
                    ],
                    8 => [
                        'value' => 'Collision with guardrail',
                        'label' => 'Collision with guardrail',
                    ],
                    9 => [
                        'value' => 'Collision with median barrier',
                        'label' => 'Collision with median barrier',
                    ],
                    10 => [
                        'value' => 'Collision with motor vehicle in traffic',
                        'label' => 'Collision with motor vehicle in traffic',
                    ],
                    11 => [
                        'value' => 'Collision with other light pole or other post/support',
                        'label' => 'Collision with other light pole or other post/support',
                    ],
                    12 => [
                        'value' => 'Collision with other movable object',
                        'label' => 'Collision with other movable object',
                    ],
                    13 => [
                        'value' => 'Collision with Other Vulnerable User',
                        'label' => 'Collision with Other Vulnerable User',
                    ],
                    14 => [
                        'value' => 'Collision with parked motor vehicle',
                        'label' => 'Collision with parked motor vehicle',
                    ],
                    15 => [
                        'value' => 'Collision with pedestrian',
                        'label' => 'Collision with pedestrian',
                    ],
                    16 => [
                        'value' => 'Collision with railway vehicle (e.g., train, engine)',
                        'label' => 'Collision with railway vehicle (e.g., train, engine)',
                    ],
                    17 => [
                        'value' => 'Collision with tree',
                        'label' => 'Collision with tree',
                    ],
                    18 => [
                        'value' => 'Collision with unknown fixed object',
                        'label' => 'Collision with unknown fixed object',
                    ],
                    19 => [
                        'value' => 'Collision with utility pole',
                        'label' => 'Collision with utility pole',
                    ],
                    20 => [
                        'value' => 'Collision with work zone maintenance equipment',
                        'label' => 'Collision with work zone maintenance equipment',
                    ],
                    21 => [
                        'value' => 'Collison with moped',
                        'label' => 'Collison with moped',
                    ],
                    22 => [
                        'value' => 'Jackknife',
                        'label' => 'Jackknife',
                    ],
                    23 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    24 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    25 => [
                        'value' => 'Other non-collision',
                        'label' => 'Other non-collision',
                    ],
                    26 => [
                        'value' => 'Overturn/rollover',
                        'label' => 'Overturn/rollover',
                    ],
                    27 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    28 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    29 => [
                        'value' => 'Unknown non-collision',
                        'label' => 'Unknown non-collision',
                    ],
                ],
            ],
            22 => [
                'name' => 'most_hrmfl_evt_cl',
                'label' => 'Most Harmful Event (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Most Harmful Event (All Vehicles)',
            ],
            23 => [
                'name' => 'drvr_cntrb_circ_cl',
                'label' => 'Driver Contributing Circumstances (All Drivers)',
                'type' => 'text',
                'placeholder' => 'Enter Driver Contributing Circumstances (All Drivers)',
            ],
            24 => [
                'name' => 'vehc_config_cl',
                'label' => 'Vehicle Configuration (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Configuration (All Vehicles)',
            ],
            25 => [
                'name' => 'street_numb',
                'label' => 'Street Number',
                'type' => 'text',
                'placeholder' => 'Enter Street Number',
            ],
            26 => [
                'name' => 'rdwy',
                'label' => 'Roadway',
                'type' => 'text',
                'placeholder' => 'Enter Roadway',
            ],
            27 => [
                'name' => 'dist_dirc_from_int',
                'label' => 'Distance and Direction from Intersection',
                'type' => 'text',
                'placeholder' => 'Enter Distance and Direction from Intersection',
            ],
            28 => [
                'name' => 'near_int_rdwy',
                'label' => 'Near Intersection Roadway',
                'type' => 'text',
                'placeholder' => 'Enter Near Intersection Roadway',
            ],
            29 => [
                'name' => 'mm_rte',
                'label' => 'Milemarker Route',
                'type' => 'text',
                'placeholder' => 'Enter Milemarker Route',
            ],
            30 => [
                'name' => 'dist_dirc_milemarker',
                'label' => 'Distance and Direction from Milemarker',
                'type' => 'text',
                'placeholder' => 'Enter Distance and Direction from Milemarker',
            ],
            31 => [
                'name' => 'milemarker',
                'label' => 'Milemarker',
                'type' => 'text',
                'placeholder' => 'Enter Milemarker',
            ],
            32 => [
                'name' => 'exit_rte',
                'label' => 'Exit Route',
                'type' => 'text',
                'placeholder' => 'Enter Exit Route',
            ],
            33 => [
                'name' => 'dist_dirc_exit',
                'label' => 'Distance and Direction from Exit',
                'type' => 'text',
                'placeholder' => 'Enter Distance and Direction from Exit',
            ],
            34 => [
                'name' => 'exit_numb',
                'label' => 'Exit Number',
                'type' => 'text',
                'placeholder' => 'Enter Exit Number',
            ],
            35 => [
                'name' => 'dist_dirc_landmark',
                'label' => 'Distance and Direction from Landmark',
                'type' => 'text',
                'placeholder' => 'Enter Distance and Direction from Landmark',
            ],
            36 => [
                'name' => 'landmark',
                'label' => 'Landmark',
                'type' => 'text',
                'placeholder' => 'Enter Landmark',
            ],
            37 => [
                'name' => 'rdwy_jnct_type_descr',
                'label' => 'Roadway Junction Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Roadway Junction Type',
                'options' => [
                    0 => [
                        'value' => 'Driveway',
                        'label' => 'Driveway',
                    ],
                    1 => [
                        'value' => 'Five-point or more',
                        'label' => 'Five-point or more',
                    ],
                    2 => [
                        'value' => 'Four-way intersection',
                        'label' => 'Four-way intersection',
                    ],
                    3 => [
                        'value' => 'Not at junction',
                        'label' => 'Not at junction',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Off-ramp',
                        'label' => 'Off-ramp',
                    ],
                    6 => [
                        'value' => 'On-ramp',
                        'label' => 'On-ramp',
                    ],
                    7 => [
                        'value' => 'Railway grade crossing',
                        'label' => 'Railway grade crossing',
                    ],
                    8 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    9 => [
                        'value' => 'T-intersection',
                        'label' => 'T-intersection',
                    ],
                    10 => [
                        'value' => 'Traffic circle',
                        'label' => 'Traffic circle',
                    ],
                    11 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    12 => [
                        'value' => 'Y-intersection',
                        'label' => 'Y-intersection',
                    ],
                ],
            ],
            38 => [
                'name' => 'traf_cntrl_devc_type_descr',
                'label' => 'Traffic Control Device Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Traffic Control Device Type',
                'options' => [
                    0 => [
                        'value' => 'Flashing traffic control signal',
                        'label' => 'Flashing traffic control signal',
                    ],
                    1 => [
                        'value' => 'No controls',
                        'label' => 'No controls',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Pedestrian Crossing signal/beacon',
                        'label' => 'Pedestrian Crossing signal/beacon',
                    ],
                    4 => [
                        'value' => 'Railway crossing device',
                        'label' => 'Railway crossing device',
                    ],
                    5 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    6 => [
                        'value' => 'School zone signs',
                        'label' => 'School zone signs',
                    ],
                    7 => [
                        'value' => 'Stop signs',
                        'label' => 'Stop signs',
                    ],
                    8 => [
                        'value' => 'Traffic control signal',
                        'label' => 'Traffic control signal',
                    ],
                    9 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    10 => [
                        'value' => 'Warning signs',
                        'label' => 'Warning signs',
                    ],
                    11 => [
                        'value' => 'Yield signs',
                        'label' => 'Yield signs',
                    ],
                ],
            ],
            39 => [
                'name' => 'trafy_descr_descr',
                'label' => 'Trafficway Description',
                'type' => 'multiselect',
                'placeholder' => 'Select Trafficway Description',
                'options' => [
                    0 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    1 => [
                        'value' => 'One-way, not divided',
                        'label' => 'One-way, not divided',
                    ],
                    2 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    3 => [
                        'value' => 'Two-way, divided, positive median barrier',
                        'label' => 'Two-way, divided, positive median barrier',
                    ],
                    4 => [
                        'value' => 'Two-way, divided, unprotected median',
                        'label' => 'Two-way, divided, unprotected median',
                    ],
                    5 => [
                        'value' => 'Two-way, not divided',
                        'label' => 'Two-way, not divided',
                    ],
                    6 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            40 => [
                'name' => 'jurisdictn',
                'label' => 'Jurisdiction-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Jurisdiction-linked RD',
                'options' => [
                    0 => [
                        'value' => 'City or Town accepted road',
                        'label' => 'City or Town accepted road',
                    ],
                    1 => [
                        'value' => 'County Institutional',
                        'label' => 'County Institutional',
                    ],
                    2 => [
                        'value' => 'Department of Conservation and Recreation',
                        'label' => 'Department of Conservation and Recreation',
                    ],
                    3 => [
                        'value' => 'Federal Institutional',
                        'label' => 'Federal Institutional',
                    ],
                    4 => [
                        'value' => 'Federal Park or Forest',
                        'label' => 'Federal Park or Forest',
                    ],
                    5 => [
                        'value' => 'Massachusetts Department of Transportation',
                        'label' => 'Massachusetts Department of Transportation',
                    ],
                    6 => [
                        'value' => 'Massachusetts Port Authority',
                        'label' => 'Massachusetts Port Authority',
                    ],
                    7 => [
                        'value' => 'Other Federal',
                        'label' => 'Other Federal',
                    ],
                    8 => [
                        'value' => 'Private',
                        'label' => 'Private',
                    ],
                    9 => [
                        'value' => 'State college or university',
                        'label' => 'State college or university',
                    ],
                    10 => [
                        'value' => 'State Institutional',
                        'label' => 'State Institutional',
                    ],
                    11 => [
                        'value' => 'State Park or Forest',
                        'label' => 'State Park or Forest',
                    ],
                    12 => [
                        'value' => 'Unaccepted by city or town',
                        'label' => 'Unaccepted by city or town',
                    ],
                    13 => [
                        'value' => 'US Air Force',
                        'label' => 'US Air Force',
                    ],
                    14 => [
                        'value' => 'US Army',
                        'label' => 'US Army',
                    ],
                    15 => [
                        'value' => 'US Army Corps of Engineers',
                        'label' => 'US Army Corps of Engineers',
                    ],
                    16 => [
                        'value' => 'US Navy',
                        'label' => 'US Navy',
                    ],
                ],
            ],
            41 => [
                'name' => 'first_hrmf_event_loc_descr',
                'label' => 'First Harmful Event Location',
                'type' => 'multiselect',
                'placeholder' => 'Select First Harmful Event Location',
                'options' => [
                    0 => [
                        'value' => 'Median',
                        'label' => 'Median',
                    ],
                    1 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    2 => [
                        'value' => 'Outside roadway',
                        'label' => 'Outside roadway',
                    ],
                    3 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    4 => [
                        'value' => 'Roadside',
                        'label' => 'Roadside',
                    ],
                    5 => [
                        'value' => 'Roadway',
                        'label' => 'Roadway',
                    ],
                    6 => [
                        'value' => 'Shoulder - paved',
                        'label' => 'Shoulder - paved',
                    ],
                    7 => [
                        'value' => 'Shoulder - travel lane',
                        'label' => 'Shoulder - travel lane',
                    ],
                    8 => [
                        'value' => 'Shoulder - unpaved',
                        'label' => 'Shoulder - unpaved',
                    ],
                    9 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            42 => [
                'name' => 'is_geocoded_status',
                'label' => 'Is Geocoded',
                'type' => 'multiselect',
                'placeholder' => 'Select Is Geocoded',
                'options' => [
                    0 => [
                        'value' => 'Low Confidence',
                        'label' => 'Low Confidence',
                    ],
                    1 => [
                        'value' => 'Multiple',
                        'label' => 'Multiple',
                    ],
                    2 => [
                        'value' => 'No',
                        'label' => 'No',
                    ],
                    3 => [
                        'value' => 'One',
                        'label' => 'One',
                    ],
                    4 => [
                        'value' => 'Yes',
                        'label' => 'Yes',
                    ],
                ],
            ],
            43 => [
                'name' => 'geocoding_method_name',
                'label' => 'Geocoding Method',
                'type' => 'multiselect',
                'placeholder' => 'Select Geocoding Method',
                'options' => [
                    0 => [
                        'value' => 'At Address',
                        'label' => 'At Address',
                    ],
                    1 => [
                        'value' => 'At Intersection',
                        'label' => 'At Intersection',
                    ],
                    2 => [
                        'value' => 'Exit Number',
                        'label' => 'Exit Number',
                    ],
                    3 => [
                        'value' => 'Landmark',
                        'label' => 'Landmark',
                    ],
                    4 => [
                        'value' => 'Mile Marker',
                        'label' => 'Mile Marker',
                    ],
                    5 => [
                        'value' => 'Off Intersection',
                        'label' => 'Off Intersection',
                    ],
                    6 => [
                        'value' => 'Operator Designated',
                        'label' => 'Operator Designated',
                    ],
                    7 => [
                        'value' => 'Rotary',
                        'label' => 'Rotary',
                    ],
                ],
            ],
            44 => [
                'name' => 'x_coord',
                'label' => 'X (NAD 1983 StatePlane Massachusetts Mainland Meters)',
                'type' => 'text',
                'placeholder' => 'Enter X (NAD 1983 StatePlane Massachusetts Mainland Meters)',
            ],
            45 => [
                'name' => 'y_coord',
                'label' => 'Y (NAD 1983 StatePlane Massachusetts Mainland Meters)',
                'type' => 'text',
                'placeholder' => 'Enter Y (NAD 1983 StatePlane Massachusetts Mainland Meters)',
            ],
            46 => [
                'name' => 'lat',
                'label' => 'Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Latitude',
            ],
            47 => [
                'name' => 'lon',
                'label' => 'Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Longitude',
            ],
            48 => [
                'name' => 'rmv_doc_ids',
                'label' => 'Document IDs',
                'type' => 'text',
                'placeholder' => 'Enter Document IDs',
            ],
            49 => [
                'name' => 'crash_rpt_ids',
                'label' => 'Crash Report IDs',
                'type' => 'text',
                'placeholder' => 'Enter Crash Report IDs',
            ],
            50 => [
                'name' => 'age_drvr_yngst',
                'label' => 'Age of Driver - Youngest Known',
                'type' => 'multiselect',
                'placeholder' => 'Select Age of Driver - Youngest Known',
                'options' => [
                    0 => [
                        'value' => '<16',
                        'label' => '<16',
                    ],
                    1 => [
                        'value' => '>84',
                        'label' => '>84',
                    ],
                    2 => [
                        'value' => '16-17',
                        'label' => '16-17',
                    ],
                    3 => [
                        'value' => '18-20',
                        'label' => '18-20',
                    ],
                    4 => [
                        'value' => '21-24',
                        'label' => '21-24',
                    ],
                    5 => [
                        'value' => '25-34',
                        'label' => '25-34',
                    ],
                    6 => [
                        'value' => '35-44',
                        'label' => '35-44',
                    ],
                    7 => [
                        'value' => '45-54',
                        'label' => '45-54',
                    ],
                    8 => [
                        'value' => '55-64',
                        'label' => '55-64',
                    ],
                    9 => [
                        'value' => '65-74',
                        'label' => '65-74',
                    ],
                    10 => [
                        'value' => '75-84',
                        'label' => '75-84',
                    ],
                ],
            ],
            51 => [
                'name' => 'age_drvr_oldest',
                'label' => 'Age of Driver - Oldest Known',
                'type' => 'multiselect',
                'placeholder' => 'Select Age of Driver - Oldest Known',
                'options' => [
                    0 => [
                        'value' => '<16',
                        'label' => '<16',
                    ],
                    1 => [
                        'value' => '>84',
                        'label' => '>84',
                    ],
                    2 => [
                        'value' => '16-17',
                        'label' => '16-17',
                    ],
                    3 => [
                        'value' => '18-20',
                        'label' => '18-20',
                    ],
                    4 => [
                        'value' => '21-24',
                        'label' => '21-24',
                    ],
                    5 => [
                        'value' => '25-34',
                        'label' => '25-34',
                    ],
                    6 => [
                        'value' => '35-44',
                        'label' => '35-44',
                    ],
                    7 => [
                        'value' => '45-54',
                        'label' => '45-54',
                    ],
                    8 => [
                        'value' => '55-64',
                        'label' => '55-64',
                    ],
                    9 => [
                        'value' => '65-74',
                        'label' => '65-74',
                    ],
                    10 => [
                        'value' => '75-84',
                        'label' => '75-84',
                    ],
                ],
            ],
            52 => [
                'name' => 'age_nonmtrst_yngst',
                'label' => 'Age of Vulnerable User - Youngest Known',
                'type' => 'multiselect',
                'placeholder' => 'Select Age of Vulnerable User - Youngest Known',
                'options' => [
                    0 => [
                        'value' => '<6',
                        'label' => '<6',
                    ],
                    1 => [
                        'value' => '>84',
                        'label' => '>84',
                    ],
                    2 => [
                        'value' => '16-20',
                        'label' => '16-20',
                    ],
                    3 => [
                        'value' => '21-24',
                        'label' => '21-24',
                    ],
                    4 => [
                        'value' => '25-34',
                        'label' => '25-34',
                    ],
                    5 => [
                        'value' => '35-44',
                        'label' => '35-44',
                    ],
                    6 => [
                        'value' => '45-54',
                        'label' => '45-54',
                    ],
                    7 => [
                        'value' => '55-64',
                        'label' => '55-64',
                    ],
                    8 => [
                        'value' => '6-15',
                        'label' => '6-15',
                    ],
                    9 => [
                        'value' => '65-74',
                        'label' => '65-74',
                    ],
                    10 => [
                        'value' => '75-84',
                        'label' => '75-84',
                    ],
                ],
            ],
            53 => [
                'name' => 'age_nonmtrst_oldest',
                'label' => 'Age of Vulnerable User - Oldest Known',
                'type' => 'multiselect',
                'placeholder' => 'Select Age of Vulnerable User - Oldest Known',
                'options' => [
                    0 => [
                        'value' => '<6',
                        'label' => '<6',
                    ],
                    1 => [
                        'value' => '>84',
                        'label' => '>84',
                    ],
                    2 => [
                        'value' => '16-20',
                        'label' => '16-20',
                    ],
                    3 => [
                        'value' => '21-24',
                        'label' => '21-24',
                    ],
                    4 => [
                        'value' => '25-34',
                        'label' => '25-34',
                    ],
                    5 => [
                        'value' => '35-44',
                        'label' => '35-44',
                    ],
                    6 => [
                        'value' => '45-54',
                        'label' => '45-54',
                    ],
                    7 => [
                        'value' => '55-64',
                        'label' => '55-64',
                    ],
                    8 => [
                        'value' => '6-15',
                        'label' => '6-15',
                    ],
                    9 => [
                        'value' => '65-74',
                        'label' => '65-74',
                    ],
                    10 => [
                        'value' => '75-84',
                        'label' => '75-84',
                    ],
                ],
            ],
            54 => [
                'name' => 'drvr_distracted_cl',
                'label' => 'Driver Distracted By (All Drivers)',
                'type' => 'text',
                'placeholder' => 'Enter Driver Distracted By (All Drivers)',
            ],
            55 => [
                'name' => 'district_num',
                'label' => 'District',
                'type' => 'multiselect',
                'placeholder' => 'Select District',
                'options' => [
                    0 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    1 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    2 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    3 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    4 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    5 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                ],
            ],
            56 => [
                'name' => 'rpa_abbr',
                'label' => 'RPA',
                'type' => 'multiselect',
                'placeholder' => 'Select RPA',
                'options' => [
                    0 => [
                        'value' => 'BRPC',
                        'label' => 'BRPC',
                    ],
                    1 => [
                        'value' => 'CCC',
                        'label' => 'CCC',
                    ],
                    2 => [
                        'value' => 'CMRPC',
                        'label' => 'CMRPC',
                    ],
                    3 => [
                        'value' => 'FRCOG',
                        'label' => 'FRCOG',
                    ],
                    4 => [
                        'value' => 'MAPC',
                        'label' => 'MAPC',
                    ],
                    5 => [
                        'value' => 'MRPC',
                        'label' => 'MRPC',
                    ],
                    6 => [
                        'value' => 'MVC',
                        'label' => 'MVC',
                    ],
                    7 => [
                        'value' => 'MVPC',
                        'label' => 'MVPC',
                    ],
                    8 => [
                        'value' => 'NMCOG',
                        'label' => 'NMCOG',
                    ],
                    9 => [
                        'value' => 'NRPEDC',
                        'label' => 'NRPEDC',
                    ],
                    10 => [
                        'value' => 'OCPC',
                        'label' => 'OCPC',
                    ],
                    11 => [
                        'value' => 'PVPC',
                        'label' => 'PVPC',
                    ],
                    12 => [
                        'value' => 'SRPEDD',
                        'label' => 'SRPEDD',
                    ],
                ],
            ],
            57 => [
                'name' => 'vehc_emer_use_cl',
                'label' => 'Vehicle Emergency Use (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Emergency Use (All Vehicles)',
            ],
            58 => [
                'name' => 'vehc_towed_from_scene_cl',
                'label' => 'Vehicle Towed From Scene (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Towed From Scene (All Vehicles)',
            ],
            59 => [
                'name' => 'cnty_name',
                'label' => 'County Name',
                'type' => 'multiselect',
                'placeholder' => 'Select County Name',
                'options' => [
                    0 => [
                        'value' => 'BARNSTABLE',
                        'label' => 'BARNSTABLE',
                    ],
                    1 => [
                        'value' => 'BERKSHIRE',
                        'label' => 'BERKSHIRE',
                    ],
                    2 => [
                        'value' => 'BRISTOL',
                        'label' => 'BRISTOL',
                    ],
                    3 => [
                        'value' => 'DUKES',
                        'label' => 'DUKES',
                    ],
                    4 => [
                        'value' => 'ESSEX',
                        'label' => 'ESSEX',
                    ],
                    5 => [
                        'value' => 'FRANKLIN',
                        'label' => 'FRANKLIN',
                    ],
                    6 => [
                        'value' => 'HAMPDEN',
                        'label' => 'HAMPDEN',
                    ],
                    7 => [
                        'value' => 'HAMPSHIRE',
                        'label' => 'HAMPSHIRE',
                    ],
                    8 => [
                        'value' => 'MIDDLESEX',
                        'label' => 'MIDDLESEX',
                    ],
                    9 => [
                        'value' => 'NANTUCKET',
                        'label' => 'NANTUCKET',
                    ],
                    10 => [
                        'value' => 'NORFOLK',
                        'label' => 'NORFOLK',
                    ],
                    11 => [
                        'value' => 'PLYMOUTH',
                        'label' => 'PLYMOUTH',
                    ],
                    12 => [
                        'value' => 'SUFFOLK',
                        'label' => 'SUFFOLK',
                    ],
                    13 => [
                        'value' => 'WORCESTER',
                        'label' => 'WORCESTER',
                    ],
                ],
            ],
            60 => [
                'name' => 'fmsca_rptbl_cl',
                'label' => 'FMCSA Reportable (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter FMCSA Reportable (All Vehicles)',
            ],
            61 => [
                'name' => 'fmsca_rptbl',
                'label' => 'FMCSA Reportable (Crash)',
                'type' => 'boolean',
            ],
            62 => [
                'name' => 'hit_run_descr',
                'label' => 'Hit and Run',
                'type' => 'boolean',
            ],
            63 => [
                'name' => 'lclty_name',
                'label' => 'Locality',
                'type' => 'multiselect',
                'placeholder' => 'Select Locality',
                'options' => [
                    0 => [
                        'value' => 'CHARLESTOWN',
                        'label' => 'CHARLESTOWN',
                    ],
                    1 => [
                        'value' => 'FLORENCE',
                        'label' => 'FLORENCE',
                    ],
                    2 => [
                        'value' => 'HYANNIS',
                        'label' => 'HYANNIS',
                    ],
                    3 => [
                        'value' => 'HYDE PARK',
                        'label' => 'HYDE PARK',
                    ],
                    4 => [
                        'value' => 'LEEDS',
                        'label' => 'LEEDS',
                    ],
                    5 => [
                        'value' => 'MATTAPAN',
                        'label' => 'MATTAPAN',
                    ],
                    6 => [
                        'value' => 'MONTAGUE CENTER',
                        'label' => 'MONTAGUE CENTER',
                    ],
                    7 => [
                        'value' => 'ROSLINDALE',
                        'label' => 'ROSLINDALE',
                    ],
                    8 => [
                        'value' => 'ROXBURY',
                        'label' => 'ROXBURY',
                    ],
                    9 => [
                        'value' => 'THORNDIKE',
                        'label' => 'THORNDIKE',
                    ],
                ],
            ],
            64 => [
                'name' => 'road_cntrb_descr',
                'label' => 'Road Contributing Circumstance',
                'type' => 'multiselect',
                'placeholder' => 'Select Road Contributing Circumstance',
                'options' => [
                    0 => [
                        'value' => 'Debris',
                        'label' => 'Debris',
                    ],
                    1 => [
                        'value' => 'Non-highway work',
                        'label' => 'Non-highway work',
                    ],
                    2 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    3 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    4 => [
                        'value' => 'Obstruction in roadway',
                        'label' => 'Obstruction in roadway',
                    ],
                    5 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    6 => [
                        'value' => 'Road surface condition (wet, icy, snow, slush, etc.)',
                        'label' => 'Road surface condition (wet, icy, snow, slush, etc.)',
                    ],
                    7 => [
                        'value' => 'Rut, holes, bumps',
                        'label' => 'Rut, holes, bumps',
                    ],
                    8 => [
                        'value' => 'Shoulders (none, low, soft)',
                        'label' => 'Shoulders (none, low, soft)',
                    ],
                    9 => [
                        'value' => 'Toll/booth/plaza related',
                        'label' => 'Toll/booth/plaza related',
                    ],
                    10 => [
                        'value' => 'Traffic congestion related',
                        'label' => 'Traffic congestion related',
                    ],
                    11 => [
                        'value' => 'Traffic control device inoperative, missing, or obscured',
                        'label' => 'Traffic control device inoperative, missing, or obscured',
                    ],
                    12 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    13 => [
                        'value' => 'Work zone (construction/maintenance/utility)',
                        'label' => 'Work zone (construction/maintenance/utility)',
                    ],
                    14 => [
                        'value' => 'Worn, travel-polished surface',
                        'label' => 'Worn, travel-polished surface',
                    ],
                ],
            ],
            65 => [
                'name' => 'schl_bus_reld_descr',
                'label' => 'School Bus Related',
                'type' => 'boolean',
            ],
            66 => [
                'name' => 'speed_limit_min',
                'label' => 'Speed Limit Min',
                'type' => 'number',
                'placeholder' => 'Min value for Speed Limit',
            ],
            67 => [
                'name' => 'speed_limit_max',
                'label' => 'Speed Limit Max',
                'type' => 'number',
                'placeholder' => 'Max value for Speed Limit',
            ],
            68 => [
                'name' => 'traf_cntrl_devc_func_descr',
                'label' => 'Traffic Control Device Functioning',
                'type' => 'multiselect',
                'placeholder' => 'Select Traffic Control Device Functioning',
                'options' => [
                    0 => [
                        'value' => 'No, device not functioning',
                        'label' => 'No, device not functioning',
                    ],
                    1 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    2 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    3 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    4 => [
                        'value' => 'Yes, device functioning',
                        'label' => 'Yes, device functioning',
                    ],
                ],
            ],
            69 => [
                'name' => 'work_zone_reld_descr',
                'label' => 'Work Zone Related',
                'type' => 'boolean',
            ],
            70 => [
                'name' => 'aadt_min',
                'label' => 'AADT-linked RD Min',
                'type' => 'number',
                'placeholder' => 'Min value for AADT-linked RD',
            ],
            71 => [
                'name' => 'aadt_max',
                'label' => 'AADT-linked RD Max',
                'type' => 'number',
                'placeholder' => 'Max value for AADT-linked RD',
            ],
            72 => [
                'name' => 'aadt_year',
                'label' => 'AADT Year-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select AADT Year-linked RD',
                'options' => [
                    0 => [
                        'value' => '2009',
                        'label' => '2009',
                    ],
                    1 => [
                        'value' => '2010',
                        'label' => '2010',
                    ],
                    2 => [
                        'value' => '2011',
                        'label' => '2011',
                    ],
                    3 => [
                        'value' => '2012',
                        'label' => '2012',
                    ],
                    4 => [
                        'value' => '2013',
                        'label' => '2013',
                    ],
                    5 => [
                        'value' => '2014',
                        'label' => '2014',
                    ],
                    6 => [
                        'value' => '2015',
                        'label' => '2015',
                    ],
                    7 => [
                        'value' => '2016',
                        'label' => '2016',
                    ],
                    8 => [
                        'value' => '2017',
                        'label' => '2017',
                    ],
                    9 => [
                        'value' => '2018',
                        'label' => '2018',
                    ],
                    10 => [
                        'value' => '2019',
                        'label' => '2019',
                    ],
                    11 => [
                        'value' => '2020',
                        'label' => '2020',
                    ],
                ],
            ],
            73 => [
                'name' => 'pk_pct_sut',
                'label' => 'Peak % Single Unit Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Peak % Single Unit Trucks-linked RD',
            ],
            74 => [
                'name' => 'av_pct_sut',
                'label' => 'Average Daily % Single Unit Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Average Daily % Single Unit Trucks-linked RD',
            ],
            75 => [
                'name' => 'pk_pct_ct',
                'label' => 'Peak % Combo Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Peak % Combo Trucks-linked RD',
            ],
            76 => [
                'name' => 'av_pct_ct',
                'label' => 'Average Daily % Combo Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Average Daily % Combo Trucks-linked RD',
            ],
            77 => [
                'name' => 'curb',
                'label' => 'Curb-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Curb-linked RD',
                'options' => [
                    0 => [
                        'value' => 'All curbs (divided highway)',
                        'label' => 'All curbs (divided highway)',
                    ],
                    1 => [
                        'value' => 'Along median only',
                        'label' => 'Along median only',
                    ],
                    2 => [
                        'value' => 'Both sides',
                        'label' => 'Both sides',
                    ],
                    3 => [
                        'value' => 'Left side only',
                        'label' => 'Left side only',
                    ],
                    4 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    5 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    6 => [
                        'value' => 'Right side only',
                        'label' => 'Right side only',
                    ],
                ],
            ],
            78 => [
                'name' => 'truck_rte',
                'label' => 'Truck Route-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Truck Route-linked RD',
                'options' => [
                    0 => [
                        'value' => 'DCR Parkway - Recreational Vehicles Only',
                        'label' => 'DCR Parkway - Recreational Vehicles Only',
                    ],
                    1 => [
                        'value' => 'Designated truck route ONLY under State Authority.  Fully available to both types of STAA vehicles described above',
                        'label' => 'Designated truck route ONLY under State Authority.  Fully available to both types of STAA vehicles described above',
                    ],
                    2 => [
                        'value' => 'Not a parkway - not on a designated truck route',
                        'label' => 'Not a parkway - not on a designated truck route',
                    ],
                ],
            ],
            79 => [
                'name' => 'lt_sidewlk',
                'label' => 'Left Sidewalk Width-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Left Sidewalk Width-linked RD',
                'options' => [
                    0 => [
                        'value' => '0.0',
                        'label' => '0.0',
                    ],
                    1 => [
                        'value' => '1.0',
                        'label' => '1.0',
                    ],
                    2 => [
                        'value' => '2.0',
                        'label' => '2.0',
                    ],
                    3 => [
                        'value' => '3.0',
                        'label' => '3.0',
                    ],
                    4 => [
                        'value' => '4.0',
                        'label' => '4.0',
                    ],
                    5 => [
                        'value' => '5.0',
                        'label' => '5.0',
                    ],
                    6 => [
                        'value' => '6.0',
                        'label' => '6.0',
                    ],
                    7 => [
                        'value' => '7.0',
                        'label' => '7.0',
                    ],
                    8 => [
                        'value' => '8.0',
                        'label' => '8.0',
                    ],
                    9 => [
                        'value' => '9.0',
                        'label' => '9.0',
                    ],
                    10 => [
                        'value' => '10.0',
                        'label' => '10.0',
                    ],
                    11 => [
                        'value' => '11.0',
                        'label' => '11.0',
                    ],
                    12 => [
                        'value' => '12.0',
                        'label' => '12.0',
                    ],
                    13 => [
                        'value' => '13.0',
                        'label' => '13.0',
                    ],
                    14 => [
                        'value' => '14.0',
                        'label' => '14.0',
                    ],
                    15 => [
                        'value' => '15.0',
                        'label' => '15.0',
                    ],
                    16 => [
                        'value' => '16.0',
                        'label' => '16.0',
                    ],
                    17 => [
                        'value' => '18.0',
                        'label' => '18.0',
                    ],
                    18 => [
                        'value' => '20.0',
                        'label' => '20.0',
                    ],
                    19 => [
                        'value' => '22.0',
                        'label' => '22.0',
                    ],
                    20 => [
                        'value' => '23.0',
                        'label' => '23.0',
                    ],
                    21 => [
                        'value' => '26.0',
                        'label' => '26.0',
                    ],
                    22 => [
                        'value' => '27.0',
                        'label' => '27.0',
                    ],
                    23 => [
                        'value' => '28.0',
                        'label' => '28.0',
                    ],
                    24 => [
                        'value' => '30.0',
                        'label' => '30.0',
                    ],
                ],
            ],
            80 => [
                'name' => 'rt_sidewlk',
                'label' => 'Right Sidewalk Width-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Right Sidewalk Width-linked RD',
                'options' => [
                    0 => [
                        'value' => '0.0',
                        'label' => '0.0',
                    ],
                    1 => [
                        'value' => '1.0',
                        'label' => '1.0',
                    ],
                    2 => [
                        'value' => '2.0',
                        'label' => '2.0',
                    ],
                    3 => [
                        'value' => '3.0',
                        'label' => '3.0',
                    ],
                    4 => [
                        'value' => '4.0',
                        'label' => '4.0',
                    ],
                    5 => [
                        'value' => '5.0',
                        'label' => '5.0',
                    ],
                    6 => [
                        'value' => '6.0',
                        'label' => '6.0',
                    ],
                    7 => [
                        'value' => '7.0',
                        'label' => '7.0',
                    ],
                    8 => [
                        'value' => '8.0',
                        'label' => '8.0',
                    ],
                    9 => [
                        'value' => '9.0',
                        'label' => '9.0',
                    ],
                    10 => [
                        'value' => '10.0',
                        'label' => '10.0',
                    ],
                    11 => [
                        'value' => '11.0',
                        'label' => '11.0',
                    ],
                    12 => [
                        'value' => '12.0',
                        'label' => '12.0',
                    ],
                    13 => [
                        'value' => '13.0',
                        'label' => '13.0',
                    ],
                    14 => [
                        'value' => '14.0',
                        'label' => '14.0',
                    ],
                    15 => [
                        'value' => '15.0',
                        'label' => '15.0',
                    ],
                    16 => [
                        'value' => '16.0',
                        'label' => '16.0',
                    ],
                    17 => [
                        'value' => '17.0',
                        'label' => '17.0',
                    ],
                    18 => [
                        'value' => '18.0',
                        'label' => '18.0',
                    ],
                    19 => [
                        'value' => '20.0',
                        'label' => '20.0',
                    ],
                    20 => [
                        'value' => '22.0',
                        'label' => '22.0',
                    ],
                    21 => [
                        'value' => '24.0',
                        'label' => '24.0',
                    ],
                    22 => [
                        'value' => '26.0',
                        'label' => '26.0',
                    ],
                    23 => [
                        'value' => '50.0',
                        'label' => '50.0',
                    ],
                ],
            ],
            81 => [
                'name' => 'shldr_lt_w',
                'label' => 'Left Shoulder Width-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Left Shoulder Width-linked RD',
                'options' => [
                    0 => [
                        'value' => '0.0',
                        'label' => '0.0',
                    ],
                    1 => [
                        'value' => '1.0',
                        'label' => '1.0',
                    ],
                    2 => [
                        'value' => '2.0',
                        'label' => '2.0',
                    ],
                    3 => [
                        'value' => '3.0',
                        'label' => '3.0',
                    ],
                    4 => [
                        'value' => '4.0',
                        'label' => '4.0',
                    ],
                    5 => [
                        'value' => '5.0',
                        'label' => '5.0',
                    ],
                    6 => [
                        'value' => '6.0',
                        'label' => '6.0',
                    ],
                    7 => [
                        'value' => '7.0',
                        'label' => '7.0',
                    ],
                    8 => [
                        'value' => '8.0',
                        'label' => '8.0',
                    ],
                    9 => [
                        'value' => '10.0',
                        'label' => '10.0',
                    ],
                    10 => [
                        'value' => '12.0',
                        'label' => '12.0',
                    ],
                    11 => [
                        'value' => '13.0',
                        'label' => '13.0',
                    ],
                    12 => [
                        'value' => '14.0',
                        'label' => '14.0',
                    ],
                    13 => [
                        'value' => '15.0',
                        'label' => '15.0',
                    ],
                    14 => [
                        'value' => '17.0',
                        'label' => '17.0',
                    ],
                    15 => [
                        'value' => '20.0',
                        'label' => '20.0',
                    ],
                    16 => [
                        'value' => '22.0',
                        'label' => '22.0',
                    ],
                    17 => [
                        'value' => '24.0',
                        'label' => '24.0',
                    ],
                    18 => [
                        'value' => '28.0',
                        'label' => '28.0',
                    ],
                ],
            ],
            82 => [
                'name' => 'shldr_lt_t',
                'label' => 'Left Shoulder Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Left Shoulder Type-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Earth Shoulder Exists',
                        'label' => 'Earth Shoulder Exists',
                    ],
                    1 => [
                        'value' => 'Hardened bituminous mix or penetration',
                        'label' => 'Hardened bituminous mix or penetration',
                    ],
                    2 => [
                        'value' => 'No Shoulder',
                        'label' => 'No Shoulder',
                    ],
                    3 => [
                        'value' => 'Stable - Unruttable compacted subgrade',
                        'label' => 'Stable - Unruttable compacted subgrade',
                    ],
                    4 => [
                        'value' => 'Unstable shoulder',
                        'label' => 'Unstable shoulder',
                    ],
                ],
            ],
            83 => [
                'name' => 'surface_wd',
                'label' => 'Surface Width-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Surface Width-linked RD',
            ],
            84 => [
                'name' => 'surface_tp',
                'label' => 'Surface Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Surface Type-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => 'Bituminous concrete road',
                        'label' => 'Bituminous concrete road',
                    ],
                    2 => [
                        'value' => 'Block road',
                        'label' => 'Block road',
                    ],
                    3 => [
                        'value' => 'Brick road',
                        'label' => 'Brick road',
                    ],
                    4 => [
                        'value' => 'Composite road; flexible over rigid',
                        'label' => 'Composite road; flexible over rigid',
                    ],
                    5 => [
                        'value' => 'Gravel or stone road',
                        'label' => 'Gravel or stone road',
                    ],
                    6 => [
                        'value' => 'Portland cement concrete road',
                        'label' => 'Portland cement concrete road',
                    ],
                    7 => [
                        'value' => 'Surface-treated road',
                        'label' => 'Surface-treated road',
                    ],
                    8 => [
                        'value' => 'Unimproved, graded earth, or soil surface road',
                        'label' => 'Unimproved, graded earth, or soil surface road',
                    ],
                ],
            ],
            85 => [
                'name' => 'shldr_rt_w',
                'label' => 'Right Shoulder Width-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Right Shoulder Width-linked RD',
                'options' => [
                    0 => [
                        'value' => '0.0',
                        'label' => '0.0',
                    ],
                    1 => [
                        'value' => '1.0',
                        'label' => '1.0',
                    ],
                    2 => [
                        'value' => '2.0',
                        'label' => '2.0',
                    ],
                    3 => [
                        'value' => '3.0',
                        'label' => '3.0',
                    ],
                    4 => [
                        'value' => '4.0',
                        'label' => '4.0',
                    ],
                    5 => [
                        'value' => '5.0',
                        'label' => '5.0',
                    ],
                    6 => [
                        'value' => '6.0',
                        'label' => '6.0',
                    ],
                    7 => [
                        'value' => '7.0',
                        'label' => '7.0',
                    ],
                    8 => [
                        'value' => '8.0',
                        'label' => '8.0',
                    ],
                    9 => [
                        'value' => '9.0',
                        'label' => '9.0',
                    ],
                    10 => [
                        'value' => '10.0',
                        'label' => '10.0',
                    ],
                    11 => [
                        'value' => '11.0',
                        'label' => '11.0',
                    ],
                    12 => [
                        'value' => '12.0',
                        'label' => '12.0',
                    ],
                    13 => [
                        'value' => '13.0',
                        'label' => '13.0',
                    ],
                    14 => [
                        'value' => '14.0',
                        'label' => '14.0',
                    ],
                    15 => [
                        'value' => '15.0',
                        'label' => '15.0',
                    ],
                    16 => [
                        'value' => '16.0',
                        'label' => '16.0',
                    ],
                    17 => [
                        'value' => '17.0',
                        'label' => '17.0',
                    ],
                    18 => [
                        'value' => '18.0',
                        'label' => '18.0',
                    ],
                    19 => [
                        'value' => '19.0',
                        'label' => '19.0',
                    ],
                    20 => [
                        'value' => '20.0',
                        'label' => '20.0',
                    ],
                    21 => [
                        'value' => '22.0',
                        'label' => '22.0',
                    ],
                    22 => [
                        'value' => '23.0',
                        'label' => '23.0',
                    ],
                    23 => [
                        'value' => '24.0',
                        'label' => '24.0',
                    ],
                    24 => [
                        'value' => '26.0',
                        'label' => '26.0',
                    ],
                    25 => [
                        'value' => '30.0',
                        'label' => '30.0',
                    ],
                    26 => [
                        'value' => '36.0',
                        'label' => '36.0',
                    ],
                ],
            ],
            86 => [
                'name' => 'shldr_rt_t',
                'label' => 'Right Shoulder Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Right Shoulder Type-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Combination shoulder',
                        'label' => 'Combination shoulder',
                    ],
                    1 => [
                        'value' => 'Earth Shoulder Exists',
                        'label' => 'Earth Shoulder Exists',
                    ],
                    2 => [
                        'value' => 'Hardened bituminous mix or penetration',
                        'label' => 'Hardened bituminous mix or penetration',
                    ],
                    3 => [
                        'value' => 'No Shoulder',
                        'label' => 'No Shoulder',
                    ],
                    4 => [
                        'value' => 'Stable - Unruttable compacted subgrade',
                        'label' => 'Stable - Unruttable compacted subgrade',
                    ],
                    5 => [
                        'value' => 'Unstable shoulder',
                        'label' => 'Unstable shoulder',
                    ],
                ],
            ],
            87 => [
                'name' => 'num_lanes',
                'label' => 'Number of Travel Lanes-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Number of Travel Lanes-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    2 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    3 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    4 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    5 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    6 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                ],
            ],
            88 => [
                'name' => 'opp_lanes',
                'label' => 'Number of Opposing Travel Lanes-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Number of Opposing Travel Lanes-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    2 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    3 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    4 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                ],
            ],
            89 => [
                'name' => 'med_width',
                'label' => 'Median Width-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Median Width-linked RD',
            ],
            90 => [
                'name' => 'med_type',
                'label' => 'Median Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Median Type-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Depressed Median',
                        'label' => 'Depressed Median',
                    ],
                    1 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    2 => [
                        'value' => 'Positive Barrier - unspecified',
                        'label' => 'Positive Barrier - unspecified',
                    ],
                    3 => [
                        'value' => 'Positive Barrier – flexible',
                        'label' => 'Positive Barrier – flexible',
                    ],
                    4 => [
                        'value' => 'Positive Barrier – rigid',
                        'label' => 'Positive Barrier – rigid',
                    ],
                    5 => [
                        'value' => 'Positive Barrier – semi-rigid',
                        'label' => 'Positive Barrier – semi-rigid',
                    ],
                    6 => [
                        'value' => 'Raised Median',
                        'label' => 'Raised Median',
                    ],
                    7 => [
                        'value' => 'Unprotected',
                        'label' => 'Unprotected',
                    ],
                ],
            ],
            91 => [
                'name' => 'urban_type',
                'label' => 'Urban Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Urban Type-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Large Urban Cluster',
                        'label' => 'Large Urban Cluster',
                    ],
                    1 => [
                        'value' => 'Large Urbanized Area',
                        'label' => 'Large Urbanized Area',
                    ],
                    2 => [
                        'value' => 'Rural',
                        'label' => 'Rural',
                    ],
                    3 => [
                        'value' => 'Small Urban Cluster',
                        'label' => 'Small Urban Cluster',
                    ],
                    4 => [
                        'value' => 'Small Urbanized Area',
                        'label' => 'Small Urbanized Area',
                    ],
                ],
            ],
            92 => [
                'name' => 'f_class',
                'label' => 'Functional Classification-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Functional Classification-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Interstate',
                        'label' => 'Interstate',
                    ],
                    1 => [
                        'value' => 'Local',
                        'label' => 'Local',
                    ],
                    2 => [
                        'value' => 'Rural minor arterial or urban principal arterial',
                        'label' => 'Rural minor arterial or urban principal arterial',
                    ],
                    3 => [
                        'value' => 'Rural or urban principal arterial',
                        'label' => 'Rural or urban principal arterial',
                    ],
                    4 => [
                        'value' => 'Urban collector or rural minor collector',
                        'label' => 'Urban collector or rural minor collector',
                    ],
                    5 => [
                        'value' => 'Urban minor arterial or rural major collector',
                        'label' => 'Urban minor arterial or rural major collector',
                    ],
                ],
            ],
            93 => [
                'name' => 'urban_area',
                'label' => 'Urbanized Area-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Urbanized Area-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Athol',
                        'label' => 'Athol',
                    ],
                    1 => [
                        'value' => 'Barnstable Town',
                        'label' => 'Barnstable Town',
                    ],
                    2 => [
                        'value' => 'Boston (MA-NH-RI)',
                        'label' => 'Boston (MA-NH-RI)',
                    ],
                    3 => [
                        'value' => 'Great Barrington',
                        'label' => 'Great Barrington',
                    ],
                    4 => [
                        'value' => 'Greenfield',
                        'label' => 'Greenfield',
                    ],
                    5 => [
                        'value' => 'Lee',
                        'label' => 'Lee',
                    ],
                    6 => [
                        'value' => 'Leominster-Fitchburg',
                        'label' => 'Leominster-Fitchburg',
                    ],
                    7 => [
                        'value' => 'Nantucket',
                        'label' => 'Nantucket',
                    ],
                    8 => [
                        'value' => 'Nashua (NH-MA)',
                        'label' => 'Nashua (NH-MA)',
                    ],
                    9 => [
                        'value' => 'New Bedford',
                        'label' => 'New Bedford',
                    ],
                    10 => [
                        'value' => 'North Adams (MA-VT)',
                        'label' => 'North Adams (MA-VT)',
                    ],
                    11 => [
                        'value' => 'North Brookfield',
                        'label' => 'North Brookfield',
                    ],
                    12 => [
                        'value' => 'Pittsfield',
                        'label' => 'Pittsfield',
                    ],
                    13 => [
                        'value' => 'Providence (RI-MA)',
                        'label' => 'Providence (RI-MA)',
                    ],
                    14 => [
                        'value' => 'Provincetown',
                        'label' => 'Provincetown',
                    ],
                    15 => [
                        'value' => 'RURAL',
                        'label' => 'RURAL',
                    ],
                    16 => [
                        'value' => 'South Deerfield',
                        'label' => 'South Deerfield',
                    ],
                    17 => [
                        'value' => 'Springfield (MA-CT)',
                        'label' => 'Springfield (MA-CT)',
                    ],
                    18 => [
                        'value' => 'Vineyard Haven',
                        'label' => 'Vineyard Haven',
                    ],
                    19 => [
                        'value' => 'Ware',
                        'label' => 'Ware',
                    ],
                    20 => [
                        'value' => 'Worcester (MA-CT)',
                        'label' => 'Worcester (MA-CT)',
                    ],
                ],
            ],
            94 => [
                'name' => 'fd_aid_rte',
                'label' => 'Federal Aid Route-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Federal Aid Route-linked RD',
            ],
            95 => [
                'name' => 'facility',
                'label' => 'Facility Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Facility Type-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Collector - Distributor',
                        'label' => 'Collector - Distributor',
                    ],
                    1 => [
                        'value' => 'Doubledeck',
                        'label' => 'Doubledeck',
                    ],
                    2 => [
                        'value' => 'Mainline roadway',
                        'label' => 'Mainline roadway',
                    ],
                    3 => [
                        'value' => 'Private Way',
                        'label' => 'Private Way',
                    ],
                    4 => [
                        'value' => 'Ramp - NB/EB',
                        'label' => 'Ramp - NB/EB',
                    ],
                    5 => [
                        'value' => 'Ramp - SB/WB',
                        'label' => 'Ramp - SB/WB',
                    ],
                    6 => [
                        'value' => 'Rotary',
                        'label' => 'Rotary',
                    ],
                    7 => [
                        'value' => 'Roundabout',
                        'label' => 'Roundabout',
                    ],
                    8 => [
                        'value' => 'Simple Ramp - Tunnel',
                        'label' => 'Simple Ramp - Tunnel',
                    ],
                    9 => [
                        'value' => 'Simple Ramp/ Channelized Turning Lane',
                        'label' => 'Simple Ramp/ Channelized Turning Lane',
                    ],
                    10 => [
                        'value' => 'Tunnel',
                        'label' => 'Tunnel',
                    ],
                ],
            ],
            96 => [
                'name' => 'operation',
                'label' => 'Street Operation-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Street Operation-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => 'One-way traffic',
                        'label' => 'One-way traffic',
                    ],
                    2 => [
                        'value' => 'Two-way traffic',
                        'label' => 'Two-way traffic',
                    ],
                ],
            ],
            97 => [
                'name' => 'control',
                'label' => 'Access Control-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Access Control-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Full Access Control',
                        'label' => 'Full Access Control',
                    ],
                    1 => [
                        'value' => 'No Access Control',
                        'label' => 'No Access Control',
                    ],
                    2 => [
                        'value' => 'Partial Access Control',
                        'label' => 'Partial Access Control',
                    ],
                ],
            ],
            98 => [
                'name' => 'peak_lane',
                'label' => 'Number of Peak Hour Lanes-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Number of Peak Hour Lanes-linked RD',
                'options' => [
                    0 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    1 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    2 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    3 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    4 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                ],
            ],
            99 => [
                'name' => 'speed_lim',
                'label' => 'Speed Limit-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Speed Limit-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    2 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    3 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    4 => [
                        'value' => '20',
                        'label' => '20',
                    ],
                    5 => [
                        'value' => '24',
                        'label' => '24',
                    ],
                    6 => [
                        'value' => '25',
                        'label' => '25',
                    ],
                    7 => [
                        'value' => '30',
                        'label' => '30',
                    ],
                    8 => [
                        'value' => '35',
                        'label' => '35',
                    ],
                    9 => [
                        'value' => '40',
                        'label' => '40',
                    ],
                    10 => [
                        'value' => '45',
                        'label' => '45',
                    ],
                    11 => [
                        'value' => '50',
                        'label' => '50',
                    ],
                    12 => [
                        'value' => '55',
                        'label' => '55',
                    ],
                    13 => [
                        'value' => '60',
                        'label' => '60',
                    ],
                    14 => [
                        'value' => '65',
                        'label' => '65',
                    ],
                    15 => [
                        'value' => '99',
                        'label' => '99',
                    ],
                ],
            ],
            100 => [
                'name' => 'streetname',
                'label' => 'Street Name-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Street Name-linked RD',
            ],
            101 => [
                'name' => 'fromstreetname',
                'label' => 'From Street Name-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter From Street Name-linked RD',
            ],
            102 => [
                'name' => 'tostreetname',
                'label' => 'To Street Name-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter To Street Name-linked RD',
            ],
            103 => [
                'name' => 'city',
                'label' => 'City-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter City-linked RD',
            ],
            104 => [
                'name' => 'struct_cnd',
                'label' => 'Structural Condition-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Structural Condition-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => 'Deficient',
                        'label' => 'Deficient',
                    ],
                    2 => [
                        'value' => 'Fair',
                        'label' => 'Fair',
                    ],
                    3 => [
                        'value' => 'Good',
                        'label' => 'Good',
                    ],
                    4 => [
                        'value' => 'Intolerable',
                        'label' => 'Intolerable',
                    ],
                ],
            ],
            105 => [
                'name' => 'terrain',
                'label' => 'Terrain-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Terrain-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Level Terrain',
                        'label' => 'Level Terrain',
                    ],
                    1 => [
                        'value' => 'Mountainous Terrain',
                        'label' => 'Mountainous Terrain',
                    ],
                    2 => [
                        'value' => 'Rolling Terrain',
                        'label' => 'Rolling Terrain',
                    ],
                ],
            ],
            106 => [
                'name' => 'urban_loc_type',
                'label' => 'Urban Location Type-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Urban Location Type-linked RD',
            ],
            107 => [
                'name' => 'aadt_deriv',
                'label' => 'AADT Derivation-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select AADT Derivation-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => 'AADT synchronized with other stations on the segment',
                        'label' => 'AADT synchronized with other stations on the segment',
                    ],
                    2 => [
                        'value' => 'Actual',
                        'label' => 'Actual',
                    ],
                    3 => [
                        'value' => 'Calculated from Partial Counts',
                        'label' => 'Calculated from Partial Counts',
                    ],
                    4 => [
                        'value' => 'Combined from child AADT\'s',
                        'label' => 'Combined from child AADT\'s',
                    ],
                    5 => [
                        'value' => 'Doubled from single direction',
                        'label' => 'Doubled from single direction',
                    ],
                    6 => [
                        'value' => 'Estimate',
                        'label' => 'Estimate',
                    ],
                    7 => [
                        'value' => 'Grown',
                        'label' => 'Grown',
                    ],
                    8 => [
                        'value' => 'Grown from Prior Year HPMS Network',
                        'label' => 'Grown from Prior Year HPMS Network',
                    ],
                    9 => [
                        'value' => 'Modified by Ramp Balancing',
                        'label' => 'Modified by Ramp Balancing',
                    ],
                    10 => [
                        'value' => 'Pulled back from HPMS network estimation routine',
                        'label' => 'Pulled back from HPMS network estimation routine',
                    ],
                    11 => [
                        'value' => 'Unknown Source',
                        'label' => 'Unknown Source',
                    ],
                ],
            ],
            108 => [
                'name' => 'statn_num_min',
                'label' => 'AADT Station Number-linked RD Min',
                'type' => 'number',
                'placeholder' => 'Min value for AADT Station Number-linked RD',
            ],
            109 => [
                'name' => 'statn_num_max',
                'label' => 'AADT Station Number-linked RD Max',
                'type' => 'number',
                'placeholder' => 'Max value for AADT Station Number-linked RD',
            ],
            110 => [
                'name' => 'op_dir_sl',
                'label' => 'Opposing Direction Speed Limit-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Opposing Direction Speed Limit-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    2 => [
                        'value' => '20',
                        'label' => '20',
                    ],
                    3 => [
                        'value' => '25',
                        'label' => '25',
                    ],
                    4 => [
                        'value' => '30',
                        'label' => '30',
                    ],
                    5 => [
                        'value' => '35',
                        'label' => '35',
                    ],
                    6 => [
                        'value' => '40',
                        'label' => '40',
                    ],
                    7 => [
                        'value' => '45',
                        'label' => '45',
                    ],
                    8 => [
                        'value' => '50',
                        'label' => '50',
                    ],
                    9 => [
                        'value' => '55',
                        'label' => '55',
                    ],
                    10 => [
                        'value' => '65',
                        'label' => '65',
                    ],
                    11 => [
                        'value' => '99',
                        'label' => '99',
                    ],
                ],
            ],
            111 => [
                'name' => 'shldr_ul_t',
                'label' => 'Undivided Left Shoulder Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Undivided Left Shoulder Type-linked RD',
                'options' => [
                    0 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    1 => [
                        'value' => 'None or Inadequate',
                        'label' => 'None or Inadequate',
                    ],
                    2 => [
                        'value' => 'Stabilized shoulder exists',
                        'label' => 'Stabilized shoulder exists',
                    ],
                    3 => [
                        'value' => 'Surfaced shoulder exists – bituminous concrete (AC)',
                        'label' => 'Surfaced shoulder exists – bituminous concrete (AC)',
                    ],
                    4 => [
                        'value' => 'Surfaced shoulder exists – Portland Cement Concrete surface (PCC',
                        'label' => 'Surfaced shoulder exists – Portland Cement Concrete surface (PCC',
                    ],
                ],
            ],
            112 => [
                'name' => 'shldr_ul_w',
                'label' => 'Undivided Left Shoulder Width-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Undivided Left Shoulder Width-linked RD',
                'options' => [
                    0 => [
                        'value' => '0.0',
                        'label' => '0.0',
                    ],
                    1 => [
                        'value' => '1.0',
                        'label' => '1.0',
                    ],
                    2 => [
                        'value' => '2.0',
                        'label' => '2.0',
                    ],
                    3 => [
                        'value' => '3.0',
                        'label' => '3.0',
                    ],
                    4 => [
                        'value' => '4.0',
                        'label' => '4.0',
                    ],
                    5 => [
                        'value' => '5.0',
                        'label' => '5.0',
                    ],
                    6 => [
                        'value' => '6.0',
                        'label' => '6.0',
                    ],
                    7 => [
                        'value' => '8.0',
                        'label' => '8.0',
                    ],
                    8 => [
                        'value' => '10.0',
                        'label' => '10.0',
                    ],
                    9 => [
                        'value' => '12.0',
                        'label' => '12.0',
                    ],
                ],
            ],
            113 => [
                'name' => 't_exc_type',
                'label' => 'Truck Exclusion Type-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Truck Exclusion Type-linked RD',
                'options' => [
                    0 => [
                        'value' => 'All vehicles over 10 tons excluded',
                        'label' => 'All vehicles over 10 tons excluded',
                    ],
                    1 => [
                        'value' => 'All vehicles over 2.5 tons excluded',
                        'label' => 'All vehicles over 2.5 tons excluded',
                    ],
                    2 => [
                        'value' => 'All vehicles over 20 tons excluded',
                        'label' => 'All vehicles over 20 tons excluded',
                    ],
                    3 => [
                        'value' => 'All vehicles over 2000 pounds excluded',
                        'label' => 'All vehicles over 2000 pounds excluded',
                    ],
                    4 => [
                        'value' => 'All vehicles over 28 feet in length excluded',
                        'label' => 'All vehicles over 28 feet in length excluded',
                    ],
                    5 => [
                        'value' => 'All vehicles over 3 tons excluded',
                        'label' => 'All vehicles over 3 tons excluded',
                    ],
                    6 => [
                        'value' => 'All vehicles over 5 tons excluded',
                        'label' => 'All vehicles over 5 tons excluded',
                    ],
                    7 => [
                        'value' => 'Cambridge Overnight Exclusions',
                        'label' => 'Cambridge Overnight Exclusions',
                    ],
                    8 => [
                        'value' => 'Commercial vehicles over 5 tons carry capacity excluded',
                        'label' => 'Commercial vehicles over 5 tons carry capacity excluded',
                    ],
                    9 => [
                        'value' => 'Hazardous Truck Route',
                        'label' => 'Hazardous Truck Route',
                    ],
                ],
            ],
            114 => [
                'name' => 't_exc_time',
                'label' => 'Truck Exclusion Time-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Truck Exclusion Time-linked RD',
                'options' => [
                    0 => [
                        'value' => '10PM to 6AM, 7 Days',
                        'label' => '10PM to 6AM, 7 Days',
                    ],
                    1 => [
                        'value' => '11PM to 6AM, 7 Days',
                        'label' => '11PM to 6AM, 7 Days',
                    ],
                    2 => [
                        'value' => '11PM to 6AM, NB Only, 7 Days',
                        'label' => '11PM to 6AM, NB Only, 7 Days',
                    ],
                    3 => [
                        'value' => '11PM to 7AM, 7 Days',
                        'label' => '11PM to 7AM, 7 Days',
                    ],
                    4 => [
                        'value' => '24 Hours, 7 Days',
                        'label' => '24 Hours, 7 Days',
                    ],
                    5 => [
                        'value' => '4PM to 6PM',
                        'label' => '4PM to 6PM',
                    ],
                    6 => [
                        'value' => '5AM to 8PM, 7 Days',
                        'label' => '5AM to 8PM, 7 Days',
                    ],
                    7 => [
                        'value' => '6AM to 10PM, 7 Days',
                        'label' => '6AM to 10PM, 7 Days',
                    ],
                    8 => [
                        'value' => '6AM to 6PM, 7 Days',
                        'label' => '6AM to 6PM, 7 Days',
                    ],
                    9 => [
                        'value' => '6AM to 7PM, 7 Days',
                        'label' => '6AM to 7PM, 7 Days',
                    ],
                    10 => [
                        'value' => '6PM to 6AM, 7 Days',
                        'label' => '6PM to 6AM, 7 Days',
                    ],
                    11 => [
                        'value' => '7AM to 11PM, 7 Days',
                        'label' => '7AM to 11PM, 7 Days',
                    ],
                    12 => [
                        'value' => '7AM to 6PM, 7 Days',
                        'label' => '7AM to 6PM, 7 Days',
                    ],
                    13 => [
                        'value' => '7PM to 7AM, 7 Days',
                        'label' => '7PM to 7AM, 7 Days',
                    ],
                    14 => [
                        'value' => '8AM to 930AM and 2PM to 330PM, School Days Only',
                        'label' => '8AM to 930AM and 2PM to 330PM, School Days Only',
                    ],
                    15 => [
                        'value' => '8PM to 6AM, 7 Days',
                        'label' => '8PM to 6AM, 7 Days',
                    ],
                    16 => [
                        'value' => '8PM to 7AM, 7 Days',
                        'label' => '8PM to 7AM, 7 Days',
                    ],
                    17 => [
                        'value' => '9PM to 6AM, 7 Days',
                        'label' => '9PM to 6AM, 7 Days',
                    ],
                    18 => [
                        'value' => '9PM to 7AM, 7 Days',
                        'label' => '9PM to 7AM, 7 Days',
                    ],
                    19 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                ],
            ],
            115 => [
                'name' => 'f_f_class',
                'label' => 'Federal Functional Classification-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Federal Functional Classification-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Interstate',
                        'label' => 'Interstate',
                    ],
                    1 => [
                        'value' => 'Local',
                        'label' => 'Local',
                    ],
                    2 => [
                        'value' => 'Major Collector',
                        'label' => 'Major Collector',
                    ],
                    3 => [
                        'value' => 'Minor Arterial',
                        'label' => 'Minor Arterial',
                    ],
                    4 => [
                        'value' => 'Minor Collector',
                        'label' => 'Minor Collector',
                    ],
                    5 => [
                        'value' => 'Principal Arterial - Other',
                        'label' => 'Principal Arterial - Other',
                    ],
                    6 => [
                        'value' => 'Principal Arterial - Other Freeways or Expressways',
                        'label' => 'Principal Arterial - Other Freeways or Expressways',
                    ],
                ],
            ],
            116 => [
                'name' => 'vehc_unit_numb',
                'label' => 'Vehicle Unit Number',
                'type' => 'multiselect',
                'placeholder' => 'Select Vehicle Unit Number',
                'options' => [
                    0 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    1 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    2 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    3 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    4 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    5 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                    6 => [
                        'value' => '7',
                        'label' => '7',
                    ],
                    7 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    8 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                    9 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    10 => [
                        'value' => '11',
                        'label' => '11',
                    ],
                    11 => [
                        'value' => '12',
                        'label' => '12',
                    ],
                    12 => [
                        'value' => '13',
                        'label' => '13',
                    ],
                    13 => [
                        'value' => '14',
                        'label' => '14',
                    ],
                ],
            ],
            117 => [
                'name' => 'alc_suspd_type_descr',
                'label' => 'Alcohol Suspected',
                'type' => 'boolean',
            ],
            118 => [
                'name' => 'driver_age_min',
                'label' => 'Driver Age Min',
                'type' => 'number',
                'placeholder' => 'Min value for Driver Age',
            ],
            119 => [
                'name' => 'driver_age_max',
                'label' => 'Driver Age Max',
                'type' => 'number',
                'placeholder' => 'Max value for Driver Age',
            ],
            120 => [
                'name' => 'drvr_cntrb_circ_descr',
                'label' => 'Driver Contributing Circ.',
                'type' => 'text',
                'placeholder' => 'Enter Driver Contributing Circ.',
            ],
            121 => [
                'name' => 'driver_distracted_type_descr',
                'label' => 'Driver Distracted',
                'type' => 'multiselect',
                'placeholder' => 'Select Driver Distracted',
                'options' => [
                    0 => [
                        'value' => 'External distraction (outside the vehicle)',
                        'label' => 'External distraction (outside the vehicle)',
                    ],
                    1 => [
                        'value' => 'Manually operating an electronic device',
                        'label' => 'Manually operating an electronic device',
                    ],
                    2 => [
                        'value' => 'Not Distracted',
                        'label' => 'Not Distracted',
                    ],
                    3 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    4 => [
                        'value' => 'Other activity (searching, eating, personal hygiene, etc.)',
                        'label' => 'Other activity (searching, eating, personal hygiene, etc.)',
                    ],
                    5 => [
                        'value' => 'Other activity, electronic device',
                        'label' => 'Other activity, electronic device',
                    ],
                    6 => [
                        'value' => 'Passenger',
                        'label' => 'Passenger',
                    ],
                    7 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    8 => [
                        'value' => 'Talking on hand-held electronic device',
                        'label' => 'Talking on hand-held electronic device',
                    ],
                    9 => [
                        'value' => 'Talking on hands-free electronic device',
                        'label' => 'Talking on hands-free electronic device',
                    ],
                    10 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            122 => [
                'name' => 'drvr_lcn_state',
                'label' => 'Driver License State',
                'type' => 'text',
                'placeholder' => 'Enter Driver License State',
            ],
            123 => [
                'name' => 'drug_suspd_type_descr',
                'label' => 'Drugs Suspected',
                'type' => 'boolean',
            ],
            124 => [
                'name' => 'emergency_use_desc',
                'label' => 'Emergency Use',
                'type' => 'boolean',
            ],
            125 => [
                'name' => 'fmsca_rptbl_vl',
                'label' => 'FMCSA Reportable (Vehicle)',
                'type' => 'boolean',
            ],
            126 => [
                'name' => 'haz_mat_placard_descr',
                'label' => 'Hazmat Placard',
                'type' => 'boolean',
            ],
            127 => [
                'name' => 'max_injr_svrty_vl',
                'label' => 'Maximum Injury Severity in Vehicle',
                'type' => 'multiselect',
                'placeholder' => 'Select Maximum Injury Severity in Vehicle',
                'options' => [
                    0 => [
                        'value' => 'Deceased not caused by crash',
                        'label' => 'Deceased not caused by crash',
                    ],
                    1 => [
                        'value' => 'Fatal injury (K)',
                        'label' => 'Fatal injury (K)',
                    ],
                    2 => [
                        'value' => 'No Apparent Injury (O)',
                        'label' => 'No Apparent Injury (O)',
                    ],
                    3 => [
                        'value' => 'No injury',
                        'label' => 'No injury',
                    ],
                    4 => [
                        'value' => 'Not Applicable',
                        'label' => 'Not Applicable',
                    ],
                    5 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    6 => [
                        'value' => 'Possible Injury (C)',
                        'label' => 'Possible Injury (C)',
                    ],
                    7 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    8 => [
                        'value' => 'Suspected Minor Injury (B)',
                        'label' => 'Suspected Minor Injury (B)',
                    ],
                    9 => [
                        'value' => 'Suspected Serious Injury (A)',
                        'label' => 'Suspected Serious Injury (A)',
                    ],
                    10 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            128 => [
                'name' => 'most_hrmf_event',
                'label' => 'Most Harmful Event (Vehicle)',
                'type' => 'multiselect',
                'placeholder' => 'Select Most Harmful Event (Vehicle)',
                'options' => [
                    0 => [
                        'value' => 'Cargo/equipment loss or shift',
                        'label' => 'Cargo/equipment loss or shift',
                    ],
                    1 => [
                        'value' => 'Collision with animal - deer',
                        'label' => 'Collision with animal - deer',
                    ],
                    2 => [
                        'value' => 'Collision with animal - other',
                        'label' => 'Collision with animal - other',
                    ],
                    3 => [
                        'value' => 'Collision with bridge',
                        'label' => 'Collision with bridge',
                    ],
                    4 => [
                        'value' => 'Collision with bridge overhead structure',
                        'label' => 'Collision with bridge overhead structure',
                    ],
                    5 => [
                        'value' => 'Collision with curb',
                        'label' => 'Collision with curb',
                    ],
                    6 => [
                        'value' => 'Collision with cyclist (bicycle, tricycle, unicycle, pedal car)',
                        'label' => 'Collision with cyclist (bicycle, tricycle, unicycle, pedal car)',
                    ],
                    7 => [
                        'value' => 'Collision with ditch',
                        'label' => 'Collision with ditch',
                    ],
                    8 => [
                        'value' => 'Collision with embankment',
                        'label' => 'Collision with embankment',
                    ],
                    9 => [
                        'value' => 'Collision with fence',
                        'label' => 'Collision with fence',
                    ],
                    10 => [
                        'value' => 'Collision with guardrail',
                        'label' => 'Collision with guardrail',
                    ],
                    11 => [
                        'value' => 'Collision with highway traffic sign post',
                        'label' => 'Collision with highway traffic sign post',
                    ],
                    12 => [
                        'value' => 'Collision with impact attenuator/crash cushion',
                        'label' => 'Collision with impact attenuator/crash cushion',
                    ],
                    13 => [
                        'value' => 'Collision with light pole or other post/support',
                        'label' => 'Collision with light pole or other post/support',
                    ],
                    14 => [
                        'value' => 'Collision with mail box',
                        'label' => 'Collision with mail box',
                    ],
                    15 => [
                        'value' => 'Collision with median barrier',
                        'label' => 'Collision with median barrier',
                    ],
                    16 => [
                        'value' => 'Collision with moped',
                        'label' => 'Collision with moped',
                    ],
                    17 => [
                        'value' => 'Collision with motor vehicle in traffic',
                        'label' => 'Collision with motor vehicle in traffic',
                    ],
                    18 => [
                        'value' => 'Collision with other fixed object (wall, building, tunnel, etc.)',
                        'label' => 'Collision with other fixed object (wall, building, tunnel, etc.)',
                    ],
                    19 => [
                        'value' => 'Collision with other movable object',
                        'label' => 'Collision with other movable object',
                    ],
                    20 => [
                        'value' => 'Collision with Other Vulnerable Users',
                        'label' => 'Collision with Other Vulnerable Users',
                    ],
                    21 => [
                        'value' => 'Collision with overhead sign support',
                        'label' => 'Collision with overhead sign support',
                    ],
                    22 => [
                        'value' => 'Collision with parked motor vehicle',
                        'label' => 'Collision with parked motor vehicle',
                    ],
                    23 => [
                        'value' => 'Collision with pedestrian',
                        'label' => 'Collision with pedestrian',
                    ],
                    24 => [
                        'value' => 'Collision with railway vehicle (e.g., train, engine)',
                        'label' => 'Collision with railway vehicle (e.g., train, engine)',
                    ],
                    25 => [
                        'value' => 'Collision with tree',
                        'label' => 'Collision with tree',
                    ],
                    26 => [
                        'value' => 'Collision with unknown fixed object',
                        'label' => 'Collision with unknown fixed object',
                    ],
                    27 => [
                        'value' => 'Collision with unknown movable object',
                        'label' => 'Collision with unknown movable object',
                    ],
                    28 => [
                        'value' => 'Collision with utility pole',
                        'label' => 'Collision with utility pole',
                    ],
                    29 => [
                        'value' => 'Collision with work zone maintenance equipment',
                        'label' => 'Collision with work zone maintenance equipment',
                    ],
                    30 => [
                        'value' => 'Fire/explosion',
                        'label' => 'Fire/explosion',
                    ],
                    31 => [
                        'value' => 'Immersion',
                        'label' => 'Immersion',
                    ],
                    32 => [
                        'value' => 'Invalid Code Specified',
                        'label' => 'Invalid Code Specified',
                    ],
                    33 => [
                        'value' => 'Jackknife',
                        'label' => 'Jackknife',
                    ],
                    34 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    35 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    36 => [
                        'value' => 'Other non-collision',
                        'label' => 'Other non-collision',
                    ],
                    37 => [
                        'value' => 'Overturn/rollover',
                        'label' => 'Overturn/rollover',
                    ],
                    38 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    39 => [
                        'value' => 'Unknown non-collision',
                        'label' => 'Unknown non-collision',
                    ],
                ],
            ],
            129 => [
                'name' => 'total_occpt_in_vehc_min',
                'label' => 'Total Occupants in Vehicle Min',
                'type' => 'number',
                'placeholder' => 'Min value for Total Occupants in Vehicle',
            ],
            130 => [
                'name' => 'total_occpt_in_vehc_max',
                'label' => 'Total Occupants in Vehicle Max',
                'type' => 'number',
                'placeholder' => 'Max value for Total Occupants in Vehicle',
            ],
            131 => [
                'name' => 'vehc_manr_act_descr',
                'label' => 'Vehicle Action Prior to Crash',
                'type' => 'multiselect',
                'placeholder' => 'Select Vehicle Action Prior to Crash',
                'options' => [
                    0 => [
                        'value' => 'Backing',
                        'label' => 'Backing',
                    ],
                    1 => [
                        'value' => 'Changing lanes',
                        'label' => 'Changing lanes',
                    ],
                    2 => [
                        'value' => 'Entering traffic lane',
                        'label' => 'Entering traffic lane',
                    ],
                    3 => [
                        'value' => 'Leaving traffic lane',
                        'label' => 'Leaving traffic lane',
                    ],
                    4 => [
                        'value' => 'Making U-turn',
                        'label' => 'Making U-turn',
                    ],
                    5 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    6 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    7 => [
                        'value' => 'Overtaking/passing',
                        'label' => 'Overtaking/passing',
                    ],
                    8 => [
                        'value' => 'Parked',
                        'label' => 'Parked',
                    ],
                    9 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    10 => [
                        'value' => 'Slowing or stopped in traffic',
                        'label' => 'Slowing or stopped in traffic',
                    ],
                    11 => [
                        'value' => 'Travelling straight ahead',
                        'label' => 'Travelling straight ahead',
                    ],
                    12 => [
                        'value' => 'Turning left',
                        'label' => 'Turning left',
                    ],
                    13 => [
                        'value' => 'Turning right',
                        'label' => 'Turning right',
                    ],
                    14 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            132 => [
                'name' => 'vehc_confg_descr',
                'label' => 'Vehicle Configuration',
                'type' => 'multiselect',
                'placeholder' => 'Select Vehicle Configuration',
                'options' => [
                    0 => [
                        'value' => 'All Terrain Vehicle (ATV)',
                        'label' => 'All Terrain Vehicle (ATV)',
                    ],
                    1 => [
                        'value' => 'Bus (seats for 16 or more, including driver)',
                        'label' => 'Bus (seats for 16 or more, including driver)',
                    ],
                    2 => [
                        'value' => 'Bus (seats for 9-15 people, including driver)',
                        'label' => 'Bus (seats for 9-15 people, including driver)',
                    ],
                    3 => [
                        'value' => 'Light truck(van, mini-van, pickup, sport utility)',
                        'label' => 'Light truck(van, mini-van, pickup, sport utility)',
                    ],
                    4 => [
                        'value' => 'Low Speed Vehicle',
                        'label' => 'Low Speed Vehicle',
                    ],
                    5 => [
                        'value' => 'MOPED',
                        'label' => 'MOPED',
                    ],
                    6 => [
                        'value' => 'Motor home/recreational vehicle',
                        'label' => 'Motor home/recreational vehicle',
                    ],
                    7 => [
                        'value' => 'Motorcycle',
                        'label' => 'Motorcycle',
                    ],
                    8 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    9 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    10 => [
                        'value' => 'Passenger car',
                        'label' => 'Passenger car',
                    ],
                    11 => [
                        'value' => 'Registered farm equipment',
                        'label' => 'Registered farm equipment',
                    ],
                    12 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    13 => [
                        'value' => 'Single-unit truck (2-axle, 6-tires)',
                        'label' => 'Single-unit truck (2-axle, 6-tires)',
                    ],
                    14 => [
                        'value' => 'Single-unit truck (3-or-more axles)',
                        'label' => 'Single-unit truck (3-or-more axles)',
                    ],
                    15 => [
                        'value' => 'Snowmobile',
                        'label' => 'Snowmobile',
                    ],
                    16 => [
                        'value' => 'Tractor/doubles',
                        'label' => 'Tractor/doubles',
                    ],
                    17 => [
                        'value' => 'Tractor/semi-trailer',
                        'label' => 'Tractor/semi-trailer',
                    ],
                    18 => [
                        'value' => 'Tractor/triples',
                        'label' => 'Tractor/triples',
                    ],
                    19 => [
                        'value' => 'Truck tractor (bobtail)',
                        'label' => 'Truck tractor (bobtail)',
                    ],
                    20 => [
                        'value' => 'Truck/trailer',
                        'label' => 'Truck/trailer',
                    ],
                    21 => [
                        'value' => 'Unknown heavy truck, cannot classify',
                        'label' => 'Unknown heavy truck, cannot classify',
                    ],
                    22 => [
                        'value' => 'Unknown vehicle configuration',
                        'label' => 'Unknown vehicle configuration',
                    ],
                ],
            ],
            133 => [
                'name' => 'vehc_most_dmgd_area',
                'label' => 'Vehicle Most Damaged Area',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Most Damaged Area',
            ],
            134 => [
                'name' => 'owner_addr_city_town',
                'label' => 'Vehicle Owner City Town',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Owner City Town',
            ],
            135 => [
                'name' => 'owner_addr_state',
                'label' => 'Vehicle Owner State',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Owner State',
            ],
            136 => [
                'name' => 'vehc_reg_state',
                'label' => 'Vehicle Registration State',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Registration State',
            ],
            137 => [
                'name' => 'vehc_reg_type_code',
                'label' => 'Vehicle Registration Type',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Registration Type',
            ],
            138 => [
                'name' => 'vehc_seq_events',
                'label' => 'Vehicle Sequence of Events',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Sequence of Events',
            ],
            139 => [
                'name' => 'vehc_towed_from_scene',
                'label' => 'Vehicle Towed From Scene',
                'type' => 'multiselect',
                'placeholder' => 'Select Vehicle Towed From Scene',
                'options' => [
                    0 => [
                        'value' => 'Invalid',
                        'label' => 'Invalid',
                    ],
                    1 => [
                        'value' => 'No',
                        'label' => 'No',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    4 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    5 => [
                        'value' => 'Yes, other reason not disabled',
                        'label' => 'Yes, other reason not disabled',
                    ],
                    6 => [
                        'value' => 'Yes, vehicle or trailer disabled',
                        'label' => 'Yes, vehicle or trailer disabled',
                    ],
                ],
            ],
            140 => [
                'name' => 'trvl_dirc_descr',
                'label' => 'Travel Direction',
                'type' => 'multiselect',
                'placeholder' => 'Select Travel Direction',
                'options' => [
                    0 => [
                        'value' => 'Eastbound',
                        'label' => 'Eastbound',
                    ],
                    1 => [
                        'value' => 'Northbound',
                        'label' => 'Northbound',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    4 => [
                        'value' => 'Southbound',
                        'label' => 'Southbound',
                    ],
                    5 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    6 => [
                        'value' => 'Westbound',
                        'label' => 'Westbound',
                    ],
                ],
            ],
            141 => [
                'name' => 'vehicle_make_descr',
                'label' => 'Vehicle Make',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Make',
            ],
            142 => [
                'name' => 'vehicle_model_descr',
                'label' => 'Vehicle Model',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Model',
            ],
            143 => [
                'name' => 'vehicle_vin',
                'label' => 'VIN',
                'type' => 'text',
                'placeholder' => 'Enter VIN',
            ],
            144 => [
                'name' => 'driver_violation_cl',
                'label' => 'Driver Violation (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Driver Violation (All Vehicles)',
            ],
            145 => [
                'name' => 'pers_numb_min',
                'label' => 'Person Number Min',
                'type' => 'number',
                'placeholder' => 'Min value for Person Number',
            ],
            146 => [
                'name' => 'pers_numb_max',
                'label' => 'Person Number Max',
                'type' => 'number',
                'placeholder' => 'Max value for Person Number',
            ],
            147 => [
                'name' => 'age_min',
                'label' => 'Age Min',
                'type' => 'number',
                'placeholder' => 'Min value for Age',
            ],
            148 => [
                'name' => 'age_max',
                'label' => 'Age Max',
                'type' => 'number',
                'placeholder' => 'Max value for Age',
            ],
            149 => [
                'name' => 'ejctn_descr',
                'label' => 'Ejection Description',
                'type' => 'multiselect',
                'placeholder' => 'Select Ejection Description',
                'options' => [
                    0 => [
                        'value' => 'Not applicable',
                        'label' => 'Not applicable',
                    ],
                    1 => [
                        'value' => 'Not ejected',
                        'label' => 'Not ejected',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Partially ejected',
                        'label' => 'Partially ejected',
                    ],
                    4 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    5 => [
                        'value' => 'Totally ejected',
                        'label' => 'Totally ejected',
                    ],
                    6 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            150 => [
                'name' => 'injy_stat_descr',
                'label' => 'Injury Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Injury Type',
                'options' => [
                    0 => [
                        'value' => 'Deceased not caused by crash',
                        'label' => 'Deceased not caused by crash',
                    ],
                    1 => [
                        'value' => 'Fatal injury (K)',
                        'label' => 'Fatal injury (K)',
                    ],
                    2 => [
                        'value' => 'No Apparent Injury (O)',
                        'label' => 'No Apparent Injury (O)',
                    ],
                    3 => [
                        'value' => 'No injury',
                        'label' => 'No injury',
                    ],
                    4 => [
                        'value' => 'Non-fatal injury - Possible',
                        'label' => 'Non-fatal injury - Possible',
                    ],
                    5 => [
                        'value' => 'Not Applicable',
                        'label' => 'Not Applicable',
                    ],
                    6 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    7 => [
                        'value' => 'Possible Injury (C)',
                        'label' => 'Possible Injury (C)',
                    ],
                    8 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    9 => [
                        'value' => 'Suspected Minor Injury (B)',
                        'label' => 'Suspected Minor Injury (B)',
                    ],
                    10 => [
                        'value' => 'Suspected Serious Injury (A)',
                        'label' => 'Suspected Serious Injury (A)',
                    ],
                    11 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            151 => [
                'name' => 'med_facly',
                'label' => 'Medical Facility',
                'type' => 'text',
                'placeholder' => 'Enter Medical Facility',
            ],
            152 => [
                'name' => 'pers_addr_city',
                'label' => 'Person Address City',
                'type' => 'text',
                'placeholder' => 'Enter Person Address City',
            ],
            153 => [
                'name' => 'state_prvn_code',
                'label' => 'Person Address State',
                'type' => 'text',
                'placeholder' => 'Enter Person Address State',
            ],
            154 => [
                'name' => 'pers_type',
                'label' => 'Person Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Person Type',
                'options' => [
                    0 => [
                        'value' => 'Driver',
                        'label' => 'Driver',
                    ],
                    1 => [
                        'value' => 'Passenger',
                        'label' => 'Passenger',
                    ],
                    2 => [
                        'value' => 'Vulnerable User',
                        'label' => 'Vulnerable User',
                    ],
                ],
            ],
            155 => [
                'name' => 'prtc_sys_use_descr',
                'label' => 'Protective System Used',
                'type' => 'multiselect',
                'placeholder' => 'Select Protective System Used',
                'options' => [
                    0 => [
                        'value' => 'Child safety seat used',
                        'label' => 'Child safety seat used',
                    ],
                    1 => [
                        'value' => 'Helmet used',
                        'label' => 'Helmet used',
                    ],
                    2 => [
                        'value' => 'Lap belt only used',
                        'label' => 'Lap belt only used',
                    ],
                    3 => [
                        'value' => 'None used - vehicle occupant',
                        'label' => 'None used - vehicle occupant',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    6 => [
                        'value' => 'Shoulder and lap belt used',
                        'label' => 'Shoulder and lap belt used',
                    ],
                    7 => [
                        'value' => 'Shoulder belt only used',
                        'label' => 'Shoulder belt only used',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            156 => [
                'name' => 'sfty_equp_desc_1',
                'label' => 'Safety Equipment 1',
                'type' => 'multiselect',
                'placeholder' => 'Select Safety Equipment 1',
                'options' => [
                    0 => [
                        'value' => 'Helmet used',
                        'label' => 'Helmet used',
                    ],
                    1 => [
                        'value' => 'Lighting',
                        'label' => 'Lighting',
                    ],
                    2 => [
                        'value' => 'None used',
                        'label' => 'None used',
                    ],
                    3 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    4 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    5 => [
                        'value' => 'Protective pads used (elbows, knees, shins, etc.)',
                        'label' => 'Protective pads used (elbows, knees, shins, etc.)',
                    ],
                    6 => [
                        'value' => 'Reflective clothing',
                        'label' => 'Reflective clothing',
                    ],
                    7 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            157 => [
                'name' => 'sfty_equp_desc_2',
                'label' => 'Safety Equipment 2',
                'type' => 'text',
                'placeholder' => 'Enter Safety Equipment 2',
            ],
            158 => [
                'name' => 'sex_descr',
                'label' => 'Sex',
                'type' => 'multiselect',
                'placeholder' => 'Select Sex',
                'options' => [
                    0 => [
                        'value' => 'F - Female',
                        'label' => 'F - Female',
                    ],
                    1 => [
                        'value' => 'M - Male',
                        'label' => 'M - Male',
                    ],
                    2 => [
                        'value' => 'N/A',
                        'label' => 'N/A',
                    ],
                    3 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    4 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    5 => [
                        'value' => 'U - Unknown',
                        'label' => 'U - Unknown',
                    ],
                    6 => [
                        'value' => 'X - Non-Binary',
                        'label' => 'X - Non-Binary',
                    ],
                ],
            ],
            159 => [
                'name' => 'trnsd_by_descr',
                'label' => 'Transported By',
                'type' => 'multiselect',
                'placeholder' => 'Select Transported By',
                'options' => [
                    0 => [
                        'value' => 'EMS Ground',
                        'label' => 'EMS Ground',
                    ],
                    1 => [
                        'value' => 'EMS(Emergency Medical Service)',
                        'label' => 'EMS(Emergency Medical Service)',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Not transported',
                        'label' => 'Not transported',
                    ],
                    4 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    5 => [
                        'value' => 'Police',
                        'label' => 'Police',
                    ],
                    6 => [
                        'value' => 'Refused Transport',
                        'label' => 'Refused Transport',
                    ],
                    7 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            160 => [
                'name' => 'non_mtrst_type_cl',
                'label' => 'Vulnerable User Type (All Persons)',
                'type' => 'text',
                'placeholder' => 'Enter Vulnerable User Type (All Persons)',
            ],
            161 => [
                'name' => 'non_mtrst_actn_cl',
                'label' => 'Vulnerable User Action (All Persons)',
                'type' => 'text',
                'placeholder' => 'Enter Vulnerable User Action (All Persons)',
            ],
            162 => [
                'name' => 'non_mtrst_loc_cl',
                'label' => 'Vulnerable User Location (All Persons)',
                'type' => 'text',
                'placeholder' => 'Enter Vulnerable User Location (All Persons)',
            ],
            163 => [
                'name' => 'non_mtrst_act_descr',
                'label' => 'Vulnerable User Action',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Action',
                'options' => [
                    0 => [
                        'value' => 'Approaching or leaving vehicle',
                        'label' => 'Approaching or leaving vehicle',
                    ],
                    1 => [
                        'value' => 'Entering or crossing location',
                        'label' => 'Entering or crossing location',
                    ],
                    2 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    3 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    4 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    5 => [
                        'value' => 'Pushing vehicle',
                        'label' => 'Pushing vehicle',
                    ],
                    6 => [
                        'value' => 'Standing',
                        'label' => 'Standing',
                    ],
                    7 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    8 => [
                        'value' => 'Walking, running or cycling',
                        'label' => 'Walking, running or cycling',
                    ],
                    9 => [
                        'value' => 'Working',
                        'label' => 'Working',
                    ],
                    10 => [
                        'value' => 'Working on vehicle',
                        'label' => 'Working on vehicle',
                    ],
                ],
            ],
            164 => [
                'name' => 'non_mtrst_cond_descr',
                'label' => 'Vulnerable User Condition',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Condition',
                'options' => [
                    0 => [
                        'value' => 'Apparently normal',
                        'label' => 'Apparently normal',
                    ],
                    1 => [
                        'value' => 'Emotional (e.g., depression, angry, disturbed)',
                        'label' => 'Emotional (e.g., depression, angry, disturbed)',
                    ],
                    2 => [
                        'value' => 'Fell asleep, fainted, fatigue, etc.',
                        'label' => 'Fell asleep, fainted, fatigue, etc.',
                    ],
                    3 => [
                        'value' => 'Illness',
                        'label' => 'Illness',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    6 => [
                        'value' => 'Physical impairment',
                        'label' => 'Physical impairment',
                    ],
                    7 => [
                        'value' => 'Under the influence of medications/drugs/alcohol',
                        'label' => 'Under the influence of medications/drugs/alcohol',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            165 => [
                'name' => 'non_mtrst_loc_descr',
                'label' => 'Vulnerable User Location',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Location',
                'options' => [
                    0 => [
                        'value' => 'At intersection but no crosswalk',
                        'label' => 'At intersection but no crosswalk',
                    ],
                    1 => [
                        'value' => 'In roadway',
                        'label' => 'In roadway',
                    ],
                    2 => [
                        'value' => 'Island',
                        'label' => 'Island',
                    ],
                    3 => [
                        'value' => 'Marked crosswalk at intersection (includes use of paint raised or other roadway material)',
                        'label' => 'Marked crosswalk at intersection (includes use of paint raised or other roadway material)',
                    ],
                    4 => [
                        'value' => 'Median (but not on shoulder)',
                        'label' => 'Median (but not on shoulder)',
                    ],
                    5 => [
                        'value' => 'Non-intersection crosswalk',
                        'label' => 'Non-intersection crosswalk',
                    ],
                    6 => [
                        'value' => 'Not in roadway',
                        'label' => 'Not in roadway',
                    ],
                    7 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    8 => [
                        'value' => 'On-Street Bike Lanes',
                        'label' => 'On-Street Bike Lanes',
                    ],
                    9 => [
                        'value' => 'On-Street Buffered Bike Lanes',
                        'label' => 'On-Street Buffered Bike Lanes',
                    ],
                    10 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    11 => [
                        'value' => 'Raised Crosswalk',
                        'label' => 'Raised Crosswalk',
                    ],
                    12 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    13 => [
                        'value' => 'Separated Bike Lanes',
                        'label' => 'Separated Bike Lanes',
                    ],
                    14 => [
                        'value' => 'Shared-use path or trails Crossing',
                        'label' => 'Shared-use path or trails Crossing',
                    ],
                    15 => [
                        'value' => 'Shoulder',
                        'label' => 'Shoulder',
                    ],
                    16 => [
                        'value' => 'Sidewalk',
                        'label' => 'Sidewalk',
                    ],
                    17 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            166 => [
                'name' => 'non_mtrst_type_descr',
                'label' => 'Vulnerable User Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Type',
                'options' => [
                    0 => [
                        'value' => 'Bicyclist',
                        'label' => 'Bicyclist',
                    ],
                    1 => [
                        'value' => 'Electric Personal Assistive Mobility Device User',
                        'label' => 'Electric Personal Assistive Mobility Device User',
                    ],
                    2 => [
                        'value' => 'Emergency Responder - Outside of vehicle',
                        'label' => 'Emergency Responder - Outside of vehicle',
                    ],
                    3 => [
                        'value' => 'Farm Equipment Operator',
                        'label' => 'Farm Equipment Operator',
                    ],
                    4 => [
                        'value' => 'Hand Cyclist',
                        'label' => 'Hand Cyclist',
                    ],
                    5 => [
                        'value' => 'In-Line Skater',
                        'label' => 'In-Line Skater',
                    ],
                    6 => [
                        'value' => 'Motorized Bicyclist',
                        'label' => 'Motorized Bicyclist',
                    ],
                    7 => [
                        'value' => 'Motorized Scooter Rider',
                        'label' => 'Motorized Scooter Rider',
                    ],
                    8 => [
                        'value' => 'Non-Motorized Scooter Rider',
                        'label' => 'Non-Motorized Scooter Rider',
                    ],
                    9 => [
                        'value' => 'Non-Motorized Wheelchair User',
                        'label' => 'Non-Motorized Wheelchair User',
                    ],
                    10 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    11 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    12 => [
                        'value' => 'Other Micromobility Device User',
                        'label' => 'Other Micromobility Device User',
                    ],
                    13 => [
                        'value' => 'Pedestrian',
                        'label' => 'Pedestrian',
                    ],
                    14 => [
                        'value' => 'Roadway Worker - Outside of vehicle',
                        'label' => 'Roadway Worker - Outside of vehicle',
                    ],
                    15 => [
                        'value' => 'Roller Skater',
                        'label' => 'Roller Skater',
                    ],
                    16 => [
                        'value' => 'Skateboarder',
                        'label' => 'Skateboarder',
                    ],
                    17 => [
                        'value' => 'Train/Trolley passenger',
                        'label' => 'Train/Trolley passenger',
                    ],
                    18 => [
                        'value' => 'Tricyclist',
                        'label' => 'Tricyclist',
                    ],
                    19 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    20 => [
                        'value' => 'Utility Worker – Outside of vehicle',
                        'label' => 'Utility Worker – Outside of vehicle',
                    ],
                ],
            ],
            167 => [
                'name' => 'non_mtrst_origin_dest_cl',
                'label' => 'Vulnerable Users Origin Destination (All Persons)',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable Users Origin Destination (All Persons)',
                'options' => [
                    0 => [
                        'value' => 'VU10: Other',
                        'label' => 'VU10: Other',
                    ],
                    1 => [
                        'value' => 'VU11: Other',
                        'label' => 'VU11: Other',
                    ],
                    2 => [
                        'value' => 'VU17: Other',
                        'label' => 'VU17: Other',
                    ],
                    3 => [
                        'value' => 'VU2: Going to or from a Delivery Vehicle',
                        'label' => 'VU2: Going to or from a Delivery Vehicle',
                    ],
                    4 => [
                        'value' => 'VU2: Going to or from a Delivery Vehicle / VU3: Going to or from a Delivery Vehicle / VU4: Going to or from a Delivery Vehicle',
                        'label' => 'VU2: Going to or from a Delivery Vehicle / VU3: Going to or from a Delivery Vehicle / VU4: Going to or from a Delivery Vehicle',
                    ],
                    5 => [
                        'value' => 'VU2: Going to or from a Mailbox',
                        'label' => 'VU2: Going to or from a Mailbox',
                    ],
                    6 => [
                        'value' => 'VU2: Going to or from a Mailbox / VU3: Going to or from a Mailbox',
                        'label' => 'VU2: Going to or from a Mailbox / VU3: Going to or from a Mailbox',
                    ],
                    7 => [
                        'value' => 'VU2: Going to or from a School Bus or a School Bus Stop',
                        'label' => 'VU2: Going to or from a School Bus or a School Bus Stop',
                    ],
                    8 => [
                        'value' => 'VU2: Going to or from a School Bus or a School Bus Stop / VU3: Going to or from a School Bus or a School Bus Stop',
                        'label' => 'VU2: Going to or from a School Bus or a School Bus Stop / VU3: Going to or from a School Bus or a School Bus Stop',
                    ],
                    9 => [
                        'value' => 'VU2: Going to or from an Ice Cream or Food Truck',
                        'label' => 'VU2: Going to or from an Ice Cream or Food Truck',
                    ],
                    10 => [
                        'value' => 'VU2: Going to or from School (K-12)',
                        'label' => 'VU2: Going to or from School (K-12)',
                    ],
                    11 => [
                        'value' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12)',
                        'label' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12)',
                    ],
                    12 => [
                        'value' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12) / VU4: Going to or from School (K-12)',
                        'label' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12) / VU4: Going to or from School (K-12)',
                    ],
                    13 => [
                        'value' => 'VU2: Going to or from Transit',
                        'label' => 'VU2: Going to or from Transit',
                    ],
                    14 => [
                        'value' => 'VU2: Going to or from Transit / VU3: Going to or from Transit',
                        'label' => 'VU2: Going to or from Transit / VU3: Going to or from Transit',
                    ],
                    15 => [
                        'value' => 'VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit',
                        'label' => 'VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit',
                    ],
                    16 => [
                        'value' => 'VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit / VU5: Going to or from Transit / VU6: Going to or from Transit',
                        'label' => 'VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit / VU5: Going to or from Transit / VU6: Going to or from Transit',
                    ],
                    17 => [
                        'value' => 'VU2: Other',
                        'label' => 'VU2: Other',
                    ],
                    18 => [
                        'value' => 'VU2: Other / VU3: Other',
                        'label' => 'VU2: Other / VU3: Other',
                    ],
                    19 => [
                        'value' => 'VU2: Other / VU3: Other / VU4: Other',
                        'label' => 'VU2: Other / VU3: Other / VU4: Other',
                    ],
                    20 => [
                        'value' => 'VU2: Other / VU3: Other / VU4: Other / VU5: Other',
                        'label' => 'VU2: Other / VU3: Other / VU4: Other / VU5: Other',
                    ],
                    21 => [
                        'value' => 'VU2: Other / VU4: Other',
                        'label' => 'VU2: Other / VU4: Other',
                    ],
                    22 => [
                        'value' => 'VU2: Other / VU4: Other / VU5: Other',
                        'label' => 'VU2: Other / VU4: Other / VU5: Other',
                    ],
                    23 => [
                        'value' => 'VU3: Going to or from a Delivery Vehicle',
                        'label' => 'VU3: Going to or from a Delivery Vehicle',
                    ],
                    24 => [
                        'value' => 'VU3: Going to or from a Mailbox',
                        'label' => 'VU3: Going to or from a Mailbox',
                    ],
                    25 => [
                        'value' => 'VU3: Going to or from a School Bus or a School Bus Stop',
                        'label' => 'VU3: Going to or from a School Bus or a School Bus Stop',
                    ],
                    26 => [
                        'value' => 'VU3: Going to or from an Ice Cream or Food Truck',
                        'label' => 'VU3: Going to or from an Ice Cream or Food Truck',
                    ],
                    27 => [
                        'value' => 'VU3: Going to or from School (K-12)',
                        'label' => 'VU3: Going to or from School (K-12)',
                    ],
                    28 => [
                        'value' => 'VU3: Going to or from Transit',
                        'label' => 'VU3: Going to or from Transit',
                    ],
                    29 => [
                        'value' => 'VU3: Other',
                        'label' => 'VU3: Other',
                    ],
                    30 => [
                        'value' => 'VU3: Other / VU4: Other',
                        'label' => 'VU3: Other / VU4: Other',
                    ],
                    31 => [
                        'value' => 'VU3: Other / VU5: Other',
                        'label' => 'VU3: Other / VU5: Other',
                    ],
                    32 => [
                        'value' => 'VU4: Going to or from a Delivery Vehicle',
                        'label' => 'VU4: Going to or from a Delivery Vehicle',
                    ],
                    33 => [
                        'value' => 'VU4: Going to or from School (K-12)',
                        'label' => 'VU4: Going to or from School (K-12)',
                    ],
                    34 => [
                        'value' => 'VU4: Going to or from School (K-12) / VU5: Going to or from School (K-12)',
                        'label' => 'VU4: Going to or from School (K-12) / VU5: Going to or from School (K-12)',
                    ],
                    35 => [
                        'value' => 'VU4: Going to or from Transit',
                        'label' => 'VU4: Going to or from Transit',
                    ],
                    36 => [
                        'value' => 'VU4: Other',
                        'label' => 'VU4: Other',
                    ],
                    37 => [
                        'value' => 'VU4: Other / VU5: Other',
                        'label' => 'VU4: Other / VU5: Other',
                    ],
                    38 => [
                        'value' => 'VU4: Other / VU5: Other / VU6: Other',
                        'label' => 'VU4: Other / VU5: Other / VU6: Other',
                    ],
                    39 => [
                        'value' => 'VU5: Going to or from School (K-12)',
                        'label' => 'VU5: Going to or from School (K-12)',
                    ],
                    40 => [
                        'value' => 'VU5: Going to or from Transit',
                        'label' => 'VU5: Going to or from Transit',
                    ],
                    41 => [
                        'value' => 'VU5: Other',
                        'label' => 'VU5: Other',
                    ],
                    42 => [
                        'value' => 'VU5: Other / VU6: Other',
                        'label' => 'VU5: Other / VU6: Other',
                    ],
                    43 => [
                        'value' => 'VU6: Going to or from School (K-12)',
                        'label' => 'VU6: Going to or from School (K-12)',
                    ],
                    44 => [
                        'value' => 'VU6: Going to or from Transit',
                        'label' => 'VU6: Going to or from Transit',
                    ],
                    45 => [
                        'value' => 'VU6: Other',
                        'label' => 'VU6: Other',
                    ],
                    46 => [
                        'value' => 'VU7: Other',
                        'label' => 'VU7: Other',
                    ],
                    47 => [
                        'value' => 'VU7: Other / VU8: Other',
                        'label' => 'VU7: Other / VU8: Other',
                    ],
                    48 => [
                        'value' => 'VU8: Other',
                        'label' => 'VU8: Other',
                    ],
                ],
            ],
            168 => [
                'name' => 'non_mtrst_cntrb_circ_cl',
                'label' => 'Vulnerable Users Contributing Circumstance (All Persons)',
                'type' => 'text',
                'placeholder' => 'Enter Vulnerable Users Contributing Circumstance (All Persons)',
            ],
            169 => [
                'name' => 'non_mtrst_distracted_by_cl',
                'label' => 'Vulnerable Users Distracted By (All Persons)',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable Users Distracted By (All Persons)',
                'options' => [
                    0 => [
                        'value' => 'VU10:(Not Distracted)',
                        'label' => 'VU10:(Not Distracted)',
                    ],
                    1 => [
                        'value' => 'VU11:(Not Distracted)',
                        'label' => 'VU11:(Not Distracted)',
                    ],
                    2 => [
                        'value' => 'VU17:(Not Distracted)',
                        'label' => 'VU17:(Not Distracted)',
                    ],
                    3 => [
                        'value' => 'VU2:(Manually operating an electronic device (texting, typing, dialing))',
                        'label' => 'VU2:(Manually operating an electronic device (texting, typing, dialing))',
                    ],
                    4 => [
                        'value' => 'VU2:(Manually operating an electronic device (texting, typing, dialing)),(Talking on hand-held electronic device)',
                        'label' => 'VU2:(Manually operating an electronic device (texting, typing, dialing)),(Talking on hand-held electronic device)',
                    ],
                    5 => [
                        'value' => 'VU2:(Manually operating an electronic device (texting, typing, dialing)),(Utilizing listening device)',
                        'label' => 'VU2:(Manually operating an electronic device (texting, typing, dialing)),(Utilizing listening device)',
                    ],
                    6 => [
                        'value' => 'VU2:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted)',
                    ],
                    7 => [
                        'value' => 'VU2:(Not Distracted) VU3:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU3:(Not Distracted)',
                    ],
                    8 => [
                        'value' => 'VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted)',
                    ],
                    9 => [
                        'value' => 'VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted) VU5:(Passenger) VU6:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted) VU5:(Passenger) VU6:(Not Distracted)',
                    ],
                    10 => [
                        'value' => 'VU2:(Not Distracted) VU3:(Passenger)',
                        'label' => 'VU2:(Not Distracted) VU3:(Passenger)',
                    ],
                    11 => [
                        'value' => 'VU2:(Not Distracted) VU4:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU4:(Not Distracted)',
                    ],
                    12 => [
                        'value' => 'VU2:(Not Distracted) VU4:(Not Distracted) VU5:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU4:(Not Distracted) VU5:(Not Distracted)',
                    ],
                    13 => [
                        'value' => 'VU2:(Not Distracted),(Manually operating an electronic device (texting, typing, dialing))',
                        'label' => 'VU2:(Not Distracted),(Manually operating an electronic device (texting, typing, dialing))',
                    ],
                    14 => [
                        'value' => 'VU2:(Not Distracted),(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU2:(Not Distracted),(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    15 => [
                        'value' => 'VU2:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU2:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    16 => [
                        'value' => 'VU2:(Other activity (searching, eating, personal hygiene, etc.)),(Not Distracted)',
                        'label' => 'VU2:(Other activity (searching, eating, personal hygiene, etc.)),(Not Distracted)',
                    ],
                    17 => [
                        'value' => 'VU2:(Reported but invalid)',
                        'label' => 'VU2:(Reported but invalid)',
                    ],
                    18 => [
                        'value' => 'VU2:(Talking on hand-held electronic device)',
                        'label' => 'VU2:(Talking on hand-held electronic device)',
                    ],
                    19 => [
                        'value' => 'VU2:(Talking on hands-free electronic device)',
                        'label' => 'VU2:(Talking on hands-free electronic device)',
                    ],
                    20 => [
                        'value' => 'VU2:(Utilizing listening device)',
                        'label' => 'VU2:(Utilizing listening device)',
                    ],
                    21 => [
                        'value' => 'VU2:(Utilizing listening device),(Not Distracted)',
                        'label' => 'VU2:(Utilizing listening device),(Not Distracted)',
                    ],
                    22 => [
                        'value' => 'VU3:(Manually operating an electronic device (texting, typing, dialing))',
                        'label' => 'VU3:(Manually operating an electronic device (texting, typing, dialing))',
                    ],
                    23 => [
                        'value' => 'VU3:(Not Distracted)',
                        'label' => 'VU3:(Not Distracted)',
                    ],
                    24 => [
                        'value' => 'VU3:(Not Distracted) VU4:(Not Distracted)',
                        'label' => 'VU3:(Not Distracted) VU4:(Not Distracted)',
                    ],
                    25 => [
                        'value' => 'VU3:(Not Distracted) VU5:(Not Distracted)',
                        'label' => 'VU3:(Not Distracted) VU5:(Not Distracted)',
                    ],
                    26 => [
                        'value' => 'VU3:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU3:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    27 => [
                        'value' => 'VU3:(Passenger)',
                        'label' => 'VU3:(Passenger)',
                    ],
                    28 => [
                        'value' => 'VU3:(Reported but invalid)',
                        'label' => 'VU3:(Reported but invalid)',
                    ],
                    29 => [
                        'value' => 'VU3:(Talking on hand-held electronic device)',
                        'label' => 'VU3:(Talking on hand-held electronic device)',
                    ],
                    30 => [
                        'value' => 'VU3:(Utilizing listening device)',
                        'label' => 'VU3:(Utilizing listening device)',
                    ],
                    31 => [
                        'value' => 'VU4:(Manually operating an electronic device (texting, typing, dialing))',
                        'label' => 'VU4:(Manually operating an electronic device (texting, typing, dialing))',
                    ],
                    32 => [
                        'value' => 'VU4:(Not Distracted)',
                        'label' => 'VU4:(Not Distracted)',
                    ],
                    33 => [
                        'value' => 'VU4:(Not Distracted) VU5:(Not Distracted)',
                        'label' => 'VU4:(Not Distracted) VU5:(Not Distracted)',
                    ],
                    34 => [
                        'value' => 'VU4:(Not Distracted) VU5:(Not Distracted) VU6:(Not Distracted)',
                        'label' => 'VU4:(Not Distracted) VU5:(Not Distracted) VU6:(Not Distracted)',
                    ],
                    35 => [
                        'value' => 'VU4:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU4:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    36 => [
                        'value' => 'VU4:(Talking on hands-free electronic device)',
                        'label' => 'VU4:(Talking on hands-free electronic device)',
                    ],
                    37 => [
                        'value' => 'VU5:(Not Distracted)',
                        'label' => 'VU5:(Not Distracted)',
                    ],
                    38 => [
                        'value' => 'VU5:(Not Distracted) VU6:(Not Distracted)',
                        'label' => 'VU5:(Not Distracted) VU6:(Not Distracted)',
                    ],
                    39 => [
                        'value' => 'VU5:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU5:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    40 => [
                        'value' => 'VU6:(Not Distracted)',
                        'label' => 'VU6:(Not Distracted)',
                    ],
                    41 => [
                        'value' => 'VU7:(Not Distracted)',
                        'label' => 'VU7:(Not Distracted)',
                    ],
                    42 => [
                        'value' => 'VU7:(Not Distracted) VU8:(Not Distracted) VU9:(Not Distracted)',
                        'label' => 'VU7:(Not Distracted) VU8:(Not Distracted) VU9:(Not Distracted)',
                    ],
                    43 => [
                        'value' => 'VU7:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU7:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    44 => [
                        'value' => 'VU7:(Other activity (searching, eating, personal hygiene, etc.)) VU8:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU7:(Other activity (searching, eating, personal hygiene, etc.)) VU8:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    45 => [
                        'value' => 'VU8:(Not Distracted)',
                        'label' => 'VU8:(Not Distracted)',
                    ],
                ],
            ],
            170 => [
                'name' => 'non_mtrst_alc_suspd_type_cl',
                'label' => 'Vulnerable Users Alcohol Suspected Type (All Persons)',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable Users Alcohol Suspected Type (All Persons)',
                'options' => [
                    0 => [
                        'value' => 'VU10: No',
                        'label' => 'VU10: No',
                    ],
                    1 => [
                        'value' => 'VU11: No',
                        'label' => 'VU11: No',
                    ],
                    2 => [
                        'value' => 'VU17: No',
                        'label' => 'VU17: No',
                    ],
                    3 => [
                        'value' => 'VU2: No',
                        'label' => 'VU2: No',
                    ],
                    4 => [
                        'value' => 'VU2: No / VU3: No',
                        'label' => 'VU2: No / VU3: No',
                    ],
                    5 => [
                        'value' => 'VU2: No / VU3: No / VU4: No',
                        'label' => 'VU2: No / VU3: No / VU4: No',
                    ],
                    6 => [
                        'value' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                    ],
                    7 => [
                        'value' => 'VU2: No / VU3: No / VU4: No / VU5: No / VU6: No',
                        'label' => 'VU2: No / VU3: No / VU4: No / VU5: No / VU6: No',
                    ],
                    8 => [
                        'value' => 'VU2: No / VU4: No',
                        'label' => 'VU2: No / VU4: No',
                    ],
                    9 => [
                        'value' => 'VU2: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU4: No / VU5: No',
                    ],
                    10 => [
                        'value' => 'VU2: Yes',
                        'label' => 'VU2: Yes',
                    ],
                    11 => [
                        'value' => 'VU2: Yes / VU3: No',
                        'label' => 'VU2: Yes / VU3: No',
                    ],
                    12 => [
                        'value' => 'VU2: Yes / VU3: Yes',
                        'label' => 'VU2: Yes / VU3: Yes',
                    ],
                    13 => [
                        'value' => 'VU3: No',
                        'label' => 'VU3: No',
                    ],
                    14 => [
                        'value' => 'VU3: No / VU4: No',
                        'label' => 'VU3: No / VU4: No',
                    ],
                    15 => [
                        'value' => 'VU3: No / VU5: No',
                        'label' => 'VU3: No / VU5: No',
                    ],
                    16 => [
                        'value' => 'VU3: Yes',
                        'label' => 'VU3: Yes',
                    ],
                    17 => [
                        'value' => 'VU4: No',
                        'label' => 'VU4: No',
                    ],
                    18 => [
                        'value' => 'VU4: No / VU5: No',
                        'label' => 'VU4: No / VU5: No',
                    ],
                    19 => [
                        'value' => 'VU4: No / VU5: No / VU6: No',
                        'label' => 'VU4: No / VU5: No / VU6: No',
                    ],
                    20 => [
                        'value' => 'VU4: No / VU5: Yes / VU6: No',
                        'label' => 'VU4: No / VU5: Yes / VU6: No',
                    ],
                    21 => [
                        'value' => 'VU4: Yes',
                        'label' => 'VU4: Yes',
                    ],
                    22 => [
                        'value' => 'VU5: No',
                        'label' => 'VU5: No',
                    ],
                    23 => [
                        'value' => 'VU5: No / VU6: No',
                        'label' => 'VU5: No / VU6: No',
                    ],
                    24 => [
                        'value' => 'VU5: Yes',
                        'label' => 'VU5: Yes',
                    ],
                    25 => [
                        'value' => 'VU6: No',
                        'label' => 'VU6: No',
                    ],
                    26 => [
                        'value' => 'VU7: No',
                        'label' => 'VU7: No',
                    ],
                    27 => [
                        'value' => 'VU7: No / VU8: No',
                        'label' => 'VU7: No / VU8: No',
                    ],
                ],
            ],
            171 => [
                'name' => 'non_mtrst_drug_suspd_type_cl',
                'label' => 'Vulnerable Users Drug Suspected Type (All Persons)',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable Users Drug Suspected Type (All Persons)',
                'options' => [
                    0 => [
                        'value' => 'VU10: No',
                        'label' => 'VU10: No',
                    ],
                    1 => [
                        'value' => 'VU11: No',
                        'label' => 'VU11: No',
                    ],
                    2 => [
                        'value' => 'VU17: No',
                        'label' => 'VU17: No',
                    ],
                    3 => [
                        'value' => 'VU2: No',
                        'label' => 'VU2: No',
                    ],
                    4 => [
                        'value' => 'VU2: No / VU3: No',
                        'label' => 'VU2: No / VU3: No',
                    ],
                    5 => [
                        'value' => 'VU2: No / VU3: No / VU4: No',
                        'label' => 'VU2: No / VU3: No / VU4: No',
                    ],
                    6 => [
                        'value' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                    ],
                    7 => [
                        'value' => 'VU2: No / VU3: No / VU4: No / VU5: No / VU6: No',
                        'label' => 'VU2: No / VU3: No / VU4: No / VU5: No / VU6: No',
                    ],
                    8 => [
                        'value' => 'VU2: No / VU4: No',
                        'label' => 'VU2: No / VU4: No',
                    ],
                    9 => [
                        'value' => 'VU2: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU4: No / VU5: No',
                    ],
                    10 => [
                        'value' => 'VU2: Yes',
                        'label' => 'VU2: Yes',
                    ],
                    11 => [
                        'value' => 'VU3: No',
                        'label' => 'VU3: No',
                    ],
                    12 => [
                        'value' => 'VU3: No / VU4: No',
                        'label' => 'VU3: No / VU4: No',
                    ],
                    13 => [
                        'value' => 'VU3: No / VU5: No',
                        'label' => 'VU3: No / VU5: No',
                    ],
                    14 => [
                        'value' => 'VU3: Yes',
                        'label' => 'VU3: Yes',
                    ],
                    15 => [
                        'value' => 'VU4: No',
                        'label' => 'VU4: No',
                    ],
                    16 => [
                        'value' => 'VU4: No / VU5: No',
                        'label' => 'VU4: No / VU5: No',
                    ],
                    17 => [
                        'value' => 'VU4: No / VU5: No / VU6: No',
                        'label' => 'VU4: No / VU5: No / VU6: No',
                    ],
                    18 => [
                        'value' => 'VU4: Yes',
                        'label' => 'VU4: Yes',
                    ],
                    19 => [
                        'value' => 'VU5: No',
                        'label' => 'VU5: No',
                    ],
                    20 => [
                        'value' => 'VU5: No / VU6: No',
                        'label' => 'VU5: No / VU6: No',
                    ],
                    21 => [
                        'value' => 'VU5: Yes',
                        'label' => 'VU5: Yes',
                    ],
                    22 => [
                        'value' => 'VU6: No',
                        'label' => 'VU6: No',
                    ],
                    23 => [
                        'value' => 'VU7: No',
                        'label' => 'VU7: No',
                    ],
                    24 => [
                        'value' => 'VU7: No / VU8: No',
                        'label' => 'VU7: No / VU8: No',
                    ],
                    25 => [
                        'value' => 'VU7: No / VU8: No / VU9: No',
                        'label' => 'VU7: No / VU8: No / VU9: No',
                    ],
                ],
            ],
            172 => [
                'name' => 'non_mtrst_event_seq_cl',
                'label' => 'Vulnerable Users Sequence of Events (All Persons)',
                'type' => 'text',
                'placeholder' => 'Enter Vulnerable Users Sequence of Events (All Persons)',
            ],
            173 => [
                'name' => 'traffic_control_type_descr',
                'label' => 'Vulnerable Users Traffic Control Device Type (All Persons)',
                'type' => 'text',
                'placeholder' => 'Enter Vulnerable Users Traffic Control Device Type (All Persons)',
            ],
            174 => [
                'name' => 'non_motorist_cntrb_circ_1',
                'label' => 'Vulnerable User Contribution 1',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Contribution 1',
                'options' => [
                    0 => [
                        'value' => 'Crossing of Roadway or Intersection',
                        'label' => 'Crossing of Roadway or Intersection',
                    ],
                    1 => [
                        'value' => 'Dart/Dash',
                        'label' => 'Dart/Dash',
                    ],
                    2 => [
                        'value' => 'Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching)',
                        'label' => 'Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching)',
                    ],
                    3 => [
                        'value' => 'Distracted',
                        'label' => 'Distracted',
                    ],
                    4 => [
                        'value' => 'Entering/Exiting Parked/Standing Vehicle',
                        'label' => 'Entering/Exiting Parked/Standing Vehicle',
                    ],
                    5 => [
                        'value' => 'Failure to Obey Traffic Sign(s), Signal(s), or Officer(s)',
                        'label' => 'Failure to Obey Traffic Sign(s), Signal(s), or Officer(s)',
                    ],
                    6 => [
                        'value' => 'Failure to use Proper Crosswalk',
                        'label' => 'Failure to use Proper Crosswalk',
                    ],
                    7 => [
                        'value' => 'Failure to Yield Right-Of-Way',
                        'label' => 'Failure to Yield Right-Of-Way',
                    ],
                    8 => [
                        'value' => 'Fleeing/Evading Law Enforcement',
                        'label' => 'Fleeing/Evading Law Enforcement',
                    ],
                    9 => [
                        'value' => 'In Roadway (Standing, Lying, Working, Playing, etc.)',
                        'label' => 'In Roadway (Standing, Lying, Working, Playing, etc.)',
                    ],
                    10 => [
                        'value' => 'Inattentive (Talking, Eating, etc.)',
                        'label' => 'Inattentive (Talking, Eating, etc.)',
                    ],
                    11 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    12 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    13 => [
                        'value' => 'Not Visible (Dark Clothing, No Lighting, etc.)',
                        'label' => 'Not Visible (Dark Clothing, No Lighting, etc.)',
                    ],
                    14 => [
                        'value' => 'Other (Explain in Narrative)',
                        'label' => 'Other (Explain in Narrative)',
                    ],
                    15 => [
                        'value' => 'Passing',
                        'label' => 'Passing',
                    ],
                    16 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    17 => [
                        'value' => 'Traveling Wrong Way',
                        'label' => 'Traveling Wrong Way',
                    ],
                    18 => [
                        'value' => 'Turn/Merge',
                        'label' => 'Turn/Merge',
                    ],
                    19 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            175 => [
                'name' => 'non_motorist_cntrb_circ_2',
                'label' => 'Vulnerable User Contribution 2',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Contribution 2',
                'options' => [
                    0 => [
                        'value' => 'Crossing of Roadway or Intersection',
                        'label' => 'Crossing of Roadway or Intersection',
                    ],
                    1 => [
                        'value' => 'Dart/Dash',
                        'label' => 'Dart/Dash',
                    ],
                    2 => [
                        'value' => 'Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching)',
                        'label' => 'Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching)',
                    ],
                    3 => [
                        'value' => 'Distracted',
                        'label' => 'Distracted',
                    ],
                    4 => [
                        'value' => 'Entering/Exiting Parked/Standing Vehicle',
                        'label' => 'Entering/Exiting Parked/Standing Vehicle',
                    ],
                    5 => [
                        'value' => 'Failure to Obey Traffic Sign(s), Signal(s), or Officer(s)',
                        'label' => 'Failure to Obey Traffic Sign(s), Signal(s), or Officer(s)',
                    ],
                    6 => [
                        'value' => 'Failure to use Proper Crosswalk',
                        'label' => 'Failure to use Proper Crosswalk',
                    ],
                    7 => [
                        'value' => 'Failure to Yield Right-Of-Way',
                        'label' => 'Failure to Yield Right-Of-Way',
                    ],
                    8 => [
                        'value' => 'Fleeing/Evading Law Enforcement',
                        'label' => 'Fleeing/Evading Law Enforcement',
                    ],
                    9 => [
                        'value' => 'In Roadway (Standing, Lying, Working, Playing, etc.)',
                        'label' => 'In Roadway (Standing, Lying, Working, Playing, etc.)',
                    ],
                    10 => [
                        'value' => 'Inattentive (Talking, Eating, etc.)',
                        'label' => 'Inattentive (Talking, Eating, etc.)',
                    ],
                    11 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    12 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    13 => [
                        'value' => 'Not Visible (Dark Clothing, No Lighting, etc.)',
                        'label' => 'Not Visible (Dark Clothing, No Lighting, etc.)',
                    ],
                    14 => [
                        'value' => 'Other (Explain in Narrative)',
                        'label' => 'Other (Explain in Narrative)',
                    ],
                    15 => [
                        'value' => 'Passing',
                        'label' => 'Passing',
                    ],
                    16 => [
                        'value' => 'Traveling Wrong Way',
                        'label' => 'Traveling Wrong Way',
                    ],
                    17 => [
                        'value' => 'Turn/Merge',
                        'label' => 'Turn/Merge',
                    ],
                    18 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            176 => [
                'name' => 'non_motorist_contact_point',
                'label' => 'Vulnerable User Contact Point',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Contact Point',
                'options' => [
                    0 => [
                        'value' => '03 - Right',
                        'label' => '03 - Right',
                    ],
                    1 => [
                        'value' => '06 - Rear',
                        'label' => '06 - Rear',
                    ],
                    2 => [
                        'value' => '09 - Left',
                        'label' => '09 - Left',
                    ],
                    3 => [
                        'value' => 'Front',
                        'label' => 'Front',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    6 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    7 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            177 => [
                'name' => 'non_motorist_distracted_by_1',
                'label' => 'Vulnerable User Distracted By 1',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Distracted By 1',
                'options' => [
                    0 => [
                        'value' => 'Manually operating an electronic device (texting, typing, dialing)',
                        'label' => 'Manually operating an electronic device (texting, typing, dialing)',
                    ],
                    1 => [
                        'value' => 'Not Distracted',
                        'label' => 'Not Distracted',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Other activity (searching, eating, personal hygiene, etc.)',
                        'label' => 'Other activity (searching, eating, personal hygiene, etc.)',
                    ],
                    4 => [
                        'value' => 'Passenger',
                        'label' => 'Passenger',
                    ],
                    5 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    6 => [
                        'value' => 'Talking on hand-held electronic device',
                        'label' => 'Talking on hand-held electronic device',
                    ],
                    7 => [
                        'value' => 'Talking on hands-free electronic device',
                        'label' => 'Talking on hands-free electronic device',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    9 => [
                        'value' => 'Utilizing listening device',
                        'label' => 'Utilizing listening device',
                    ],
                ],
            ],
            178 => [
                'name' => 'non_motorist_distracted_by_2',
                'label' => 'Vulnerable User Distracted By 2',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Distracted By 2',
                'options' => [
                    0 => [
                        'value' => 'Manually operating an electronic device (texting, typing, dialing)',
                        'label' => 'Manually operating an electronic device (texting, typing, dialing)',
                    ],
                    1 => [
                        'value' => 'Not Distracted',
                        'label' => 'Not Distracted',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Other activity (searching, eating, personal hygiene, etc.)',
                        'label' => 'Other activity (searching, eating, personal hygiene, etc.)',
                    ],
                    4 => [
                        'value' => 'Talking on hand-held electronic device',
                        'label' => 'Talking on hand-held electronic device',
                    ],
                    5 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    6 => [
                        'value' => 'Utilizing listening device',
                        'label' => 'Utilizing listening device',
                    ],
                ],
            ],
            179 => [
                'name' => 'non_motorist_ejection_descr',
                'label' => 'Vulnerable User Ejection',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Ejection',
                'options' => [
                    0 => [
                        'value' => 'Not applicable',
                        'label' => 'Not applicable',
                    ],
                    1 => [
                        'value' => 'Not ejected',
                        'label' => 'Not ejected',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Partially ejected',
                        'label' => 'Partially ejected',
                    ],
                    4 => [
                        'value' => 'Totally ejected',
                        'label' => 'Totally ejected',
                    ],
                    5 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            180 => [
                'name' => 'non_motorist_event_sequence_1',
                'label' => 'Vulnerable User Event Sequence 1',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Event Sequence 1',
                'options' => [
                    0 => [
                        'value' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                        'label' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                    ],
                    1 => [
                        'value' => 'Approaching or leaving vehicle',
                        'label' => 'Approaching or leaving vehicle',
                    ],
                    2 => [
                        'value' => 'Changing lanes',
                        'label' => 'Changing lanes',
                    ],
                    3 => [
                        'value' => 'Collision with door opening of parked car - Back Left',
                        'label' => 'Collision with door opening of parked car - Back Left',
                    ],
                    4 => [
                        'value' => 'Collision with door opening of parked car - Front left',
                        'label' => 'Collision with door opening of parked car - Front left',
                    ],
                    5 => [
                        'value' => 'Collision with door opening of parked car - Front right',
                        'label' => 'Collision with door opening of parked car - Front right',
                    ],
                    6 => [
                        'value' => 'Collision with motor vehicle in transport',
                        'label' => 'Collision with motor vehicle in transport',
                    ],
                    7 => [
                        'value' => 'Collision with parked motor vehicle, stationary',
                        'label' => 'Collision with parked motor vehicle, stationary',
                    ],
                    8 => [
                        'value' => 'Crossing Roadway',
                        'label' => 'Crossing Roadway',
                    ],
                    9 => [
                        'value' => 'In Roadway – Other',
                        'label' => 'In Roadway – Other',
                    ],
                    10 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    11 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    12 => [
                        'value' => 'Overtaking/passing',
                        'label' => 'Overtaking/passing',
                    ],
                    13 => [
                        'value' => 'Pushing vehicle',
                        'label' => 'Pushing vehicle',
                    ],
                    14 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    15 => [
                        'value' => 'Slowed or stopped',
                        'label' => 'Slowed or stopped',
                    ],
                    16 => [
                        'value' => 'Traveling straight ahead',
                        'label' => 'Traveling straight ahead',
                    ],
                    17 => [
                        'value' => 'Turning left',
                        'label' => 'Turning left',
                    ],
                    18 => [
                        'value' => 'Turning right',
                        'label' => 'Turning right',
                    ],
                    19 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    20 => [
                        'value' => 'Waiting to Cross Roadway',
                        'label' => 'Waiting to Cross Roadway',
                    ],
                    21 => [
                        'value' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                    ],
                    22 => [
                        'value' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                    ],
                    23 => [
                        'value' => 'Walking/Cycling on Sidewalk',
                        'label' => 'Walking/Cycling on Sidewalk',
                    ],
                    24 => [
                        'value' => 'Working - other',
                        'label' => 'Working - other',
                    ],
                    25 => [
                        'value' => 'Working in Trafficway (Incident Response)',
                        'label' => 'Working in Trafficway (Incident Response)',
                    ],
                    26 => [
                        'value' => 'Working on vehicle',
                        'label' => 'Working on vehicle',
                    ],
                ],
            ],
            181 => [
                'name' => 'non_motorist_event_sequence_2',
                'label' => 'Vulnerable User Event Sequence 2',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Event Sequence 2',
                'options' => [
                    0 => [
                        'value' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                        'label' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                    ],
                    1 => [
                        'value' => 'Approaching or leaving vehicle',
                        'label' => 'Approaching or leaving vehicle',
                    ],
                    2 => [
                        'value' => 'Changing lanes',
                        'label' => 'Changing lanes',
                    ],
                    3 => [
                        'value' => 'Collision with door opening of parked car - Back right',
                        'label' => 'Collision with door opening of parked car - Back right',
                    ],
                    4 => [
                        'value' => 'Collision with door opening of parked car - Front left',
                        'label' => 'Collision with door opening of parked car - Front left',
                    ],
                    5 => [
                        'value' => 'Collision with door opening of parked car - Front right',
                        'label' => 'Collision with door opening of parked car - Front right',
                    ],
                    6 => [
                        'value' => 'Collision with motor vehicle in transport',
                        'label' => 'Collision with motor vehicle in transport',
                    ],
                    7 => [
                        'value' => 'Collision with parked motor vehicle, stationary',
                        'label' => 'Collision with parked motor vehicle, stationary',
                    ],
                    8 => [
                        'value' => 'Crossing Roadway',
                        'label' => 'Crossing Roadway',
                    ],
                    9 => [
                        'value' => 'In Roadway – Other',
                        'label' => 'In Roadway – Other',
                    ],
                    10 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    11 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    12 => [
                        'value' => 'Overtaking/passing',
                        'label' => 'Overtaking/passing',
                    ],
                    13 => [
                        'value' => 'Pushing vehicle',
                        'label' => 'Pushing vehicle',
                    ],
                    14 => [
                        'value' => 'Slowed or stopped',
                        'label' => 'Slowed or stopped',
                    ],
                    15 => [
                        'value' => 'Traveling straight ahead',
                        'label' => 'Traveling straight ahead',
                    ],
                    16 => [
                        'value' => 'Turning left',
                        'label' => 'Turning left',
                    ],
                    17 => [
                        'value' => 'Turning right',
                        'label' => 'Turning right',
                    ],
                    18 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    19 => [
                        'value' => 'Waiting to Cross Roadway',
                        'label' => 'Waiting to Cross Roadway',
                    ],
                    20 => [
                        'value' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                    ],
                    21 => [
                        'value' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                    ],
                    22 => [
                        'value' => 'Walking/Cycling on Sidewalk',
                        'label' => 'Walking/Cycling on Sidewalk',
                    ],
                    23 => [
                        'value' => 'Working - other',
                        'label' => 'Working - other',
                    ],
                    24 => [
                        'value' => 'Working in Trafficway (Incident Response)',
                        'label' => 'Working in Trafficway (Incident Response)',
                    ],
                    25 => [
                        'value' => 'Working on vehicle',
                        'label' => 'Working on vehicle',
                    ],
                ],
            ],
            182 => [
                'name' => 'non_motorist_event_sequence_3',
                'label' => 'Vulnerable User Event Sequence 3',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Event Sequence 3',
                'options' => [
                    0 => [
                        'value' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                        'label' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                    ],
                    1 => [
                        'value' => 'Approaching or leaving vehicle',
                        'label' => 'Approaching or leaving vehicle',
                    ],
                    2 => [
                        'value' => 'Changing lanes',
                        'label' => 'Changing lanes',
                    ],
                    3 => [
                        'value' => 'Collision with door opening of parked car - Back right',
                        'label' => 'Collision with door opening of parked car - Back right',
                    ],
                    4 => [
                        'value' => 'Collision with door opening of parked car - Front right',
                        'label' => 'Collision with door opening of parked car - Front right',
                    ],
                    5 => [
                        'value' => 'Collision with motor vehicle in transport',
                        'label' => 'Collision with motor vehicle in transport',
                    ],
                    6 => [
                        'value' => 'Collision with parked motor vehicle, stationary',
                        'label' => 'Collision with parked motor vehicle, stationary',
                    ],
                    7 => [
                        'value' => 'Crossing Roadway',
                        'label' => 'Crossing Roadway',
                    ],
                    8 => [
                        'value' => 'In Roadway – Other',
                        'label' => 'In Roadway – Other',
                    ],
                    9 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    10 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    11 => [
                        'value' => 'Overtaking/passing',
                        'label' => 'Overtaking/passing',
                    ],
                    12 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    13 => [
                        'value' => 'Slowed or stopped',
                        'label' => 'Slowed or stopped',
                    ],
                    14 => [
                        'value' => 'Traveling straight ahead',
                        'label' => 'Traveling straight ahead',
                    ],
                    15 => [
                        'value' => 'Turning left',
                        'label' => 'Turning left',
                    ],
                    16 => [
                        'value' => 'Turning right',
                        'label' => 'Turning right',
                    ],
                    17 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    18 => [
                        'value' => 'Waiting to Cross Roadway',
                        'label' => 'Waiting to Cross Roadway',
                    ],
                    19 => [
                        'value' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                    ],
                    20 => [
                        'value' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                    ],
                    21 => [
                        'value' => 'Walking/Cycling on Sidewalk',
                        'label' => 'Walking/Cycling on Sidewalk',
                    ],
                    22 => [
                        'value' => 'Working - other',
                        'label' => 'Working - other',
                    ],
                    23 => [
                        'value' => 'Working in Trafficway (Incident Response)',
                        'label' => 'Working in Trafficway (Incident Response)',
                    ],
                    24 => [
                        'value' => 'Working on vehicle',
                        'label' => 'Working on vehicle',
                    ],
                ],
            ],
            183 => [
                'name' => 'non_motorist_event_sequence_4',
                'label' => 'Vulnerable User Event Sequence 4',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Event Sequence 4',
                'options' => [
                    0 => [
                        'value' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                        'label' => 'Adjacent to Roadway (e.g., Shoulder, Median)',
                    ],
                    1 => [
                        'value' => 'Approaching or leaving vehicle',
                        'label' => 'Approaching or leaving vehicle',
                    ],
                    2 => [
                        'value' => 'Changing lanes',
                        'label' => 'Changing lanes',
                    ],
                    3 => [
                        'value' => 'Collision with door opening of parked car - Front left',
                        'label' => 'Collision with door opening of parked car - Front left',
                    ],
                    4 => [
                        'value' => 'Collision with motor vehicle in transport',
                        'label' => 'Collision with motor vehicle in transport',
                    ],
                    5 => [
                        'value' => 'Collision with parked motor vehicle, stationary',
                        'label' => 'Collision with parked motor vehicle, stationary',
                    ],
                    6 => [
                        'value' => 'Crossing Roadway',
                        'label' => 'Crossing Roadway',
                    ],
                    7 => [
                        'value' => 'In Roadway – Other',
                        'label' => 'In Roadway – Other',
                    ],
                    8 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    9 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    10 => [
                        'value' => 'Overtaking/passing',
                        'label' => 'Overtaking/passing',
                    ],
                    11 => [
                        'value' => 'Slowed or stopped',
                        'label' => 'Slowed or stopped',
                    ],
                    12 => [
                        'value' => 'Traveling straight ahead',
                        'label' => 'Traveling straight ahead',
                    ],
                    13 => [
                        'value' => 'Turning left',
                        'label' => 'Turning left',
                    ],
                    14 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    15 => [
                        'value' => 'Waiting to Cross Roadway',
                        'label' => 'Waiting to Cross Roadway',
                    ],
                    16 => [
                        'value' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                    ],
                    17 => [
                        'value' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                    ],
                    18 => [
                        'value' => 'Walking/Cycling on Sidewalk',
                        'label' => 'Walking/Cycling on Sidewalk',
                    ],
                    19 => [
                        'value' => 'Working in Trafficway (Incident Response)',
                        'label' => 'Working in Trafficway (Incident Response)',
                    ],
                    20 => [
                        'value' => 'Working on vehicle',
                        'label' => 'Working on vehicle',
                    ],
                ],
            ],
            184 => [
                'name' => 'non_motorist_driver_lic_state',
                'label' => 'Vulnerable User Driver License State Province',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Driver License State Province',
                'options' => [
                    0 => [
                        'value' => '96',
                        'label' => '96',
                    ],
                    1 => [
                        'value' => 'AK',
                        'label' => 'AK',
                    ],
                    2 => [
                        'value' => 'AZ',
                        'label' => 'AZ',
                    ],
                    3 => [
                        'value' => 'CA',
                        'label' => 'CA',
                    ],
                    4 => [
                        'value' => 'CO',
                        'label' => 'CO',
                    ],
                    5 => [
                        'value' => 'CT',
                        'label' => 'CT',
                    ],
                    6 => [
                        'value' => 'DC',
                        'label' => 'DC',
                    ],
                    7 => [
                        'value' => 'FL',
                        'label' => 'FL',
                    ],
                    8 => [
                        'value' => 'FR',
                        'label' => 'FR',
                    ],
                    9 => [
                        'value' => 'GA',
                        'label' => 'GA',
                    ],
                    10 => [
                        'value' => 'IA',
                        'label' => 'IA',
                    ],
                    11 => [
                        'value' => 'ID',
                        'label' => 'ID',
                    ],
                    12 => [
                        'value' => 'IL',
                        'label' => 'IL',
                    ],
                    13 => [
                        'value' => 'KS',
                        'label' => 'KS',
                    ],
                    14 => [
                        'value' => 'LA',
                        'label' => 'LA',
                    ],
                    15 => [
                        'value' => 'MA',
                        'label' => 'MA',
                    ],
                    16 => [
                        'value' => 'MD',
                        'label' => 'MD',
                    ],
                    17 => [
                        'value' => 'ME',
                        'label' => 'ME',
                    ],
                    18 => [
                        'value' => 'MI',
                        'label' => 'MI',
                    ],
                    19 => [
                        'value' => 'MN',
                        'label' => 'MN',
                    ],
                    20 => [
                        'value' => 'NC',
                        'label' => 'NC',
                    ],
                    21 => [
                        'value' => 'NH',
                        'label' => 'NH',
                    ],
                    22 => [
                        'value' => 'NJ',
                        'label' => 'NJ',
                    ],
                    23 => [
                        'value' => 'NV',
                        'label' => 'NV',
                    ],
                    24 => [
                        'value' => 'NY',
                        'label' => 'NY',
                    ],
                    25 => [
                        'value' => 'OH',
                        'label' => 'OH',
                    ],
                    26 => [
                        'value' => 'ON',
                        'label' => 'ON',
                    ],
                    27 => [
                        'value' => 'OT',
                        'label' => 'OT',
                    ],
                    28 => [
                        'value' => 'PA',
                        'label' => 'PA',
                    ],
                    29 => [
                        'value' => 'RI',
                        'label' => 'RI',
                    ],
                    30 => [
                        'value' => 'SC',
                        'label' => 'SC',
                    ],
                    31 => [
                        'value' => 'TN',
                        'label' => 'TN',
                    ],
                    32 => [
                        'value' => 'TX',
                        'label' => 'TX',
                    ],
                    33 => [
                        'value' => 'UT',
                        'label' => 'UT',
                    ],
                    34 => [
                        'value' => 'VA',
                        'label' => 'VA',
                    ],
                    35 => [
                        'value' => 'WA',
                        'label' => 'WA',
                    ],
                ],
            ],
            185 => [
                'name' => 'non_motorist_primary_injury',
                'label' => 'Vulnerable User Primary Injury Area',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Primary Injury Area',
                'options' => [
                    0 => [
                        'value' => 'Head',
                        'label' => 'Head',
                    ],
                    1 => [
                        'value' => 'Lower Limbs',
                        'label' => 'Lower Limbs',
                    ],
                    2 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    3 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    4 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    5 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    6 => [
                        'value' => 'Torso',
                        'label' => 'Torso',
                    ],
                    7 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    8 => [
                        'value' => 'Upper Limbs',
                        'label' => 'Upper Limbs',
                    ],
                ],
            ],
            186 => [
                'name' => 'non_motorist_seating_position',
                'label' => 'Vulnerable User Seating Position',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Seating Position',
                'options' => [
                    0 => [
                        'value' => 'Enclosed passenger area',
                        'label' => 'Enclosed passenger area',
                    ],
                    1 => [
                        'value' => 'Front seat - left side',
                        'label' => 'Front seat - left side',
                    ],
                    2 => [
                        'value' => 'Front seat – right side',
                        'label' => 'Front seat – right side',
                    ],
                    3 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    4 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    5 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    6 => [
                        'value' => 'Riding on exterior',
                        'label' => 'Riding on exterior',
                    ],
                    7 => [
                        'value' => 'Second seat - left side',
                        'label' => 'Second seat - left side',
                    ],
                    8 => [
                        'value' => 'Second seat - right side',
                        'label' => 'Second seat - right side',
                    ],
                    9 => [
                        'value' => 'Trailing unit',
                        'label' => 'Trailing unit',
                    ],
                    10 => [
                        'value' => 'Unenclosed passenger area',
                        'label' => 'Unenclosed passenger area',
                    ],
                    11 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            187 => [
                'name' => 'non_motorist_traffic_control',
                'label' => 'Vulnerable User Traffic Control Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Traffic Control Type',
                'options' => [
                    0 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    1 => [
                        'value' => 'Not Reported',
                        'label' => 'Not Reported',
                    ],
                    2 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    3 => [
                        'value' => 'Person - Crossing guard',
                        'label' => 'Person - Crossing guard',
                    ],
                    4 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    5 => [
                        'value' => 'VU Crossing Sign',
                        'label' => 'VU Crossing Sign',
                    ],
                    6 => [
                        'value' => 'VU Crossing Signal',
                        'label' => 'VU Crossing Signal',
                    ],
                    7 => [
                        'value' => 'VU Prohibited Sign',
                        'label' => 'VU Prohibited Sign',
                    ],
                ],
            ],
            188 => [
                'name' => 'non_motorist_trapped_descr',
                'label' => 'Vulnerable User Trapped',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Trapped',
                'options' => [
                    0 => [
                        'value' => 'Freed by mechanical means',
                        'label' => 'Freed by mechanical means',
                    ],
                    1 => [
                        'value' => 'Freed by nonmechanical means',
                        'label' => 'Freed by nonmechanical means',
                    ],
                    2 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    3 => [
                        'value' => 'Not trapped',
                        'label' => 'Not trapped',
                    ],
                    4 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            189 => [
                'name' => 'non_motorist_origin_dest',
                'label' => 'Vulnerable User Origin Destination',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Origin Destination',
                'options' => [
                    0 => [
                        'value' => 'Going to or from a Delivery Vehicle',
                        'label' => 'Going to or from a Delivery Vehicle',
                    ],
                    1 => [
                        'value' => 'Going to or from a Mailbox',
                        'label' => 'Going to or from a Mailbox',
                    ],
                    2 => [
                        'value' => 'Going to or from a School Bus or a School Bus Stop',
                        'label' => 'Going to or from a School Bus or a School Bus Stop',
                    ],
                    3 => [
                        'value' => 'Going to or from an Ice Cream or Food Truck',
                        'label' => 'Going to or from an Ice Cream or Food Truck',
                    ],
                    4 => [
                        'value' => 'Going to or from School (K-12)',
                        'label' => 'Going to or from School (K-12)',
                    ],
                    5 => [
                        'value' => 'Going to or from Transit',
                        'label' => 'Going to or from Transit',
                    ],
                    6 => [
                        'value' => 'Not Reported',
                        'label' => 'Not Reported',
                    ],
                    7 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    8 => [
                        'value' => 'Reported but Invalid',
                        'label' => 'Reported but Invalid',
                    ],
                    9 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            190 => [
                'name' => 'non_mtrst_test_type_descr',
                'label' => 'Vulnerable User Test Type',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Test Type',
                'options' => [
                    0 => [
                        'value' => 'Blood',
                        'label' => 'Blood',
                    ],
                    1 => [
                        'value' => 'Breath',
                        'label' => 'Breath',
                    ],
                    2 => [
                        'value' => 'Not Reported',
                        'label' => 'Not Reported',
                    ],
                    3 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    4 => [
                        'value' => 'Test Not Given',
                        'label' => 'Test Not Given',
                    ],
                    5 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            191 => [
                'name' => 'non_mtrst_test_status_descr',
                'label' => 'Vulnerable User Test Status',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Test Status',
                'options' => [
                    0 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    1 => [
                        'value' => 'Test Given',
                        'label' => 'Test Given',
                    ],
                    2 => [
                        'value' => 'Test Not Given',
                        'label' => 'Test Not Given',
                    ],
                    3 => [
                        'value' => 'Test Refused',
                        'label' => 'Test Refused',
                    ],
                    4 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    5 => [
                        'value' => 'Unknown, if tested',
                        'label' => 'Unknown, if tested',
                    ],
                ],
            ],
            192 => [
                'name' => 'non_mtrst_test_result_descr',
                'label' => 'Vulnerable User Test Result',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Test Result',
                'options' => [
                    0 => [
                        'value' => '0.00 - 0.01',
                        'label' => '0.00 - 0.01',
                    ],
                    1 => [
                        'value' => '0.08 or greater',
                        'label' => '0.08 or greater',
                    ],
                    2 => [
                        'value' => 'BAC Test Performed, Results Unknown',
                        'label' => 'BAC Test Performed, Results Unknown',
                    ],
                    3 => [
                        'value' => 'Not Reported',
                        'label' => 'Not Reported',
                    ],
                    4 => [
                        'value' => 'Positive Reading with no Actual Value',
                        'label' => 'Positive Reading with no Actual Value',
                    ],
                    5 => [
                        'value' => 'Test Not Given',
                        'label' => 'Test Not Given',
                    ],
                    6 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    7 => [
                        'value' => 'Unknown, if tested',
                        'label' => 'Unknown, if tested',
                    ],
                ],
            ],
            193 => [
                'name' => 'crash_date_text_raw',
                'label' => 'Crash Date (Raw Text)',
                'type' => 'text',
                'placeholder' => 'Enter Crash Date (Raw Text)',
            ],
            194 => [
                'name' => 'crash_time_2_raw',
                'label' => 'Crash Time (Raw Text)',
                'type' => 'text',
                'placeholder' => 'Enter Crash Time (Raw Text)',
            ],
            195 => [
                'name' => 'objectid_source_min',
                'label' => 'Source OBJECTID Min',
                'type' => 'number',
                'placeholder' => 'Min value for Source OBJECTID',
            ],
            196 => [
                'name' => 'objectid_source_max',
                'label' => 'Source OBJECTID Max',
                'type' => 'number',
                'placeholder' => 'Max value for Source OBJECTID',
            ],
        ],
        'contextData' => 'Dataset of Massachusetts Person-Level Crash Datas. Filter by attributes like city town name, date (Crash Date/Time), crash hour.',
        'searchableColumns' => [
            0 => 'crash_numb',
            1 => 'city_town_name',
            2 => 'crash_datetime',
            3 => 'crash_hour',
            4 => 'crash_status',
            5 => 'crash_severity_descr',
            6 => 'max_injr_svrty_cl',
            7 => 'numb_vehc',
            8 => 'numb_nonfatal_injr',
            9 => 'numb_fatal_injr',
            10 => 'polc_agncy_type_descr',
            11 => 'year',
            12 => 'manr_coll_descr',
            13 => 'vehc_mnvr_actn_cl',
            14 => 'vehc_trvl_dirc_cl',
            15 => 'vehc_seq_events_cl',
            16 => 'ambnt_light_descr',
            17 => 'weath_cond_descr',
            18 => 'road_surf_cond_descr',
            19 => 'first_hrmf_event_descr',
            20 => 'most_hrmfl_evt_cl',
            21 => 'drvr_cntrb_circ_cl',
            22 => 'vehc_config_cl',
            23 => 'street_numb',
            24 => 'rdwy',
            25 => 'dist_dirc_from_int',
            26 => 'near_int_rdwy',
            27 => 'mm_rte',
            28 => 'dist_dirc_milemarker',
            29 => 'milemarker',
            30 => 'exit_rte',
            31 => 'dist_dirc_exit',
            32 => 'exit_numb',
            33 => 'dist_dirc_landmark',
            34 => 'landmark',
            35 => 'rdwy_jnct_type_descr',
            36 => 'traf_cntrl_devc_type_descr',
            37 => 'trafy_descr_descr',
            38 => 'jurisdictn',
            39 => 'first_hrmf_event_loc_descr',
            40 => 'is_geocoded_status',
            41 => 'geocoding_method_name',
            42 => 'x_coord',
            43 => 'y_coord',
            44 => 'lat',
            45 => 'lon',
            46 => 'rmv_doc_ids',
            47 => 'crash_rpt_ids',
            48 => 'age_drvr_yngst',
            49 => 'age_drvr_oldest',
            50 => 'age_nonmtrst_yngst',
            51 => 'age_nonmtrst_oldest',
            52 => 'drvr_distracted_cl',
            53 => 'district_num',
            54 => 'rpa_abbr',
            55 => 'vehc_emer_use_cl',
            56 => 'vehc_towed_from_scene_cl',
            57 => 'cnty_name',
            58 => 'fmsca_rptbl_cl',
            59 => 'fmsca_rptbl',
            60 => 'hit_run_descr',
            61 => 'lclty_name',
            62 => 'road_cntrb_descr',
            63 => 'schl_bus_reld_descr',
            64 => 'speed_limit',
            65 => 'traf_cntrl_devc_func_descr',
            66 => 'work_zone_reld_descr',
            67 => 'aadt',
            68 => 'aadt_year',
            69 => 'pk_pct_sut',
            70 => 'av_pct_sut',
            71 => 'pk_pct_ct',
            72 => 'av_pct_ct',
            73 => 'curb',
            74 => 'truck_rte',
            75 => 'lt_sidewlk',
            76 => 'rt_sidewlk',
            77 => 'shldr_lt_w',
            78 => 'shldr_lt_t',
            79 => 'surface_wd',
            80 => 'surface_tp',
            81 => 'shldr_rt_w',
            82 => 'shldr_rt_t',
            83 => 'num_lanes',
            84 => 'opp_lanes',
            85 => 'med_width',
            86 => 'med_type',
            87 => 'urban_type',
            88 => 'f_class',
            89 => 'urban_area',
            90 => 'fd_aid_rte',
            91 => 'facility',
            92 => 'operation',
            93 => 'control',
            94 => 'peak_lane',
            95 => 'speed_lim',
            96 => 'streetname',
            97 => 'fromstreetname',
            98 => 'tostreetname',
            99 => 'city',
            100 => 'struct_cnd',
            101 => 'terrain',
            102 => 'urban_loc_type',
            103 => 'aadt_deriv',
            104 => 'statn_num',
            105 => 'op_dir_sl',
            106 => 'shldr_ul_t',
            107 => 'shldr_ul_w',
            108 => 't_exc_type',
            109 => 't_exc_time',
            110 => 'f_f_class',
            111 => 'vehc_unit_numb',
            112 => 'alc_suspd_type_descr',
            113 => 'driver_age',
            114 => 'drvr_cntrb_circ_descr',
            115 => 'driver_distracted_type_descr',
            116 => 'drvr_lcn_state',
            117 => 'drug_suspd_type_descr',
            118 => 'emergency_use_desc',
            119 => 'fmsca_rptbl_vl',
            120 => 'haz_mat_placard_descr',
            121 => 'max_injr_svrty_vl',
            122 => 'most_hrmf_event',
            123 => 'total_occpt_in_vehc',
            124 => 'vehc_manr_act_descr',
            125 => 'vehc_confg_descr',
            126 => 'vehc_most_dmgd_area',
            127 => 'owner_addr_city_town',
            128 => 'owner_addr_state',
            129 => 'vehc_reg_state',
            130 => 'vehc_reg_type_code',
            131 => 'vehc_seq_events',
            132 => 'vehc_towed_from_scene',
            133 => 'trvl_dirc_descr',
            134 => 'vehicle_make_descr',
            135 => 'vehicle_model_descr',
            136 => 'vehicle_vin',
            137 => 'driver_violation_cl',
            138 => 'pers_numb',
            139 => 'age',
            140 => 'ejctn_descr',
            141 => 'injy_stat_descr',
            142 => 'med_facly',
            143 => 'pers_addr_city',
            144 => 'state_prvn_code',
            145 => 'pers_type',
            146 => 'prtc_sys_use_descr',
            147 => 'sfty_equp_desc_1',
            148 => 'sfty_equp_desc_2',
            149 => 'sex_descr',
            150 => 'trnsd_by_descr',
            151 => 'non_mtrst_type_cl',
            152 => 'non_mtrst_actn_cl',
            153 => 'non_mtrst_loc_cl',
            154 => 'non_mtrst_act_descr',
            155 => 'non_mtrst_cond_descr',
            156 => 'non_mtrst_loc_descr',
            157 => 'non_mtrst_type_descr',
            158 => 'non_mtrst_origin_dest_cl',
            159 => 'non_mtrst_cntrb_circ_cl',
            160 => 'non_mtrst_distracted_by_cl',
            161 => 'non_mtrst_alc_suspd_type_cl',
            162 => 'non_mtrst_drug_suspd_type_cl',
            163 => 'non_mtrst_event_seq_cl',
            164 => 'traffic_control_type_descr',
            165 => 'non_motorist_cntrb_circ_1',
            166 => 'non_motorist_cntrb_circ_2',
            167 => 'non_motorist_contact_point',
            168 => 'non_motorist_distracted_by_1',
            169 => 'non_motorist_distracted_by_2',
            170 => 'non_motorist_ejection_descr',
            171 => 'non_motorist_event_sequence_1',
            172 => 'non_motorist_event_sequence_2',
            173 => 'non_motorist_event_sequence_3',
            174 => 'non_motorist_event_sequence_4',
            175 => 'non_motorist_driver_lic_state',
            176 => 'non_motorist_primary_injury',
            177 => 'non_motorist_seating_position',
            178 => 'non_motorist_traffic_control',
            179 => 'non_motorist_trapped_descr',
            180 => 'non_motorist_origin_dest',
            181 => 'non_mtrst_test_type_descr',
            182 => 'non_mtrst_test_status_descr',
            183 => 'non_mtrst_test_result_descr',
            184 => 'crash_date_text_raw',
            185 => 'crash_time_2_raw',
            186 => 'objectid_source',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Crash Date/Time\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Crash Date/Time\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'crash_numb_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Crash Number.',
            ],
            'crash_numb_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Crash Number.',
            ],
            'city_town_name' => [
                'type' => 'string',
                'description' => 'Filter by City Town Name.',
            ],
            'crash_hour' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Crash Hour. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23.',
            ],
            'crash_status' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Crash Status. Provide a comma-separated list or an array of values. Possible values: Open, Open Fatal.',
            ],
            'crash_severity_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Crash Severity. Provide a comma-separated list or an array of values. Possible values: Fatal injury, Non-fatal injury, Not Reported, Property damage only (none injured), Unknown.',
            ],
            'max_injr_svrty_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Max Injury Severity Reported. Provide a comma-separated list or an array of values. Possible values: Deceased not caused by crash, Fatal injury (K), No Apparent Injury (O), No injury, Non-fatal injury - Possible, Not Applicable, Not reported, Possible Injury (C), Reported but invalid, Suspected Minor Injury (B), Suspected Serious Injury (A), Unknown.',
            ],
            'numb_vehc' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Number of Vehicles. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14.',
            ],
            'numb_nonfatal_injr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Total NonFatal Injuries. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12.',
            ],
            'numb_fatal_injr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Total Fatal Injuries. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3.',
            ],
            'polc_agncy_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Police Agency Type. Provide a comma-separated list or an array of values. Possible values: Campus police, Local police, MBTA police, State police.',
            ],
            'year' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Year. Provide a comma-separated list or an array of values. Possible values: 2023, 2024, 2025.',
            ],
            'crash_person_id' => [
                'type' => 'string',
                'description' => 'Filter by Crash Person Id.',
            ],
            'manr_coll_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Manner of Collision. Provide a comma-separated list or an array of values. Possible values: Angle, Front to Front, Front to Rear, Head-on, Not reported, Rear to Side, Rear-end, Rear-to-rear, Sideswipe, opposite direction, Sideswipe, same direction, Single vehicle crash, Unknown.',
            ],
            'vehc_mnvr_actn_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Actions Prior to Crash (All Vehicles).',
            ],
            'vehc_trvl_dirc_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Travel Direction (All Vehicles).',
            ],
            'vehc_seq_events_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Sequence of Events (All Vehicles).',
            ],
            'ambnt_light_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Light Condition. Provide a comma-separated list or an array of values. Possible values: Dark - lighted roadway, Dark - roadway not lighted, Dark - unknown roadway lighting, Dawn, Daylight, Dusk, Not reported, Other, Reported but invalid, Unknown.',
            ],
            'weath_cond_descr' => [
                'type' => 'string',
                'description' => 'Filter by Weather Condition.',
            ],
            'road_surf_cond_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Road Surface Condition. Provide a comma-separated list or an array of values. Possible values: Dry, Ice, Not reported, Other, Reported but invalid, Sand, mud, dirt, oil, gravel, Slush, Snow, Unknown, Water (standing, moving), Wet.',
            ],
            'first_hrmf_event_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by First Harmful Event. Provide a comma-separated list or an array of values. Possible values: Collision with animal - deer, Collision with animal - other, Collision with bridge, Collision with bridge overhead structure, Collision with curb, Collision with cyclist, Collision with ditch, Collision with embankment, Collision with guardrail, Collision with median barrier, Collision with motor vehicle in traffic, Collision with other light pole or other post/support, Collision with other movable object, Collision with Other Vulnerable User, Collision with parked motor vehicle, Collision with pedestrian, Collision with railway vehicle (e.g., train, engine), Collision with tree, Collision with unknown fixed object, Collision with utility pole, Collision with work zone maintenance equipment, Collison with moped, Jackknife, Not reported, Other, Other non-collision, Overturn/rollover, Reported but invalid, Unknown, Unknown non-collision.',
            ],
            'most_hrmfl_evt_cl' => [
                'type' => 'string',
                'description' => 'Filter by Most Harmful Event (All Vehicles).',
            ],
            'drvr_cntrb_circ_cl' => [
                'type' => 'string',
                'description' => 'Filter by Driver Contributing Circumstances (All Drivers).',
            ],
            'vehc_config_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Configuration (All Vehicles).',
            ],
            'street_numb' => [
                'type' => 'string',
                'description' => 'Filter by Street Number.',
            ],
            'rdwy' => [
                'type' => 'string',
                'description' => 'Filter by Roadway.',
            ],
            'dist_dirc_from_int' => [
                'type' => 'string',
                'description' => 'Filter by Distance and Direction from Intersection.',
            ],
            'near_int_rdwy' => [
                'type' => 'string',
                'description' => 'Filter by Near Intersection Roadway.',
            ],
            'mm_rte' => [
                'type' => 'string',
                'description' => 'Filter by Milemarker Route.',
            ],
            'dist_dirc_milemarker' => [
                'type' => 'string',
                'description' => 'Filter by Distance and Direction from Milemarker.',
            ],
            'milemarker' => [
                'type' => 'string',
                'description' => 'Filter by Milemarker.',
            ],
            'exit_rte' => [
                'type' => 'string',
                'description' => 'Filter by Exit Route.',
            ],
            'dist_dirc_exit' => [
                'type' => 'string',
                'description' => 'Filter by Distance and Direction from Exit.',
            ],
            'exit_numb' => [
                'type' => 'string',
                'description' => 'Filter by Exit Number.',
            ],
            'dist_dirc_landmark' => [
                'type' => 'string',
                'description' => 'Filter by Distance and Direction from Landmark.',
            ],
            'landmark' => [
                'type' => 'string',
                'description' => 'Filter by Landmark.',
            ],
            'rdwy_jnct_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Roadway Junction Type. Provide a comma-separated list or an array of values. Possible values: Driveway, Five-point or more, Four-way intersection, Not at junction, Not reported, Off-ramp, On-ramp, Railway grade crossing, Reported but invalid, T-intersection, Traffic circle, Unknown, Y-intersection.',
            ],
            'traf_cntrl_devc_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Traffic Control Device Type. Provide a comma-separated list or an array of values. Possible values: Flashing traffic control signal, No controls, Not reported, Pedestrian Crossing signal/beacon, Railway crossing device, Reported but invalid, School zone signs, Stop signs, Traffic control signal, Unknown, Warning signs, Yield signs.',
            ],
            'trafy_descr_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Trafficway Description. Provide a comma-separated list or an array of values. Possible values: Not reported, One-way, not divided, Reported but invalid, Two-way, divided, positive median barrier, Two-way, divided, unprotected median, Two-way, not divided, Unknown.',
            ],
            'jurisdictn' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Jurisdiction-linked RD. Provide a comma-separated list or an array of values. Possible values: City or Town accepted road, County Institutional, Department of Conservation and Recreation, Federal Institutional, Federal Park or Forest, Massachusetts Department of Transportation, Massachusetts Port Authority, Other Federal, Private, State college or university, State Institutional, State Park or Forest, Unaccepted by city or town, US Air Force, US Army, US Army Corps of Engineers, US Navy.',
            ],
            'first_hrmf_event_loc_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by First Harmful Event Location. Provide a comma-separated list or an array of values. Possible values: Median, Not reported, Outside roadway, Reported but invalid, Roadside, Roadway, Shoulder - paved, Shoulder - travel lane, Shoulder - unpaved, Unknown.',
            ],
            'is_geocoded_status' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Is Geocoded. Provide a comma-separated list or an array of values. Possible values: Low Confidence, Multiple, No, One, Yes.',
            ],
            'geocoding_method_name' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Geocoding Method. Provide a comma-separated list or an array of values. Possible values: At Address, At Intersection, Exit Number, Landmark, Mile Marker, Off Intersection, Operator Designated, Rotary.',
            ],
            'x_coord' => [
                'type' => 'string',
                'description' => 'Filter by X (NAD 1983 StatePlane Massachusetts Mainland Meters).',
            ],
            'y_coord' => [
                'type' => 'string',
                'description' => 'Filter by Y (NAD 1983 StatePlane Massachusetts Mainland Meters).',
            ],
            'lat' => [
                'type' => 'string',
                'description' => 'Filter by Latitude.',
            ],
            'lon' => [
                'type' => 'string',
                'description' => 'Filter by Longitude.',
            ],
            'rmv_doc_ids' => [
                'type' => 'string',
                'description' => 'Filter by Document IDs.',
            ],
            'crash_rpt_ids' => [
                'type' => 'string',
                'description' => 'Filter by Crash Report IDs.',
            ],
            'age_drvr_yngst' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Age of Driver - Youngest Known. Provide a comma-separated list or an array of values. Possible values: <16, >84, 16-17, 18-20, 21-24, 25-34, 35-44, 45-54, 55-64, 65-74, 75-84.',
            ],
            'age_drvr_oldest' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Age of Driver - Oldest Known. Provide a comma-separated list or an array of values. Possible values: <16, >84, 16-17, 18-20, 21-24, 25-34, 35-44, 45-54, 55-64, 65-74, 75-84.',
            ],
            'age_nonmtrst_yngst' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Age of Vulnerable User - Youngest Known. Provide a comma-separated list or an array of values. Possible values: <6, >84, 16-20, 21-24, 25-34, 35-44, 45-54, 55-64, 6-15, 65-74, 75-84.',
            ],
            'age_nonmtrst_oldest' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Age of Vulnerable User - Oldest Known. Provide a comma-separated list or an array of values. Possible values: <6, >84, 16-20, 21-24, 25-34, 35-44, 45-54, 55-64, 6-15, 65-74, 75-84.',
            ],
            'drvr_distracted_cl' => [
                'type' => 'string',
                'description' => 'Filter by Driver Distracted By (All Drivers).',
            ],
            'district_num' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by District. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6.',
            ],
            'rpa_abbr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by RPA. Provide a comma-separated list or an array of values. Possible values: BRPC, CCC, CMRPC, FRCOG, MAPC, MRPC, MVC, MVPC, NMCOG, NRPEDC, OCPC, PVPC, SRPEDD.',
            ],
            'vehc_emer_use_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Emergency Use (All Vehicles).',
            ],
            'vehc_towed_from_scene_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Towed From Scene (All Vehicles).',
            ],
            'cnty_name' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by County Name. Provide a comma-separated list or an array of values. Possible values: BARNSTABLE, BERKSHIRE, BRISTOL, DUKES, ESSEX, FRANKLIN, HAMPDEN, HAMPSHIRE, MIDDLESEX, NANTUCKET, NORFOLK, PLYMOUTH, SUFFOLK, WORCESTER.',
            ],
            'fmsca_rptbl_cl' => [
                'type' => 'string',
                'description' => 'Filter by FMCSA Reportable (All Vehicles).',
            ],
            'fmsca_rptbl' => [
                'type' => 'boolean',
                'description' => 'Filter by FMCSA Reportable (Crash).',
            ],
            'hit_run_descr' => [
                'type' => 'boolean',
                'description' => 'Filter by Hit and Run.',
            ],
            'lclty_name' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Locality. Provide a comma-separated list or an array of values. Possible values: CHARLESTOWN, FLORENCE, HYANNIS, HYDE PARK, LEEDS, MATTAPAN, MONTAGUE CENTER, ROSLINDALE, ROXBURY, THORNDIKE.',
            ],
            'road_cntrb_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Road Contributing Circumstance. Provide a comma-separated list or an array of values. Possible values: Debris, Non-highway work, None, Not reported, Obstruction in roadway, Other, Road surface condition (wet, icy, snow, slush, etc.), Rut, holes, bumps, Shoulders (none, low, soft), Toll/booth/plaza related, Traffic congestion related, Traffic control device inoperative, missing, or obscured, Unknown, Work zone (construction/maintenance/utility), Worn, travel-polished surface.',
            ],
            'schl_bus_reld_descr' => [
                'type' => 'boolean',
                'description' => 'Filter by School Bus Related.',
            ],
            'speed_limit_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Speed Limit.',
            ],
            'speed_limit_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Speed Limit.',
            ],
            'traf_cntrl_devc_func_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Traffic Control Device Functioning. Provide a comma-separated list or an array of values. Possible values: No, device not functioning, Not reported, Reported but invalid, Unknown, Yes, device functioning.',
            ],
            'work_zone_reld_descr' => [
                'type' => 'boolean',
                'description' => 'Filter by Work Zone Related.',
            ],
            'aadt_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for AADT-linked RD.',
            ],
            'aadt_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for AADT-linked RD.',
            ],
            'aadt_year' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by AADT Year-linked RD. Provide a comma-separated list or an array of values. Possible values: 2009, 2010, 2011, 2012, 2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020.',
            ],
            'pk_pct_sut' => [
                'type' => 'string',
                'description' => 'Filter by Peak % Single Unit Trucks-linked RD.',
            ],
            'av_pct_sut' => [
                'type' => 'string',
                'description' => 'Filter by Average Daily % Single Unit Trucks-linked RD.',
            ],
            'pk_pct_ct' => [
                'type' => 'string',
                'description' => 'Filter by Peak % Combo Trucks-linked RD.',
            ],
            'av_pct_ct' => [
                'type' => 'string',
                'description' => 'Filter by Average Daily % Combo Trucks-linked RD.',
            ],
            'curb' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Curb-linked RD. Provide a comma-separated list or an array of values. Possible values: All curbs (divided highway), Along median only, Both sides, Left side only, None, Other, Right side only.',
            ],
            'truck_rte' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Truck Route-linked RD. Provide a comma-separated list or an array of values. Possible values: DCR Parkway - Recreational Vehicles Only, Designated truck route ONLY under State Authority.  Fully available to both types of STAA vehicles described above, Not a parkway - not on a designated truck route.',
            ],
            'lt_sidewlk' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Left Sidewalk Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 18.0, 20.0, 22.0, 23.0, 26.0, 27.0, 28.0, 30.0.',
            ],
            'rt_sidewlk' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Right Sidewalk Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 20.0, 22.0, 24.0, 26.0, 50.0.',
            ],
            'shldr_lt_w' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Left Shoulder Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 10.0, 12.0, 13.0, 14.0, 15.0, 17.0, 20.0, 22.0, 24.0, 28.0.',
            ],
            'shldr_lt_t' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Left Shoulder Type-linked RD. Provide a comma-separated list or an array of values. Possible values: Earth Shoulder Exists, Hardened bituminous mix or penetration, No Shoulder, Stable - Unruttable compacted subgrade, Unstable shoulder.',
            ],
            'surface_wd' => [
                'type' => 'string',
                'description' => 'Filter by Surface Width-linked RD.',
            ],
            'surface_tp' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Surface Type-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, Bituminous concrete road, Block road, Brick road, Composite road; flexible over rigid, Gravel or stone road, Portland cement concrete road, Surface-treated road, Unimproved, graded earth, or soil surface road.',
            ],
            'shldr_rt_w' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Right Shoulder Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 19.0, 20.0, 22.0, 23.0, 24.0, 26.0, 30.0, 36.0.',
            ],
            'shldr_rt_t' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Right Shoulder Type-linked RD. Provide a comma-separated list or an array of values. Possible values: Combination shoulder, Earth Shoulder Exists, Hardened bituminous mix or penetration, No Shoulder, Stable - Unruttable compacted subgrade, Unstable shoulder.',
            ],
            'num_lanes' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Number of Travel Lanes-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5, 6.',
            ],
            'opp_lanes' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Number of Opposing Travel Lanes-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4.',
            ],
            'med_width' => [
                'type' => 'string',
                'description' => 'Filter by Median Width-linked RD.',
            ],
            'med_type' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Median Type-linked RD. Provide a comma-separated list or an array of values. Possible values: Depressed Median, None, Positive Barrier - unspecified, Positive Barrier – flexible, Positive Barrier – rigid, Positive Barrier – semi-rigid, Raised Median, Unprotected.',
            ],
            'urban_type' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Urban Type-linked RD. Provide a comma-separated list or an array of values. Possible values: Large Urban Cluster, Large Urbanized Area, Rural, Small Urban Cluster, Small Urbanized Area.',
            ],
            'f_class' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Functional Classification-linked RD. Provide a comma-separated list or an array of values. Possible values: Interstate, Local, Rural minor arterial or urban principal arterial, Rural or urban principal arterial, Urban collector or rural minor collector, Urban minor arterial or rural major collector.',
            ],
            'urban_area' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Urbanized Area-linked RD. Provide a comma-separated list or an array of values. Possible values: Athol, Barnstable Town, Boston (MA-NH-RI), Great Barrington, Greenfield, Lee, Leominster-Fitchburg, Nantucket, Nashua (NH-MA), New Bedford, North Adams (MA-VT), North Brookfield, Pittsfield, Providence (RI-MA), Provincetown, RURAL, South Deerfield, Springfield (MA-CT), Vineyard Haven, Ware, Worcester (MA-CT).',
            ],
            'fd_aid_rte' => [
                'type' => 'string',
                'description' => 'Filter by Federal Aid Route-linked RD.',
            ],
            'facility' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Facility Type-linked RD. Provide a comma-separated list or an array of values. Possible values: Collector - Distributor, Doubledeck, Mainline roadway, Private Way, Ramp - NB/EB, Ramp - SB/WB, Rotary, Roundabout, Simple Ramp - Tunnel, Simple Ramp/ Channelized Turning Lane, Tunnel.',
            ],
            'operation' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Street Operation-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, One-way traffic, Two-way traffic.',
            ],
            'control' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Access Control-linked RD. Provide a comma-separated list or an array of values. Possible values: Full Access Control, No Access Control, Partial Access Control.',
            ],
            'peak_lane' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Number of Peak Hour Lanes-linked RD. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5.',
            ],
            'speed_lim' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Speed Limit-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, 5, 10, 15, 20, 24, 25, 30, 35, 40, 45, 50, 55, 60, 65, 99.',
            ],
            'streetname' => [
                'type' => 'string',
                'description' => 'Filter by Street Name-linked RD.',
            ],
            'fromstreetname' => [
                'type' => 'string',
                'description' => 'Filter by From Street Name-linked RD.',
            ],
            'tostreetname' => [
                'type' => 'string',
                'description' => 'Filter by To Street Name-linked RD.',
            ],
            'city' => [
                'type' => 'string',
                'description' => 'Filter by City-linked RD.',
            ],
            'struct_cnd' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Structural Condition-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, Deficient, Fair, Good, Intolerable.',
            ],
            'terrain' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Terrain-linked RD. Provide a comma-separated list or an array of values. Possible values: Level Terrain, Mountainous Terrain, Rolling Terrain.',
            ],
            'urban_loc_type' => [
                'type' => 'string',
                'description' => 'Filter by Urban Location Type-linked RD.',
            ],
            'aadt_deriv' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by AADT Derivation-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, AADT synchronized with other stations on the segment, Actual, Calculated from Partial Counts, Combined from child AADT\'s, Doubled from single direction, Estimate, Grown, Grown from Prior Year HPMS Network, Modified by Ramp Balancing, Pulled back from HPMS network estimation routine, Unknown Source.',
            ],
            'statn_num_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for AADT Station Number-linked RD.',
            ],
            'statn_num_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for AADT Station Number-linked RD.',
            ],
            'op_dir_sl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Opposing Direction Speed Limit-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, 15, 20, 25, 30, 35, 40, 45, 50, 55, 65, 99.',
            ],
            'shldr_ul_t' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Undivided Left Shoulder Type-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, None or Inadequate, Stabilized shoulder exists, Surfaced shoulder exists – bituminous concrete (AC), Surfaced shoulder exists – Portland Cement Concrete surface (PCC.',
            ],
            'shldr_ul_w' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Undivided Left Shoulder Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 8.0, 10.0, 12.0.',
            ],
            't_exc_type' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Truck Exclusion Type-linked RD. Provide a comma-separated list or an array of values. Possible values: All vehicles over 10 tons excluded, All vehicles over 2.5 tons excluded, All vehicles over 20 tons excluded, All vehicles over 2000 pounds excluded, All vehicles over 28 feet in length excluded, All vehicles over 3 tons excluded, All vehicles over 5 tons excluded, Cambridge Overnight Exclusions, Commercial vehicles over 5 tons carry capacity excluded, Hazardous Truck Route.',
            ],
            't_exc_time' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Truck Exclusion Time-linked RD. Provide a comma-separated list or an array of values. Possible values: 10PM to 6AM, 7 Days, 11PM to 6AM, 7 Days, 11PM to 6AM, NB Only, 7 Days, 11PM to 7AM, 7 Days, 24 Hours, 7 Days, 4PM to 6PM, 5AM to 8PM, 7 Days, 6AM to 10PM, 7 Days, 6AM to 6PM, 7 Days, 6AM to 7PM, 7 Days, 6PM to 6AM, 7 Days, 7AM to 11PM, 7 Days, 7AM to 6PM, 7 Days, 7PM to 7AM, 7 Days, 8AM to 930AM and 2PM to 330PM, School Days Only, 8PM to 6AM, 7 Days, 8PM to 7AM, 7 Days, 9PM to 6AM, 7 Days, 9PM to 7AM, 7 Days, None.',
            ],
            'f_f_class' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Federal Functional Classification-linked RD. Provide a comma-separated list or an array of values. Possible values: Interstate, Local, Major Collector, Minor Arterial, Minor Collector, Principal Arterial - Other, Principal Arterial - Other Freeways or Expressways.',
            ],
            'vehc_unit_numb' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vehicle Unit Number. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14.',
            ],
            'alc_suspd_type_descr' => [
                'type' => 'boolean',
                'description' => 'Filter by Alcohol Suspected.',
            ],
            'driver_age_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Driver Age.',
            ],
            'driver_age_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Driver Age.',
            ],
            'drvr_cntrb_circ_descr' => [
                'type' => 'string',
                'description' => 'Filter by Driver Contributing Circ..',
            ],
            'driver_distracted_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Driver Distracted. Provide a comma-separated list or an array of values. Possible values: External distraction (outside the vehicle), Manually operating an electronic device, Not Distracted, Not reported, Other activity (searching, eating, personal hygiene, etc.), Other activity, electronic device, Passenger, Reported but invalid, Talking on hand-held electronic device, Talking on hands-free electronic device, Unknown.',
            ],
            'drvr_lcn_state' => [
                'type' => 'string',
                'description' => 'Filter by Driver License State.',
            ],
            'drug_suspd_type_descr' => [
                'type' => 'boolean',
                'description' => 'Filter by Drugs Suspected.',
            ],
            'emergency_use_desc' => [
                'type' => 'boolean',
                'description' => 'Filter by Emergency Use.',
            ],
            'fmsca_rptbl_vl' => [
                'type' => 'boolean',
                'description' => 'Filter by FMCSA Reportable (Vehicle).',
            ],
            'haz_mat_placard_descr' => [
                'type' => 'boolean',
                'description' => 'Filter by Hazmat Placard.',
            ],
            'max_injr_svrty_vl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Maximum Injury Severity in Vehicle. Provide a comma-separated list or an array of values. Possible values: Deceased not caused by crash, Fatal injury (K), No Apparent Injury (O), No injury, Not Applicable, Not reported, Possible Injury (C), Reported but invalid, Suspected Minor Injury (B), Suspected Serious Injury (A), Unknown.',
            ],
            'most_hrmf_event' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Most Harmful Event (Vehicle). Provide a comma-separated list or an array of values. Possible values: Cargo/equipment loss or shift, Collision with animal - deer, Collision with animal - other, Collision with bridge, Collision with bridge overhead structure, Collision with curb, Collision with cyclist (bicycle, tricycle, unicycle, pedal car), Collision with ditch, Collision with embankment, Collision with fence, Collision with guardrail, Collision with highway traffic sign post, Collision with impact attenuator/crash cushion, Collision with light pole or other post/support, Collision with mail box, Collision with median barrier, Collision with moped, Collision with motor vehicle in traffic, Collision with other fixed object (wall, building, tunnel, etc.), Collision with other movable object, Collision with Other Vulnerable Users, Collision with overhead sign support, Collision with parked motor vehicle, Collision with pedestrian, Collision with railway vehicle (e.g., train, engine), Collision with tree, Collision with unknown fixed object, Collision with unknown movable object, Collision with utility pole, Collision with work zone maintenance equipment, Fire/explosion, Immersion, Invalid Code Specified, Jackknife, Not reported, Other, Other non-collision, Overturn/rollover, Unknown, Unknown non-collision.',
            ],
            'total_occpt_in_vehc_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Total Occupants in Vehicle.',
            ],
            'total_occpt_in_vehc_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Total Occupants in Vehicle.',
            ],
            'vehc_manr_act_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vehicle Action Prior to Crash. Provide a comma-separated list or an array of values. Possible values: Backing, Changing lanes, Entering traffic lane, Leaving traffic lane, Making U-turn, Not reported, Other, Overtaking/passing, Parked, Reported but invalid, Slowing or stopped in traffic, Travelling straight ahead, Turning left, Turning right, Unknown.',
            ],
            'vehc_confg_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vehicle Configuration. Provide a comma-separated list or an array of values. Possible values: All Terrain Vehicle (ATV), Bus (seats for 16 or more, including driver), Bus (seats for 9-15 people, including driver), Light truck(van, mini-van, pickup, sport utility), Low Speed Vehicle, MOPED, Motor home/recreational vehicle, Motorcycle, Not reported, Other, Passenger car, Registered farm equipment, Reported but invalid, Single-unit truck (2-axle, 6-tires), Single-unit truck (3-or-more axles), Snowmobile, Tractor/doubles, Tractor/semi-trailer, Tractor/triples, Truck tractor (bobtail), Truck/trailer, Unknown heavy truck, cannot classify, Unknown vehicle configuration.',
            ],
            'vehc_most_dmgd_area' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Most Damaged Area.',
            ],
            'owner_addr_city_town' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Owner City Town.',
            ],
            'owner_addr_state' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Owner State.',
            ],
            'vehc_reg_state' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Registration State.',
            ],
            'vehc_reg_type_code' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Registration Type.',
            ],
            'vehc_seq_events' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Sequence of Events.',
            ],
            'vehc_towed_from_scene' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vehicle Towed From Scene. Provide a comma-separated list or an array of values. Possible values: Invalid, No, Not reported, Reported but invalid, Unknown, Yes, other reason not disabled, Yes, vehicle or trailer disabled.',
            ],
            'trvl_dirc_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Travel Direction. Provide a comma-separated list or an array of values. Possible values: Eastbound, Northbound, Not reported, Reported but invalid, Southbound, Unknown, Westbound.',
            ],
            'vehicle_make_descr' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Make.',
            ],
            'vehicle_model_descr' => [
                'type' => 'string',
                'description' => 'Filter by Vehicle Model.',
            ],
            'vehicle_vin' => [
                'type' => 'string',
                'description' => 'Filter by VIN.',
            ],
            'driver_violation_cl' => [
                'type' => 'string',
                'description' => 'Filter by Driver Violation (All Vehicles).',
            ],
            'pers_numb_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Person Number.',
            ],
            'pers_numb_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Person Number.',
            ],
            'age_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Age.',
            ],
            'age_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Age.',
            ],
            'ejctn_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Ejection Description. Provide a comma-separated list or an array of values. Possible values: Not applicable, Not ejected, Not reported, Partially ejected, Reported but invalid, Totally ejected, Unknown.',
            ],
            'injy_stat_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Injury Type. Provide a comma-separated list or an array of values. Possible values: Deceased not caused by crash, Fatal injury (K), No Apparent Injury (O), No injury, Non-fatal injury - Possible, Not Applicable, Not reported, Possible Injury (C), Reported but invalid, Suspected Minor Injury (B), Suspected Serious Injury (A), Unknown.',
            ],
            'med_facly' => [
                'type' => 'string',
                'description' => 'Filter by Medical Facility.',
            ],
            'pers_addr_city' => [
                'type' => 'string',
                'description' => 'Filter by Person Address City.',
            ],
            'state_prvn_code' => [
                'type' => 'string',
                'description' => 'Filter by Person Address State.',
            ],
            'pers_type' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Person Type. Provide a comma-separated list or an array of values. Possible values: Driver, Passenger, Vulnerable User.',
            ],
            'prtc_sys_use_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Protective System Used. Provide a comma-separated list or an array of values. Possible values: Child safety seat used, Helmet used, Lap belt only used, None used - vehicle occupant, Not reported, Reported but invalid, Shoulder and lap belt used, Shoulder belt only used, Unknown.',
            ],
            'sfty_equp_desc_1' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Safety Equipment 1. Provide a comma-separated list or an array of values. Possible values: Helmet used, Lighting, None used, Not reported, Other, Protective pads used (elbows, knees, shins, etc.), Reflective clothing, Reported but invalid, Unknown.',
            ],
            'sfty_equp_desc_2' => [
                'type' => 'string',
                'description' => 'Filter by Safety Equipment 2.',
            ],
            'sex_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Sex. Provide a comma-separated list or an array of values. Possible values: F - Female, M - Male, N/A, Not reported, Reported but invalid, U - Unknown, X - Non-Binary.',
            ],
            'trnsd_by_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Transported By. Provide a comma-separated list or an array of values. Possible values: EMS Ground, EMS(Emergency Medical Service), Not reported, Not transported, Other, Police, Refused Transport, Reported but invalid, Unknown.',
            ],
            'non_mtrst_type_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable User Type (All Persons).',
            ],
            'non_mtrst_actn_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable User Action (All Persons).',
            ],
            'non_mtrst_loc_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable User Location (All Persons).',
            ],
            'non_mtrst_act_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Action. Provide a comma-separated list or an array of values. Possible values: Approaching or leaving vehicle, Entering or crossing location, None, Not reported, Other, Pushing vehicle, Standing, Unknown, Walking, running or cycling, Working, Working on vehicle.',
            ],
            'non_mtrst_cond_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Condition. Provide a comma-separated list or an array of values. Possible values: Apparently normal, Emotional (e.g., depression, angry, disturbed), Fell asleep, fainted, fatigue, etc., Illness, Not reported, Other, Physical impairment, Under the influence of medications/drugs/alcohol, Unknown.',
            ],
            'non_mtrst_loc_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Location. Provide a comma-separated list or an array of values. Possible values: At intersection but no crosswalk, In roadway, Island, Marked crosswalk at intersection (includes use of paint raised or other roadway material), Median (but not on shoulder), Non-intersection crosswalk, Not in roadway, Not reported, On-Street Bike Lanes, On-Street Buffered Bike Lanes, Other, Raised Crosswalk, Reported but invalid, Separated Bike Lanes, Shared-use path or trails Crossing, Shoulder, Sidewalk, Unknown.',
            ],
            'non_mtrst_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Type. Provide a comma-separated list or an array of values. Possible values: Bicyclist, Electric Personal Assistive Mobility Device User, Emergency Responder - Outside of vehicle, Farm Equipment Operator, Hand Cyclist, In-Line Skater, Motorized Bicyclist, Motorized Scooter Rider, Non-Motorized Scooter Rider, Non-Motorized Wheelchair User, Not reported, Other, Other Micromobility Device User, Pedestrian, Roadway Worker - Outside of vehicle, Roller Skater, Skateboarder, Train/Trolley passenger, Tricyclist, Unknown, Utility Worker – Outside of vehicle.',
            ],
            'non_mtrst_origin_dest_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Origin Destination (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10: Other, VU11: Other, VU17: Other, VU2: Going to or from a Delivery Vehicle, VU2: Going to or from a Delivery Vehicle / VU3: Going to or from a Delivery Vehicle / VU4: Going to or from a Delivery Vehicle, VU2: Going to or from a Mailbox, VU2: Going to or from a Mailbox / VU3: Going to or from a Mailbox, VU2: Going to or from a School Bus or a School Bus Stop, VU2: Going to or from a School Bus or a School Bus Stop / VU3: Going to or from a School Bus or a School Bus Stop, VU2: Going to or from an Ice Cream or Food Truck, VU2: Going to or from School (K-12), VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12), VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12) / VU4: Going to or from School (K-12), VU2: Going to or from Transit, VU2: Going to or from Transit / VU3: Going to or from Transit, VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit, VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit / VU5: Going to or from Transit / VU6: Going to or from Transit, VU2: Other, VU2: Other / VU3: Other, VU2: Other / VU3: Other / VU4: Other, VU2: Other / VU3: Other / VU4: Other / VU5: Other, VU2: Other / VU4: Other, VU2: Other / VU4: Other / VU5: Other, VU3: Going to or from a Delivery Vehicle, VU3: Going to or from a Mailbox, VU3: Going to or from a School Bus or a School Bus Stop, VU3: Going to or from an Ice Cream or Food Truck, VU3: Going to or from School (K-12), VU3: Going to or from Transit, VU3: Other, VU3: Other / VU4: Other, VU3: Other / VU5: Other, VU4: Going to or from a Delivery Vehicle, VU4: Going to or from School (K-12), VU4: Going to or from School (K-12) / VU5: Going to or from School (K-12), VU4: Going to or from Transit, VU4: Other, VU4: Other / VU5: Other, VU4: Other / VU5: Other / VU6: Other, VU5: Going to or from School (K-12), VU5: Going to or from Transit, VU5: Other, VU5: Other / VU6: Other, VU6: Going to or from School (K-12), VU6: Going to or from Transit, VU6: Other, VU7: Other, VU7: Other / VU8: Other, VU8: Other.',
            ],
            'non_mtrst_cntrb_circ_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable Users Contributing Circumstance (All Persons).',
            ],
            'non_mtrst_distracted_by_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Distracted By (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10:(Not Distracted), VU11:(Not Distracted), VU17:(Not Distracted), VU2:(Manually operating an electronic device (texting, typing, dialing)), VU2:(Manually operating an electronic device (texting, typing, dialing)),(Talking on hand-held electronic device), VU2:(Manually operating an electronic device (texting, typing, dialing)),(Utilizing listening device), VU2:(Not Distracted), VU2:(Not Distracted) VU3:(Not Distracted), VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted), VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted) VU5:(Passenger) VU6:(Not Distracted), VU2:(Not Distracted) VU3:(Passenger), VU2:(Not Distracted) VU4:(Not Distracted), VU2:(Not Distracted) VU4:(Not Distracted) VU5:(Not Distracted), VU2:(Not Distracted),(Manually operating an electronic device (texting, typing, dialing)), VU2:(Not Distracted),(Other activity (searching, eating, personal hygiene, etc.)), VU2:(Other activity (searching, eating, personal hygiene, etc.)), VU2:(Other activity (searching, eating, personal hygiene, etc.)),(Not Distracted), VU2:(Reported but invalid), VU2:(Talking on hand-held electronic device), VU2:(Talking on hands-free electronic device), VU2:(Utilizing listening device), VU2:(Utilizing listening device),(Not Distracted), VU3:(Manually operating an electronic device (texting, typing, dialing)), VU3:(Not Distracted), VU3:(Not Distracted) VU4:(Not Distracted), VU3:(Not Distracted) VU5:(Not Distracted), VU3:(Other activity (searching, eating, personal hygiene, etc.)), VU3:(Passenger), VU3:(Reported but invalid), VU3:(Talking on hand-held electronic device), VU3:(Utilizing listening device), VU4:(Manually operating an electronic device (texting, typing, dialing)), VU4:(Not Distracted), VU4:(Not Distracted) VU5:(Not Distracted), VU4:(Not Distracted) VU5:(Not Distracted) VU6:(Not Distracted), VU4:(Other activity (searching, eating, personal hygiene, etc.)), VU4:(Talking on hands-free electronic device), VU5:(Not Distracted), VU5:(Not Distracted) VU6:(Not Distracted), VU5:(Other activity (searching, eating, personal hygiene, etc.)), VU6:(Not Distracted), VU7:(Not Distracted), VU7:(Not Distracted) VU8:(Not Distracted) VU9:(Not Distracted), VU7:(Other activity (searching, eating, personal hygiene, etc.)), VU7:(Other activity (searching, eating, personal hygiene, etc.)) VU8:(Other activity (searching, eating, personal hygiene, etc.)), VU8:(Not Distracted).',
            ],
            'non_mtrst_alc_suspd_type_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Alcohol Suspected Type (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10: No, VU11: No, VU17: No, VU2: No, VU2: No / VU3: No, VU2: No / VU3: No / VU4: No, VU2: No / VU3: No / VU4: No / VU5: No, VU2: No / VU3: No / VU4: No / VU5: No / VU6: No, VU2: No / VU4: No, VU2: No / VU4: No / VU5: No, VU2: Yes, VU2: Yes / VU3: No, VU2: Yes / VU3: Yes, VU3: No, VU3: No / VU4: No, VU3: No / VU5: No, VU3: Yes, VU4: No, VU4: No / VU5: No, VU4: No / VU5: No / VU6: No, VU4: No / VU5: Yes / VU6: No, VU4: Yes, VU5: No, VU5: No / VU6: No, VU5: Yes, VU6: No, VU7: No, VU7: No / VU8: No.',
            ],
            'non_mtrst_drug_suspd_type_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Drug Suspected Type (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10: No, VU11: No, VU17: No, VU2: No, VU2: No / VU3: No, VU2: No / VU3: No / VU4: No, VU2: No / VU3: No / VU4: No / VU5: No, VU2: No / VU3: No / VU4: No / VU5: No / VU6: No, VU2: No / VU4: No, VU2: No / VU4: No / VU5: No, VU2: Yes, VU3: No, VU3: No / VU4: No, VU3: No / VU5: No, VU3: Yes, VU4: No, VU4: No / VU5: No, VU4: No / VU5: No / VU6: No, VU4: Yes, VU5: No, VU5: No / VU6: No, VU5: Yes, VU6: No, VU7: No, VU7: No / VU8: No, VU7: No / VU8: No / VU9: No.',
            ],
            'non_mtrst_event_seq_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable Users Sequence of Events (All Persons).',
            ],
            'traffic_control_type_descr' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable Users Traffic Control Device Type (All Persons).',
            ],
            'non_motorist_cntrb_circ_1' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Contribution 1. Provide a comma-separated list or an array of values. Possible values: Crossing of Roadway or Intersection, Dart/Dash, Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching), Distracted, Entering/Exiting Parked/Standing Vehicle, Failure to Obey Traffic Sign(s), Signal(s), or Officer(s), Failure to use Proper Crosswalk, Failure to Yield Right-Of-Way, Fleeing/Evading Law Enforcement, In Roadway (Standing, Lying, Working, Playing, etc.), Inattentive (Talking, Eating, etc.), None, Not reported, Not Visible (Dark Clothing, No Lighting, etc.), Other (Explain in Narrative), Passing, Reported but invalid, Traveling Wrong Way, Turn/Merge, Unknown.',
            ],
            'non_motorist_cntrb_circ_2' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Contribution 2. Provide a comma-separated list or an array of values. Possible values: Crossing of Roadway or Intersection, Dart/Dash, Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching), Distracted, Entering/Exiting Parked/Standing Vehicle, Failure to Obey Traffic Sign(s), Signal(s), or Officer(s), Failure to use Proper Crosswalk, Failure to Yield Right-Of-Way, Fleeing/Evading Law Enforcement, In Roadway (Standing, Lying, Working, Playing, etc.), Inattentive (Talking, Eating, etc.), None, Not reported, Not Visible (Dark Clothing, No Lighting, etc.), Other (Explain in Narrative), Passing, Traveling Wrong Way, Turn/Merge, Unknown.',
            ],
            'non_motorist_contact_point' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Contact Point. Provide a comma-separated list or an array of values. Possible values: 03 - Right, 06 - Rear, 09 - Left, Front, Not reported, Other, Reported but invalid, Unknown.',
            ],
            'non_motorist_distracted_by_1' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Distracted By 1. Provide a comma-separated list or an array of values. Possible values: Manually operating an electronic device (texting, typing, dialing), Not Distracted, Not reported, Other activity (searching, eating, personal hygiene, etc.), Passenger, Reported but invalid, Talking on hand-held electronic device, Talking on hands-free electronic device, Unknown, Utilizing listening device.',
            ],
            'non_motorist_distracted_by_2' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Distracted By 2. Provide a comma-separated list or an array of values. Possible values: Manually operating an electronic device (texting, typing, dialing), Not Distracted, Not reported, Other activity (searching, eating, personal hygiene, etc.), Talking on hand-held electronic device, Unknown, Utilizing listening device.',
            ],
            'non_motorist_ejection_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Ejection. Provide a comma-separated list or an array of values. Possible values: Not applicable, Not ejected, Not reported, Partially ejected, Totally ejected, Unknown.',
            ],
            'non_motorist_event_sequence_1' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Event Sequence 1. Provide a comma-separated list or an array of values. Possible values: Adjacent to Roadway (e.g., Shoulder, Median), Approaching or leaving vehicle, Changing lanes, Collision with door opening of parked car - Back Left, Collision with door opening of parked car - Front left, Collision with door opening of parked car - Front right, Collision with motor vehicle in transport, Collision with parked motor vehicle, stationary, Crossing Roadway, In Roadway – Other, Not reported, Other, Overtaking/passing, Pushing vehicle, Reported but invalid, Slowed or stopped, Traveling straight ahead, Turning left, Turning right, Unknown, Waiting to Cross Roadway, Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane), Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane), Walking/Cycling on Sidewalk, Working - other, Working in Trafficway (Incident Response), Working on vehicle.',
            ],
            'non_motorist_event_sequence_2' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Event Sequence 2. Provide a comma-separated list or an array of values. Possible values: Adjacent to Roadway (e.g., Shoulder, Median), Approaching or leaving vehicle, Changing lanes, Collision with door opening of parked car - Back right, Collision with door opening of parked car - Front left, Collision with door opening of parked car - Front right, Collision with motor vehicle in transport, Collision with parked motor vehicle, stationary, Crossing Roadway, In Roadway – Other, Not reported, Other, Overtaking/passing, Pushing vehicle, Slowed or stopped, Traveling straight ahead, Turning left, Turning right, Unknown, Waiting to Cross Roadway, Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane), Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane), Walking/Cycling on Sidewalk, Working - other, Working in Trafficway (Incident Response), Working on vehicle.',
            ],
            'non_motorist_event_sequence_3' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Event Sequence 3. Provide a comma-separated list or an array of values. Possible values: Adjacent to Roadway (e.g., Shoulder, Median), Approaching or leaving vehicle, Changing lanes, Collision with door opening of parked car - Back right, Collision with door opening of parked car - Front right, Collision with motor vehicle in transport, Collision with parked motor vehicle, stationary, Crossing Roadway, In Roadway – Other, Not reported, Other, Overtaking/passing, Reported but invalid, Slowed or stopped, Traveling straight ahead, Turning left, Turning right, Unknown, Waiting to Cross Roadway, Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane), Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane), Walking/Cycling on Sidewalk, Working - other, Working in Trafficway (Incident Response), Working on vehicle.',
            ],
            'non_motorist_event_sequence_4' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Event Sequence 4. Provide a comma-separated list or an array of values. Possible values: Adjacent to Roadway (e.g., Shoulder, Median), Approaching or leaving vehicle, Changing lanes, Collision with door opening of parked car - Front left, Collision with motor vehicle in transport, Collision with parked motor vehicle, stationary, Crossing Roadway, In Roadway – Other, Not reported, Other, Overtaking/passing, Slowed or stopped, Traveling straight ahead, Turning left, Unknown, Waiting to Cross Roadway, Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane), Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane), Walking/Cycling on Sidewalk, Working in Trafficway (Incident Response), Working on vehicle.',
            ],
            'non_motorist_driver_lic_state' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Driver License State Province. Provide a comma-separated list or an array of values. Possible values: 96, AK, AZ, CA, CO, CT, DC, FL, FR, GA, IA, ID, IL, KS, LA, MA, MD, ME, MI, MN, NC, NH, NJ, NV, NY, OH, ON, OT, PA, RI, SC, TN, TX, UT, VA, WA.',
            ],
            'non_motorist_primary_injury' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Primary Injury Area. Provide a comma-separated list or an array of values. Possible values: Head, Lower Limbs, None, Not reported, Other, Reported but invalid, Torso, Unknown, Upper Limbs.',
            ],
            'non_motorist_seating_position' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Seating Position. Provide a comma-separated list or an array of values. Possible values: Enclosed passenger area, Front seat - left side, Front seat – right side, Not reported, Other, Reported but invalid, Riding on exterior, Second seat - left side, Second seat - right side, Trailing unit, Unenclosed passenger area, Unknown.',
            ],
            'non_motorist_traffic_control' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Traffic Control Type. Provide a comma-separated list or an array of values. Possible values: None, Not Reported, Other, Person - Crossing guard, Unknown, VU Crossing Sign, VU Crossing Signal, VU Prohibited Sign.',
            ],
            'non_motorist_trapped_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Trapped. Provide a comma-separated list or an array of values. Possible values: Freed by mechanical means, Freed by nonmechanical means, Not reported, Not trapped, Unknown.',
            ],
            'non_motorist_origin_dest' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Origin Destination. Provide a comma-separated list or an array of values. Possible values: Going to or from a Delivery Vehicle, Going to or from a Mailbox, Going to or from a School Bus or a School Bus Stop, Going to or from an Ice Cream or Food Truck, Going to or from School (K-12), Going to or from Transit, Not Reported, Other, Reported but Invalid, Unknown.',
            ],
            'non_mtrst_test_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Test Type. Provide a comma-separated list or an array of values. Possible values: Blood, Breath, Not Reported, Other, Test Not Given, Unknown.',
            ],
            'non_mtrst_test_status_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Test Status. Provide a comma-separated list or an array of values. Possible values: Not reported, Test Given, Test Not Given, Test Refused, Unknown, Unknown, if tested.',
            ],
            'non_mtrst_test_result_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Test Result. Provide a comma-separated list or an array of values. Possible values: 0.00 - 0.01, 0.08 or greater, BAC Test Performed, Results Unknown, Not Reported, Positive Reading with no Actual Value, Test Not Given, Unknown, Unknown, if tested.',
            ],
            'crash_date_text_raw' => [
                'type' => 'string',
                'description' => 'Filter by Crash Date (Raw Text).',
            ],
            'crash_time_2_raw' => [
                'type' => 'string',
                'description' => 'Filter by Crash Time (Raw Text).',
            ],
            'objectid_source_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Source OBJECTID.',
            ],
            'objectid_source_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Source OBJECTID.',
            ],
        ],
    ],
    'App\\Models\\ConstructionOffHour' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'app_no',
                'label' => 'App No',
                'type' => 'text',
                'placeholder' => 'Enter App No',
            ],
            2 => [
                'name' => 'stop_datetime_start',
                'label' => 'Stop Datetime Start',
                'type' => 'date',
                'placeholder' => 'Start date for Stop Datetime',
            ],
            3 => [
                'name' => 'stop_datetime_end',
                'label' => 'Stop Datetime End',
                'type' => 'date',
                'placeholder' => 'End date for Stop Datetime',
            ],
            4 => [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'text',
                'placeholder' => 'Enter Address',
            ],
            5 => [
                'name' => 'ward',
                'label' => 'Ward',
                'type' => 'text',
                'placeholder' => 'Enter Ward',
            ],
            6 => [
                'name' => 'language_code',
                'label' => 'Language Code',
                'type' => 'text',
                'placeholder' => 'Enter Language Code',
            ],
            7 => [
                'name' => 'latitude_min',
                'label' => 'Latitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Latitude',
            ],
            8 => [
                'name' => 'latitude_max',
                'label' => 'Latitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Latitude',
            ],
            9 => [
                'name' => 'longitude_min',
                'label' => 'Longitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Longitude',
            ],
            10 => [
                'name' => 'longitude_max',
                'label' => 'Longitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Longitude',
            ],
        ],
        'contextData' => 'Dataset of Construction Off Hours. Filter by attributes like app no, date (Start Datetime), address.',
        'searchableColumns' => [
            0 => 'app_no',
            1 => 'address',
            2 => 'ward',
            3 => 'latitude',
            4 => 'longitude',
            5 => 'language_code',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Start Datetime\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Start Datetime\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'app_no' => [
                'type' => 'string',
                'description' => 'Filter by App No.',
            ],
            'stop_datetime_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Stop Datetime (YYYY-MM-DD)',
            ],
            'stop_datetime_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Stop Datetime (YYYY-MM-DD)',
            ],
            'address' => [
                'type' => 'string',
                'description' => 'Filter by Address.',
            ],
            'ward' => [
                'type' => 'string',
                'description' => 'Filter by Ward.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
            'latitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Latitude.',
            ],
            'latitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Latitude.',
            ],
            'longitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Longitude.',
            ],
            'longitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Longitude.',
            ],
        ],
    ],
    'App\\Models\\CambridgeCrimeReportData' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'file_number_external',
                'label' => 'File Number External',
                'type' => 'text',
                'placeholder' => 'Enter File Number External',
            ],
            2 => [
                'name' => 'date_of_report_start',
                'label' => 'Date Of Report Start',
                'type' => 'date',
                'placeholder' => 'Start date for Date Of Report',
            ],
            3 => [
                'name' => 'date_of_report_end',
                'label' => 'Date Of Report End',
                'type' => 'date',
                'placeholder' => 'End date for Date Of Report',
            ],
            4 => [
                'name' => 'crime_datetime_raw',
                'label' => 'Crime Datetime Raw',
                'type' => 'text',
                'placeholder' => 'Enter Crime Datetime Raw',
            ],
            5 => [
                'name' => 'crime_end_time_start',
                'label' => 'Crime End Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Crime End Time',
            ],
            6 => [
                'name' => 'crime_end_time_end',
                'label' => 'Crime End Time End',
                'type' => 'date',
                'placeholder' => 'End date for Crime End Time',
            ],
            7 => [
                'name' => 'crime',
                'label' => 'Crime',
                'type' => 'text',
                'placeholder' => 'Enter Crime',
            ],
            8 => [
                'name' => 'reporting_area',
                'label' => 'Reporting Area',
                'type' => 'text',
                'placeholder' => 'Enter Reporting Area',
            ],
            9 => [
                'name' => 'neighborhood',
                'label' => 'Neighborhood',
                'type' => 'text',
                'placeholder' => 'Enter Neighborhood',
            ],
            10 => [
                'name' => 'location_address',
                'label' => 'Location Address',
                'type' => 'text',
                'placeholder' => 'Enter Location Address',
            ],
            11 => [
                'name' => 'latitude',
                'label' => 'Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Latitude',
            ],
            12 => [
                'name' => 'longitude',
                'label' => 'Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Longitude',
            ],
            13 => [
                'name' => 'crime_details',
                'label' => 'Crime Details',
                'type' => 'text',
                'placeholder' => 'Enter Crime Details',
            ],
        ],
        'contextData' => 'Dataset of Cambridge Crime Reports. Filter by attributes like file number external, crime datetime raw, date (Crime Start Time).',
        'searchableColumns' => [
            0 => 'id',
            1 => 'file_number_external',
            2 => 'crime_datetime_raw',
            3 => 'crime',
            4 => 'reporting_area',
            5 => 'neighborhood',
            6 => 'location_address',
            7 => 'latitude',
            8 => 'longitude',
            9 => 'crime_details',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Crime Start Time\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Crime Start Time\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'file_number_external' => [
                'type' => 'string',
                'description' => 'Filter by File Number External.',
            ],
            'date_of_report_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Date Of Report (YYYY-MM-DD)',
            ],
            'date_of_report_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Date Of Report (YYYY-MM-DD)',
            ],
            'crime_datetime_raw' => [
                'type' => 'string',
                'description' => 'Filter by Crime Datetime Raw.',
            ],
            'crime_end_time_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Crime End Time (YYYY-MM-DD)',
            ],
            'crime_end_time_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Crime End Time (YYYY-MM-DD)',
            ],
            'crime' => [
                'type' => 'string',
                'description' => 'Filter by Crime.',
            ],
            'reporting_area' => [
                'type' => 'string',
                'description' => 'Filter by Reporting Area.',
            ],
            'neighborhood' => [
                'type' => 'string',
                'description' => 'Filter by Neighborhood.',
            ],
            'location_address' => [
                'type' => 'string',
                'description' => 'Filter by Location Address.',
            ],
            'latitude' => [
                'type' => 'string',
                'description' => 'Filter by Latitude.',
            ],
            'longitude' => [
                'type' => 'string',
                'description' => 'Filter by Longitude.',
            ],
            'crime_details' => [
                'type' => 'string',
                'description' => 'Filter by Crime Details.',
            ],
        ],
    ],
    'App\\Models\\CrimeData' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'incident_number',
                'label' => 'Incident Number',
                'type' => 'text',
                'placeholder' => 'Enter Incident Number',
            ],
            2 => [
                'name' => 'offense_code_min',
                'label' => 'Offense Code Min',
                'type' => 'number',
                'placeholder' => 'Min value for Offense Code',
            ],
            3 => [
                'name' => 'offense_code_max',
                'label' => 'Offense Code Max',
                'type' => 'number',
                'placeholder' => 'Max value for Offense Code',
            ],
            4 => [
                'name' => 'language_code',
                'label' => 'Language Code',
                'type' => 'text',
                'placeholder' => 'Enter Language Code',
            ],
            5 => [
                'name' => 'offense_code_group',
                'label' => 'Offense Code Group',
                'type' => 'text',
                'placeholder' => 'Enter Offense Code Group',
            ],
            6 => [
                'name' => 'offense_description',
                'label' => 'Offense Description',
                'type' => 'text',
                'placeholder' => 'Enter Offense Description',
            ],
            7 => [
                'name' => 'district',
                'label' => 'District',
                'type' => 'text',
                'placeholder' => 'Enter District',
            ],
            8 => [
                'name' => 'reporting_area',
                'label' => 'Reporting Area',
                'type' => 'text',
                'placeholder' => 'Enter Reporting Area',
            ],
            9 => [
                'name' => 'shooting_min',
                'label' => 'Shooting Min',
                'type' => 'number',
                'placeholder' => 'Min value for Shooting',
            ],
            10 => [
                'name' => 'shooting_max',
                'label' => 'Shooting Max',
                'type' => 'number',
                'placeholder' => 'Max value for Shooting',
            ],
            11 => [
                'name' => 'year_min',
                'label' => 'Year Min',
                'type' => 'number',
                'placeholder' => 'Min value for Year',
            ],
            12 => [
                'name' => 'year_max',
                'label' => 'Year Max',
                'type' => 'number',
                'placeholder' => 'Max value for Year',
            ],
            13 => [
                'name' => 'month_min',
                'label' => 'Month Min',
                'type' => 'number',
                'placeholder' => 'Min value for Month',
            ],
            14 => [
                'name' => 'month_max',
                'label' => 'Month Max',
                'type' => 'number',
                'placeholder' => 'Max value for Month',
            ],
            15 => [
                'name' => 'day_of_week',
                'label' => 'Day Of Week',
                'type' => 'text',
                'placeholder' => 'Enter Day Of Week',
            ],
            16 => [
                'name' => 'hour_min',
                'label' => 'Hour Min',
                'type' => 'number',
                'placeholder' => 'Min value for Hour',
            ],
            17 => [
                'name' => 'hour_max',
                'label' => 'Hour Max',
                'type' => 'number',
                'placeholder' => 'Max value for Hour',
            ],
            18 => [
                'name' => 'ucr_part',
                'label' => 'Ucr Part',
                'type' => 'text',
                'placeholder' => 'Enter Ucr Part',
            ],
            19 => [
                'name' => 'street',
                'label' => 'Street',
                'type' => 'text',
                'placeholder' => 'Enter Street',
            ],
            20 => [
                'name' => 'lat_min',
                'label' => 'Lat Min',
                'type' => 'number',
                'placeholder' => 'Min value for Lat',
            ],
            21 => [
                'name' => 'lat_max',
                'label' => 'Lat Max',
                'type' => 'number',
                'placeholder' => 'Max value for Lat',
            ],
            22 => [
                'name' => 'long_min',
                'label' => 'Long Min',
                'type' => 'number',
                'placeholder' => 'Min value for Long',
            ],
            23 => [
                'name' => 'long_max',
                'label' => 'Long Max',
                'type' => 'number',
                'placeholder' => 'Max value for Long',
            ],
            24 => [
                'name' => 'location',
                'label' => 'Location',
                'type' => 'text',
                'placeholder' => 'Enter Location',
            ],
            25 => [
                'name' => 'source_city',
                'label' => 'Source City',
                'type' => 'text',
                'placeholder' => 'Enter Source City',
            ],
            26 => [
                'name' => 'crime_start_time_start',
                'label' => 'Crime Start Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Crime Start Time',
            ],
            27 => [
                'name' => 'crime_start_time_end',
                'label' => 'Crime Start Time End',
                'type' => 'date',
                'placeholder' => 'End date for Crime Start Time',
            ],
            28 => [
                'name' => 'crime_end_time_start',
                'label' => 'Crime End Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Crime End Time',
            ],
            29 => [
                'name' => 'crime_end_time_end',
                'label' => 'Crime End Time End',
                'type' => 'date',
                'placeholder' => 'End date for Crime End Time',
            ],
            30 => [
                'name' => 'crime_details',
                'label' => 'Crime Details',
                'type' => 'text',
                'placeholder' => 'Enter Crime Details',
            ],
        ],
        'contextData' => 'Dataset of Boston Crimes. Filter by attributes like incident number, language code, offense code group.',
        'searchableColumns' => [
            0 => 'incident_number',
            1 => 'offense_code',
            2 => 'offense_code_group',
            3 => 'offense_description',
            4 => 'district',
            5 => 'reporting_area',
            6 => 'shooting',
            7 => 'year',
            8 => 'month',
            9 => 'day_of_week',
            10 => 'hour',
            11 => 'ucr_part',
            12 => 'street',
            13 => 'location',
            14 => 'language_code',
            15 => 'crime_details',
            16 => 'crime_start_time',
            17 => 'crime_end_time',
            18 => 'source_city',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Occurred On Date\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Occurred On Date\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'incident_number' => [
                'type' => 'string',
                'description' => 'Filter by Incident Number.',
            ],
            'offense_code_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Offense Code.',
            ],
            'offense_code_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Offense Code.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
            'offense_code_group' => [
                'type' => 'string',
                'description' => 'Filter by Offense Code Group.',
            ],
            'offense_description' => [
                'type' => 'string',
                'description' => 'Filter by Offense Description.',
            ],
            'district' => [
                'type' => 'string',
                'description' => 'Filter by District.',
            ],
            'reporting_area' => [
                'type' => 'string',
                'description' => 'Filter by Reporting Area.',
            ],
            'shooting_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Shooting.',
            ],
            'shooting_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Shooting.',
            ],
            'year_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Year.',
            ],
            'year_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Year.',
            ],
            'month_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Month.',
            ],
            'month_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Month.',
            ],
            'day_of_week' => [
                'type' => 'string',
                'description' => 'Filter by Day Of Week.',
            ],
            'hour_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Hour.',
            ],
            'hour_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Hour.',
            ],
            'ucr_part' => [
                'type' => 'string',
                'description' => 'Filter by Ucr Part.',
            ],
            'street' => [
                'type' => 'string',
                'description' => 'Filter by Street.',
            ],
            'lat_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Lat.',
            ],
            'lat_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Lat.',
            ],
            'long_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Long.',
            ],
            'long_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Long.',
            ],
            'location' => [
                'type' => 'string',
                'description' => 'Filter by Location.',
            ],
            'source_city' => [
                'type' => 'string',
                'description' => 'Filter by Source City.',
            ],
            'crime_start_time_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Crime Start Time (YYYY-MM-DD)',
            ],
            'crime_start_time_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Crime Start Time (YYYY-MM-DD)',
            ],
            'crime_end_time_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Crime End Time (YYYY-MM-DD)',
            ],
            'crime_end_time_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Crime End Time (YYYY-MM-DD)',
            ],
            'crime_details' => [
                'type' => 'string',
                'description' => 'Filter by Crime Details.',
            ],
        ],
    ],
    'App\\Models\\ThreeOneOneCase' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'case_enquiry_id_min',
                'label' => 'Case Enquiry Id Min',
                'type' => 'number',
                'placeholder' => 'Min value for Case Enquiry Id',
            ],
            2 => [
                'name' => 'case_enquiry_id_max',
                'label' => 'Case Enquiry Id Max',
                'type' => 'number',
                'placeholder' => 'Max value for Case Enquiry Id',
            ],
            3 => [
                'name' => 'sla_target_dt',
                'label' => 'Sla Target Dt',
                'type' => 'text',
                'placeholder' => 'Enter Sla Target Dt',
            ],
            4 => [
                'name' => 'closed_dt_start',
                'label' => 'Closed Dt Start',
                'type' => 'date',
                'placeholder' => 'Start date for Closed Dt',
            ],
            5 => [
                'name' => 'closed_dt_end',
                'label' => 'Closed Dt End',
                'type' => 'date',
                'placeholder' => 'End date for Closed Dt',
            ],
            6 => [
                'name' => 'on_time',
                'label' => 'On Time',
                'type' => 'text',
                'placeholder' => 'Enter On Time',
            ],
            7 => [
                'name' => 'case_status',
                'label' => 'Case Status',
                'type' => 'text',
                'placeholder' => 'Enter Case Status',
            ],
            8 => [
                'name' => 'closure_reason',
                'label' => 'Closure Reason',
                'type' => 'text',
                'placeholder' => 'Enter Closure Reason',
            ],
            9 => [
                'name' => 'case_title',
                'label' => 'Case Title',
                'type' => 'text',
                'placeholder' => 'Enter Case Title',
            ],
            10 => [
                'name' => 'subject',
                'label' => 'Subject',
                'type' => 'text',
                'placeholder' => 'Enter Subject',
            ],
            11 => [
                'name' => 'reason',
                'label' => 'Reason',
                'type' => 'text',
                'placeholder' => 'Enter Reason',
            ],
            12 => [
                'name' => 'type',
                'label' => 'Type',
                'type' => 'text',
                'placeholder' => 'Enter Type',
            ],
            13 => [
                'name' => 'queue',
                'label' => 'Queue',
                'type' => 'text',
                'placeholder' => 'Enter Queue',
            ],
            14 => [
                'name' => 'department',
                'label' => 'Department',
                'type' => 'text',
                'placeholder' => 'Enter Department',
            ],
            15 => [
                'name' => 'submitted_photo',
                'label' => 'Submitted Photo',
                'type' => 'text',
                'placeholder' => 'Enter Submitted Photo',
            ],
            16 => [
                'name' => 'closed_photo',
                'label' => 'Closed Photo',
                'type' => 'text',
                'placeholder' => 'Enter Closed Photo',
            ],
            17 => [
                'name' => 'location',
                'label' => 'Location',
                'type' => 'text',
                'placeholder' => 'Enter Location',
            ],
            18 => [
                'name' => 'fire_district',
                'label' => 'Fire District',
                'type' => 'text',
                'placeholder' => 'Enter Fire District',
            ],
            19 => [
                'name' => 'pwd_district',
                'label' => 'Pwd District',
                'type' => 'text',
                'placeholder' => 'Enter Pwd District',
            ],
            20 => [
                'name' => 'city_council_district',
                'label' => 'City Council District',
                'type' => 'text',
                'placeholder' => 'Enter City Council District',
            ],
            21 => [
                'name' => 'police_district',
                'label' => 'Police District',
                'type' => 'text',
                'placeholder' => 'Enter Police District',
            ],
            22 => [
                'name' => 'neighborhood',
                'label' => 'Neighborhood',
                'type' => 'text',
                'placeholder' => 'Enter Neighborhood',
            ],
            23 => [
                'name' => 'neighborhood_services_district',
                'label' => 'Neighborhood Services District',
                'type' => 'text',
                'placeholder' => 'Enter Neighborhood Services District',
            ],
            24 => [
                'name' => 'ward',
                'label' => 'Ward',
                'type' => 'text',
                'placeholder' => 'Enter Ward',
            ],
            25 => [
                'name' => 'precinct',
                'label' => 'Precinct',
                'type' => 'text',
                'placeholder' => 'Enter Precinct',
            ],
            26 => [
                'name' => 'location_street_name',
                'label' => 'Location Street Name',
                'type' => 'text',
                'placeholder' => 'Enter Location Street Name',
            ],
            27 => [
                'name' => 'location_zipcode_min',
                'label' => 'Location Zipcode Min',
                'type' => 'number',
                'placeholder' => 'Min value for Location Zipcode',
            ],
            28 => [
                'name' => 'location_zipcode_max',
                'label' => 'Location Zipcode Max',
                'type' => 'number',
                'placeholder' => 'Max value for Location Zipcode',
            ],
            29 => [
                'name' => 'latitude_min',
                'label' => 'Latitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Latitude',
            ],
            30 => [
                'name' => 'latitude_max',
                'label' => 'Latitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Latitude',
            ],
            31 => [
                'name' => 'longitude_min',
                'label' => 'Longitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Longitude',
            ],
            32 => [
                'name' => 'longitude_max',
                'label' => 'Longitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Longitude',
            ],
            33 => [
                'name' => 'source',
                'label' => 'Source',
                'type' => 'text',
                'placeholder' => 'Enter Source',
            ],
            34 => [
                'name' => 'checksum',
                'label' => 'Checksum',
                'type' => 'text',
                'placeholder' => 'Enter Checksum',
            ],
            35 => [
                'name' => 'language_code',
                'label' => 'Language Code',
                'type' => 'text',
                'placeholder' => 'Enter Language Code',
            ],
            36 => [
                'name' => 'ward_number',
                'label' => 'Ward Number',
                'type' => 'text',
                'placeholder' => 'Enter Ward Number',
            ],
            37 => [
                'name' => 'threeoneonedescription',
                'label' => 'Threeoneonedescription',
                'type' => 'text',
                'placeholder' => 'Enter Threeoneonedescription',
            ],
            38 => [
                'name' => 'source_city',
                'label' => 'Source City',
                'type' => 'text',
                'placeholder' => 'Enter Source City',
            ],
        ],
        'contextData' => 'Dataset of 311 Cases. Filter by attributes like date (Open Dt), sla target dt, on time.',
        'searchableColumns' => [
            0 => 'id',
            1 => 'case_enquiry_id',
            2 => 'open_dt',
            3 => 'sla_target_dt',
            4 => 'closed_dt',
            5 => 'on_time',
            6 => 'case_status',
            7 => 'closure_reason',
            8 => 'case_title',
            9 => 'subject',
            10 => 'reason',
            11 => 'type',
            12 => 'queue',
            13 => 'department',
            14 => 'submitted_photo',
            15 => 'closed_photo',
            16 => 'location',
            17 => 'fire_district',
            18 => 'pwd_district',
            19 => 'city_council_district',
            20 => 'police_district',
            21 => 'neighborhood',
            22 => 'neighborhood_services_district',
            23 => 'ward',
            24 => 'precinct',
            25 => 'location_street_name',
            26 => 'location_zipcode',
            27 => 'latitude',
            28 => 'longitude',
            29 => 'source',
            30 => 'ward_number',
            31 => 'language_code',
            32 => 'threeoneonedescription',
            33 => 'source_city',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Open Dt\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Open Dt\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'case_enquiry_id_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Case Enquiry Id.',
            ],
            'case_enquiry_id_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Case Enquiry Id.',
            ],
            'sla_target_dt' => [
                'type' => 'string',
                'description' => 'Filter by Sla Target Dt.',
            ],
            'closed_dt_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Closed Dt (YYYY-MM-DD)',
            ],
            'closed_dt_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Closed Dt (YYYY-MM-DD)',
            ],
            'on_time' => [
                'type' => 'string',
                'description' => 'Filter by On Time.',
            ],
            'case_status' => [
                'type' => 'string',
                'description' => 'Filter by Case Status.',
            ],
            'closure_reason' => [
                'type' => 'string',
                'description' => 'Filter by Closure Reason.',
            ],
            'case_title' => [
                'type' => 'string',
                'description' => 'Filter by Case Title.',
            ],
            'subject' => [
                'type' => 'string',
                'description' => 'Filter by Subject.',
            ],
            'reason' => [
                'type' => 'string',
                'description' => 'Filter by Reason.',
            ],
            'type' => [
                'type' => 'string',
                'description' => 'Filter by Type.',
            ],
            'queue' => [
                'type' => 'string',
                'description' => 'Filter by Queue.',
            ],
            'department' => [
                'type' => 'string',
                'description' => 'Filter by Department.',
            ],
            'submitted_photo' => [
                'type' => 'string',
                'description' => 'Filter by Submitted Photo.',
            ],
            'closed_photo' => [
                'type' => 'string',
                'description' => 'Filter by Closed Photo.',
            ],
            'location' => [
                'type' => 'string',
                'description' => 'Filter by Location.',
            ],
            'fire_district' => [
                'type' => 'string',
                'description' => 'Filter by Fire District.',
            ],
            'pwd_district' => [
                'type' => 'string',
                'description' => 'Filter by Pwd District.',
            ],
            'city_council_district' => [
                'type' => 'string',
                'description' => 'Filter by City Council District.',
            ],
            'police_district' => [
                'type' => 'string',
                'description' => 'Filter by Police District.',
            ],
            'neighborhood' => [
                'type' => 'string',
                'description' => 'Filter by Neighborhood.',
            ],
            'neighborhood_services_district' => [
                'type' => 'string',
                'description' => 'Filter by Neighborhood Services District.',
            ],
            'ward' => [
                'type' => 'string',
                'description' => 'Filter by Ward.',
            ],
            'precinct' => [
                'type' => 'string',
                'description' => 'Filter by Precinct.',
            ],
            'location_street_name' => [
                'type' => 'string',
                'description' => 'Filter by Location Street Name.',
            ],
            'location_zipcode_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Location Zipcode.',
            ],
            'location_zipcode_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Location Zipcode.',
            ],
            'latitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Latitude.',
            ],
            'latitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Latitude.',
            ],
            'longitude_min' => [
                'type' => 'number',
                'description' => 'Minimum value for Longitude.',
            ],
            'longitude_max' => [
                'type' => 'number',
                'description' => 'Maximum value for Longitude.',
            ],
            'source' => [
                'type' => 'string',
                'description' => 'Filter by Source.',
            ],
            'checksum' => [
                'type' => 'string',
                'description' => 'Filter by Checksum.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
            'ward_number' => [
                'type' => 'string',
                'description' => 'Filter by Ward Number.',
            ],
            'threeoneonedescription' => [
                'type' => 'string',
                'description' => 'Filter by Threeoneonedescription.',
            ],
            'source_city' => [
                'type' => 'string',
                'description' => 'Filter by Source City.',
            ],
        ],
    ],
    'App\\Models\\CambridgeSanitaryInspectionData' => [
        'filterableFieldsDescription' => [
            0 => [
                'name' => 'search_term',
                'label' => 'General Search',
                'type' => 'text',
                'placeholder' => 'Search across all fields...',
            ],
            1 => [
                'name' => 'case_number_group',
                'label' => 'Case Number Group',
                'type' => 'text',
                'placeholder' => 'Enter Case Number Group',
            ],
            2 => [
                'name' => 'address',
                'label' => 'Address',
                'type' => 'text',
                'placeholder' => 'Enter Address',
            ],
            3 => [
                'name' => 'parcel',
                'label' => 'Parcel',
                'type' => 'text',
                'placeholder' => 'Enter Parcel',
            ],
            4 => [
                'name' => 'establishment_name',
                'label' => 'Establishment Name',
                'type' => 'text',
                'placeholder' => 'Enter Establishment Name',
            ],
            5 => [
                'name' => 'code_number',
                'label' => 'Code Number',
                'type' => 'text',
                'placeholder' => 'Enter Code Number',
            ],
            6 => [
                'name' => 'code_description',
                'label' => 'Code Description',
                'type' => 'text',
                'placeholder' => 'Enter Code Description',
            ],
            7 => [
                'name' => 'inspector_comments',
                'label' => 'Inspector Comments',
                'type' => 'text',
                'placeholder' => 'Enter Inspector Comments',
            ],
            8 => [
                'name' => 'case_open_date_start',
                'label' => 'Case Open Date Start',
                'type' => 'date',
                'placeholder' => 'Start date for Case Open Date',
            ],
            9 => [
                'name' => 'case_open_date_end',
                'label' => 'Case Open Date End',
                'type' => 'date',
                'placeholder' => 'End date for Case Open Date',
            ],
            10 => [
                'name' => 'case_closed_date_start',
                'label' => 'Case Closed Date Start',
                'type' => 'date',
                'placeholder' => 'Start date for Case Closed Date',
            ],
            11 => [
                'name' => 'case_closed_date_end',
                'label' => 'Case Closed Date End',
                'type' => 'date',
                'placeholder' => 'End date for Case Closed Date',
            ],
            12 => [
                'name' => 'date_corrected_start',
                'label' => 'Date Corrected Start',
                'type' => 'date',
                'placeholder' => 'Start date for Date Corrected',
            ],
            13 => [
                'name' => 'date_corrected_end',
                'label' => 'Date Corrected End',
                'type' => 'date',
                'placeholder' => 'End date for Date Corrected',
            ],
            14 => [
                'name' => 'code_case_status',
                'label' => 'Code Case Status',
                'type' => 'text',
                'placeholder' => 'Enter Code Case Status',
            ],
            15 => [
                'name' => 'latitude',
                'label' => 'Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Latitude',
            ],
            16 => [
                'name' => 'longitude',
                'label' => 'Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Longitude',
            ],
            17 => [
                'name' => 'geocoded_column_text',
                'label' => 'Geocoded Column Text',
                'type' => 'text',
                'placeholder' => 'Enter Geocoded Column Text',
            ],
            18 => [
                'name' => 'unique_violation_identifier',
                'label' => 'Unique Violation Identifier',
                'type' => 'text',
                'placeholder' => 'Enter Unique Violation Identifier',
            ],
        ],
        'contextData' => 'Dataset of Cambridge Sanitary Inspections. Filter by attributes like case number group, address, parcel.',
        'searchableColumns' => [
            0 => 'id',
            1 => 'case_number_group',
            2 => 'address',
            3 => 'parcel',
            4 => 'establishment_name',
            5 => 'code_number',
            6 => 'code_description',
            7 => 'inspector_comments',
            8 => 'code_case_status',
            9 => 'latitude',
            10 => 'longitude',
            11 => 'geocoded_column_text',
            12 => 'unique_violation_identifier',
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'Date Cited\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'Date Cited\' (YYYY-MM-DD)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Limit the number of records. Default is 1000, max 5000.',
            ],
            'case_number_group' => [
                'type' => 'string',
                'description' => 'Filter by Case Number Group.',
            ],
            'address' => [
                'type' => 'string',
                'description' => 'Filter by Address.',
            ],
            'parcel' => [
                'type' => 'string',
                'description' => 'Filter by Parcel.',
            ],
            'establishment_name' => [
                'type' => 'string',
                'description' => 'Filter by Establishment Name.',
            ],
            'code_number' => [
                'type' => 'string',
                'description' => 'Filter by Code Number.',
            ],
            'code_description' => [
                'type' => 'string',
                'description' => 'Filter by Code Description.',
            ],
            'inspector_comments' => [
                'type' => 'string',
                'description' => 'Filter by Inspector Comments.',
            ],
            'case_open_date_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Case Open Date (YYYY-MM-DD)',
            ],
            'case_open_date_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Case Open Date (YYYY-MM-DD)',
            ],
            'case_closed_date_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Case Closed Date (YYYY-MM-DD)',
            ],
            'case_closed_date_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Case Closed Date (YYYY-MM-DD)',
            ],
            'date_corrected_start' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for Date Corrected (YYYY-MM-DD)',
            ],
            'date_corrected_end' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for Date Corrected (YYYY-MM-DD)',
            ],
            'code_case_status' => [
                'type' => 'string',
                'description' => 'Filter by Code Case Status.',
            ],
            'latitude' => [
                'type' => 'string',
                'description' => 'Filter by Latitude.',
            ],
            'longitude' => [
                'type' => 'string',
                'description' => 'Filter by Longitude.',
            ],
            'geocoded_column_text' => [
                'type' => 'string',
                'description' => 'Filter by Geocoded Column Text.',
            ],
            'unique_violation_identifier' => [
                'type' => 'string',
                'description' => 'Filter by Unique Violation Identifier.',
            ],
        ],
    ],
];
