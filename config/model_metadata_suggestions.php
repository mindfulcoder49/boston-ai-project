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
                'type' => 'multiselect',
                'placeholder' => 'Select Status',
                'options' => [
                    0 => [
                        'value' => 'Closed',
                        'label' => 'Closed',
                    ],
                    1 => [
                        'value' => 'Open',
                        'label' => 'Open',
                    ],
                    2 => [
                        'value' => 'Void',
                        'label' => 'Void',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Violation Suffix',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => 'AV',
                        'label' => 'AV',
                    ],
                    2 => [
                        'value' => 'AVE',
                        'label' => 'AVE',
                    ],
                    3 => [
                        'value' => 'BL',
                        'label' => 'BL',
                    ],
                    4 => [
                        'value' => 'BLVD',
                        'label' => 'BLVD',
                    ],
                    5 => [
                        'value' => 'CC',
                        'label' => 'CC',
                    ],
                    6 => [
                        'value' => 'CI',
                        'label' => 'CI',
                    ],
                    7 => [
                        'value' => 'CIR',
                        'label' => 'CIR',
                    ],
                    8 => [
                        'value' => 'CT',
                        'label' => 'CT',
                    ],
                    9 => [
                        'value' => 'DR',
                        'label' => 'DR',
                    ],
                    10 => [
                        'value' => 'GRN',
                        'label' => 'GRN',
                    ],
                    11 => [
                        'value' => 'HW',
                        'label' => 'HW',
                    ],
                    12 => [
                        'value' => 'LN',
                        'label' => 'LN',
                    ],
                    13 => [
                        'value' => 'PARK',
                        'label' => 'PARK',
                    ],
                    14 => [
                        'value' => 'PK',
                        'label' => 'PK',
                    ],
                    15 => [
                        'value' => 'PL',
                        'label' => 'PL',
                    ],
                    16 => [
                        'value' => 'PW',
                        'label' => 'PW',
                    ],
                    17 => [
                        'value' => 'PZ',
                        'label' => 'PZ',
                    ],
                    18 => [
                        'value' => 'RD',
                        'label' => 'RD',
                    ],
                    19 => [
                        'value' => 'RO',
                        'label' => 'RO',
                    ],
                    20 => [
                        'value' => 'SQ',
                        'label' => 'SQ',
                    ],
                    21 => [
                        'value' => 'ST',
                        'label' => 'ST',
                    ],
                    22 => [
                        'value' => 'TE',
                        'label' => 'TE',
                    ],
                    23 => [
                        'value' => 'TER',
                        'label' => 'TER',
                    ],
                    24 => [
                        'value' => 'WAY',
                        'label' => 'WAY',
                    ],
                    25 => [
                        'value' => 'WH',
                        'label' => 'WH',
                    ],
                    26 => [
                        'value' => 'WY',
                        'label' => 'WY',
                    ],
                ],
            ],
            11 => [
                'name' => 'violation_city',
                'label' => 'Violation City',
                'type' => 'multiselect',
                'placeholder' => 'Select Violation City',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => 'Allston',
                        'label' => 'Allston',
                    ],
                    2 => [
                        'value' => 'Allston/Boston',
                        'label' => 'Allston/Boston',
                    ],
                    3 => [
                        'value' => 'Back Bay/',
                        'label' => 'Back Bay/',
                    ],
                    4 => [
                        'value' => 'Boston',
                        'label' => 'Boston',
                    ],
                    5 => [
                        'value' => 'Boston/West End',
                        'label' => 'Boston/West End',
                    ],
                    6 => [
                        'value' => 'Brighton',
                        'label' => 'Brighton',
                    ],
                    7 => [
                        'value' => 'Brighton/',
                        'label' => 'Brighton/',
                    ],
                    8 => [
                        'value' => 'Charlestown',
                        'label' => 'Charlestown',
                    ],
                    9 => [
                        'value' => 'Charlestown/',
                        'label' => 'Charlestown/',
                    ],
                    10 => [
                        'value' => 'Charlestown666',
                        'label' => 'Charlestown666',
                    ],
                    11 => [
                        'value' => 'Chestnut Hill',
                        'label' => 'Chestnut Hill',
                    ],
                    12 => [
                        'value' => 'Chinatown',
                        'label' => 'Chinatown',
                    ],
                    13 => [
                        'value' => 'Dorchester',
                        'label' => 'Dorchester',
                    ],
                    14 => [
                        'value' => 'Dorchester (Lower Mills)',
                        'label' => 'Dorchester (Lower Mills)',
                    ],
                    15 => [
                        'value' => 'Dorchester Center',
                        'label' => 'Dorchester Center',
                    ],
                    16 => [
                        'value' => 'Dorchester/',
                        'label' => 'Dorchester/',
                    ],
                    17 => [
                        'value' => 'East Boston',
                        'label' => 'East Boston',
                    ],
                    18 => [
                        'value' => 'East Boston/',
                        'label' => 'East Boston/',
                    ],
                    19 => [
                        'value' => 'East Boston//',
                        'label' => 'East Boston//',
                    ],
                    20 => [
                        'value' => 'Fenway/',
                        'label' => 'Fenway/',
                    ],
                    21 => [
                        'value' => 'Financial District',
                        'label' => 'Financial District',
                    ],
                    22 => [
                        'value' => 'Financial District/',
                        'label' => 'Financial District/',
                    ],
                    23 => [
                        'value' => 'Hyde Park',
                        'label' => 'Hyde Park',
                    ],
                    24 => [
                        'value' => 'Hyde Park/',
                        'label' => 'Hyde Park/',
                    ],
                    25 => [
                        'value' => 'Jamaica Plain',
                        'label' => 'Jamaica Plain',
                    ],
                    26 => [
                        'value' => 'Jamaica Plain/',
                        'label' => 'Jamaica Plain/',
                    ],
                    27 => [
                        'value' => 'Kenmore/fenway',
                        'label' => 'Kenmore/fenway',
                    ],
                    28 => [
                        'value' => 'Mattapan',
                        'label' => 'Mattapan',
                    ],
                    29 => [
                        'value' => 'Mattapan/',
                        'label' => 'Mattapan/',
                    ],
                    30 => [
                        'value' => 'Mission Hill',
                        'label' => 'Mission Hill',
                    ],
                    31 => [
                        'value' => 'Mission Hill/',
                        'label' => 'Mission Hill/',
                    ],
                    32 => [
                        'value' => 'NorthEnd',
                        'label' => 'NorthEnd',
                    ],
                    33 => [
                        'value' => 'NorthEnd/',
                        'label' => 'NorthEnd/',
                    ],
                    34 => [
                        'value' => 'Roslindale',
                        'label' => 'Roslindale',
                    ],
                    35 => [
                        'value' => 'Roslindale/',
                        'label' => 'Roslindale/',
                    ],
                    36 => [
                        'value' => 'Roxbury',
                        'label' => 'Roxbury',
                    ],
                    37 => [
                        'value' => 'ROXBURY CROSSIN',
                        'label' => 'ROXBURY CROSSIN',
                    ],
                    38 => [
                        'value' => 'Roxbury/',
                        'label' => 'Roxbury/',
                    ],
                    39 => [
                        'value' => 'South Boston',
                        'label' => 'South Boston',
                    ],
                    40 => [
                        'value' => 'South End',
                        'label' => 'South End',
                    ],
                    41 => [
                        'value' => 'Theater District',
                        'label' => 'Theater District',
                    ],
                    42 => [
                        'value' => 'West End',
                        'label' => 'West End',
                    ],
                    43 => [
                        'value' => 'West Roxbury',
                        'label' => 'West Roxbury',
                    ],
                    44 => [
                        'value' => 'West Roxbury/',
                        'label' => 'West Roxbury/',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Violation Zip',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => '02108',
                        'label' => '02108',
                    ],
                    2 => [
                        'value' => '02109',
                        'label' => '02109',
                    ],
                    3 => [
                        'value' => '02110',
                        'label' => '02110',
                    ],
                    4 => [
                        'value' => '02111',
                        'label' => '02111',
                    ],
                    5 => [
                        'value' => '02113',
                        'label' => '02113',
                    ],
                    6 => [
                        'value' => '02114',
                        'label' => '02114',
                    ],
                    7 => [
                        'value' => '02115',
                        'label' => '02115',
                    ],
                    8 => [
                        'value' => '02116',
                        'label' => '02116',
                    ],
                    9 => [
                        'value' => '02118',
                        'label' => '02118',
                    ],
                    10 => [
                        'value' => '02119',
                        'label' => '02119',
                    ],
                    11 => [
                        'value' => '02120',
                        'label' => '02120',
                    ],
                    12 => [
                        'value' => '02121',
                        'label' => '02121',
                    ],
                    13 => [
                        'value' => '02122',
                        'label' => '02122',
                    ],
                    14 => [
                        'value' => '02123',
                        'label' => '02123',
                    ],
                    15 => [
                        'value' => '02124',
                        'label' => '02124',
                    ],
                    16 => [
                        'value' => '02125',
                        'label' => '02125',
                    ],
                    17 => [
                        'value' => '02126',
                        'label' => '02126',
                    ],
                    18 => [
                        'value' => '02126-1616',
                        'label' => '02126-1616',
                    ],
                    19 => [
                        'value' => '02127',
                        'label' => '02127',
                    ],
                    20 => [
                        'value' => '02128',
                        'label' => '02128',
                    ],
                    21 => [
                        'value' => '02129',
                        'label' => '02129',
                    ],
                    22 => [
                        'value' => '02130',
                        'label' => '02130',
                    ],
                    23 => [
                        'value' => '02131',
                        'label' => '02131',
                    ],
                    24 => [
                        'value' => '02132',
                        'label' => '02132',
                    ],
                    25 => [
                        'value' => '02134',
                        'label' => '02134',
                    ],
                    26 => [
                        'value' => '02135',
                        'label' => '02135',
                    ],
                    27 => [
                        'value' => '02136',
                        'label' => '02136',
                    ],
                    28 => [
                        'value' => '02199',
                        'label' => '02199',
                    ],
                    29 => [
                        'value' => '02210',
                        'label' => '02210',
                    ],
                    30 => [
                        'value' => '02215',
                        'label' => '02215',
                    ],
                    31 => [
                        'value' => '02446',
                        'label' => '02446',
                    ],
                    32 => [
                        'value' => '02467',
                        'label' => '02467',
                    ],
                ],
            ],
            14 => [
                'name' => 'ward',
                'label' => 'Ward',
                'type' => 'multiselect',
                'placeholder' => 'Select Ward',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => '01',
                        'label' => '01',
                    ],
                    2 => [
                        'value' => '02',
                        'label' => '02',
                    ],
                    3 => [
                        'value' => '03',
                        'label' => '03',
                    ],
                    4 => [
                        'value' => '04',
                        'label' => '04',
                    ],
                    5 => [
                        'value' => '05',
                        'label' => '05',
                    ],
                    6 => [
                        'value' => '06',
                        'label' => '06',
                    ],
                    7 => [
                        'value' => '07',
                        'label' => '07',
                    ],
                    8 => [
                        'value' => '08',
                        'label' => '08',
                    ],
                    9 => [
                        'value' => '09',
                        'label' => '09',
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
                ],
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Status. Provide a comma-separated list or an array of values. Possible values: Closed, Open, Void.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Violation Suffix. Provide a comma-separated list or an array of values. Possible values:  , AV, AVE, BL, BLVD, CC, CI, CIR, CT, DR, GRN, HW, LN, PARK, PK, PL, PW, PZ, RD, RO, SQ, ST, TE, TER, WAY, WH, WY.',
            ],
            'violation_city' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Violation City. Provide a comma-separated list or an array of values. Possible values:  , Allston, Allston/Boston, Back Bay/, Boston, Boston/West End, Brighton, Brighton/, Charlestown, Charlestown/, Charlestown666, Chestnut Hill, Chinatown, Dorchester, Dorchester (Lower Mills), Dorchester Center, Dorchester/, East Boston, East Boston/, East Boston//, Fenway/, Financial District, Financial District/, Hyde Park, Hyde Park/, Jamaica Plain, Jamaica Plain/, Kenmore/fenway, Mattapan, Mattapan/, Mission Hill, Mission Hill/, NorthEnd, NorthEnd/, Roslindale, Roslindale/, Roxbury, ROXBURY CROSSIN, Roxbury/, South Boston, South End, Theater District, West End, West Roxbury, West Roxbury/.',
            ],
            'violation_state' => [
                'type' => 'string',
                'description' => 'Filter by Violation State.',
            ],
            'violation_zip' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Violation Zip. Provide a comma-separated list or an array of values. Possible values:  , 02108, 02109, 02110, 02111, 02113, 02114, 02115, 02116, 02118, 02119, 02120, 02121, 02122, 02123, 02124, 02125, 02126, 02126-1616, 02127, 02128, 02129, 02130, 02131, 02132, 02134, 02135, 02136, 02199, 02210, 02215, 02446, 02467.',
            ],
            'ward' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Ward. Provide a comma-separated list or an array of values. Possible values:  , 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Address Geocoded',
                'options' => [
                    0 => [
                        'value' => '17 Fifth St
Cambridge, MA 02141
(42.37244, -71.08291)',
                        'label' => '17 Fifth St
Cambridge, MA 02141
(42.37244, -71.08291)',
                    ],
                    1 => [
                        'value' => '17 PERRY ST
Cambridge, MA 02139
(42.36215, -71.10689)',
                        'label' => '17 PERRY ST
Cambridge, MA 02139
(42.36215, -71.10689)',
                    ],
                    2 => [
                        'value' => '195 Upland Rd
Cambridge, MA 02140
(42.38642, -71.12673)',
                        'label' => '195 Upland Rd
Cambridge, MA 02140
(42.38642, -71.12673)',
                    ],
                    3 => [
                        'value' => '9 Oak St
Cambridge, MA 02139
(42.37395, -71.09958)',
                        'label' => '9 Oak St
Cambridge, MA 02139
(42.37395, -71.09958)',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Status',
                'options' => [
                    0 => [
                        'value' => 'Active',
                        'label' => 'Active',
                    ],
                    1 => [
                        'value' => 'Complete',
                        'label' => 'Complete',
                    ],
                ],
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
                'name' => 'number_of_residential_units',
                'label' => 'Number Of Residential Units',
                'type' => 'multiselect',
                'placeholder' => 'Select Number Of Residential Units',
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
                        'value' => '13',
                        'label' => '13',
                    ],
                    10 => [
                        'value' => '14',
                        'label' => '14',
                    ],
                    11 => [
                        'value' => '23',
                        'label' => '23',
                    ],
                ],
            ],
            10 => [
                'name' => 'current_property_use',
                'label' => 'Current Property Use',
                'type' => 'multiselect',
                'placeholder' => 'Select Current Property Use',
                'options' => [
                    0 => [
                        'value' => 'Accessory',
                        'label' => 'Accessory',
                    ],
                    1 => [
                        'value' => 'Commercial / Mixed Use',
                        'label' => 'Commercial / Mixed Use',
                    ],
                    2 => [
                        'value' => 'Multi-Family (3 units or greater)',
                        'label' => 'Multi-Family (3 units or greater)',
                    ],
                    3 => [
                        'value' => 'One-Family',
                        'label' => 'One-Family',
                    ],
                    4 => [
                        'value' => 'Townhouse',
                        'label' => 'Townhouse',
                    ],
                    5 => [
                        'value' => 'Two-Family',
                        'label' => 'Two-Family',
                    ],
                    6 => [
                        'value' => 'Vacant Lot',
                        'label' => 'Vacant Lot',
                    ],
                ],
            ],
            11 => [
                'name' => 'proposed_property_use',
                'label' => 'Proposed Property Use',
                'type' => 'multiselect',
                'placeholder' => 'Select Proposed Property Use',
                'options' => [
                    0 => [
                        'value' => 'Accessory',
                        'label' => 'Accessory',
                    ],
                    1 => [
                        'value' => 'Commercial / Mixed Use',
                        'label' => 'Commercial / Mixed Use',
                    ],
                    2 => [
                        'value' => 'Multi-Family (3 units or greater)',
                        'label' => 'Multi-Family (3 units or greater)',
                    ],
                    3 => [
                        'value' => 'One-Family',
                        'label' => 'One-Family',
                    ],
                    4 => [
                        'value' => 'Townhouse',
                        'label' => 'Townhouse',
                    ],
                    5 => [
                        'value' => 'Two-Family',
                        'label' => 'Two-Family',
                    ],
                ],
            ],
            12 => [
                'name' => 'total_cost_of_construction',
                'label' => 'Total Cost Of Construction',
                'type' => 'text',
                'placeholder' => 'Enter Total Cost Of Construction',
            ],
            13 => [
                'name' => 'detailed_description_of_work',
                'label' => 'Detailed Description Of Work',
                'type' => 'text',
                'placeholder' => 'Enter Detailed Description Of Work',
            ],
            14 => [
                'name' => 'gross_square_footage_min',
                'label' => 'Gross Square Footage Min',
                'type' => 'number',
                'placeholder' => 'Min value for Gross Square Footage',
            ],
            15 => [
                'name' => 'gross_square_footage_max',
                'label' => 'Gross Square Footage Max',
                'type' => 'number',
                'placeholder' => 'Max value for Gross Square Footage',
            ],
            16 => [
                'name' => 'building_use',
                'label' => 'Building Use',
                'type' => 'multiselect',
                'placeholder' => 'Select Building Use',
                'options' => [
                    0 => [
                        'value' => 'Commercial / Mixed Use',
                        'label' => 'Commercial / Mixed Use',
                    ],
                    1 => [
                        'value' => 'Multi Family (3 or more dwelling units)',
                        'label' => 'Multi Family (3 or more dwelling units)',
                    ],
                    2 => [
                        'value' => 'One or Two Family Dwelling',
                        'label' => 'One or Two Family Dwelling',
                    ],
                    3 => [
                        'value' => 'Townhouse',
                        'label' => 'Townhouse',
                    ],
                ],
            ],
            17 => [
                'name' => 'maplot_number',
                'label' => 'Maplot Number',
                'type' => 'text',
                'placeholder' => 'Enter Maplot Number',
            ],
            18 => [
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Address Geocoded. Provide a comma-separated list or an array of values. Possible values: 17 Fifth St
Cambridge, MA 02141
(42.37244, -71.08291), 17 PERRY ST
Cambridge, MA 02139
(42.36215, -71.10689), 195 Upland Rd
Cambridge, MA 02140
(42.38642, -71.12673), 9 Oak St
Cambridge, MA 02139
(42.37395, -71.09958).',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Status. Provide a comma-separated list or an array of values. Possible values: Active, Complete.',
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
            'number_of_residential_units' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Number Of Residential Units. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5, 6, 7, 8, 13, 14, 23.',
            ],
            'current_property_use' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Current Property Use. Provide a comma-separated list or an array of values. Possible values: Accessory, Commercial / Mixed Use, Multi-Family (3 units or greater), One-Family, Townhouse, Two-Family, Vacant Lot.',
            ],
            'proposed_property_use' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Proposed Property Use. Provide a comma-separated list or an array of values. Possible values: Accessory, Commercial / Mixed Use, Multi-Family (3 units or greater), One-Family, Townhouse, Two-Family.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Building Use. Provide a comma-separated list or an array of values. Possible values: Commercial / Mixed Use, Multi Family (3 or more dwelling units), One or Two Family Dwelling, Townhouse.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Licstatus',
                'options' => [
                    0 => [
                        'value' => 'Active',
                        'label' => 'Active',
                    ],
                    1 => [
                        'value' => 'Deleted',
                        'label' => 'Deleted',
                    ],
                    2 => [
                        'value' => 'Inactive',
                        'label' => 'Inactive',
                    ],
                ],
            ],
            14 => [
                'name' => 'licensecat',
                'label' => 'Licensecat',
                'type' => 'multiselect',
                'placeholder' => 'Select Licensecat',
                'options' => [
                    0 => [
                        'value' => 'FS',
                        'label' => 'FS',
                    ],
                    1 => [
                        'value' => 'FT',
                        'label' => 'FT',
                    ],
                    2 => [
                        'value' => 'MFW',
                        'label' => 'MFW',
                    ],
                    3 => [
                        'value' => 'RF',
                        'label' => 'RF',
                    ],
                ],
            ],
            15 => [
                'name' => 'descript',
                'label' => 'Descript',
                'type' => 'multiselect',
                'placeholder' => 'Select Descript',
                'options' => [
                    0 => [
                        'value' => 'Eating & Drinking',
                        'label' => 'Eating & Drinking',
                    ],
                    1 => [
                        'value' => 'Eating & Drinking w/ Take Out',
                        'label' => 'Eating & Drinking w/ Take Out',
                    ],
                    2 => [
                        'value' => 'Mobile Food Walk On',
                        'label' => 'Mobile Food Walk On',
                    ],
                    3 => [
                        'value' => 'Retail Food',
                        'label' => 'Retail Food',
                    ],
                ],
            ],
            16 => [
                'name' => 'result',
                'label' => 'Result',
                'type' => 'multiselect',
                'placeholder' => 'Select Result',
                'options' => [
                    0 => [
                        'value' => 'Closed',
                        'label' => 'Closed',
                    ],
                    1 => [
                        'value' => 'DATAERR',
                        'label' => 'DATAERR',
                    ],
                    2 => [
                        'value' => 'Fail',
                        'label' => 'Fail',
                    ],
                    3 => [
                        'value' => 'Failed',
                        'label' => 'Failed',
                    ],
                    4 => [
                        'value' => 'HE_Closure',
                        'label' => 'HE_Closure',
                    ],
                    5 => [
                        'value' => 'HE_Fail',
                        'label' => 'HE_Fail',
                    ],
                    6 => [
                        'value' => 'HE_FailExt',
                        'label' => 'HE_FailExt',
                    ],
                    7 => [
                        'value' => 'HE_FAILNOR',
                        'label' => 'HE_FAILNOR',
                    ],
                    8 => [
                        'value' => 'HE_Filed',
                        'label' => 'HE_Filed',
                    ],
                    9 => [
                        'value' => 'HE_Hearing',
                        'label' => 'HE_Hearing',
                    ],
                    10 => [
                        'value' => 'HE_Hold',
                        'label' => 'HE_Hold',
                    ],
                    11 => [
                        'value' => 'HE_Misc',
                        'label' => 'HE_Misc',
                    ],
                    12 => [
                        'value' => 'HE_NotReq',
                        'label' => 'HE_NotReq',
                    ],
                    13 => [
                        'value' => 'HE_OutBus',
                        'label' => 'HE_OutBus',
                    ],
                    14 => [
                        'value' => 'HE_Pass',
                        'label' => 'HE_Pass',
                    ],
                    15 => [
                        'value' => 'HE_TSOP',
                        'label' => 'HE_TSOP',
                    ],
                    16 => [
                        'value' => 'HE_VolClos',
                        'label' => 'HE_VolClos',
                    ],
                    17 => [
                        'value' => 'NoViol',
                        'label' => 'NoViol',
                    ],
                    18 => [
                        'value' => 'Pass',
                        'label' => 'Pass',
                    ],
                    19 => [
                        'value' => 'PassViol',
                        'label' => 'PassViol',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Viol Level',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => '-',
                        'label' => '-',
                    ],
                    2 => [
                        'value' => '*',
                        'label' => '*',
                    ],
                    3 => [
                        'value' => '**',
                        'label' => '**',
                    ],
                    4 => [
                        'value' => '***',
                        'label' => '***',
                    ],
                    5 => [
                        'value' => '1919',
                        'label' => '1919',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Viol Status',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => 'Fail',
                        'label' => 'Fail',
                    ],
                    2 => [
                        'value' => 'Pass',
                        'label' => 'Pass',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select City',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => 'ALLSTON',
                        'label' => 'ALLSTON',
                    ],
                    2 => [
                        'value' => 'BACK BAY/',
                        'label' => 'BACK BAY/',
                    ],
                    3 => [
                        'value' => 'BOSTON',
                        'label' => 'BOSTON',
                    ],
                    4 => [
                        'value' => 'BOSTON/CHINATOWN',
                        'label' => 'BOSTON/CHINATOWN',
                    ],
                    5 => [
                        'value' => 'BOSTON/WEST END',
                        'label' => 'BOSTON/WEST END',
                    ],
                    6 => [
                        'value' => 'BRIGHTON',
                        'label' => 'BRIGHTON',
                    ],
                    7 => [
                        'value' => 'BRIGHTON/',
                        'label' => 'BRIGHTON/',
                    ],
                    8 => [
                        'value' => 'CHARLESTOWN',
                        'label' => 'CHARLESTOWN',
                    ],
                    9 => [
                        'value' => 'CHARLESTOWN/',
                        'label' => 'CHARLESTOWN/',
                    ],
                    10 => [
                        'value' => 'CHESTNUT HILL',
                        'label' => 'CHESTNUT HILL',
                    ],
                    11 => [
                        'value' => 'DORCHESTER',
                        'label' => 'DORCHESTER',
                    ],
                    12 => [
                        'value' => 'DORCHESTER CENTER',
                        'label' => 'DORCHESTER CENTER',
                    ],
                    13 => [
                        'value' => 'DORCHESTER CENTER/',
                        'label' => 'DORCHESTER CENTER/',
                    ],
                    14 => [
                        'value' => 'DORCHESTER/',
                        'label' => 'DORCHESTER/',
                    ],
                    15 => [
                        'value' => 'DOWNTOWN/FINANCIAL DISTRICT',
                        'label' => 'DOWNTOWN/FINANCIAL DISTRICT',
                    ],
                    16 => [
                        'value' => 'EAST BOSTON',
                        'label' => 'EAST BOSTON',
                    ],
                    17 => [
                        'value' => 'FENWAY',
                        'label' => 'FENWAY',
                    ],
                    18 => [
                        'value' => 'FENWAY/',
                        'label' => 'FENWAY/',
                    ],
                    19 => [
                        'value' => 'FINANCIAL DISTRICT',
                        'label' => 'FINANCIAL DISTRICT',
                    ],
                    20 => [
                        'value' => 'FINANCIAL DISTRICT/',
                        'label' => 'FINANCIAL DISTRICT/',
                    ],
                    21 => [
                        'value' => 'HYDE PARK',
                        'label' => 'HYDE PARK',
                    ],
                    22 => [
                        'value' => 'JAMAICA PLAIN',
                        'label' => 'JAMAICA PLAIN',
                    ],
                    23 => [
                        'value' => 'MATTAPAN',
                        'label' => 'MATTAPAN',
                    ],
                    24 => [
                        'value' => 'MATTAPAN/',
                        'label' => 'MATTAPAN/',
                    ],
                    25 => [
                        'value' => 'MISSION HILL',
                        'label' => 'MISSION HILL',
                    ],
                    26 => [
                        'value' => 'MISSION HILL/',
                        'label' => 'MISSION HILL/',
                    ],
                    27 => [
                        'value' => 'ROSLINDALE',
                        'label' => 'ROSLINDALE',
                    ],
                    28 => [
                        'value' => 'ROSLINDALE/',
                        'label' => 'ROSLINDALE/',
                    ],
                    29 => [
                        'value' => 'ROXBURY',
                        'label' => 'ROXBURY',
                    ],
                    30 => [
                        'value' => 'ROXBURY CROSSIN',
                        'label' => 'ROXBURY CROSSIN',
                    ],
                    31 => [
                        'value' => 'ROXBURY/BOSTON',
                        'label' => 'ROXBURY/BOSTON',
                    ],
                    32 => [
                        'value' => 'SOUTH BOSTON',
                        'label' => 'SOUTH BOSTON',
                    ],
                    33 => [
                        'value' => 'SOUTH BOSTON/',
                        'label' => 'SOUTH BOSTON/',
                    ],
                    34 => [
                        'value' => 'SOUTH END/',
                        'label' => 'SOUTH END/',
                    ],
                    35 => [
                        'value' => 'WEST ROXBURY',
                        'label' => 'WEST ROXBURY',
                    ],
                    36 => [
                        'value' => 'WEST ROXBURY//',
                        'label' => 'WEST ROXBURY//',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Zip',
                'options' => [
                    0 => [
                        'value' => '00000',
                        'label' => '00000',
                    ],
                    1 => [
                        'value' => '02050',
                        'label' => '02050',
                    ],
                    2 => [
                        'value' => '02108',
                        'label' => '02108',
                    ],
                    3 => [
                        'value' => '02109',
                        'label' => '02109',
                    ],
                    4 => [
                        'value' => '02110',
                        'label' => '02110',
                    ],
                    5 => [
                        'value' => '02111',
                        'label' => '02111',
                    ],
                    6 => [
                        'value' => '02113',
                        'label' => '02113',
                    ],
                    7 => [
                        'value' => '02114',
                        'label' => '02114',
                    ],
                    8 => [
                        'value' => '02115',
                        'label' => '02115',
                    ],
                    9 => [
                        'value' => '02116',
                        'label' => '02116',
                    ],
                    10 => [
                        'value' => '02118',
                        'label' => '02118',
                    ],
                    11 => [
                        'value' => '02119',
                        'label' => '02119',
                    ],
                    12 => [
                        'value' => '02119-3212',
                        'label' => '02119-3212',
                    ],
                    13 => [
                        'value' => '02120',
                        'label' => '02120',
                    ],
                    14 => [
                        'value' => '02121',
                        'label' => '02121',
                    ],
                    15 => [
                        'value' => '02122',
                        'label' => '02122',
                    ],
                    16 => [
                        'value' => '02124',
                        'label' => '02124',
                    ],
                    17 => [
                        'value' => '02125',
                        'label' => '02125',
                    ],
                    18 => [
                        'value' => '02125-1663',
                        'label' => '02125-1663',
                    ],
                    19 => [
                        'value' => '02126',
                        'label' => '02126',
                    ],
                    20 => [
                        'value' => '02127',
                        'label' => '02127',
                    ],
                    21 => [
                        'value' => '02128',
                        'label' => '02128',
                    ],
                    22 => [
                        'value' => '02129',
                        'label' => '02129',
                    ],
                    23 => [
                        'value' => '02130',
                        'label' => '02130',
                    ],
                    24 => [
                        'value' => '02131',
                        'label' => '02131',
                    ],
                    25 => [
                        'value' => '02132',
                        'label' => '02132',
                    ],
                    26 => [
                        'value' => '02134',
                        'label' => '02134',
                    ],
                    27 => [
                        'value' => '02135',
                        'label' => '02135',
                    ],
                    28 => [
                        'value' => '02136',
                        'label' => '02136',
                    ],
                    29 => [
                        'value' => '02145',
                        'label' => '02145',
                    ],
                    30 => [
                        'value' => '02148',
                        'label' => '02148',
                    ],
                    31 => [
                        'value' => '02163',
                        'label' => '02163',
                    ],
                    32 => [
                        'value' => '02188',
                        'label' => '02188',
                    ],
                    33 => [
                        'value' => '02199',
                        'label' => '02199',
                    ],
                    34 => [
                        'value' => '02201',
                        'label' => '02201',
                    ],
                    35 => [
                        'value' => '02205',
                        'label' => '02205',
                    ],
                    36 => [
                        'value' => '02210',
                        'label' => '02210',
                    ],
                    37 => [
                        'value' => '02215',
                        'label' => '02215',
                    ],
                    38 => [
                        'value' => '02446',
                        'label' => '02446',
                    ],
                    39 => [
                        'value' => '02467',
                        'label' => '02467',
                    ],
                ],
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Licstatus. Provide a comma-separated list or an array of values. Possible values: Active, Deleted, Inactive.',
            ],
            'licensecat' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Licensecat. Provide a comma-separated list or an array of values. Possible values: FS, FT, MFW, RF.',
            ],
            'descript' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Descript. Provide a comma-separated list or an array of values. Possible values: Eating & Drinking, Eating & Drinking w/ Take Out, Mobile Food Walk On, Retail Food.',
            ],
            'result' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Result. Provide a comma-separated list or an array of values. Possible values: Closed, DATAERR, Fail, Failed, HE_Closure, HE_Fail, HE_FailExt, HE_FAILNOR, HE_Filed, HE_Hearing, HE_Hold, HE_Misc, HE_NotReq, HE_OutBus, HE_Pass, HE_TSOP, HE_VolClos, NoViol, Pass, PassViol.',
            ],
            'violation' => [
                'type' => 'string',
                'description' => 'Filter by Violation.',
            ],
            'viol_level' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Viol Level. Provide a comma-separated list or an array of values. Possible values:  , -, *, **, ***, 1919.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Viol Status. Provide a comma-separated list or an array of values. Possible values:  , Fail, Pass.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by City. Provide a comma-separated list or an array of values. Possible values:  , ALLSTON, BACK BAY/, BOSTON, BOSTON/CHINATOWN, BOSTON/WEST END, BRIGHTON, BRIGHTON/, CHARLESTOWN, CHARLESTOWN/, CHESTNUT HILL, DORCHESTER, DORCHESTER CENTER, DORCHESTER CENTER/, DORCHESTER/, DOWNTOWN/FINANCIAL DISTRICT, EAST BOSTON, FENWAY, FENWAY/, FINANCIAL DISTRICT, FINANCIAL DISTRICT/, HYDE PARK, JAMAICA PLAIN, MATTAPAN, MATTAPAN/, MISSION HILL, MISSION HILL/, ROSLINDALE, ROSLINDALE/, ROXBURY, ROXBURY CROSSIN, ROXBURY/BOSTON, SOUTH BOSTON, SOUTH BOSTON/, SOUTH END/, WEST ROXBURY, WEST ROXBURY//.',
            ],
            'state' => [
                'type' => 'string',
                'description' => 'Filter by State.',
            ],
            'zip' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Zip. Provide a comma-separated list or an array of values. Possible values: 00000, 02050, 02108, 02109, 02110, 02111, 02113, 02114, 02115, 02116, 02118, 02119, 02119-3212, 02120, 02121, 02122, 02124, 02125, 02125-1663, 02126, 02127, 02128, 02129, 02130, 02131, 02132, 02134, 02135, 02136, 02145, 02148, 02163, 02188, 02199, 02201, 02205, 02210, 02215, 02446, 02467.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Description',
                'options' => [
                    0 => [
                        'value' => 'Access for Repairs and Alterations',
                        'label' => 'Access for Repairs and Alterations',
                    ],
                    1 => [
                        'value' => 'Amperage',
                        'label' => 'Amperage',
                    ],
                    2 => [
                        'value' => 'Bathroom Lighting and Electrical Outlets',
                        'label' => 'Bathroom Lighting and Electrical Outlets',
                    ],
                    3 => [
                        'value' => 'Collection of Garbage and Rubbish',
                        'label' => 'Collection of Garbage and Rubbish',
                    ],
                    4 => [
                        'value' => 'Cond. Deemed to Endgr. or Impair Health or Safety',
                        'label' => 'Cond. Deemed to Endgr. or Impair Health or Safety',
                    ],
                    5 => [
                        'value' => 'Egress Obstructions',
                        'label' => 'Egress Obstructions',
                    ],
                    6 => [
                        'value' => 'Extermination of Insects, Rodents and Skunks',
                        'label' => 'Extermination of Insects, Rodents and Skunks',
                    ],
                    7 => [
                        'value' => 'Grade Level',
                        'label' => 'Grade Level',
                    ],
                    8 => [
                        'value' => 'Habitable Rooms Other than Kitchen -- Natural Light and Electrical Outlets',
                        'label' => 'Habitable Rooms Other than Kitchen -- Natural Light and Electrical Outlets',
                    ],
                    9 => [
                        'value' => 'Heating Facilities Required',
                        'label' => 'Heating Facilities Required',
                    ],
                    10 => [
                        'value' => 'Hot Water',
                        'label' => 'Hot Water',
                    ],
                    11 => [
                        'value' => 'Kitchen Facilities',
                        'label' => 'Kitchen Facilities',
                    ],
                    12 => [
                        'value' => 'Light Fixtures Oth than in Habit. Rooms or Kitch.',
                        'label' => 'Light Fixtures Oth than in Habit. Rooms or Kitch.',
                    ],
                    13 => [
                        'value' => 'Light in Passageways, Hallways, and Stairways',
                        'label' => 'Light in Passageways, Hallways, and Stairways',
                    ],
                    14 => [
                        'value' => 'Locks',
                        'label' => 'Locks',
                    ],
                    15 => [
                        'value' => 'Maint. of Areas Free from Garbage and Rubbish',
                        'label' => 'Maint. of Areas Free from Garbage and Rubbish',
                    ],
                    16 => [
                        'value' => 'Means of Egress',
                        'label' => 'Means of Egress',
                    ],
                    17 => [
                        'value' => 'Metering of Electricity, Gas and Water',
                        'label' => 'Metering of Electricity, Gas and Water',
                    ],
                    18 => [
                        'value' => 'Minimum Square Footage',
                        'label' => 'Minimum Square Footage',
                    ],
                    19 => [
                        'value' => 'Natural and Mechanical Ventilation',
                        'label' => 'Natural and Mechanical Ventilation',
                    ],
                    20 => [
                        'value' => 'Occupant\'s Inst. and Maint. Responsibilities',
                        'label' => 'Occupant\'s Inst. and Maint. Responsibilities',
                    ],
                    21 => [
                        'value' => 'Occupant\'s Resp. Respecting Structural Elements',
                        'label' => 'Occupant\'s Resp. Respecting Structural Elements',
                    ],
                    22 => [
                        'value' => 'Owner\'s Inst. and Maint. Responsibilities',
                        'label' => 'Owner\'s Inst. and Maint. Responsibilities',
                    ],
                    23 => [
                        'value' => 'Owner\'s Resp. to Maintain Structural Elements',
                        'label' => 'Owner\'s Resp. to Maintain Structural Elements',
                    ],
                    24 => [
                        'value' => 'Plumbing Connections',
                        'label' => 'Plumbing Connections',
                    ],
                    25 => [
                        'value' => 'Posting of Name of Owner',
                        'label' => 'Posting of Name of Owner',
                    ],
                    26 => [
                        'value' => 'Potable Water',
                        'label' => 'Potable Water',
                    ],
                    27 => [
                        'value' => 'Safe Condition',
                        'label' => 'Safe Condition',
                    ],
                    28 => [
                        'value' => 'Sanitary Drainage System Required',
                        'label' => 'Sanitary Drainage System Required',
                    ],
                    29 => [
                        'value' => 'Screens for Doors',
                        'label' => 'Screens for Doors',
                    ],
                    30 => [
                        'value' => 'Screens for Windows',
                        'label' => 'Screens for Windows',
                    ],
                    31 => [
                        'value' => 'Shared Facilities',
                        'label' => 'Shared Facilities',
                    ],
                    32 => [
                        'value' => 'Smoke Detectors and Carbon Monoxide Alarms',
                        'label' => 'Smoke Detectors and Carbon Monoxide Alarms',
                    ],
                    33 => [
                        'value' => 'Storage of Garbage and Rubbish',
                        'label' => 'Storage of Garbage and Rubbish',
                    ],
                    34 => [
                        'value' => 'Temperature Requirements',
                        'label' => 'Temperature Requirements',
                    ],
                    35 => [
                        'value' => 'Venting',
                        'label' => 'Venting',
                    ],
                    36 => [
                        'value' => 'Washbasins, Toilets, Tubs and Showers',
                        'label' => 'Washbasins, Toilets, Tubs and Showers',
                    ],
                    37 => [
                        'value' => 'Weathertight Elements',
                        'label' => 'Weathertight Elements',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Status',
                'options' => [
                    0 => [
                        'value' => 'Cited',
                        'label' => 'Cited',
                    ],
                    1 => [
                        'value' => 'Corrected',
                        'label' => 'Corrected',
                    ],
                    2 => [
                        'value' => 'In Progress',
                        'label' => 'In Progress',
                    ],
                ],
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Description. Provide a comma-separated list or an array of values. Possible values: Access for Repairs and Alterations, Amperage, Bathroom Lighting and Electrical Outlets, Collection of Garbage and Rubbish, Cond. Deemed to Endgr. or Impair Health or Safety, Egress Obstructions, Extermination of Insects, Rodents and Skunks, Grade Level, Habitable Rooms Other than Kitchen -- Natural Light and Electrical Outlets, Heating Facilities Required, Hot Water, Kitchen Facilities, Light Fixtures Oth than in Habit. Rooms or Kitch., Light in Passageways, Hallways, and Stairways, Locks, Maint. of Areas Free from Garbage and Rubbish, Means of Egress, Metering of Electricity, Gas and Water, Minimum Square Footage, Natural and Mechanical Ventilation, Occupant\'s Inst. and Maint. Responsibilities, Occupant\'s Resp. Respecting Structural Elements, Owner\'s Inst. and Maint. Responsibilities, Owner\'s Resp. to Maintain Structural Elements, Plumbing Connections, Posting of Name of Owner, Potable Water, Safe Condition, Sanitary Drainage System Required, Screens for Doors, Screens for Windows, Shared Facilities, Smoke Detectors and Carbon Monoxide Alarms, Storage of Garbage and Rubbish, Temperature Requirements, Venting, Washbasins, Toilets, Tubs and Showers, Weathertight Elements.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Status. Provide a comma-separated list or an array of values. Possible values: Cited, Corrected, In Progress.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Permittypedescr',
                'options' => [
                    0 => [
                        'value' => 'Amendment to a Long Form',
                        'label' => 'Amendment to a Long Form',
                    ],
                    1 => [
                        'value' => 'Certificate of Occupancy',
                        'label' => 'Certificate of Occupancy',
                    ],
                    2 => [
                        'value' => 'Electrical Fire Alarms',
                        'label' => 'Electrical Fire Alarms',
                    ],
                    3 => [
                        'value' => 'Electrical Low Voltage',
                        'label' => 'Electrical Low Voltage',
                    ],
                    4 => [
                        'value' => 'Electrical Permit',
                        'label' => 'Electrical Permit',
                    ],
                    5 => [
                        'value' => 'Electrical Temporary Service',
                        'label' => 'Electrical Temporary Service',
                    ],
                    6 => [
                        'value' => 'Erect/New Construction',
                        'label' => 'Erect/New Construction',
                    ],
                    7 => [
                        'value' => 'Foundation Permit',
                        'label' => 'Foundation Permit',
                    ],
                    8 => [
                        'value' => 'Gas Permit',
                        'label' => 'Gas Permit',
                    ],
                    9 => [
                        'value' => 'Long Form/Alteration Permit',
                        'label' => 'Long Form/Alteration Permit',
                    ],
                    10 => [
                        'value' => 'Plumbing Permit',
                        'label' => 'Plumbing Permit',
                    ],
                    11 => [
                        'value' => 'Short Form Bldg Permit',
                        'label' => 'Short Form Bldg Permit',
                    ],
                    12 => [
                        'value' => 'Use of Premises',
                        'label' => 'Use of Premises',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Status',
                'options' => [
                    0 => [
                        'value' => 'Closed',
                        'label' => 'Closed',
                    ],
                    1 => [
                        'value' => 'Issued',
                        'label' => 'Issued',
                    ],
                    2 => [
                        'value' => 'Open',
                        'label' => 'Open',
                    ],
                    3 => [
                        'value' => 'Stop Work',
                        'label' => 'Stop Work',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Occupancytype',
                'options' => [
                    0 => [
                        'value' => '1-2FAM',
                        'label' => '1-2FAM',
                    ],
                    1 => [
                        'value' => '1-3FAM',
                        'label' => '1-3FAM',
                    ],
                    2 => [
                        'value' => '1-4FAM',
                        'label' => '1-4FAM',
                    ],
                    3 => [
                        'value' => '1-7FAM',
                        'label' => '1-7FAM',
                    ],
                    4 => [
                        'value' => '1Unit',
                        'label' => '1Unit',
                    ],
                    5 => [
                        'value' => '2unit',
                        'label' => '2unit',
                    ],
                    6 => [
                        'value' => '3unit',
                        'label' => '3unit',
                    ],
                    7 => [
                        'value' => '4unit',
                        'label' => '4unit',
                    ],
                    8 => [
                        'value' => '5unit',
                        'label' => '5unit',
                    ],
                    9 => [
                        'value' => '6unit',
                        'label' => '6unit',
                    ],
                    10 => [
                        'value' => '7More',
                        'label' => '7More',
                    ],
                    11 => [
                        'value' => '7unit',
                        'label' => '7unit',
                    ],
                    12 => [
                        'value' => 'Comm',
                        'label' => 'Comm',
                    ],
                    13 => [
                        'value' => 'Mixed',
                        'label' => 'Mixed',
                    ],
                    14 => [
                        'value' => 'Multi',
                        'label' => 'Multi',
                    ],
                    15 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    16 => [
                        'value' => 'VacLd',
                        'label' => 'VacLd',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Zip',
                'options' => [
                    0 => [
                        'value' => '02026',
                        'label' => '02026',
                    ],
                    1 => [
                        'value' => '02108',
                        'label' => '02108',
                    ],
                    2 => [
                        'value' => '02109',
                        'label' => '02109',
                    ],
                    3 => [
                        'value' => '02110',
                        'label' => '02110',
                    ],
                    4 => [
                        'value' => '02111',
                        'label' => '02111',
                    ],
                    5 => [
                        'value' => '02113',
                        'label' => '02113',
                    ],
                    6 => [
                        'value' => '02114',
                        'label' => '02114',
                    ],
                    7 => [
                        'value' => '02115',
                        'label' => '02115',
                    ],
                    8 => [
                        'value' => '02116',
                        'label' => '02116',
                    ],
                    9 => [
                        'value' => '02117',
                        'label' => '02117',
                    ],
                    10 => [
                        'value' => '02118',
                        'label' => '02118',
                    ],
                    11 => [
                        'value' => '02119',
                        'label' => '02119',
                    ],
                    12 => [
                        'value' => '02120',
                        'label' => '02120',
                    ],
                    13 => [
                        'value' => '02121',
                        'label' => '02121',
                    ],
                    14 => [
                        'value' => '02122',
                        'label' => '02122',
                    ],
                    15 => [
                        'value' => '02123',
                        'label' => '02123',
                    ],
                    16 => [
                        'value' => '02124',
                        'label' => '02124',
                    ],
                    17 => [
                        'value' => '02125',
                        'label' => '02125',
                    ],
                    18 => [
                        'value' => '02126',
                        'label' => '02126',
                    ],
                    19 => [
                        'value' => '02127',
                        'label' => '02127',
                    ],
                    20 => [
                        'value' => '02128',
                        'label' => '02128',
                    ],
                    21 => [
                        'value' => '02129',
                        'label' => '02129',
                    ],
                    22 => [
                        'value' => '02130',
                        'label' => '02130',
                    ],
                    23 => [
                        'value' => '02131',
                        'label' => '02131',
                    ],
                    24 => [
                        'value' => '02132',
                        'label' => '02132',
                    ],
                    25 => [
                        'value' => '02134',
                        'label' => '02134',
                    ],
                    26 => [
                        'value' => '02135',
                        'label' => '02135',
                    ],
                    27 => [
                        'value' => '02136',
                        'label' => '02136',
                    ],
                    28 => [
                        'value' => '02137',
                        'label' => '02137',
                    ],
                    29 => [
                        'value' => '02163',
                        'label' => '02163',
                    ],
                    30 => [
                        'value' => '02186',
                        'label' => '02186',
                    ],
                    31 => [
                        'value' => '02199',
                        'label' => '02199',
                    ],
                    32 => [
                        'value' => '02210',
                        'label' => '02210',
                    ],
                    33 => [
                        'value' => '02215',
                        'label' => '02215',
                    ],
                    34 => [
                        'value' => '02222',
                        'label' => '02222',
                    ],
                    35 => [
                        'value' => '02446',
                        'label' => '02446',
                    ],
                    36 => [
                        'value' => '02458',
                        'label' => '02458',
                    ],
                    37 => [
                        'value' => '02467',
                        'label' => '02467',
                    ],
                    38 => [
                        'value' => '02468',
                        'label' => '02468',
                    ],
                ],
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Permittypedescr. Provide a comma-separated list or an array of values. Possible values: Amendment to a Long Form, Certificate of Occupancy, Electrical Fire Alarms, Electrical Low Voltage, Electrical Permit, Electrical Temporary Service, Erect/New Construction, Foundation Permit, Gas Permit, Long Form/Alteration Permit, Plumbing Permit, Short Form Bldg Permit, Use of Premises.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Status. Provide a comma-separated list or an array of values. Possible values: Closed, Issued, Open, Stop Work.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
            'occupancytype' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Occupancytype. Provide a comma-separated list or an array of values. Possible values: 1-2FAM, 1-3FAM, 1-4FAM, 1-7FAM, 1Unit, 2unit, 3unit, 4unit, 5unit, 6unit, 7More, 7unit, Comm, Mixed, Multi, Other, VacLd.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Zip. Provide a comma-separated list or an array of values. Possible values: 02026, 02108, 02109, 02110, 02111, 02113, 02114, 02115, 02116, 02117, 02118, 02119, 02120, 02121, 02122, 02123, 02124, 02125, 02126, 02127, 02128, 02129, 02130, 02131, 02132, 02134, 02135, 02136, 02137, 02163, 02186, 02199, 02210, 02215, 02222, 02446, 02458, 02467, 02468.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Ticket Status',
                'options' => [
                    0 => [
                        'value' => 'Acknowledged',
                        'label' => 'Acknowledged',
                    ],
                    1 => [
                        'value' => 'Archived',
                        'label' => 'Archived',
                    ],
                    2 => [
                        'value' => 'Closed',
                        'label' => 'Closed',
                    ],
                    3 => [
                        'value' => 'Open',
                        'label' => 'Open',
                    ],
                ],
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Ticket Status. Provide a comma-separated list or an array of values. Possible values: Acknowledged, Archived, Closed, Open.',
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
                'name' => 'year',
                'label' => 'Year',
                'type' => 'multiselect',
                'placeholder' => 'Select Year',
                'options' => [
                    0 => [
                        'value' => '2024',
                        'label' => '2024',
                    ],
                    1 => [
                        'value' => '2025',
                        'label' => '2025',
                    ],
                ],
            ],
            7 => [
                'name' => 'month',
                'label' => 'Month',
                'type' => 'multiselect',
                'placeholder' => 'Select Month',
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
                        'value' => '12',
                        'label' => '12',
                    ],
                ],
            ],
            8 => [
                'name' => 'day_of_week',
                'label' => 'Day Of Week',
                'type' => 'multiselect',
                'placeholder' => 'Select Day Of Week',
                'options' => [
                    0 => [
                        'value' => 'Friday',
                        'label' => 'Friday',
                    ],
                    1 => [
                        'value' => 'Monday',
                        'label' => 'Monday',
                    ],
                    2 => [
                        'value' => 'Saturday',
                        'label' => 'Saturday',
                    ],
                    3 => [
                        'value' => 'Sunday',
                        'label' => 'Sunday',
                    ],
                    4 => [
                        'value' => 'Thursday',
                        'label' => 'Thursday',
                    ],
                    5 => [
                        'value' => 'Tuesday',
                        'label' => 'Tuesday',
                    ],
                    6 => [
                        'value' => 'Wednesday',
                        'label' => 'Wednesday',
                    ],
                ],
            ],
            9 => [
                'name' => 'hour',
                'label' => 'Hour',
                'type' => 'multiselect',
                'placeholder' => 'Select Hour',
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
            10 => [
                'name' => 'incident_type',
                'label' => 'Incident Type',
                'type' => 'text',
                'placeholder' => 'Enter Incident Type',
            ],
            11 => [
                'name' => 'incident_address',
                'label' => 'Incident Address',
                'type' => 'text',
                'placeholder' => 'Enter Incident Address',
            ],
            12 => [
                'name' => 'incident_latitude',
                'label' => 'Incident Latitude',
                'type' => 'text',
                'placeholder' => 'Enter Incident Latitude',
            ],
            13 => [
                'name' => 'incident_longitude',
                'label' => 'Incident Longitude',
                'type' => 'text',
                'placeholder' => 'Enter Incident Longitude',
            ],
            14 => [
                'name' => 'incident_description',
                'label' => 'Incident Description',
                'type' => 'text',
                'placeholder' => 'Enter Incident Description',
            ],
            15 => [
                'name' => 'arrest_name',
                'label' => 'Arrest Name',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Name',
            ],
            16 => [
                'name' => 'arrest_address',
                'label' => 'Arrest Address',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Address',
            ],
            17 => [
                'name' => 'arrest_age',
                'label' => 'Arrest Age',
                'type' => 'multiselect',
                'placeholder' => 'Select Arrest Age',
                'options' => [
                    0 => [
                        'value' => '-71',
                        'label' => '-71',
                    ],
                    1 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    2 => [
                        'value' => '18',
                        'label' => '18',
                    ],
                    3 => [
                        'value' => '19',
                        'label' => '19',
                    ],
                    4 => [
                        'value' => '20',
                        'label' => '20',
                    ],
                    5 => [
                        'value' => '21',
                        'label' => '21',
                    ],
                    6 => [
                        'value' => '22',
                        'label' => '22',
                    ],
                    7 => [
                        'value' => '23',
                        'label' => '23',
                    ],
                    8 => [
                        'value' => '24',
                        'label' => '24',
                    ],
                    9 => [
                        'value' => '25',
                        'label' => '25',
                    ],
                    10 => [
                        'value' => '26',
                        'label' => '26',
                    ],
                    11 => [
                        'value' => '27',
                        'label' => '27',
                    ],
                    12 => [
                        'value' => '28',
                        'label' => '28',
                    ],
                    13 => [
                        'value' => '29',
                        'label' => '29',
                    ],
                    14 => [
                        'value' => '30',
                        'label' => '30',
                    ],
                    15 => [
                        'value' => '31',
                        'label' => '31',
                    ],
                    16 => [
                        'value' => '32',
                        'label' => '32',
                    ],
                    17 => [
                        'value' => '33',
                        'label' => '33',
                    ],
                    18 => [
                        'value' => '34',
                        'label' => '34',
                    ],
                    19 => [
                        'value' => '35',
                        'label' => '35',
                    ],
                    20 => [
                        'value' => '36',
                        'label' => '36',
                    ],
                    21 => [
                        'value' => '37',
                        'label' => '37',
                    ],
                    22 => [
                        'value' => '38',
                        'label' => '38',
                    ],
                    23 => [
                        'value' => '39',
                        'label' => '39',
                    ],
                    24 => [
                        'value' => '40',
                        'label' => '40',
                    ],
                    25 => [
                        'value' => '41',
                        'label' => '41',
                    ],
                    26 => [
                        'value' => '42',
                        'label' => '42',
                    ],
                    27 => [
                        'value' => '43',
                        'label' => '43',
                    ],
                    28 => [
                        'value' => '44',
                        'label' => '44',
                    ],
                    29 => [
                        'value' => '45',
                        'label' => '45',
                    ],
                    30 => [
                        'value' => '46',
                        'label' => '46',
                    ],
                    31 => [
                        'value' => '47',
                        'label' => '47',
                    ],
                    32 => [
                        'value' => '48',
                        'label' => '48',
                    ],
                    33 => [
                        'value' => '49',
                        'label' => '49',
                    ],
                    34 => [
                        'value' => '50',
                        'label' => '50',
                    ],
                    35 => [
                        'value' => '51',
                        'label' => '51',
                    ],
                    36 => [
                        'value' => '52',
                        'label' => '52',
                    ],
                    37 => [
                        'value' => '53',
                        'label' => '53',
                    ],
                    38 => [
                        'value' => '54',
                        'label' => '54',
                    ],
                    39 => [
                        'value' => '55',
                        'label' => '55',
                    ],
                    40 => [
                        'value' => '56',
                        'label' => '56',
                    ],
                    41 => [
                        'value' => '57',
                        'label' => '57',
                    ],
                    42 => [
                        'value' => '58',
                        'label' => '58',
                    ],
                    43 => [
                        'value' => '59',
                        'label' => '59',
                    ],
                    44 => [
                        'value' => '61',
                        'label' => '61',
                    ],
                    45 => [
                        'value' => '62',
                        'label' => '62',
                    ],
                    46 => [
                        'value' => '63',
                        'label' => '63',
                    ],
                    47 => [
                        'value' => '65',
                        'label' => '65',
                    ],
                    48 => [
                        'value' => '911',
                        'label' => '911',
                    ],
                ],
            ],
            18 => [
                'name' => 'arrest_date_parsed',
                'label' => 'Arrest Date Parsed',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Date Parsed',
            ],
            19 => [
                'name' => 'arrest_charges',
                'label' => 'Arrest Charges',
                'type' => 'text',
                'placeholder' => 'Enter Arrest Charges',
            ],
            20 => [
                'name' => 'crime_details_concatenated',
                'label' => 'Crime Details Concatenated',
                'type' => 'text',
                'placeholder' => 'Enter Crime Details Concatenated',
            ],
            21 => [
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
            'year' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Year. Provide a comma-separated list or an array of values. Possible values: 2024, 2025.',
            ],
            'month' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Month. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 12.',
            ],
            'day_of_week' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Day Of Week. Provide a comma-separated list or an array of values. Possible values: Friday, Monday, Saturday, Sunday, Thursday, Tuesday, Wednesday.',
            ],
            'hour' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Hour. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23.',
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
            'arrest_age' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Arrest Age. Provide a comma-separated list or an array of values. Possible values: -71, 0, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 61, 62, 63, 65, 911.',
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
                        'value' => 'Property damage only (none injured)',
                        'label' => 'Property damage only (none injured)',
                    ],
                    3 => [
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
                        'value' => 'Not Applicable',
                        'label' => 'Not Applicable',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Possible Injury (C)',
                        'label' => 'Possible Injury (C)',
                    ],
                    6 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    7 => [
                        'value' => 'Suspected Minor Injury (B)',
                        'label' => 'Suspected Minor Injury (B)',
                    ],
                    8 => [
                        'value' => 'Suspected Serious Injury (A)',
                        'label' => 'Suspected Serious Injury (A)',
                    ],
                    9 => [
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
                        'value' => '10',
                        'label' => '10',
                    ],
                    9 => [
                        'value' => '13',
                        'label' => '13',
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
                        'value' => 'Local police',
                        'label' => 'Local police',
                    ],
                    1 => [
                        'value' => 'MBTA police',
                        'label' => 'MBTA police',
                    ],
                    2 => [
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
                        'value' => '2024',
                        'label' => '2024',
                    ],
                    1 => [
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
                        'value' => 'Sand, mud, dirt, oil, gravel',
                        'label' => 'Sand, mud, dirt, oil, gravel',
                    ],
                    5 => [
                        'value' => 'Slush',
                        'label' => 'Slush',
                    ],
                    6 => [
                        'value' => 'Snow',
                        'label' => 'Snow',
                    ],
                    7 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    8 => [
                        'value' => 'Water (standing, moving)',
                        'label' => 'Water (standing, moving)',
                    ],
                    9 => [
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
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    28 => [
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
                        'value' => 'T-intersection',
                        'label' => 'T-intersection',
                    ],
                    9 => [
                        'value' => 'Traffic circle',
                        'label' => 'Traffic circle',
                    ],
                    10 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    11 => [
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
                        'value' => 'School zone signs',
                        'label' => 'School zone signs',
                    ],
                    6 => [
                        'value' => 'Stop signs',
                        'label' => 'Stop signs',
                    ],
                    7 => [
                        'value' => 'Traffic control signal',
                        'label' => 'Traffic control signal',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    9 => [
                        'value' => 'Warning signs',
                        'label' => 'Warning signs',
                    ],
                    10 => [
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
                        'value' => 'Federal Park or Forest',
                        'label' => 'Federal Park or Forest',
                    ],
                    4 => [
                        'value' => 'Massachusetts Department of Transportation',
                        'label' => 'Massachusetts Department of Transportation',
                    ],
                    5 => [
                        'value' => 'Massachusetts Port Authority',
                        'label' => 'Massachusetts Port Authority',
                    ],
                    6 => [
                        'value' => 'Private',
                        'label' => 'Private',
                    ],
                    7 => [
                        'value' => 'State Institutional',
                        'label' => 'State Institutional',
                    ],
                    8 => [
                        'value' => 'State Park or Forest',
                        'label' => 'State Park or Forest',
                    ],
                    9 => [
                        'value' => 'Unaccepted by city or town',
                        'label' => 'Unaccepted by city or town',
                    ],
                    10 => [
                        'value' => 'US Air Force',
                        'label' => 'US Air Force',
                    ],
                    11 => [
                        'value' => 'US Army',
                        'label' => 'US Army',
                    ],
                    12 => [
                        'value' => 'US Army Corps of Engineers',
                        'label' => 'US Army Corps of Engineers',
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
                        'value' => 'Roadside',
                        'label' => 'Roadside',
                    ],
                    4 => [
                        'value' => 'Roadway',
                        'label' => 'Roadway',
                    ],
                    5 => [
                        'value' => 'Shoulder - paved',
                        'label' => 'Shoulder - paved',
                    ],
                    6 => [
                        'value' => 'Shoulder - travel lane',
                        'label' => 'Shoulder - travel lane',
                    ],
                    7 => [
                        'value' => 'Shoulder - unpaved',
                        'label' => 'Shoulder - unpaved',
                    ],
                    8 => [
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
                        'value' => 'Multiple',
                        'label' => 'Multiple',
                    ],
                    1 => [
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
                'name' => 'location_min',
                'label' => 'Location Min',
                'type' => 'number',
                'placeholder' => 'Min value for Location',
            ],
            49 => [
                'name' => 'location_max',
                'label' => 'Location Max',
                'type' => 'number',
                'placeholder' => 'Max value for Location',
            ],
            50 => [
                'name' => 'rmv_doc_ids',
                'label' => 'Document IDs',
                'type' => 'text',
                'placeholder' => 'Enter Document IDs',
            ],
            51 => [
                'name' => 'crash_rpt_ids',
                'label' => 'Crash Report IDs',
                'type' => 'text',
                'placeholder' => 'Enter Crash Report IDs',
            ],
            52 => [
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
            53 => [
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
            54 => [
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
            55 => [
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
            56 => [
                'name' => 'drvr_distracted_cl',
                'label' => 'Driver Distracted By (All Drivers)',
                'type' => 'text',
                'placeholder' => 'Enter Driver Distracted By (All Drivers)',
            ],
            57 => [
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
            58 => [
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
            59 => [
                'name' => 'vehc_emer_use_cl',
                'label' => 'Vehicle Emergency Use (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Emergency Use (All Vehicles)',
            ],
            60 => [
                'name' => 'vehc_towed_from_scene_cl',
                'label' => 'Vehicle Towed From Scene (All Vehicles)',
                'type' => 'text',
                'placeholder' => 'Enter Vehicle Towed From Scene (All Vehicles)',
            ],
            61 => [
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
            62 => [
                'name' => 'fmsca_rptbl_cl',
                'label' => 'FMCSA Reportable (All Vehicles)',
                'type' => 'multiselect',
                'placeholder' => 'Select FMCSA Reportable (All Vehicles)',
                'options' => [
                    0 => [
                        'value' => 'V1:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable)',
                    ],
                    1 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable)',
                    ],
                    2 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable)',
                    ],
                    3 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                    ],
                    4 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable)',
                    ],
                    5 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable)',
                    ],
                    6 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally re',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally re',
                    ],
                    7 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(Yes, federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(Yes, federally reportable)',
                    ],
                    8 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(Yes, federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(Yes, federally reportable)',
                    ],
                    9 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally reportable)',
                    ],
                    10 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable)',
                    ],
                    11 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable) / V5:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable) / V5:(No, not federally reportable)',
                    ],
                    12 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable)',
                    ],
                    13 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable)',
                    ],
                    14 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor',
                        'label' => 'V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor',
                    ],
                    15 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable)',
                    ],
                    16 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable)',
                    ],
                    17 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                    ],
                    18 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor',
                        'label' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor',
                    ],
                    19 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable)',
                    ],
                    20 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable) / V5:(No, not federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable) / V5:(No, not federally reportable)',
                    ],
                    21 => [
                        'value' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(Yes, federally reportable)',
                        'label' => 'V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(Yes, federally reportable)',
                    ],
                    22 => [
                        'value' => 'V1:(Yes, federally reportable)',
                        'label' => 'V1:(Yes, federally reportable)',
                    ],
                    23 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable)',
                    ],
                    24 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable)',
                    ],
                    25 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                    ],
                    26 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor',
                        'label' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor',
                    ],
                    27 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable)',
                    ],
                    28 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable)',
                    ],
                    29 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(Yes, federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(Yes, federally reportable)',
                    ],
                    30 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable)',
                    ],
                    31 => [
                        'value' => 'V1:(Yes, federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                        'label' => 'V1:(Yes, federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable)',
                    ],
                    32 => [
                        'value' => 'V2:(No, not federally reportable)',
                        'label' => 'V2:(No, not federally reportable)',
                    ],
                    33 => [
                        'value' => 'V2:(No, not federally reportable) / V3:(No, not federally reportable)',
                        'label' => 'V2:(No, not federally reportable) / V3:(No, not federally reportable)',
                    ],
                ],
            ],
            63 => [
                'name' => 'fmsca_rptbl',
                'label' => 'FMCSA Reportable (Crash)',
                'type' => 'boolean',
            ],
            64 => [
                'name' => 'hit_run_descr',
                'label' => 'Hit and Run',
                'type' => 'boolean',
            ],
            65 => [
                'name' => 'lclty_name',
                'label' => 'Locality',
                'type' => 'multiselect',
                'placeholder' => 'Select Locality',
                'options' => [
                    0 => [
                        'value' => 'HYDE PARK',
                        'label' => 'HYDE PARK',
                    ],
                    1 => [
                        'value' => 'MATTAPAN',
                        'label' => 'MATTAPAN',
                    ],
                    2 => [
                        'value' => 'THORNDIKE',
                        'label' => 'THORNDIKE',
                    ],
                ],
            ],
            66 => [
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
            67 => [
                'name' => 'schl_bus_reld_descr',
                'label' => 'School Bus Related',
                'type' => 'boolean',
            ],
            68 => [
                'name' => 'speed_limit',
                'label' => 'Speed Limit',
                'type' => 'multiselect',
                'placeholder' => 'Select Speed Limit',
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
                        'value' => '5',
                        'label' => '5',
                    ],
                    4 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    5 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                    6 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    7 => [
                        'value' => '14',
                        'label' => '14',
                    ],
                    8 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    9 => [
                        'value' => '20',
                        'label' => '20',
                    ],
                    10 => [
                        'value' => '22',
                        'label' => '22',
                    ],
                    11 => [
                        'value' => '23',
                        'label' => '23',
                    ],
                    12 => [
                        'value' => '24',
                        'label' => '24',
                    ],
                    13 => [
                        'value' => '25',
                        'label' => '25',
                    ],
                    14 => [
                        'value' => '26',
                        'label' => '26',
                    ],
                    15 => [
                        'value' => '28',
                        'label' => '28',
                    ],
                    16 => [
                        'value' => '29',
                        'label' => '29',
                    ],
                    17 => [
                        'value' => '30',
                        'label' => '30',
                    ],
                    18 => [
                        'value' => '35',
                        'label' => '35',
                    ],
                    19 => [
                        'value' => '36',
                        'label' => '36',
                    ],
                    20 => [
                        'value' => '40',
                        'label' => '40',
                    ],
                    21 => [
                        'value' => '43',
                        'label' => '43',
                    ],
                    22 => [
                        'value' => '45',
                        'label' => '45',
                    ],
                    23 => [
                        'value' => '50',
                        'label' => '50',
                    ],
                    24 => [
                        'value' => '55',
                        'label' => '55',
                    ],
                    25 => [
                        'value' => '60',
                        'label' => '60',
                    ],
                    26 => [
                        'value' => '65',
                        'label' => '65',
                    ],
                    27 => [
                        'value' => '85',
                        'label' => '85',
                    ],
                    28 => [
                        'value' => '88',
                        'label' => '88',
                    ],
                ],
            ],
            69 => [
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
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    3 => [
                        'value' => 'Yes, device functioning',
                        'label' => 'Yes, device functioning',
                    ],
                ],
            ],
            70 => [
                'name' => 'work_zone_reld_descr',
                'label' => 'Work Zone Related',
                'type' => 'boolean',
            ],
            71 => [
                'name' => 'aadt_min',
                'label' => 'AADT-linked RD Min',
                'type' => 'number',
                'placeholder' => 'Min value for AADT-linked RD',
            ],
            72 => [
                'name' => 'aadt_max',
                'label' => 'AADT-linked RD Max',
                'type' => 'number',
                'placeholder' => 'Max value for AADT-linked RD',
            ],
            73 => [
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
                        'value' => '2011',
                        'label' => '2011',
                    ],
                    2 => [
                        'value' => '2012',
                        'label' => '2012',
                    ],
                    3 => [
                        'value' => '2013',
                        'label' => '2013',
                    ],
                    4 => [
                        'value' => '2014',
                        'label' => '2014',
                    ],
                    5 => [
                        'value' => '2015',
                        'label' => '2015',
                    ],
                    6 => [
                        'value' => '2016',
                        'label' => '2016',
                    ],
                    7 => [
                        'value' => '2017',
                        'label' => '2017',
                    ],
                    8 => [
                        'value' => '2018',
                        'label' => '2018',
                    ],
                    9 => [
                        'value' => '2019',
                        'label' => '2019',
                    ],
                    10 => [
                        'value' => '2020',
                        'label' => '2020',
                    ],
                ],
            ],
            74 => [
                'name' => 'pk_pct_sut',
                'label' => 'Peak % Single Unit Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Peak % Single Unit Trucks-linked RD',
            ],
            75 => [
                'name' => 'av_pct_sut',
                'label' => 'Average Daily % Single Unit Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Average Daily % Single Unit Trucks-linked RD',
            ],
            76 => [
                'name' => 'pk_pct_ct',
                'label' => 'Peak % Combo Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Peak % Combo Trucks-linked RD',
            ],
            77 => [
                'name' => 'av_pct_ct',
                'label' => 'Average Daily % Combo Trucks-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Average Daily % Combo Trucks-linked RD',
            ],
            78 => [
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
            79 => [
                'name' => 'truck_rte',
                'label' => 'Truck Route-linked RD',
                'type' => 'multiselect',
                'placeholder' => 'Select Truck Route-linked RD',
                'options' => [
                    0 => [
                        'value' => 'Designated truck route ONLY under State Authority.  Fully available to both types of STAA vehicles described above',
                        'label' => 'Designated truck route ONLY under State Authority.  Fully available to both types of STAA vehicles described above',
                    ],
                    1 => [
                        'value' => 'Not a parkway - not on a designated truck route',
                        'label' => 'Not a parkway - not on a designated truck route',
                    ],
                ],
            ],
            80 => [
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
                        'value' => '26.0',
                        'label' => '26.0',
                    ],
                    21 => [
                        'value' => '27.0',
                        'label' => '27.0',
                    ],
                    22 => [
                        'value' => '30.0',
                        'label' => '30.0',
                    ],
                ],
            ],
            81 => [
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
                        'value' => '26.0',
                        'label' => '26.0',
                    ],
                ],
            ],
            82 => [
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
                        'value' => '17.0',
                        'label' => '17.0',
                    ],
                    13 => [
                        'value' => '20.0',
                        'label' => '20.0',
                    ],
                    14 => [
                        'value' => '22.0',
                        'label' => '22.0',
                    ],
                ],
            ],
            83 => [
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
            84 => [
                'name' => 'surface_wd',
                'label' => 'Surface Width-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Surface Width-linked RD',
            ],
            85 => [
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
            86 => [
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
                        'value' => '20.0',
                        'label' => '20.0',
                    ],
                    20 => [
                        'value' => '22.0',
                        'label' => '22.0',
                    ],
                    21 => [
                        'value' => '23.0',
                        'label' => '23.0',
                    ],
                    22 => [
                        'value' => '30.0',
                        'label' => '30.0',
                    ],
                ],
            ],
            87 => [
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
            88 => [
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
                ],
            ],
            89 => [
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
            90 => [
                'name' => 'med_width',
                'label' => 'Median Width-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Median Width-linked RD',
            ],
            91 => [
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
            92 => [
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
            93 => [
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
            94 => [
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
                        'value' => 'Greenfield',
                        'label' => 'Greenfield',
                    ],
                    4 => [
                        'value' => 'Lee',
                        'label' => 'Lee',
                    ],
                    5 => [
                        'value' => 'Leominster-Fitchburg',
                        'label' => 'Leominster-Fitchburg',
                    ],
                    6 => [
                        'value' => 'Nantucket',
                        'label' => 'Nantucket',
                    ],
                    7 => [
                        'value' => 'Nashua (NH-MA)',
                        'label' => 'Nashua (NH-MA)',
                    ],
                    8 => [
                        'value' => 'New Bedford',
                        'label' => 'New Bedford',
                    ],
                    9 => [
                        'value' => 'North Adams (MA-VT)',
                        'label' => 'North Adams (MA-VT)',
                    ],
                    10 => [
                        'value' => 'North Brookfield',
                        'label' => 'North Brookfield',
                    ],
                    11 => [
                        'value' => 'Pittsfield',
                        'label' => 'Pittsfield',
                    ],
                    12 => [
                        'value' => 'Providence (RI-MA)',
                        'label' => 'Providence (RI-MA)',
                    ],
                    13 => [
                        'value' => 'Provincetown',
                        'label' => 'Provincetown',
                    ],
                    14 => [
                        'value' => 'RURAL',
                        'label' => 'RURAL',
                    ],
                    15 => [
                        'value' => 'South Deerfield',
                        'label' => 'South Deerfield',
                    ],
                    16 => [
                        'value' => 'Springfield (MA-CT)',
                        'label' => 'Springfield (MA-CT)',
                    ],
                    17 => [
                        'value' => 'Vineyard Haven',
                        'label' => 'Vineyard Haven',
                    ],
                    18 => [
                        'value' => 'Ware',
                        'label' => 'Ware',
                    ],
                    19 => [
                        'value' => 'Worcester (MA-CT)',
                        'label' => 'Worcester (MA-CT)',
                    ],
                ],
            ],
            95 => [
                'name' => 'fd_aid_rte',
                'label' => 'Federal Aid Route-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Federal Aid Route-linked RD',
            ],
            96 => [
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
                        'value' => 'Ramp - NB/EB',
                        'label' => 'Ramp - NB/EB',
                    ],
                    4 => [
                        'value' => 'Ramp - SB/WB',
                        'label' => 'Ramp - SB/WB',
                    ],
                    5 => [
                        'value' => 'Rotary',
                        'label' => 'Rotary',
                    ],
                    6 => [
                        'value' => 'Roundabout',
                        'label' => 'Roundabout',
                    ],
                    7 => [
                        'value' => 'Simple Ramp - Tunnel',
                        'label' => 'Simple Ramp - Tunnel',
                    ],
                    8 => [
                        'value' => 'Simple Ramp/ Channelized Turning Lane',
                        'label' => 'Simple Ramp/ Channelized Turning Lane',
                    ],
                    9 => [
                        'value' => 'Tunnel',
                        'label' => 'Tunnel',
                    ],
                ],
            ],
            97 => [
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
            98 => [
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
            99 => [
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
            100 => [
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
                        'value' => '10',
                        'label' => '10',
                    ],
                    2 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    3 => [
                        'value' => '20',
                        'label' => '20',
                    ],
                    4 => [
                        'value' => '24',
                        'label' => '24',
                    ],
                    5 => [
                        'value' => '25',
                        'label' => '25',
                    ],
                    6 => [
                        'value' => '30',
                        'label' => '30',
                    ],
                    7 => [
                        'value' => '35',
                        'label' => '35',
                    ],
                    8 => [
                        'value' => '40',
                        'label' => '40',
                    ],
                    9 => [
                        'value' => '45',
                        'label' => '45',
                    ],
                    10 => [
                        'value' => '50',
                        'label' => '50',
                    ],
                    11 => [
                        'value' => '55',
                        'label' => '55',
                    ],
                    12 => [
                        'value' => '60',
                        'label' => '60',
                    ],
                    13 => [
                        'value' => '65',
                        'label' => '65',
                    ],
                    14 => [
                        'value' => '99',
                        'label' => '99',
                    ],
                ],
            ],
            101 => [
                'name' => 'streetname',
                'label' => 'Street Name-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Street Name-linked RD',
            ],
            102 => [
                'name' => 'fromstreetname',
                'label' => 'From Street Name-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter From Street Name-linked RD',
            ],
            103 => [
                'name' => 'tostreetname',
                'label' => 'To Street Name-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter To Street Name-linked RD',
            ],
            104 => [
                'name' => 'city',
                'label' => 'City-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter City-linked RD',
            ],
            105 => [
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
            106 => [
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
            107 => [
                'name' => 'urban_loc_type',
                'label' => 'Urban Location Type-linked RD',
                'type' => 'text',
                'placeholder' => 'Enter Urban Location Type-linked RD',
            ],
            108 => [
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
                ],
            ],
            109 => [
                'name' => 'statn_num_min',
                'label' => 'AADT Station Number-linked RD Min',
                'type' => 'number',
                'placeholder' => 'Min value for AADT Station Number-linked RD',
            ],
            110 => [
                'name' => 'statn_num_max',
                'label' => 'AADT Station Number-linked RD Max',
                'type' => 'number',
                'placeholder' => 'Max value for AADT Station Number-linked RD',
            ],
            111 => [
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
                        'value' => '99',
                        'label' => '99',
                    ],
                ],
            ],
            112 => [
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
            113 => [
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
            114 => [
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
                        'value' => 'All vehicles over 2000 pounds excluded',
                        'label' => 'All vehicles over 2000 pounds excluded',
                    ],
                    3 => [
                        'value' => 'All vehicles over 28 feet in length excluded',
                        'label' => 'All vehicles over 28 feet in length excluded',
                    ],
                    4 => [
                        'value' => 'All vehicles over 3 tons excluded',
                        'label' => 'All vehicles over 3 tons excluded',
                    ],
                    5 => [
                        'value' => 'All vehicles over 5 tons excluded',
                        'label' => 'All vehicles over 5 tons excluded',
                    ],
                    6 => [
                        'value' => 'Cambridge Overnight Exclusions',
                        'label' => 'Cambridge Overnight Exclusions',
                    ],
                    7 => [
                        'value' => 'Commercial vehicles over 5 tons carry capacity excluded',
                        'label' => 'Commercial vehicles over 5 tons carry capacity excluded',
                    ],
                    8 => [
                        'value' => 'Hazardous Truck Route',
                        'label' => 'Hazardous Truck Route',
                    ],
                ],
            ],
            115 => [
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
                        'value' => '11PM to 7AM, 7 Days',
                        'label' => '11PM to 7AM, 7 Days',
                    ],
                    3 => [
                        'value' => '24 Hours, 7 Days',
                        'label' => '24 Hours, 7 Days',
                    ],
                    4 => [
                        'value' => '4PM to 6PM',
                        'label' => '4PM to 6PM',
                    ],
                    5 => [
                        'value' => '5AM to 8PM, 7 Days',
                        'label' => '5AM to 8PM, 7 Days',
                    ],
                    6 => [
                        'value' => '6AM to 10PM, 7 Days',
                        'label' => '6AM to 10PM, 7 Days',
                    ],
                    7 => [
                        'value' => '6AM to 6PM, 7 Days',
                        'label' => '6AM to 6PM, 7 Days',
                    ],
                    8 => [
                        'value' => '6AM to 7PM, 7 Days',
                        'label' => '6AM to 7PM, 7 Days',
                    ],
                    9 => [
                        'value' => '6PM to 6AM, 7 Days',
                        'label' => '6PM to 6AM, 7 Days',
                    ],
                    10 => [
                        'value' => '7AM to 6PM, 7 Days',
                        'label' => '7AM to 6PM, 7 Days',
                    ],
                    11 => [
                        'value' => '7PM to 7AM, 7 Days',
                        'label' => '7PM to 7AM, 7 Days',
                    ],
                    12 => [
                        'value' => '8AM to 930AM and 2PM to 330PM, School Days Only',
                        'label' => '8AM to 930AM and 2PM to 330PM, School Days Only',
                    ],
                    13 => [
                        'value' => '8PM to 6AM, 7 Days',
                        'label' => '8PM to 6AM, 7 Days',
                    ],
                    14 => [
                        'value' => '8PM to 7AM, 7 Days',
                        'label' => '8PM to 7AM, 7 Days',
                    ],
                    15 => [
                        'value' => '9PM to 6AM, 7 Days',
                        'label' => '9PM to 6AM, 7 Days',
                    ],
                    16 => [
                        'value' => '9PM to 7AM, 7 Days',
                        'label' => '9PM to 7AM, 7 Days',
                    ],
                    17 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                ],
            ],
            116 => [
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
            117 => [
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
                ],
            ],
            118 => [
                'name' => 'alc_suspd_type_descr',
                'label' => 'Alcohol Suspected',
                'type' => 'boolean',
            ],
            119 => [
                'name' => 'driver_age_min',
                'label' => 'Driver Age Min',
                'type' => 'number',
                'placeholder' => 'Min value for Driver Age',
            ],
            120 => [
                'name' => 'driver_age_max',
                'label' => 'Driver Age Max',
                'type' => 'number',
                'placeholder' => 'Max value for Driver Age',
            ],
            121 => [
                'name' => 'drvr_cntrb_circ_descr',
                'label' => 'Driver Contributing Circ.',
                'type' => 'text',
                'placeholder' => 'Enter Driver Contributing Circ.',
            ],
            122 => [
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
            123 => [
                'name' => 'drvr_lcn_state',
                'label' => 'Driver License State',
                'type' => 'text',
                'placeholder' => 'Enter Driver License State',
            ],
            124 => [
                'name' => 'drug_suspd_type_descr',
                'label' => 'Drugs Suspected',
                'type' => 'boolean',
            ],
            125 => [
                'name' => 'emergency_use_desc',
                'label' => 'Emergency Use',
                'type' => 'boolean',
            ],
            126 => [
                'name' => 'fmsca_rptbl_vl',
                'label' => 'FMCSA Reportable (Vehicle)',
                'type' => 'boolean',
            ],
            127 => [
                'name' => 'haz_mat_placard_descr',
                'label' => 'Hazmat Placard',
                'type' => 'boolean',
            ],
            128 => [
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
                        'value' => 'Not Applicable',
                        'label' => 'Not Applicable',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Possible Injury (C)',
                        'label' => 'Possible Injury (C)',
                    ],
                    6 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    7 => [
                        'value' => 'Suspected Minor Injury (B)',
                        'label' => 'Suspected Minor Injury (B)',
                    ],
                    8 => [
                        'value' => 'Suspected Serious Injury (A)',
                        'label' => 'Suspected Serious Injury (A)',
                    ],
                    9 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                ],
            ],
            129 => [
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
            130 => [
                'name' => 'total_occpt_in_vehc',
                'label' => 'Total Occupants in Vehicle',
                'type' => 'multiselect',
                'placeholder' => 'Select Total Occupants in Vehicle',
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
                    14 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    15 => [
                        'value' => '16',
                        'label' => '16',
                    ],
                    16 => [
                        'value' => '17',
                        'label' => '17',
                    ],
                    17 => [
                        'value' => '18',
                        'label' => '18',
                    ],
                    18 => [
                        'value' => '21',
                        'label' => '21',
                    ],
                    19 => [
                        'value' => '22',
                        'label' => '22',
                    ],
                    20 => [
                        'value' => '24',
                        'label' => '24',
                    ],
                    21 => [
                        'value' => '26',
                        'label' => '26',
                    ],
                    22 => [
                        'value' => '28',
                        'label' => '28',
                    ],
                    23 => [
                        'value' => '32',
                        'label' => '32',
                    ],
                    24 => [
                        'value' => '33',
                        'label' => '33',
                    ],
                    25 => [
                        'value' => '34',
                        'label' => '34',
                    ],
                    26 => [
                        'value' => '35',
                        'label' => '35',
                    ],
                    27 => [
                        'value' => '38',
                        'label' => '38',
                    ],
                    28 => [
                        'value' => '40',
                        'label' => '40',
                    ],
                    29 => [
                        'value' => '46',
                        'label' => '46',
                    ],
                    30 => [
                        'value' => '49',
                        'label' => '49',
                    ],
                    31 => [
                        'value' => '56',
                        'label' => '56',
                    ],
                ],
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
                        'value' => 'No',
                        'label' => 'No',
                    ],
                    1 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    2 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    3 => [
                        'value' => 'Yes, other reason not disabled',
                        'label' => 'Yes, other reason not disabled',
                    ],
                    4 => [
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
                        'value' => 'Totally ejected',
                        'label' => 'Totally ejected',
                    ],
                    5 => [
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
                        'value' => 'Not Applicable',
                        'label' => 'Not Applicable',
                    ],
                    4 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    5 => [
                        'value' => 'Possible Injury (C)',
                        'label' => 'Possible Injury (C)',
                    ],
                    6 => [
                        'value' => 'Reported but invalid',
                        'label' => 'Reported but invalid',
                    ],
                    7 => [
                        'value' => 'Suspected Minor Injury (B)',
                        'label' => 'Suspected Minor Injury (B)',
                    ],
                    8 => [
                        'value' => 'Suspected Serious Injury (A)',
                        'label' => 'Suspected Serious Injury (A)',
                    ],
                    9 => [
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
                        'value' => 'Reflective clothing',
                        'label' => 'Reflective clothing',
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
                        'value' => 'U - Unknown',
                        'label' => 'U - Unknown',
                    ],
                    4 => [
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
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Action (All Persons)',
                'options' => [
                    0 => [
                        'value' => 'VU2: Approaching or leaving vehicle',
                        'label' => 'VU2: Approaching or leaving vehicle',
                    ],
                    1 => [
                        'value' => 'VU2: Approaching or leaving vehicle / VU3: Standing / VU4: Approaching or leaving vehicle',
                        'label' => 'VU2: Approaching or leaving vehicle / VU3: Standing / VU4: Approaching or leaving vehicle',
                    ],
                    2 => [
                        'value' => 'VU2: Entering or crossing location',
                        'label' => 'VU2: Entering or crossing location',
                    ],
                    3 => [
                        'value' => 'VU2: Entering or crossing location / VU3: Entering or crossing location',
                        'label' => 'VU2: Entering or crossing location / VU3: Entering or crossing location',
                    ],
                    4 => [
                        'value' => 'VU2: Entering or crossing location / VU3: Entering or crossing location / VU4: Entering or crossing location',
                        'label' => 'VU2: Entering or crossing location / VU3: Entering or crossing location / VU4: Entering or crossing location',
                    ],
                    5 => [
                        'value' => 'VU2: None',
                        'label' => 'VU2: None',
                    ],
                    6 => [
                        'value' => 'VU2: Other',
                        'label' => 'VU2: Other',
                    ],
                    7 => [
                        'value' => 'VU2: Other / VU3: Other',
                        'label' => 'VU2: Other / VU3: Other',
                    ],
                    8 => [
                        'value' => 'VU2: Pushing vehicle',
                        'label' => 'VU2: Pushing vehicle',
                    ],
                    9 => [
                        'value' => 'VU2: Standing',
                        'label' => 'VU2: Standing',
                    ],
                    10 => [
                        'value' => 'VU2: Standing / VU3: Other / VU4: Other',
                        'label' => 'VU2: Standing / VU3: Other / VU4: Other',
                    ],
                    11 => [
                        'value' => 'VU2: Standing / VU3: Standing',
                        'label' => 'VU2: Standing / VU3: Standing',
                    ],
                    12 => [
                        'value' => 'VU2: Walking, running or cycling',
                        'label' => 'VU2: Walking, running or cycling',
                    ],
                    13 => [
                        'value' => 'VU2: Walking, running or cycling / VU3: Other',
                        'label' => 'VU2: Walking, running or cycling / VU3: Other',
                    ],
                    14 => [
                        'value' => 'VU2: Walking, running or cycling / VU3: Walking, running or cycling',
                        'label' => 'VU2: Walking, running or cycling / VU3: Walking, running or cycling',
                    ],
                    15 => [
                        'value' => 'VU2: Walking, running or cycling / VU3: Walking, running or cycling / VU4: Walking, running or cycling / VU5: Walking, running or cycling',
                        'label' => 'VU2: Walking, running or cycling / VU3: Walking, running or cycling / VU4: Walking, running or cycling / VU5: Walking, running or cycling',
                    ],
                    16 => [
                        'value' => 'VU2: Working',
                        'label' => 'VU2: Working',
                    ],
                    17 => [
                        'value' => 'VU2: Working / VU4: Working / VU5: Working',
                        'label' => 'VU2: Working / VU4: Working / VU5: Working',
                    ],
                    18 => [
                        'value' => 'VU2: Working on vehicle',
                        'label' => 'VU2: Working on vehicle',
                    ],
                    19 => [
                        'value' => 'VU3: Approaching or leaving vehicle',
                        'label' => 'VU3: Approaching or leaving vehicle',
                    ],
                    20 => [
                        'value' => 'VU3: Entering or crossing location',
                        'label' => 'VU3: Entering or crossing location',
                    ],
                    21 => [
                        'value' => 'VU3: Entering or crossing location / VU4: Entering or crossing location',
                        'label' => 'VU3: Entering or crossing location / VU4: Entering or crossing location',
                    ],
                    22 => [
                        'value' => 'VU3: None',
                        'label' => 'VU3: None',
                    ],
                    23 => [
                        'value' => 'VU3: Other',
                        'label' => 'VU3: Other',
                    ],
                    24 => [
                        'value' => 'VU3: Other / VU4: Other / VU5: Other',
                        'label' => 'VU3: Other / VU4: Other / VU5: Other',
                    ],
                    25 => [
                        'value' => 'VU3: Standing',
                        'label' => 'VU3: Standing',
                    ],
                    26 => [
                        'value' => 'VU3: Walking, running or cycling',
                        'label' => 'VU3: Walking, running or cycling',
                    ],
                    27 => [
                        'value' => 'VU3: Walking, running or cycling / VU4: Walking, running or cycling',
                        'label' => 'VU3: Walking, running or cycling / VU4: Walking, running or cycling',
                    ],
                    28 => [
                        'value' => 'VU3: Working',
                        'label' => 'VU3: Working',
                    ],
                    29 => [
                        'value' => 'VU4: Approaching or leaving vehicle',
                        'label' => 'VU4: Approaching or leaving vehicle',
                    ],
                    30 => [
                        'value' => 'VU4: Entering or crossing location',
                        'label' => 'VU4: Entering or crossing location',
                    ],
                    31 => [
                        'value' => 'VU4: None',
                        'label' => 'VU4: None',
                    ],
                    32 => [
                        'value' => 'VU4: Other',
                        'label' => 'VU4: Other',
                    ],
                    33 => [
                        'value' => 'VU4: Standing',
                        'label' => 'VU4: Standing',
                    ],
                    34 => [
                        'value' => 'VU4: Walking, running or cycling',
                        'label' => 'VU4: Walking, running or cycling',
                    ],
                    35 => [
                        'value' => 'VU4: Working',
                        'label' => 'VU4: Working',
                    ],
                    36 => [
                        'value' => 'VU4: Working / VU5: Working / VU6: Working',
                        'label' => 'VU4: Working / VU5: Working / VU6: Working',
                    ],
                    37 => [
                        'value' => 'VU5: Entering or crossing location',
                        'label' => 'VU5: Entering or crossing location',
                    ],
                    38 => [
                        'value' => 'VU5: Entering or crossing location / VU6: Entering or crossing location',
                        'label' => 'VU5: Entering or crossing location / VU6: Entering or crossing location',
                    ],
                    39 => [
                        'value' => 'VU5: Standing',
                        'label' => 'VU5: Standing',
                    ],
                    40 => [
                        'value' => 'VU5: Walking, running or cycling',
                        'label' => 'VU5: Walking, running or cycling',
                    ],
                    41 => [
                        'value' => 'VU5: Walking, running or cycling / VU6: Walking, running or cycling',
                        'label' => 'VU5: Walking, running or cycling / VU6: Walking, running or cycling',
                    ],
                    42 => [
                        'value' => 'VU6: Approaching or leaving vehicle',
                        'label' => 'VU6: Approaching or leaving vehicle',
                    ],
                    43 => [
                        'value' => 'VU6: Standing',
                        'label' => 'VU6: Standing',
                    ],
                    44 => [
                        'value' => 'VU6: Working on vehicle',
                        'label' => 'VU6: Working on vehicle',
                    ],
                ],
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
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    2 => [
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
                        'value' => 'Non-intersection crosswalk',
                        'label' => 'Non-intersection crosswalk',
                    ],
                    5 => [
                        'value' => 'Not in roadway',
                        'label' => 'Not in roadway',
                    ],
                    6 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    7 => [
                        'value' => 'On-Street Bike Lanes',
                        'label' => 'On-Street Bike Lanes',
                    ],
                    8 => [
                        'value' => 'On-Street Buffered Bike Lanes',
                        'label' => 'On-Street Buffered Bike Lanes',
                    ],
                    9 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    10 => [
                        'value' => 'Raised Crosswalk',
                        'label' => 'Raised Crosswalk',
                    ],
                    11 => [
                        'value' => 'Separated Bike Lanes',
                        'label' => 'Separated Bike Lanes',
                    ],
                    12 => [
                        'value' => 'Shared-use path or trails Crossing',
                        'label' => 'Shared-use path or trails Crossing',
                    ],
                    13 => [
                        'value' => 'Shoulder',
                        'label' => 'Shoulder',
                    ],
                    14 => [
                        'value' => 'Sidewalk',
                        'label' => 'Sidewalk',
                    ],
                    15 => [
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
                        'value' => 'Pedestrian',
                        'label' => 'Pedestrian',
                    ],
                    13 => [
                        'value' => 'Roadway Worker - Outside of vehicle',
                        'label' => 'Roadway Worker - Outside of vehicle',
                    ],
                    14 => [
                        'value' => 'Skateboarder',
                        'label' => 'Skateboarder',
                    ],
                    15 => [
                        'value' => 'Train/Trolley passenger',
                        'label' => 'Train/Trolley passenger',
                    ],
                    16 => [
                        'value' => 'Tricyclist',
                        'label' => 'Tricyclist',
                    ],
                    17 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    18 => [
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
                        'value' => 'VU2: Going to or from a Delivery Vehicle',
                        'label' => 'VU2: Going to or from a Delivery Vehicle',
                    ],
                    2 => [
                        'value' => 'VU2: Going to or from a Delivery Vehicle / VU3: Going to or from a Delivery Vehicle / VU4: Going to or from a Delivery Vehicle',
                        'label' => 'VU2: Going to or from a Delivery Vehicle / VU3: Going to or from a Delivery Vehicle / VU4: Going to or from a Delivery Vehicle',
                    ],
                    3 => [
                        'value' => 'VU2: Going to or from a Mailbox',
                        'label' => 'VU2: Going to or from a Mailbox',
                    ],
                    4 => [
                        'value' => 'VU2: Going to or from a School Bus or a School Bus Stop',
                        'label' => 'VU2: Going to or from a School Bus or a School Bus Stop',
                    ],
                    5 => [
                        'value' => 'VU2: Going to or from a School Bus or a School Bus Stop / VU3: Going to or from a School Bus or a School Bus Stop',
                        'label' => 'VU2: Going to or from a School Bus or a School Bus Stop / VU3: Going to or from a School Bus or a School Bus Stop',
                    ],
                    6 => [
                        'value' => 'VU2: Going to or from an Ice Cream or Food Truck',
                        'label' => 'VU2: Going to or from an Ice Cream or Food Truck',
                    ],
                    7 => [
                        'value' => 'VU2: Going to or from School (K-12)',
                        'label' => 'VU2: Going to or from School (K-12)',
                    ],
                    8 => [
                        'value' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12)',
                        'label' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12)',
                    ],
                    9 => [
                        'value' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12) / VU4: Going to or from School (K-12)',
                        'label' => 'VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12) / VU4: Going to or from School (K-12)',
                    ],
                    10 => [
                        'value' => 'VU2: Going to or from Transit',
                        'label' => 'VU2: Going to or from Transit',
                    ],
                    11 => [
                        'value' => 'VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit',
                        'label' => 'VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit',
                    ],
                    12 => [
                        'value' => 'VU2: Other',
                        'label' => 'VU2: Other',
                    ],
                    13 => [
                        'value' => 'VU2: Other / VU3: Other',
                        'label' => 'VU2: Other / VU3: Other',
                    ],
                    14 => [
                        'value' => 'VU2: Other / VU3: Other / VU4: Other / VU5: Other',
                        'label' => 'VU2: Other / VU3: Other / VU4: Other / VU5: Other',
                    ],
                    15 => [
                        'value' => 'VU2: Other / VU4: Other / VU5: Other',
                        'label' => 'VU2: Other / VU4: Other / VU5: Other',
                    ],
                    16 => [
                        'value' => 'VU3: Going to or from a Delivery Vehicle',
                        'label' => 'VU3: Going to or from a Delivery Vehicle',
                    ],
                    17 => [
                        'value' => 'VU3: Going to or from a School Bus or a School Bus Stop',
                        'label' => 'VU3: Going to or from a School Bus or a School Bus Stop',
                    ],
                    18 => [
                        'value' => 'VU3: Going to or from School (K-12)',
                        'label' => 'VU3: Going to or from School (K-12)',
                    ],
                    19 => [
                        'value' => 'VU3: Going to or from Transit',
                        'label' => 'VU3: Going to or from Transit',
                    ],
                    20 => [
                        'value' => 'VU3: Other',
                        'label' => 'VU3: Other',
                    ],
                    21 => [
                        'value' => 'VU3: Other / VU4: Other',
                        'label' => 'VU3: Other / VU4: Other',
                    ],
                    22 => [
                        'value' => 'VU4: Going to or from School (K-12)',
                        'label' => 'VU4: Going to or from School (K-12)',
                    ],
                    23 => [
                        'value' => 'VU4: Going to or from Transit',
                        'label' => 'VU4: Going to or from Transit',
                    ],
                    24 => [
                        'value' => 'VU4: Other',
                        'label' => 'VU4: Other',
                    ],
                    25 => [
                        'value' => 'VU4: Other / VU5: Other / VU6: Other',
                        'label' => 'VU4: Other / VU5: Other / VU6: Other',
                    ],
                    26 => [
                        'value' => 'VU5: Going to or from Transit',
                        'label' => 'VU5: Going to or from Transit',
                    ],
                    27 => [
                        'value' => 'VU5: Other',
                        'label' => 'VU5: Other',
                    ],
                    28 => [
                        'value' => 'VU5: Other / VU6: Other',
                        'label' => 'VU5: Other / VU6: Other',
                    ],
                    29 => [
                        'value' => 'VU6: Other',
                        'label' => 'VU6: Other',
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
                        'value' => 'VU2:(Manually operating an electronic device (texting, typing, dialing))',
                        'label' => 'VU2:(Manually operating an electronic device (texting, typing, dialing))',
                    ],
                    2 => [
                        'value' => 'VU2:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted)',
                    ],
                    3 => [
                        'value' => 'VU2:(Not Distracted) VU3:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU3:(Not Distracted)',
                    ],
                    4 => [
                        'value' => 'VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted)',
                    ],
                    5 => [
                        'value' => 'VU2:(Not Distracted) VU4:(Not Distracted) VU5:(Not Distracted)',
                        'label' => 'VU2:(Not Distracted) VU4:(Not Distracted) VU5:(Not Distracted)',
                    ],
                    6 => [
                        'value' => 'VU2:(Not Distracted),(Manually operating an electronic device (texting, typing, dialing))',
                        'label' => 'VU2:(Not Distracted),(Manually operating an electronic device (texting, typing, dialing))',
                    ],
                    7 => [
                        'value' => 'VU2:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU2:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    8 => [
                        'value' => 'VU2:(Talking on hand-held electronic device)',
                        'label' => 'VU2:(Talking on hand-held electronic device)',
                    ],
                    9 => [
                        'value' => 'VU2:(Talking on hands-free electronic device)',
                        'label' => 'VU2:(Talking on hands-free electronic device)',
                    ],
                    10 => [
                        'value' => 'VU2:(Utilizing listening device)',
                        'label' => 'VU2:(Utilizing listening device)',
                    ],
                    11 => [
                        'value' => 'VU3:(Manually operating an electronic device (texting, typing, dialing))',
                        'label' => 'VU3:(Manually operating an electronic device (texting, typing, dialing))',
                    ],
                    12 => [
                        'value' => 'VU3:(Not Distracted)',
                        'label' => 'VU3:(Not Distracted)',
                    ],
                    13 => [
                        'value' => 'VU3:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU3:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    14 => [
                        'value' => 'VU3:(Passenger)',
                        'label' => 'VU3:(Passenger)',
                    ],
                    15 => [
                        'value' => 'VU3:(Utilizing listening device)',
                        'label' => 'VU3:(Utilizing listening device)',
                    ],
                    16 => [
                        'value' => 'VU4:(Not Distracted)',
                        'label' => 'VU4:(Not Distracted)',
                    ],
                    17 => [
                        'value' => 'VU4:(Not Distracted) VU5:(Not Distracted) VU6:(Not Distracted)',
                        'label' => 'VU4:(Not Distracted) VU5:(Not Distracted) VU6:(Not Distracted)',
                    ],
                    18 => [
                        'value' => 'VU5:(Not Distracted)',
                        'label' => 'VU5:(Not Distracted)',
                    ],
                    19 => [
                        'value' => 'VU5:(Not Distracted) VU6:(Not Distracted)',
                        'label' => 'VU5:(Not Distracted) VU6:(Not Distracted)',
                    ],
                    20 => [
                        'value' => 'VU5:(Other activity (searching, eating, personal hygiene, etc.))',
                        'label' => 'VU5:(Other activity (searching, eating, personal hygiene, etc.))',
                    ],
                    21 => [
                        'value' => 'VU6:(Not Distracted)',
                        'label' => 'VU6:(Not Distracted)',
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
                        'value' => 'VU2: No',
                        'label' => 'VU2: No',
                    ],
                    2 => [
                        'value' => 'VU2: No / VU3: No',
                        'label' => 'VU2: No / VU3: No',
                    ],
                    3 => [
                        'value' => 'VU2: No / VU3: No / VU4: No',
                        'label' => 'VU2: No / VU3: No / VU4: No',
                    ],
                    4 => [
                        'value' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                    ],
                    5 => [
                        'value' => 'VU2: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU4: No / VU5: No',
                    ],
                    6 => [
                        'value' => 'VU2: Yes',
                        'label' => 'VU2: Yes',
                    ],
                    7 => [
                        'value' => 'VU3: No',
                        'label' => 'VU3: No',
                    ],
                    8 => [
                        'value' => 'VU3: No / VU4: No',
                        'label' => 'VU3: No / VU4: No',
                    ],
                    9 => [
                        'value' => 'VU3: Yes',
                        'label' => 'VU3: Yes',
                    ],
                    10 => [
                        'value' => 'VU4: No',
                        'label' => 'VU4: No',
                    ],
                    11 => [
                        'value' => 'VU4: No / VU5: No / VU6: No',
                        'label' => 'VU4: No / VU5: No / VU6: No',
                    ],
                    12 => [
                        'value' => 'VU5: No',
                        'label' => 'VU5: No',
                    ],
                    13 => [
                        'value' => 'VU5: No / VU6: No',
                        'label' => 'VU5: No / VU6: No',
                    ],
                    14 => [
                        'value' => 'VU5: Yes',
                        'label' => 'VU5: Yes',
                    ],
                    15 => [
                        'value' => 'VU6: No',
                        'label' => 'VU6: No',
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
                        'value' => 'VU2: No',
                        'label' => 'VU2: No',
                    ],
                    2 => [
                        'value' => 'VU2: No / VU3: No',
                        'label' => 'VU2: No / VU3: No',
                    ],
                    3 => [
                        'value' => 'VU2: No / VU3: No / VU4: No',
                        'label' => 'VU2: No / VU3: No / VU4: No',
                    ],
                    4 => [
                        'value' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU3: No / VU4: No / VU5: No',
                    ],
                    5 => [
                        'value' => 'VU2: No / VU4: No / VU5: No',
                        'label' => 'VU2: No / VU4: No / VU5: No',
                    ],
                    6 => [
                        'value' => 'VU2: Yes',
                        'label' => 'VU2: Yes',
                    ],
                    7 => [
                        'value' => 'VU3: No',
                        'label' => 'VU3: No',
                    ],
                    8 => [
                        'value' => 'VU3: No / VU4: No',
                        'label' => 'VU3: No / VU4: No',
                    ],
                    9 => [
                        'value' => 'VU4: No',
                        'label' => 'VU4: No',
                    ],
                    10 => [
                        'value' => 'VU4: No / VU5: No / VU6: No',
                        'label' => 'VU4: No / VU5: No / VU6: No',
                    ],
                    11 => [
                        'value' => 'VU5: No',
                        'label' => 'VU5: No',
                    ],
                    12 => [
                        'value' => 'VU5: No / VU6: No',
                        'label' => 'VU5: No / VU6: No',
                    ],
                    13 => [
                        'value' => 'VU6: No',
                        'label' => 'VU6: No',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable Users Traffic Control Device Type (All Persons)',
                'options' => [
                    0 => [
                        'value' => 'VU10: None',
                        'label' => 'VU10: None',
                    ],
                    1 => [
                        'value' => 'VU2: None',
                        'label' => 'VU2: None',
                    ],
                    2 => [
                        'value' => 'VU2: None / VU3: None',
                        'label' => 'VU2: None / VU3: None',
                    ],
                    3 => [
                        'value' => 'VU2: None / VU3: None / VU4: None',
                        'label' => 'VU2: None / VU3: None / VU4: None',
                    ],
                    4 => [
                        'value' => 'VU2: None / VU3: None / VU4: None / VU5: None',
                        'label' => 'VU2: None / VU3: None / VU4: None / VU5: None',
                    ],
                    5 => [
                        'value' => 'VU2: None / VU3: Person - Crossing guard',
                        'label' => 'VU2: None / VU3: Person - Crossing guard',
                    ],
                    6 => [
                        'value' => 'VU2: None / VU4: None / VU5: None',
                        'label' => 'VU2: None / VU4: None / VU5: None',
                    ],
                    7 => [
                        'value' => 'VU2: Other',
                        'label' => 'VU2: Other',
                    ],
                    8 => [
                        'value' => 'VU2: Other / VU3: Other',
                        'label' => 'VU2: Other / VU3: Other',
                    ],
                    9 => [
                        'value' => 'VU2: Person - Crossing guard',
                        'label' => 'VU2: Person - Crossing guard',
                    ],
                    10 => [
                        'value' => 'VU2: Person - Crossing guard / VU3: Person - Crossing guard',
                        'label' => 'VU2: Person - Crossing guard / VU3: Person - Crossing guard',
                    ],
                    11 => [
                        'value' => 'VU2: VU Crossing Sign',
                        'label' => 'VU2: VU Crossing Sign',
                    ],
                    12 => [
                        'value' => 'VU2: VU Crossing Sign / VU3: VU Crossing Sign',
                        'label' => 'VU2: VU Crossing Sign / VU3: VU Crossing Sign',
                    ],
                    13 => [
                        'value' => 'VU2: VU Crossing Signal',
                        'label' => 'VU2: VU Crossing Signal',
                    ],
                    14 => [
                        'value' => 'VU2: VU Crossing Signal / VU3: VU Crossing Signal',
                        'label' => 'VU2: VU Crossing Signal / VU3: VU Crossing Signal',
                    ],
                    15 => [
                        'value' => 'VU2: VU Crossing Signal / VU3: VU Crossing Signal / VU4: VU Crossing Signal',
                        'label' => 'VU2: VU Crossing Signal / VU3: VU Crossing Signal / VU4: VU Crossing Signal',
                    ],
                    16 => [
                        'value' => 'VU2: VU Prohibited Sign',
                        'label' => 'VU2: VU Prohibited Sign',
                    ],
                    17 => [
                        'value' => 'VU3: None',
                        'label' => 'VU3: None',
                    ],
                    18 => [
                        'value' => 'VU3: None / VU4: None',
                        'label' => 'VU3: None / VU4: None',
                    ],
                    19 => [
                        'value' => 'VU3: Other',
                        'label' => 'VU3: Other',
                    ],
                    20 => [
                        'value' => 'VU3: Other / VU4: Other',
                        'label' => 'VU3: Other / VU4: Other',
                    ],
                    21 => [
                        'value' => 'VU3: Person - Crossing guard',
                        'label' => 'VU3: Person - Crossing guard',
                    ],
                    22 => [
                        'value' => 'VU3: VU Crossing Sign',
                        'label' => 'VU3: VU Crossing Sign',
                    ],
                    23 => [
                        'value' => 'VU3: VU Crossing Signal',
                        'label' => 'VU3: VU Crossing Signal',
                    ],
                    24 => [
                        'value' => 'VU3: VU Prohibited Sign',
                        'label' => 'VU3: VU Prohibited Sign',
                    ],
                    25 => [
                        'value' => 'VU4: None',
                        'label' => 'VU4: None',
                    ],
                    26 => [
                        'value' => 'VU4: Other',
                        'label' => 'VU4: Other',
                    ],
                    27 => [
                        'value' => 'VU4: Other / VU5: Other / VU6: Other',
                        'label' => 'VU4: Other / VU5: Other / VU6: Other',
                    ],
                    28 => [
                        'value' => 'VU4: VU Crossing Sign',
                        'label' => 'VU4: VU Crossing Sign',
                    ],
                    29 => [
                        'value' => 'VU4: VU Crossing Signal',
                        'label' => 'VU4: VU Crossing Signal',
                    ],
                    30 => [
                        'value' => 'VU4: VU Prohibited Sign',
                        'label' => 'VU4: VU Prohibited Sign',
                    ],
                    31 => [
                        'value' => 'VU5: None',
                        'label' => 'VU5: None',
                    ],
                    32 => [
                        'value' => 'VU5: Other',
                        'label' => 'VU5: Other',
                    ],
                    33 => [
                        'value' => 'VU5: VU Crossing Signal',
                        'label' => 'VU5: VU Crossing Signal',
                    ],
                    34 => [
                        'value' => 'VU5: VU Crossing Signal / VU6: VU Crossing Signal',
                        'label' => 'VU5: VU Crossing Signal / VU6: VU Crossing Signal',
                    ],
                    35 => [
                        'value' => 'VU6: None',
                        'label' => 'VU6: None',
                    ],
                ],
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
                        'value' => 'In Roadway (Standing, Lying, Working, Playing, etc.)',
                        'label' => 'In Roadway (Standing, Lying, Working, Playing, etc.)',
                    ],
                    9 => [
                        'value' => 'Inattentive (Talking, Eating, etc.)',
                        'label' => 'Inattentive (Talking, Eating, etc.)',
                    ],
                    10 => [
                        'value' => 'None',
                        'label' => 'None',
                    ],
                    11 => [
                        'value' => 'Not reported',
                        'label' => 'Not reported',
                    ],
                    12 => [
                        'value' => 'Not Visible (Dark Clothing, No Lighting, etc.)',
                        'label' => 'Not Visible (Dark Clothing, No Lighting, etc.)',
                    ],
                    13 => [
                        'value' => 'Other (Explain in Narrative)',
                        'label' => 'Other (Explain in Narrative)',
                    ],
                    14 => [
                        'value' => 'Passing',
                        'label' => 'Passing',
                    ],
                    15 => [
                        'value' => 'Traveling Wrong Way',
                        'label' => 'Traveling Wrong Way',
                    ],
                    16 => [
                        'value' => 'Turn/Merge',
                        'label' => 'Turn/Merge',
                    ],
                    17 => [
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
                        'value' => 'Talking on hand-held electronic device',
                        'label' => 'Talking on hand-held electronic device',
                    ],
                    6 => [
                        'value' => 'Talking on hands-free electronic device',
                        'label' => 'Talking on hands-free electronic device',
                    ],
                    7 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    8 => [
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
                        'value' => 'Unknown',
                        'label' => 'Unknown',
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
                        'value' => 'Turning right',
                        'label' => 'Turning right',
                    ],
                    15 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    16 => [
                        'value' => 'Waiting to Cross Roadway',
                        'label' => 'Waiting to Cross Roadway',
                    ],
                    17 => [
                        'value' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                    ],
                    18 => [
                        'value' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                    ],
                    19 => [
                        'value' => 'Walking/Cycling on Sidewalk',
                        'label' => 'Walking/Cycling on Sidewalk',
                    ],
                    20 => [
                        'value' => 'Working - other',
                        'label' => 'Working - other',
                    ],
                    21 => [
                        'value' => 'Working in Trafficway (Incident Response)',
                        'label' => 'Working in Trafficway (Incident Response)',
                    ],
                    22 => [
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
                        'value' => 'Collision with door opening of parked car - Back right',
                        'label' => 'Collision with door opening of parked car - Back right',
                    ],
                    3 => [
                        'value' => 'Collision with door opening of parked car - Front right',
                        'label' => 'Collision with door opening of parked car - Front right',
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
                        'value' => 'Traveling straight ahead',
                        'label' => 'Traveling straight ahead',
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
                        'value' => 'Working - other',
                        'label' => 'Working - other',
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
                        'value' => 'Approaching or leaving vehicle',
                        'label' => 'Approaching or leaving vehicle',
                    ],
                    1 => [
                        'value' => 'Collision with motor vehicle in transport',
                        'label' => 'Collision with motor vehicle in transport',
                    ],
                    2 => [
                        'value' => 'Crossing Roadway',
                        'label' => 'Crossing Roadway',
                    ],
                    3 => [
                        'value' => 'In Roadway – Other',
                        'label' => 'In Roadway – Other',
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
                        'value' => 'Overtaking/passing',
                        'label' => 'Overtaking/passing',
                    ],
                    7 => [
                        'value' => 'Traveling straight ahead',
                        'label' => 'Traveling straight ahead',
                    ],
                    8 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    9 => [
                        'value' => 'Waiting to Cross Roadway',
                        'label' => 'Waiting to Cross Roadway',
                    ],
                    10 => [
                        'value' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane)',
                    ],
                    11 => [
                        'value' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                        'label' => 'Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane)',
                    ],
                    12 => [
                        'value' => 'Walking/Cycling on Sidewalk',
                        'label' => 'Walking/Cycling on Sidewalk',
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
                        'value' => 'FL',
                        'label' => 'FL',
                    ],
                    4 => [
                        'value' => 'FR',
                        'label' => 'FR',
                    ],
                    5 => [
                        'value' => 'GA',
                        'label' => 'GA',
                    ],
                    6 => [
                        'value' => 'ID',
                        'label' => 'ID',
                    ],
                    7 => [
                        'value' => 'LA',
                        'label' => 'LA',
                    ],
                    8 => [
                        'value' => 'MA',
                        'label' => 'MA',
                    ],
                    9 => [
                        'value' => 'ME',
                        'label' => 'ME',
                    ],
                    10 => [
                        'value' => 'NH',
                        'label' => 'NH',
                    ],
                    11 => [
                        'value' => 'NJ',
                        'label' => 'NJ',
                    ],
                    12 => [
                        'value' => 'NY',
                        'label' => 'NY',
                    ],
                    13 => [
                        'value' => 'OH',
                        'label' => 'OH',
                    ],
                    14 => [
                        'value' => 'PA',
                        'label' => 'PA',
                    ],
                    15 => [
                        'value' => 'RI',
                        'label' => 'RI',
                    ],
                    16 => [
                        'value' => 'TX',
                        'label' => 'TX',
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
                        'value' => 'Trailing unit',
                        'label' => 'Trailing unit',
                    ],
                    9 => [
                        'value' => 'Unenclosed passenger area',
                        'label' => 'Unenclosed passenger area',
                    ],
                    10 => [
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
                        'value' => 'Test Not Given',
                        'label' => 'Test Not Given',
                    ],
                    4 => [
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
                ],
            ],
            192 => [
                'name' => 'non_mtrst_test_result_descr',
                'label' => 'Vulnerable User Test Result',
                'type' => 'multiselect',
                'placeholder' => 'Select Vulnerable User Test Result',
                'options' => [
                    0 => [
                        'value' => 'BAC Test Performed, Results Unknown',
                        'label' => 'BAC Test Performed, Results Unknown',
                    ],
                    1 => [
                        'value' => 'Not Reported',
                        'label' => 'Not Reported',
                    ],
                    2 => [
                        'value' => 'Test Not Given',
                        'label' => 'Test Not Given',
                    ],
                    3 => [
                        'value' => 'Unknown',
                        'label' => 'Unknown',
                    ],
                    4 => [
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
                'description' => 'Filter by Crash Severity. Provide a comma-separated list or an array of values. Possible values: Fatal injury, Non-fatal injury, Property damage only (none injured), Unknown.',
            ],
            'max_injr_svrty_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Max Injury Severity Reported. Provide a comma-separated list or an array of values. Possible values: Deceased not caused by crash, Fatal injury (K), No Apparent Injury (O), Not Applicable, Not reported, Possible Injury (C), Reported but invalid, Suspected Minor Injury (B), Suspected Serious Injury (A), Unknown.',
            ],
            'numb_vehc' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Number of Vehicles. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 10, 13.',
            ],
            'numb_nonfatal_injr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Total NonFatal Injuries. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10.',
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
                'description' => 'Filter by Police Agency Type. Provide a comma-separated list or an array of values. Possible values: Local police, MBTA police, State police.',
            ],
            'year' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Year. Provide a comma-separated list or an array of values. Possible values: 2024, 2025.',
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
                'description' => 'Filter by Road Surface Condition. Provide a comma-separated list or an array of values. Possible values: Dry, Ice, Not reported, Other, Sand, mud, dirt, oil, gravel, Slush, Snow, Unknown, Water (standing, moving), Wet.',
            ],
            'first_hrmf_event_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by First Harmful Event. Provide a comma-separated list or an array of values. Possible values: Collision with animal - deer, Collision with animal - other, Collision with bridge, Collision with bridge overhead structure, Collision with curb, Collision with cyclist, Collision with ditch, Collision with embankment, Collision with guardrail, Collision with median barrier, Collision with motor vehicle in traffic, Collision with other light pole or other post/support, Collision with other movable object, Collision with Other Vulnerable User, Collision with parked motor vehicle, Collision with pedestrian, Collision with railway vehicle (e.g., train, engine), Collision with tree, Collision with unknown fixed object, Collision with utility pole, Collision with work zone maintenance equipment, Collison with moped, Jackknife, Not reported, Other, Other non-collision, Overturn/rollover, Unknown, Unknown non-collision.',
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
                'description' => 'Filter by Roadway Junction Type. Provide a comma-separated list or an array of values. Possible values: Driveway, Five-point or more, Four-way intersection, Not at junction, Not reported, Off-ramp, On-ramp, Railway grade crossing, T-intersection, Traffic circle, Unknown, Y-intersection.',
            ],
            'traf_cntrl_devc_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Traffic Control Device Type. Provide a comma-separated list or an array of values. Possible values: Flashing traffic control signal, No controls, Not reported, Pedestrian Crossing signal/beacon, Railway crossing device, School zone signs, Stop signs, Traffic control signal, Unknown, Warning signs, Yield signs.',
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
                'description' => 'Filter by Jurisdiction-linked RD. Provide a comma-separated list or an array of values. Possible values: City or Town accepted road, County Institutional, Department of Conservation and Recreation, Federal Park or Forest, Massachusetts Department of Transportation, Massachusetts Port Authority, Private, State Institutional, State Park or Forest, Unaccepted by city or town, US Air Force, US Army, US Army Corps of Engineers.',
            ],
            'first_hrmf_event_loc_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by First Harmful Event Location. Provide a comma-separated list or an array of values. Possible values: Median, Not reported, Outside roadway, Roadside, Roadway, Shoulder - paved, Shoulder - travel lane, Shoulder - unpaved, Unknown.',
            ],
            'is_geocoded_status' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Is Geocoded. Provide a comma-separated list or an array of values. Possible values: Multiple, Yes.',
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
            'location_min' => [
                'type' => 'integer',
                'description' => 'Minimum value for Location.',
            ],
            'location_max' => [
                'type' => 'integer',
                'description' => 'Maximum value for Location.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by FMCSA Reportable (All Vehicles). Provide a comma-separated list or an array of values. Possible values: V1:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally re, V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(Yes, federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(Yes, federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable) / V5:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable), V1:(No, not federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor, V1:(No, not federally reportable) / V2:(Yes, federally reportable), V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable), V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable), V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor, V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable), V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(Yes, federally reportable) / V5:(No, not federally reportable), V1:(No, not federally reportable) / V2:(Yes, federally reportable) / V3:(Yes, federally reportable), V1:(Yes, federally reportable), V1:(Yes, federally reportable) / V2:(No, not federally reportable), V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable), V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable), V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable) / V5:(No, not federally reportable) / V6:(No, not federally reportable) / V7:(No, not federally repor, V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable), V1:(Yes, federally reportable) / V2:(No, not federally reportable) / V3:(Yes, federally reportable) / V4:(No, not federally reportable), V1:(Yes, federally reportable) / V2:(Yes, federally reportable), V1:(Yes, federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable), V1:(Yes, federally reportable) / V2:(Yes, federally reportable) / V3:(No, not federally reportable) / V4:(No, not federally reportable), V2:(No, not federally reportable), V2:(No, not federally reportable) / V3:(No, not federally reportable).',
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
                'description' => 'Filter by Locality. Provide a comma-separated list or an array of values. Possible values: HYDE PARK, MATTAPAN, THORNDIKE.',
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
            'speed_limit' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Speed Limit. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 5, 8, 9, 10, 14, 15, 20, 22, 23, 24, 25, 26, 28, 29, 30, 35, 36, 40, 43, 45, 50, 55, 60, 65, 85, 88.',
            ],
            'traf_cntrl_devc_func_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Traffic Control Device Functioning. Provide a comma-separated list or an array of values. Possible values: No, device not functioning, Not reported, Unknown, Yes, device functioning.',
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
                'description' => 'Filter by AADT Year-linked RD. Provide a comma-separated list or an array of values. Possible values: 2009, 2011, 2012, 2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020.',
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
                'description' => 'Filter by Truck Route-linked RD. Provide a comma-separated list or an array of values. Possible values: Designated truck route ONLY under State Authority.  Fully available to both types of STAA vehicles described above, Not a parkway - not on a designated truck route.',
            ],
            'lt_sidewlk' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Left Sidewalk Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 18.0, 20.0, 22.0, 26.0, 27.0, 30.0.',
            ],
            'rt_sidewlk' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Right Sidewalk Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 20.0, 22.0, 26.0.',
            ],
            'shldr_lt_w' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Left Shoulder Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 10.0, 12.0, 13.0, 17.0, 20.0, 22.0.',
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
                'description' => 'Filter by Right Shoulder Width-linked RD. Provide a comma-separated list or an array of values. Possible values: 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 20.0, 22.0, 23.0, 30.0.',
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
                'description' => 'Filter by Number of Travel Lanes-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5.',
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
                'description' => 'Filter by Urbanized Area-linked RD. Provide a comma-separated list or an array of values. Possible values: Athol, Barnstable Town, Boston (MA-NH-RI), Greenfield, Lee, Leominster-Fitchburg, Nantucket, Nashua (NH-MA), New Bedford, North Adams (MA-VT), North Brookfield, Pittsfield, Providence (RI-MA), Provincetown, RURAL, South Deerfield, Springfield (MA-CT), Vineyard Haven, Ware, Worcester (MA-CT).',
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
                'description' => 'Filter by Facility Type-linked RD. Provide a comma-separated list or an array of values. Possible values: Collector - Distributor, Doubledeck, Mainline roadway, Ramp - NB/EB, Ramp - SB/WB, Rotary, Roundabout, Simple Ramp - Tunnel, Simple Ramp/ Channelized Turning Lane, Tunnel.',
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
                'description' => 'Filter by Speed Limit-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, 10, 15, 20, 24, 25, 30, 35, 40, 45, 50, 55, 60, 65, 99.',
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
                'description' => 'Filter by AADT Derivation-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, AADT synchronized with other stations on the segment, Actual, Calculated from Partial Counts, Combined from child AADT\'s, Doubled from single direction, Estimate, Grown, Grown from Prior Year HPMS Network, Modified by Ramp Balancing, Pulled back from HPMS network estimation routine.',
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
                'description' => 'Filter by Opposing Direction Speed Limit-linked RD. Provide a comma-separated list or an array of values. Possible values: 0, 15, 20, 25, 30, 35, 40, 45, 50, 55, 99.',
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
                'description' => 'Filter by Truck Exclusion Type-linked RD. Provide a comma-separated list or an array of values. Possible values: All vehicles over 10 tons excluded, All vehicles over 2.5 tons excluded, All vehicles over 2000 pounds excluded, All vehicles over 28 feet in length excluded, All vehicles over 3 tons excluded, All vehicles over 5 tons excluded, Cambridge Overnight Exclusions, Commercial vehicles over 5 tons carry capacity excluded, Hazardous Truck Route.',
            ],
            't_exc_time' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Truck Exclusion Time-linked RD. Provide a comma-separated list or an array of values. Possible values: 10PM to 6AM, 7 Days, 11PM to 6AM, 7 Days, 11PM to 7AM, 7 Days, 24 Hours, 7 Days, 4PM to 6PM, 5AM to 8PM, 7 Days, 6AM to 10PM, 7 Days, 6AM to 6PM, 7 Days, 6AM to 7PM, 7 Days, 6PM to 6AM, 7 Days, 7AM to 6PM, 7 Days, 7PM to 7AM, 7 Days, 8AM to 930AM and 2PM to 330PM, School Days Only, 8PM to 6AM, 7 Days, 8PM to 7AM, 7 Days, 9PM to 6AM, 7 Days, 9PM to 7AM, 7 Days, None.',
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
                'description' => 'Filter by Vehicle Unit Number. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13.',
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
                'description' => 'Filter by Maximum Injury Severity in Vehicle. Provide a comma-separated list or an array of values. Possible values: Deceased not caused by crash, Fatal injury (K), No Apparent Injury (O), Not Applicable, Not reported, Possible Injury (C), Reported but invalid, Suspected Minor Injury (B), Suspected Serious Injury (A), Unknown.',
            ],
            'most_hrmf_event' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Most Harmful Event (Vehicle). Provide a comma-separated list or an array of values. Possible values: Cargo/equipment loss or shift, Collision with animal - deer, Collision with animal - other, Collision with bridge, Collision with bridge overhead structure, Collision with curb, Collision with cyclist (bicycle, tricycle, unicycle, pedal car), Collision with ditch, Collision with embankment, Collision with fence, Collision with guardrail, Collision with highway traffic sign post, Collision with impact attenuator/crash cushion, Collision with light pole or other post/support, Collision with mail box, Collision with median barrier, Collision with moped, Collision with motor vehicle in traffic, Collision with other fixed object (wall, building, tunnel, etc.), Collision with other movable object, Collision with Other Vulnerable Users, Collision with overhead sign support, Collision with parked motor vehicle, Collision with pedestrian, Collision with railway vehicle (e.g., train, engine), Collision with tree, Collision with unknown fixed object, Collision with unknown movable object, Collision with utility pole, Collision with work zone maintenance equipment, Fire/explosion, Immersion, Invalid Code Specified, Jackknife, Not reported, Other, Other non-collision, Overturn/rollover, Unknown, Unknown non-collision.',
            ],
            'total_occpt_in_vehc' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Total Occupants in Vehicle. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 21, 22, 24, 26, 28, 32, 33, 34, 35, 38, 40, 46, 49, 56.',
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
                'description' => 'Filter by Vehicle Towed From Scene. Provide a comma-separated list or an array of values. Possible values: No, Not reported, Unknown, Yes, other reason not disabled, Yes, vehicle or trailer disabled.',
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
                'description' => 'Filter by Ejection Description. Provide a comma-separated list or an array of values. Possible values: Not applicable, Not ejected, Not reported, Partially ejected, Totally ejected, Unknown.',
            ],
            'injy_stat_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Injury Type. Provide a comma-separated list or an array of values. Possible values: Deceased not caused by crash, Fatal injury (K), No Apparent Injury (O), Not Applicable, Not reported, Possible Injury (C), Reported but invalid, Suspected Minor Injury (B), Suspected Serious Injury (A), Unknown.',
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
                'description' => 'Filter by Safety Equipment 1. Provide a comma-separated list or an array of values. Possible values: Helmet used, Lighting, None used, Not reported, Other, Reflective clothing, Reported but invalid, Unknown.',
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
                'description' => 'Filter by Sex. Provide a comma-separated list or an array of values. Possible values: F - Female, M - Male, N/A, U - Unknown, X - Non-Binary.',
            ],
            'trnsd_by_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Transported By. Provide a comma-separated list or an array of values. Possible values: EMS Ground, EMS(Emergency Medical Service), Not reported, Not transported, Other, Police, Refused Transport, Unknown.',
            ],
            'non_mtrst_type_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable User Type (All Persons).',
            ],
            'non_mtrst_actn_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Action (All Persons). Provide a comma-separated list or an array of values. Possible values: VU2: Approaching or leaving vehicle, VU2: Approaching or leaving vehicle / VU3: Standing / VU4: Approaching or leaving vehicle, VU2: Entering or crossing location, VU2: Entering or crossing location / VU3: Entering or crossing location, VU2: Entering or crossing location / VU3: Entering or crossing location / VU4: Entering or crossing location, VU2: None, VU2: Other, VU2: Other / VU3: Other, VU2: Pushing vehicle, VU2: Standing, VU2: Standing / VU3: Other / VU4: Other, VU2: Standing / VU3: Standing, VU2: Walking, running or cycling, VU2: Walking, running or cycling / VU3: Other, VU2: Walking, running or cycling / VU3: Walking, running or cycling, VU2: Walking, running or cycling / VU3: Walking, running or cycling / VU4: Walking, running or cycling / VU5: Walking, running or cycling, VU2: Working, VU2: Working / VU4: Working / VU5: Working, VU2: Working on vehicle, VU3: Approaching or leaving vehicle, VU3: Entering or crossing location, VU3: Entering or crossing location / VU4: Entering or crossing location, VU3: None, VU3: Other, VU3: Other / VU4: Other / VU5: Other, VU3: Standing, VU3: Walking, running or cycling, VU3: Walking, running or cycling / VU4: Walking, running or cycling, VU3: Working, VU4: Approaching or leaving vehicle, VU4: Entering or crossing location, VU4: None, VU4: Other, VU4: Standing, VU4: Walking, running or cycling, VU4: Working, VU4: Working / VU5: Working / VU6: Working, VU5: Entering or crossing location, VU5: Entering or crossing location / VU6: Entering or crossing location, VU5: Standing, VU5: Walking, running or cycling, VU5: Walking, running or cycling / VU6: Walking, running or cycling, VU6: Approaching or leaving vehicle, VU6: Standing, VU6: Working on vehicle.',
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
                'description' => 'Filter by Vulnerable User Condition. Provide a comma-separated list or an array of values. Possible values: Apparently normal, Not reported, Unknown.',
            ],
            'non_mtrst_loc_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Location. Provide a comma-separated list or an array of values. Possible values: At intersection but no crosswalk, In roadway, Island, Marked crosswalk at intersection (includes use of paint raised or other roadway material), Non-intersection crosswalk, Not in roadway, Not reported, On-Street Bike Lanes, On-Street Buffered Bike Lanes, Other, Raised Crosswalk, Separated Bike Lanes, Shared-use path or trails Crossing, Shoulder, Sidewalk, Unknown.',
            ],
            'non_mtrst_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Type. Provide a comma-separated list or an array of values. Possible values: Bicyclist, Electric Personal Assistive Mobility Device User, Emergency Responder - Outside of vehicle, Farm Equipment Operator, Hand Cyclist, In-Line Skater, Motorized Bicyclist, Motorized Scooter Rider, Non-Motorized Scooter Rider, Non-Motorized Wheelchair User, Not reported, Other, Pedestrian, Roadway Worker - Outside of vehicle, Skateboarder, Train/Trolley passenger, Tricyclist, Unknown, Utility Worker – Outside of vehicle.',
            ],
            'non_mtrst_origin_dest_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Origin Destination (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10: Other, VU2: Going to or from a Delivery Vehicle, VU2: Going to or from a Delivery Vehicle / VU3: Going to or from a Delivery Vehicle / VU4: Going to or from a Delivery Vehicle, VU2: Going to or from a Mailbox, VU2: Going to or from a School Bus or a School Bus Stop, VU2: Going to or from a School Bus or a School Bus Stop / VU3: Going to or from a School Bus or a School Bus Stop, VU2: Going to or from an Ice Cream or Food Truck, VU2: Going to or from School (K-12), VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12), VU2: Going to or from School (K-12) / VU3: Going to or from School (K-12) / VU4: Going to or from School (K-12), VU2: Going to or from Transit, VU2: Going to or from Transit / VU3: Going to or from Transit / VU4: Going to or from Transit, VU2: Other, VU2: Other / VU3: Other, VU2: Other / VU3: Other / VU4: Other / VU5: Other, VU2: Other / VU4: Other / VU5: Other, VU3: Going to or from a Delivery Vehicle, VU3: Going to or from a School Bus or a School Bus Stop, VU3: Going to or from School (K-12), VU3: Going to or from Transit, VU3: Other, VU3: Other / VU4: Other, VU4: Going to or from School (K-12), VU4: Going to or from Transit, VU4: Other, VU4: Other / VU5: Other / VU6: Other, VU5: Going to or from Transit, VU5: Other, VU5: Other / VU6: Other, VU6: Other.',
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
                'description' => 'Filter by Vulnerable Users Distracted By (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10:(Not Distracted), VU2:(Manually operating an electronic device (texting, typing, dialing)), VU2:(Not Distracted), VU2:(Not Distracted) VU3:(Not Distracted), VU2:(Not Distracted) VU3:(Not Distracted) VU4:(Not Distracted), VU2:(Not Distracted) VU4:(Not Distracted) VU5:(Not Distracted), VU2:(Not Distracted),(Manually operating an electronic device (texting, typing, dialing)), VU2:(Other activity (searching, eating, personal hygiene, etc.)), VU2:(Talking on hand-held electronic device), VU2:(Talking on hands-free electronic device), VU2:(Utilizing listening device), VU3:(Manually operating an electronic device (texting, typing, dialing)), VU3:(Not Distracted), VU3:(Other activity (searching, eating, personal hygiene, etc.)), VU3:(Passenger), VU3:(Utilizing listening device), VU4:(Not Distracted), VU4:(Not Distracted) VU5:(Not Distracted) VU6:(Not Distracted), VU5:(Not Distracted), VU5:(Not Distracted) VU6:(Not Distracted), VU5:(Other activity (searching, eating, personal hygiene, etc.)), VU6:(Not Distracted).',
            ],
            'non_mtrst_alc_suspd_type_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Alcohol Suspected Type (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10: No, VU2: No, VU2: No / VU3: No, VU2: No / VU3: No / VU4: No, VU2: No / VU3: No / VU4: No / VU5: No, VU2: No / VU4: No / VU5: No, VU2: Yes, VU3: No, VU3: No / VU4: No, VU3: Yes, VU4: No, VU4: No / VU5: No / VU6: No, VU5: No, VU5: No / VU6: No, VU5: Yes, VU6: No.',
            ],
            'non_mtrst_drug_suspd_type_cl' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Drug Suspected Type (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10: No, VU2: No, VU2: No / VU3: No, VU2: No / VU3: No / VU4: No, VU2: No / VU3: No / VU4: No / VU5: No, VU2: No / VU4: No / VU5: No, VU2: Yes, VU3: No, VU3: No / VU4: No, VU4: No, VU4: No / VU5: No / VU6: No, VU5: No, VU5: No / VU6: No, VU6: No.',
            ],
            'non_mtrst_event_seq_cl' => [
                'type' => 'string',
                'description' => 'Filter by Vulnerable Users Sequence of Events (All Persons).',
            ],
            'traffic_control_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable Users Traffic Control Device Type (All Persons). Provide a comma-separated list or an array of values. Possible values: VU10: None, VU2: None, VU2: None / VU3: None, VU2: None / VU3: None / VU4: None, VU2: None / VU3: None / VU4: None / VU5: None, VU2: None / VU3: Person - Crossing guard, VU2: None / VU4: None / VU5: None, VU2: Other, VU2: Other / VU3: Other, VU2: Person - Crossing guard, VU2: Person - Crossing guard / VU3: Person - Crossing guard, VU2: VU Crossing Sign, VU2: VU Crossing Sign / VU3: VU Crossing Sign, VU2: VU Crossing Signal, VU2: VU Crossing Signal / VU3: VU Crossing Signal, VU2: VU Crossing Signal / VU3: VU Crossing Signal / VU4: VU Crossing Signal, VU2: VU Prohibited Sign, VU3: None, VU3: None / VU4: None, VU3: Other, VU3: Other / VU4: Other, VU3: Person - Crossing guard, VU3: VU Crossing Sign, VU3: VU Crossing Signal, VU3: VU Prohibited Sign, VU4: None, VU4: Other, VU4: Other / VU5: Other / VU6: Other, VU4: VU Crossing Sign, VU4: VU Crossing Signal, VU4: VU Prohibited Sign, VU5: None, VU5: Other, VU5: VU Crossing Signal, VU5: VU Crossing Signal / VU6: VU Crossing Signal, VU6: None.',
            ],
            'non_motorist_cntrb_circ_1' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Contribution 1. Provide a comma-separated list or an array of values. Possible values: Crossing of Roadway or Intersection, Dart/Dash, Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching), Distracted, Entering/Exiting Parked/Standing Vehicle, Failure to Obey Traffic Sign(s), Signal(s), or Officer(s), Failure to use Proper Crosswalk, Failure to Yield Right-Of-Way, Fleeing/Evading Law Enforcement, In Roadway (Standing, Lying, Working, Playing, etc.), Inattentive (Talking, Eating, etc.), None, Not reported, Not Visible (Dark Clothing, No Lighting, etc.), Other (Explain in Narrative), Passing, Traveling Wrong Way, Turn/Merge, Unknown.',
            ],
            'non_motorist_cntrb_circ_2' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Contribution 2. Provide a comma-separated list or an array of values. Possible values: Crossing of Roadway or Intersection, Dart/Dash, Disabled Vehicle-Related (Working on, Pushing, Leaving/Approaching), Distracted, Entering/Exiting Parked/Standing Vehicle, Failure to Obey Traffic Sign(s), Signal(s), or Officer(s), Failure to use Proper Crosswalk, Failure to Yield Right-Of-Way, In Roadway (Standing, Lying, Working, Playing, etc.), Inattentive (Talking, Eating, etc.), None, Not reported, Not Visible (Dark Clothing, No Lighting, etc.), Other (Explain in Narrative), Passing, Traveling Wrong Way, Turn/Merge, Unknown.',
            ],
            'non_motorist_contact_point' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Contact Point. Provide a comma-separated list or an array of values. Possible values: 03 - Right, 06 - Rear, 09 - Left, Front, Not reported, Other, Unknown.',
            ],
            'non_motorist_distracted_by_1' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Distracted By 1. Provide a comma-separated list or an array of values. Possible values: Manually operating an electronic device (texting, typing, dialing), Not Distracted, Not reported, Other activity (searching, eating, personal hygiene, etc.), Passenger, Talking on hand-held electronic device, Talking on hands-free electronic device, Unknown, Utilizing listening device.',
            ],
            'non_motorist_distracted_by_2' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Distracted By 2. Provide a comma-separated list or an array of values. Possible values: Manually operating an electronic device (texting, typing, dialing), Not Distracted, Not reported, Unknown.',
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
                'description' => 'Filter by Vulnerable User Event Sequence 2. Provide a comma-separated list or an array of values. Possible values: Adjacent to Roadway (e.g., Shoulder, Median), Approaching or leaving vehicle, Changing lanes, Collision with door opening of parked car - Front left, Collision with motor vehicle in transport, Collision with parked motor vehicle, stationary, Crossing Roadway, In Roadway – Other, Not reported, Other, Overtaking/passing, Slowed or stopped, Traveling straight ahead, Turning left, Turning right, Unknown, Waiting to Cross Roadway, Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane), Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane), Walking/Cycling on Sidewalk, Working - other, Working in Trafficway (Incident Response), Working on vehicle.',
            ],
            'non_motorist_event_sequence_3' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Event Sequence 3. Provide a comma-separated list or an array of values. Possible values: Adjacent to Roadway (e.g., Shoulder, Median), Approaching or leaving vehicle, Collision with door opening of parked car - Back right, Collision with door opening of parked car - Front right, Collision with motor vehicle in transport, Collision with parked motor vehicle, stationary, Crossing Roadway, In Roadway – Other, Not reported, Other, Overtaking/passing, Traveling straight ahead, Turning left, Turning right, Unknown, Waiting to Cross Roadway, Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane), Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane), Walking/Cycling on Sidewalk, Working - other.',
            ],
            'non_motorist_event_sequence_4' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Event Sequence 4. Provide a comma-separated list or an array of values. Possible values: Approaching or leaving vehicle, Collision with motor vehicle in transport, Crossing Roadway, In Roadway – Other, Not reported, Other, Overtaking/passing, Traveling straight ahead, Unknown, Waiting to Cross Roadway, Walking/Cycling Along Roadway Against Traffic (In or Adjacent to Travel Lane), Walking/Cycling Along Roadway with Traffic (In or Adjacent to Travel Lane), Walking/Cycling on Sidewalk.',
            ],
            'non_motorist_driver_lic_state' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Driver License State Province. Provide a comma-separated list or an array of values. Possible values: 96, AK, AZ, FL, FR, GA, ID, LA, MA, ME, NH, NJ, NY, OH, PA, RI, TX.',
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
                'description' => 'Filter by Vulnerable User Seating Position. Provide a comma-separated list or an array of values. Possible values: Enclosed passenger area, Front seat - left side, Front seat – right side, Not reported, Other, Reported but invalid, Riding on exterior, Second seat - left side, Trailing unit, Unenclosed passenger area, Unknown.',
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
                'description' => 'Filter by Vulnerable User Origin Destination. Provide a comma-separated list or an array of values. Possible values: Going to or from a Delivery Vehicle, Going to or from a Mailbox, Going to or from a School Bus or a School Bus Stop, Going to or from an Ice Cream or Food Truck, Going to or from School (K-12), Going to or from Transit, Not Reported, Other, Unknown.',
            ],
            'non_mtrst_test_type_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Test Type. Provide a comma-separated list or an array of values. Possible values: Blood, Breath, Not Reported, Test Not Given, Unknown.',
            ],
            'non_mtrst_test_status_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Test Status. Provide a comma-separated list or an array of values. Possible values: Not reported, Test Given, Test Not Given, Test Refused, Unknown.',
            ],
            'non_mtrst_test_result_descr' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Vulnerable User Test Result. Provide a comma-separated list or an array of values. Possible values: BAC Test Performed, Results Unknown, Not Reported, Test Not Given, Unknown, Unknown, if tested.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Ward',
                'options' => [
                    0 => [
                        'value' => '01',
                        'label' => '01',
                    ],
                    1 => [
                        'value' => '02',
                        'label' => '02',
                    ],
                    2 => [
                        'value' => '03',
                        'label' => '03',
                    ],
                    3 => [
                        'value' => '04',
                        'label' => '04',
                    ],
                    4 => [
                        'value' => '05',
                        'label' => '05',
                    ],
                    5 => [
                        'value' => '06',
                        'label' => '06',
                    ],
                    6 => [
                        'value' => '07',
                        'label' => '07',
                    ],
                    7 => [
                        'value' => '08',
                        'label' => '08',
                    ],
                    8 => [
                        'value' => '09',
                        'label' => '09',
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
                    14 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    15 => [
                        'value' => '16',
                        'label' => '16',
                    ],
                    16 => [
                        'value' => '17',
                        'label' => '17',
                    ],
                    17 => [
                        'value' => '18',
                        'label' => '18',
                    ],
                    18 => [
                        'value' => '19',
                        'label' => '19',
                    ],
                    19 => [
                        'value' => '20',
                        'label' => '20',
                    ],
                    20 => [
                        'value' => '21',
                        'label' => '21',
                    ],
                    21 => [
                        'value' => '22',
                        'label' => '22',
                    ],
                ],
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Ward. Provide a comma-separated list or an array of values. Possible values: 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select Neighborhood',
                'options' => [
                    0 => [
                        'value' => 'Baldwin',
                        'label' => 'Baldwin',
                    ],
                    1 => [
                        'value' => 'Cambridgeport',
                        'label' => 'Cambridgeport',
                    ],
                    2 => [
                        'value' => 'East Cambridge',
                        'label' => 'East Cambridge',
                    ],
                    3 => [
                        'value' => 'Highlands',
                        'label' => 'Highlands',
                    ],
                    4 => [
                        'value' => 'Inman/Harrington',
                        'label' => 'Inman/Harrington',
                    ],
                    5 => [
                        'value' => 'Mid-Cambridge',
                        'label' => 'Mid-Cambridge',
                    ],
                    6 => [
                        'value' => 'MIT',
                        'label' => 'MIT',
                    ],
                    7 => [
                        'value' => 'North Cambridge',
                        'label' => 'North Cambridge',
                    ],
                    8 => [
                        'value' => 'Peabody',
                        'label' => 'Peabody',
                    ],
                    9 => [
                        'value' => 'Riverside',
                        'label' => 'Riverside',
                    ],
                    10 => [
                        'value' => 'Strawberry Hill',
                        'label' => 'Strawberry Hill',
                    ],
                    11 => [
                        'value' => 'The Port',
                        'label' => 'The Port',
                    ],
                    12 => [
                        'value' => 'West Cambridge',
                        'label' => 'West Cambridge',
                    ],
                ],
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Neighborhood. Provide a comma-separated list or an array of values. Possible values: Baldwin, Cambridgeport, East Cambridge, Highlands, Inman/Harrington, Mid-Cambridge, MIT, North Cambridge, Peabody, Riverside, Strawberry Hill, The Port, West Cambridge.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select District',
                'options' => [
                    0 => [
                        'value' => 'A1',
                        'label' => 'A1',
                    ],
                    1 => [
                        'value' => 'A15',
                        'label' => 'A15',
                    ],
                    2 => [
                        'value' => 'A7',
                        'label' => 'A7',
                    ],
                    3 => [
                        'value' => 'B2',
                        'label' => 'B2',
                    ],
                    4 => [
                        'value' => 'B3',
                        'label' => 'B3',
                    ],
                    5 => [
                        'value' => 'C11',
                        'label' => 'C11',
                    ],
                    6 => [
                        'value' => 'C6',
                        'label' => 'C6',
                    ],
                    7 => [
                        'value' => 'D14',
                        'label' => 'D14',
                    ],
                    8 => [
                        'value' => 'D4',
                        'label' => 'D4',
                    ],
                    9 => [
                        'value' => 'E13',
                        'label' => 'E13',
                    ],
                    10 => [
                        'value' => 'E18',
                        'label' => 'E18',
                    ],
                    11 => [
                        'value' => 'E5',
                        'label' => 'E5',
                    ],
                    12 => [
                        'value' => 'External',
                        'label' => 'External',
                    ],
                    13 => [
                        'value' => 'Outside of',
                        'label' => 'Outside of',
                    ],
                ],
            ],
            8 => [
                'name' => 'reporting_area',
                'label' => 'Reporting Area',
                'type' => 'text',
                'placeholder' => 'Enter Reporting Area',
            ],
            9 => [
                'name' => 'shooting',
                'label' => 'Shooting',
                'type' => 'boolean',
            ],
            10 => [
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
            11 => [
                'name' => 'month',
                'label' => 'Month',
                'type' => 'multiselect',
                'placeholder' => 'Select Month',
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
                ],
            ],
            12 => [
                'name' => 'day_of_week',
                'label' => 'Day Of Week',
                'type' => 'multiselect',
                'placeholder' => 'Select Day Of Week',
                'options' => [
                    0 => [
                        'value' => 'Friday',
                        'label' => 'Friday',
                    ],
                    1 => [
                        'value' => 'Monday',
                        'label' => 'Monday',
                    ],
                    2 => [
                        'value' => 'Saturday',
                        'label' => 'Saturday',
                    ],
                    3 => [
                        'value' => 'Sunday',
                        'label' => 'Sunday',
                    ],
                    4 => [
                        'value' => 'Thursday',
                        'label' => 'Thursday',
                    ],
                    5 => [
                        'value' => 'Tuesday',
                        'label' => 'Tuesday',
                    ],
                    6 => [
                        'value' => 'Wednesday',
                        'label' => 'Wednesday',
                    ],
                ],
            ],
            13 => [
                'name' => 'hour',
                'label' => 'Hour',
                'type' => 'multiselect',
                'placeholder' => 'Select Hour',
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
            14 => [
                'name' => 'ucr_part',
                'label' => 'Ucr Part',
                'type' => 'text',
                'placeholder' => 'Enter Ucr Part',
            ],
            15 => [
                'name' => 'street',
                'label' => 'Street',
                'type' => 'text',
                'placeholder' => 'Enter Street',
            ],
            16 => [
                'name' => 'lat_min',
                'label' => 'Lat Min',
                'type' => 'number',
                'placeholder' => 'Min value for Lat',
            ],
            17 => [
                'name' => 'lat_max',
                'label' => 'Lat Max',
                'type' => 'number',
                'placeholder' => 'Max value for Lat',
            ],
            18 => [
                'name' => 'long_min',
                'label' => 'Long Min',
                'type' => 'number',
                'placeholder' => 'Min value for Long',
            ],
            19 => [
                'name' => 'long_max',
                'label' => 'Long Max',
                'type' => 'number',
                'placeholder' => 'Max value for Long',
            ],
            20 => [
                'name' => 'location',
                'label' => 'Location',
                'type' => 'text',
                'placeholder' => 'Enter Location',
            ],
            21 => [
                'name' => 'source_city',
                'label' => 'Source City',
                'type' => 'text',
                'placeholder' => 'Enter Source City',
            ],
            22 => [
                'name' => 'crime_start_time_start',
                'label' => 'Crime Start Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Crime Start Time',
            ],
            23 => [
                'name' => 'crime_start_time_end',
                'label' => 'Crime Start Time End',
                'type' => 'date',
                'placeholder' => 'End date for Crime Start Time',
            ],
            24 => [
                'name' => 'crime_end_time_start',
                'label' => 'Crime End Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Crime End Time',
            ],
            25 => [
                'name' => 'crime_end_time_end',
                'label' => 'Crime End Time End',
                'type' => 'date',
                'placeholder' => 'End date for Crime End Time',
            ],
            26 => [
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by District. Provide a comma-separated list or an array of values. Possible values: A1, A15, A7, B2, B3, C11, C6, D14, D4, E13, E18, E5, External, Outside of.',
            ],
            'reporting_area' => [
                'type' => 'string',
                'description' => 'Filter by Reporting Area.',
            ],
            'shooting' => [
                'type' => 'boolean',
                'description' => 'Filter by Shooting (true/false).',
            ],
            'year' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Year. Provide a comma-separated list or an array of values. Possible values: 2023, 2024, 2025.',
            ],
            'month' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Month. Provide a comma-separated list or an array of values. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12.',
            ],
            'day_of_week' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Day Of Week. Provide a comma-separated list or an array of values. Possible values: Friday, Monday, Saturday, Sunday, Thursday, Tuesday, Wednesday.',
            ],
            'hour' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Hour. Provide a comma-separated list or an array of values. Possible values: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23.',
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
                'type' => 'multiselect',
                'placeholder' => 'Select On Time',
                'options' => [
                    0 => [
                        'value' => 'ONTIME',
                        'label' => 'ONTIME',
                    ],
                    1 => [
                        'value' => 'OVERDUE',
                        'label' => 'OVERDUE',
                    ],
                ],
            ],
            7 => [
                'name' => 'case_status',
                'label' => 'Case Status',
                'type' => 'multiselect',
                'placeholder' => 'Select Case Status',
                'options' => [
                    0 => [
                        'value' => 'Closed',
                        'label' => 'Closed',
                    ],
                    1 => [
                        'value' => 'Open',
                        'label' => 'Open',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Subject',
                'options' => [
                    0 => [
                        'value' => 'Animal Control',
                        'label' => 'Animal Control',
                    ],
                    1 => [
                        'value' => 'Boston Police Department',
                        'label' => 'Boston Police Department',
                    ],
                    2 => [
                        'value' => 'Boston Water & Sewer Commission',
                        'label' => 'Boston Water & Sewer Commission',
                    ],
                    3 => [
                        'value' => 'Inspectional Services',
                        'label' => 'Inspectional Services',
                    ],
                    4 => [
                        'value' => 'Mayor\'s 24 Hour Hotline',
                        'label' => 'Mayor\'s 24 Hour Hotline',
                    ],
                    5 => [
                        'value' => 'Neighborhood Services',
                        'label' => 'Neighborhood Services',
                    ],
                    6 => [
                        'value' => 'Parks & Recreation Department',
                        'label' => 'Parks & Recreation Department',
                    ],
                    7 => [
                        'value' => 'Property Management',
                        'label' => 'Property Management',
                    ],
                    8 => [
                        'value' => 'Public Works Department',
                        'label' => 'Public Works Department',
                    ],
                    9 => [
                        'value' => 'Transportation - Traffic Division',
                        'label' => 'Transportation - Traffic Division',
                    ],
                ],
            ],
            11 => [
                'name' => 'reason',
                'label' => 'Reason',
                'type' => 'multiselect',
                'placeholder' => 'Select Reason',
                'options' => [
                    0 => [
                        'value' => 'Abandoned Bicycle',
                        'label' => 'Abandoned Bicycle',
                    ],
                    1 => [
                        'value' => 'Administrative & General Requests',
                        'label' => 'Administrative & General Requests',
                    ],
                    2 => [
                        'value' => 'Air Pollution Control',
                        'label' => 'Air Pollution Control',
                    ],
                    3 => [
                        'value' => 'Alert Boston',
                        'label' => 'Alert Boston',
                    ],
                    4 => [
                        'value' => 'Animal Issues',
                        'label' => 'Animal Issues',
                    ],
                    5 => [
                        'value' => 'Billing',
                        'label' => 'Billing',
                    ],
                    6 => [
                        'value' => 'Boston Bikes',
                        'label' => 'Boston Bikes',
                    ],
                    7 => [
                        'value' => 'Bridge Maintenance',
                        'label' => 'Bridge Maintenance',
                    ],
                    8 => [
                        'value' => 'Building',
                        'label' => 'Building',
                    ],
                    9 => [
                        'value' => 'Catchbasin',
                        'label' => 'Catchbasin',
                    ],
                    10 => [
                        'value' => 'Cemetery',
                        'label' => 'Cemetery',
                    ],
                    11 => [
                        'value' => 'Code Enforcement',
                        'label' => 'Code Enforcement',
                    ],
                    12 => [
                        'value' => 'Employee & General Comments',
                        'label' => 'Employee & General Comments',
                    ],
                    13 => [
                        'value' => 'Enforcement & Abandoned Vehicles',
                        'label' => 'Enforcement & Abandoned Vehicles',
                    ],
                    14 => [
                        'value' => 'Environmental Services',
                        'label' => 'Environmental Services',
                    ],
                    15 => [
                        'value' => 'Fire Hydrant',
                        'label' => 'Fire Hydrant',
                    ],
                    16 => [
                        'value' => 'General Request',
                        'label' => 'General Request',
                    ],
                    17 => [
                        'value' => 'Generic Noise Disturbance',
                        'label' => 'Generic Noise Disturbance',
                    ],
                    18 => [
                        'value' => 'Graffiti',
                        'label' => 'Graffiti',
                    ],
                    19 => [
                        'value' => 'Health',
                        'label' => 'Health',
                    ],
                    20 => [
                        'value' => 'Highway Maintenance',
                        'label' => 'Highway Maintenance',
                    ],
                    21 => [
                        'value' => 'Housing',
                        'label' => 'Housing',
                    ],
                    22 => [
                        'value' => 'Massport',
                        'label' => 'Massport',
                    ],
                    23 => [
                        'value' => 'Needle Program',
                        'label' => 'Needle Program',
                    ],
                    24 => [
                        'value' => 'Neighborhood Services Issues',
                        'label' => 'Neighborhood Services Issues',
                    ],
                    25 => [
                        'value' => 'Noise Disturbance',
                        'label' => 'Noise Disturbance',
                    ],
                    26 => [
                        'value' => 'Notification',
                        'label' => 'Notification',
                    ],
                    27 => [
                        'value' => 'Office of The Parking Clerk',
                        'label' => 'Office of The Parking Clerk',
                    ],
                    28 => [
                        'value' => 'Operations',
                        'label' => 'Operations',
                    ],
                    29 => [
                        'value' => 'Park Maintenance & Safety',
                        'label' => 'Park Maintenance & Safety',
                    ],
                    30 => [
                        'value' => 'Parking Complaints',
                        'label' => 'Parking Complaints',
                    ],
                    31 => [
                        'value' => 'Pothole',
                        'label' => 'Pothole',
                    ],
                    32 => [
                        'value' => 'Programs',
                        'label' => 'Programs',
                    ],
                    33 => [
                        'value' => 'Quality of Life',
                        'label' => 'Quality of Life',
                    ],
                    34 => [
                        'value' => 'Recycling',
                        'label' => 'Recycling',
                    ],
                    35 => [
                        'value' => 'Sanitation',
                        'label' => 'Sanitation',
                    ],
                    36 => [
                        'value' => 'Sidewalk Cover / Manhole',
                        'label' => 'Sidewalk Cover / Manhole',
                    ],
                    37 => [
                        'value' => 'Signs & Signals',
                        'label' => 'Signs & Signals',
                    ],
                    38 => [
                        'value' => 'Street Cleaning',
                        'label' => 'Street Cleaning',
                    ],
                    39 => [
                        'value' => 'Street Lights',
                        'label' => 'Street Lights',
                    ],
                    40 => [
                        'value' => 'Traffic Management & Engineering',
                        'label' => 'Traffic Management & Engineering',
                    ],
                    41 => [
                        'value' => 'Trees',
                        'label' => 'Trees',
                    ],
                    42 => [
                        'value' => 'Valet',
                        'label' => 'Valet',
                    ],
                    43 => [
                        'value' => 'Weights and Measures',
                        'label' => 'Weights and Measures',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Department',
                'options' => [
                    0 => [
                        'value' => 'ANML',
                        'label' => 'ANML',
                    ],
                    1 => [
                        'value' => 'BHA_',
                        'label' => 'BHA_',
                    ],
                    2 => [
                        'value' => 'BPD_',
                        'label' => 'BPD_',
                    ],
                    3 => [
                        'value' => 'BPS_',
                        'label' => 'BPS_',
                    ],
                    4 => [
                        'value' => 'BTDT',
                        'label' => 'BTDT',
                    ],
                    5 => [
                        'value' => 'BWSC',
                        'label' => 'BWSC',
                    ],
                    6 => [
                        'value' => 'DISB',
                        'label' => 'DISB',
                    ],
                    7 => [
                        'value' => 'DND_',
                        'label' => 'DND_',
                    ],
                    8 => [
                        'value' => 'ECON',
                        'label' => 'ECON',
                    ],
                    9 => [
                        'value' => 'GEN_',
                        'label' => 'GEN_',
                    ],
                    10 => [
                        'value' => 'GRNi',
                        'label' => 'GRNi',
                    ],
                    11 => [
                        'value' => 'INFO',
                        'label' => 'INFO',
                    ],
                    12 => [
                        'value' => 'ISD',
                        'label' => 'ISD',
                    ],
                    13 => [
                        'value' => 'ONS_',
                        'label' => 'ONS_',
                    ],
                    14 => [
                        'value' => 'PARK',
                        'label' => 'PARK',
                    ],
                    15 => [
                        'value' => 'PROP',
                        'label' => 'PROP',
                    ],
                    16 => [
                        'value' => 'PWDx',
                        'label' => 'PWDx',
                    ],
                ],
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
                'type' => 'multiselect',
                'placeholder' => 'Select Fire District',
                'options' => [
                    0 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    1 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    2 => [
                        'value' => '11',
                        'label' => '11',
                    ],
                    3 => [
                        'value' => '12',
                        'label' => '12',
                    ],
                    4 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    5 => [
                        'value' => '4',
                        'label' => '4',
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
                ],
            ],
            19 => [
                'name' => 'pwd_district',
                'label' => 'Pwd District',
                'type' => 'multiselect',
                'placeholder' => 'Select Pwd District',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => '02',
                        'label' => '02',
                    ],
                    2 => [
                        'value' => '03',
                        'label' => '03',
                    ],
                    3 => [
                        'value' => '04',
                        'label' => '04',
                    ],
                    4 => [
                        'value' => '05',
                        'label' => '05',
                    ],
                    5 => [
                        'value' => '06',
                        'label' => '06',
                    ],
                    6 => [
                        'value' => '07',
                        'label' => '07',
                    ],
                    7 => [
                        'value' => '08',
                        'label' => '08',
                    ],
                    8 => [
                        'value' => '09',
                        'label' => '09',
                    ],
                    9 => [
                        'value' => '10A',
                        'label' => '10A',
                    ],
                    10 => [
                        'value' => '10B',
                        'label' => '10B',
                    ],
                    11 => [
                        'value' => '1A',
                        'label' => '1A',
                    ],
                    12 => [
                        'value' => '1B',
                        'label' => '1B',
                    ],
                    13 => [
                        'value' => '1C',
                        'label' => '1C',
                    ],
                    14 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    15 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    16 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    17 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    18 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                    19 => [
                        'value' => '7',
                        'label' => '7',
                    ],
                    20 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    21 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                ],
            ],
            20 => [
                'name' => 'city_council_district',
                'label' => 'City Council District',
                'type' => 'multiselect',
                'placeholder' => 'Select City Council District',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    2 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    3 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    4 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    5 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    6 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    7 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                    8 => [
                        'value' => '7',
                        'label' => '7',
                    ],
                    9 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    10 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                ],
            ],
            21 => [
                'name' => 'police_district',
                'label' => 'Police District',
                'type' => 'multiselect',
                'placeholder' => 'Select Police District',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => 'A1',
                        'label' => 'A1',
                    ],
                    2 => [
                        'value' => 'A15',
                        'label' => 'A15',
                    ],
                    3 => [
                        'value' => 'A7',
                        'label' => 'A7',
                    ],
                    4 => [
                        'value' => 'B2',
                        'label' => 'B2',
                    ],
                    5 => [
                        'value' => 'B3',
                        'label' => 'B3',
                    ],
                    6 => [
                        'value' => 'C11',
                        'label' => 'C11',
                    ],
                    7 => [
                        'value' => 'C6',
                        'label' => 'C6',
                    ],
                    8 => [
                        'value' => 'D14',
                        'label' => 'D14',
                    ],
                    9 => [
                        'value' => 'D4',
                        'label' => 'D4',
                    ],
                    10 => [
                        'value' => 'E13',
                        'label' => 'E13',
                    ],
                    11 => [
                        'value' => 'E18',
                        'label' => 'E18',
                    ],
                    12 => [
                        'value' => 'E5',
                        'label' => 'E5',
                    ],
                ],
            ],
            22 => [
                'name' => 'neighborhood',
                'label' => 'Neighborhood',
                'type' => 'multiselect',
                'placeholder' => 'Select Neighborhood',
                'options' => [
                    0 => [
                        'value' => 'Allston',
                        'label' => 'Allston',
                    ],
                    1 => [
                        'value' => 'Allston / Brighton',
                        'label' => 'Allston / Brighton',
                    ],
                    2 => [
                        'value' => 'Back Bay',
                        'label' => 'Back Bay',
                    ],
                    3 => [
                        'value' => 'Beacon Hill',
                        'label' => 'Beacon Hill',
                    ],
                    4 => [
                        'value' => 'Boston',
                        'label' => 'Boston',
                    ],
                    5 => [
                        'value' => 'Brighton',
                        'label' => 'Brighton',
                    ],
                    6 => [
                        'value' => 'Charlestown',
                        'label' => 'Charlestown',
                    ],
                    7 => [
                        'value' => 'Chestnut Hill',
                        'label' => 'Chestnut Hill',
                    ],
                    8 => [
                        'value' => 'Dorchester',
                        'label' => 'Dorchester',
                    ],
                    9 => [
                        'value' => 'Downtown / Financial District',
                        'label' => 'Downtown / Financial District',
                    ],
                    10 => [
                        'value' => 'East Boston',
                        'label' => 'East Boston',
                    ],
                    11 => [
                        'value' => 'Fenway / Kenmore / Audubon Circle / Longwood',
                        'label' => 'Fenway / Kenmore / Audubon Circle / Longwood',
                    ],
                    12 => [
                        'value' => 'Greater Mattapan',
                        'label' => 'Greater Mattapan',
                    ],
                    13 => [
                        'value' => 'Hyde Park',
                        'label' => 'Hyde Park',
                    ],
                    14 => [
                        'value' => 'Jamaica Plain',
                        'label' => 'Jamaica Plain',
                    ],
                    15 => [
                        'value' => 'Mattapan',
                        'label' => 'Mattapan',
                    ],
                    16 => [
                        'value' => 'Mission Hill',
                        'label' => 'Mission Hill',
                    ],
                    17 => [
                        'value' => 'Roslindale',
                        'label' => 'Roslindale',
                    ],
                    18 => [
                        'value' => 'Roxbury',
                        'label' => 'Roxbury',
                    ],
                    19 => [
                        'value' => 'South Boston',
                        'label' => 'South Boston',
                    ],
                    20 => [
                        'value' => 'South Boston / South Boston Waterfront',
                        'label' => 'South Boston / South Boston Waterfront',
                    ],
                    21 => [
                        'value' => 'South End',
                        'label' => 'South End',
                    ],
                    22 => [
                        'value' => 'West Roxbury',
                        'label' => 'West Roxbury',
                    ],
                ],
            ],
            23 => [
                'name' => 'neighborhood_services_district',
                'label' => 'Neighborhood Services District',
                'type' => 'multiselect',
                'placeholder' => 'Select Neighborhood Services District',
                'options' => [
                    0 => [
                        'value' => ' ',
                        'label' => ' ',
                    ],
                    1 => [
                        'value' => '0',
                        'label' => '0',
                    ],
                    2 => [
                        'value' => '1',
                        'label' => '1',
                    ],
                    3 => [
                        'value' => '10',
                        'label' => '10',
                    ],
                    4 => [
                        'value' => '11',
                        'label' => '11',
                    ],
                    5 => [
                        'value' => '12',
                        'label' => '12',
                    ],
                    6 => [
                        'value' => '13',
                        'label' => '13',
                    ],
                    7 => [
                        'value' => '14',
                        'label' => '14',
                    ],
                    8 => [
                        'value' => '15',
                        'label' => '15',
                    ],
                    9 => [
                        'value' => '2',
                        'label' => '2',
                    ],
                    10 => [
                        'value' => '3',
                        'label' => '3',
                    ],
                    11 => [
                        'value' => '4',
                        'label' => '4',
                    ],
                    12 => [
                        'value' => '5',
                        'label' => '5',
                    ],
                    13 => [
                        'value' => '6',
                        'label' => '6',
                    ],
                    14 => [
                        'value' => '7',
                        'label' => '7',
                    ],
                    15 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    16 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                ],
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
                'name' => 'location_zipcode',
                'label' => 'Location Zipcode',
                'type' => 'multiselect',
                'placeholder' => 'Select Location Zipcode',
                'options' => [
                    0 => [
                        'value' => '2108',
                        'label' => '2108',
                    ],
                    1 => [
                        'value' => '2109',
                        'label' => '2109',
                    ],
                    2 => [
                        'value' => '2110',
                        'label' => '2110',
                    ],
                    3 => [
                        'value' => '2111',
                        'label' => '2111',
                    ],
                    4 => [
                        'value' => '2113',
                        'label' => '2113',
                    ],
                    5 => [
                        'value' => '2114',
                        'label' => '2114',
                    ],
                    6 => [
                        'value' => '2115',
                        'label' => '2115',
                    ],
                    7 => [
                        'value' => '2116',
                        'label' => '2116',
                    ],
                    8 => [
                        'value' => '2118',
                        'label' => '2118',
                    ],
                    9 => [
                        'value' => '2119',
                        'label' => '2119',
                    ],
                    10 => [
                        'value' => '2120',
                        'label' => '2120',
                    ],
                    11 => [
                        'value' => '2121',
                        'label' => '2121',
                    ],
                    12 => [
                        'value' => '2122',
                        'label' => '2122',
                    ],
                    13 => [
                        'value' => '2124',
                        'label' => '2124',
                    ],
                    14 => [
                        'value' => '2125',
                        'label' => '2125',
                    ],
                    15 => [
                        'value' => '2126',
                        'label' => '2126',
                    ],
                    16 => [
                        'value' => '2127',
                        'label' => '2127',
                    ],
                    17 => [
                        'value' => '2128',
                        'label' => '2128',
                    ],
                    18 => [
                        'value' => '2129',
                        'label' => '2129',
                    ],
                    19 => [
                        'value' => '2130',
                        'label' => '2130',
                    ],
                    20 => [
                        'value' => '2131',
                        'label' => '2131',
                    ],
                    21 => [
                        'value' => '2132',
                        'label' => '2132',
                    ],
                    22 => [
                        'value' => '2133',
                        'label' => '2133',
                    ],
                    23 => [
                        'value' => '2134',
                        'label' => '2134',
                    ],
                    24 => [
                        'value' => '2135',
                        'label' => '2135',
                    ],
                    25 => [
                        'value' => '2136',
                        'label' => '2136',
                    ],
                    26 => [
                        'value' => '2163',
                        'label' => '2163',
                    ],
                    27 => [
                        'value' => '2199',
                        'label' => '2199',
                    ],
                    28 => [
                        'value' => '2201',
                        'label' => '2201',
                    ],
                    29 => [
                        'value' => '2203',
                        'label' => '2203',
                    ],
                    30 => [
                        'value' => '2210',
                        'label' => '2210',
                    ],
                    31 => [
                        'value' => '2215',
                        'label' => '2215',
                    ],
                    32 => [
                        'value' => '2446',
                        'label' => '2446',
                    ],
                    33 => [
                        'value' => '2467',
                        'label' => '2467',
                    ],
                ],
            ],
            28 => [
                'name' => 'latitude_min',
                'label' => 'Latitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Latitude',
            ],
            29 => [
                'name' => 'latitude_max',
                'label' => 'Latitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Latitude',
            ],
            30 => [
                'name' => 'longitude_min',
                'label' => 'Longitude Min',
                'type' => 'number',
                'placeholder' => 'Min value for Longitude',
            ],
            31 => [
                'name' => 'longitude_max',
                'label' => 'Longitude Max',
                'type' => 'number',
                'placeholder' => 'Max value for Longitude',
            ],
            32 => [
                'name' => 'source',
                'label' => 'Source',
                'type' => 'multiselect',
                'placeholder' => 'Select Source',
                'options' => [
                    0 => [
                        'value' => 'Citizens Connect App',
                        'label' => 'Citizens Connect App',
                    ],
                    1 => [
                        'value' => 'City Worker App',
                        'label' => 'City Worker App',
                    ],
                    2 => [
                        'value' => 'Constituent Call',
                        'label' => 'Constituent Call',
                    ],
                    3 => [
                        'value' => 'Employee Generated',
                        'label' => 'Employee Generated',
                    ],
                    4 => [
                        'value' => 'Maximo Integration',
                        'label' => 'Maximo Integration',
                    ],
                    5 => [
                        'value' => 'Self Service',
                        'label' => 'Self Service',
                    ],
                ],
            ],
            33 => [
                'name' => 'checksum',
                'label' => 'Checksum',
                'type' => 'text',
                'placeholder' => 'Enter Checksum',
            ],
            34 => [
                'name' => 'language_code',
                'label' => 'Language Code',
                'type' => 'text',
                'placeholder' => 'Enter Language Code',
            ],
            35 => [
                'name' => 'ward_number',
                'label' => 'Ward Number',
                'type' => 'text',
                'placeholder' => 'Enter Ward Number',
            ],
            36 => [
                'name' => 'threeoneonedescription',
                'label' => 'Threeoneonedescription',
                'type' => 'text',
                'placeholder' => 'Enter Threeoneonedescription',
            ],
            37 => [
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by On Time. Provide a comma-separated list or an array of values. Possible values: ONTIME, OVERDUE.',
            ],
            'case_status' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Case Status. Provide a comma-separated list or an array of values. Possible values: Closed, Open.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Subject. Provide a comma-separated list or an array of values. Possible values: Animal Control, Boston Police Department, Boston Water & Sewer Commission, Inspectional Services, Mayor\'s 24 Hour Hotline, Neighborhood Services, Parks & Recreation Department, Property Management, Public Works Department, Transportation - Traffic Division.',
            ],
            'reason' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Reason. Provide a comma-separated list or an array of values. Possible values: Abandoned Bicycle, Administrative & General Requests, Air Pollution Control, Alert Boston, Animal Issues, Billing, Boston Bikes, Bridge Maintenance, Building, Catchbasin, Cemetery, Code Enforcement, Employee & General Comments, Enforcement & Abandoned Vehicles, Environmental Services, Fire Hydrant, General Request, Generic Noise Disturbance, Graffiti, Health, Highway Maintenance, Housing, Massport, Needle Program, Neighborhood Services Issues, Noise Disturbance, Notification, Office of The Parking Clerk, Operations, Park Maintenance & Safety, Parking Complaints, Pothole, Programs, Quality of Life, Recycling, Sanitation, Sidewalk Cover / Manhole, Signs & Signals, Street Cleaning, Street Lights, Traffic Management & Engineering, Trees, Valet, Weights and Measures.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Department. Provide a comma-separated list or an array of values. Possible values: ANML, BHA_, BPD_, BPS_, BTDT, BWSC, DISB, DND_, ECON, GEN_, GRNi, INFO, ISD, ONS_, PARK, PROP, PWDx.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Fire District. Provide a comma-separated list or an array of values. Possible values: 1, 10, 11, 12, 3, 4, 6, 7, 8, 9.',
            ],
            'pwd_district' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Pwd District. Provide a comma-separated list or an array of values. Possible values:  , 02, 03, 04, 05, 06, 07, 08, 09, 10A, 10B, 1A, 1B, 1C, 2, 3, 4, 5, 6, 7, 8, 9.',
            ],
            'city_council_district' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by City Council District. Provide a comma-separated list or an array of values. Possible values:  , 0, 1, 2, 3, 4, 5, 6, 7, 8, 9.',
            ],
            'police_district' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Police District. Provide a comma-separated list or an array of values. Possible values:  , A1, A15, A7, B2, B3, C11, C6, D14, D4, E13, E18, E5.',
            ],
            'neighborhood' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Neighborhood. Provide a comma-separated list or an array of values. Possible values: Allston, Allston / Brighton, Back Bay, Beacon Hill, Boston, Brighton, Charlestown, Chestnut Hill, Dorchester, Downtown / Financial District, East Boston, Fenway / Kenmore / Audubon Circle / Longwood, Greater Mattapan, Hyde Park, Jamaica Plain, Mattapan, Mission Hill, Roslindale, Roxbury, South Boston, South Boston / South Boston Waterfront, South End, West Roxbury.',
            ],
            'neighborhood_services_district' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Neighborhood Services District. Provide a comma-separated list or an array of values. Possible values:  , 0, 1, 10, 11, 12, 13, 14, 15, 2, 3, 4, 5, 6, 7, 8, 9.',
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
            'location_zipcode' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Location Zipcode. Provide a comma-separated list or an array of values. Possible values: 2108, 2109, 2110, 2111, 2113, 2114, 2115, 2116, 2118, 2119, 2120, 2121, 2122, 2124, 2125, 2126, 2127, 2128, 2129, 2130, 2131, 2132, 2133, 2134, 2135, 2136, 2163, 2199, 2201, 2203, 2210, 2215, 2446, 2467.',
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
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'description' => 'Filter by Source. Provide a comma-separated list or an array of values. Possible values: Citizens Connect App, City Worker App, Constituent Call, Employee Generated, Maximo Integration, Self Service.',
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
