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
                'type' => 'select',
                'placeholder' => 'Select Status',
                'options' => [
                    0 => [
                        'value' => 'Cited',
                        'label' => 'Cited',
                    ],
                    1 => [
                        'value' => 'Closed',
                        'label' => 'Closed',
                    ],
                    2 => [
                        'value' => 'Corrected',
                        'label' => 'Corrected',
                    ],
                    3 => [
                        'value' => 'In Progress',
                        'label' => 'In Progress',
                    ],
                    4 => [
                        'value' => 'Open',
                        'label' => 'Open',
                    ],
                    5 => [
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
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
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
                'description' => 'Start date for \'status_dttm\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'status_dttm\' (YYYY-MM-DD)',
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
                'description' => 'Filter by Status. Possible values: Cited, Closed, Corrected, In Progress, Open, Void.',
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
                'description' => 'Filter by Violation Suffix. Possible values:  , AV, AVE, BL, BLVD, CC, CI, CIR, CT, DR, GRN, HW, LN, PARK, PK, PL, PW, PZ, RD, RO, SQ, ST, TE, TER, WAY, WH, WY.',
            ],
            'violation_city' => [
                'type' => 'string',
                'description' => 'Filter by Violation City. Possible values:  , Allston, Allston/Boston, Back Bay/, Boston, Boston/West End, Brighton, Brighton/, Charlestown, Charlestown/, Charlestown666, Chestnut Hill, Chinatown, Dorchester, Dorchester (Lower Mills), Dorchester Center, Dorchester/, East Boston, East Boston/, East Boston//, Fenway/, Financial District, Financial District/, Hyde Park, Hyde Park/, Jamaica Plain, Jamaica Plain/, Kenmore/fenway, Mattapan, Mattapan/, Mission Hill, Mission Hill/, NorthEnd, NorthEnd/, Roslindale, Roslindale/, Roxbury, ROXBURY CROSSIN, Roxbury/, South Boston, South End, Theater District, West End, West Roxbury, West Roxbury/.',
            ],
            'violation_state' => [
                'type' => 'string',
                'description' => 'Filter by Violation State.',
            ],
            'violation_zip' => [
                'type' => 'string',
                'description' => 'Filter by Violation Zip. Possible values:  , 02108, 02109, 02110, 02111, 02113, 02114, 02115, 02116, 02118, 02119, 02120, 02121, 02122, 02123, 02124, 02125, 02126, 02126-1616, 02127, 02128, 02129, 02130, 02131, 02132, 02134, 02135, 02136, 02199, 02210, 02215, 02446, 02467.',
            ],
            'ward' => [
                'type' => 'string',
                'description' => 'Filter by Ward. Possible values:  , 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22.',
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
                'type' => 'select',
                'placeholder' => 'Select Licstatus',
                'options' => [
                    0 => [
                        'value' => 'Active',
                        'label' => 'Active',
                    ],
                    1 => [
                        'value' => 'Complete',
                        'label' => 'Complete',
                    ],
                    2 => [
                        'value' => 'Deleted',
                        'label' => 'Deleted',
                    ],
                    3 => [
                        'value' => 'Inactive',
                        'label' => 'Inactive',
                    ],
                ],
            ],
            14 => [
                'name' => 'licensecat',
                'label' => 'Licensecat',
                'type' => 'select',
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
                'type' => 'text',
                'placeholder' => 'Enter Descript',
            ],
            16 => [
                'name' => 'result',
                'label' => 'Result',
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
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
                        'value' => 'Cambridge',
                        'label' => 'Cambridge',
                    ],
                    9 => [
                        'value' => 'CHARLESTOWN',
                        'label' => 'CHARLESTOWN',
                    ],
                    10 => [
                        'value' => 'CHARLESTOWN/',
                        'label' => 'CHARLESTOWN/',
                    ],
                    11 => [
                        'value' => 'CHESTNUT HILL',
                        'label' => 'CHESTNUT HILL',
                    ],
                    12 => [
                        'value' => 'DORCHESTER',
                        'label' => 'DORCHESTER',
                    ],
                    13 => [
                        'value' => 'DORCHESTER CENTER',
                        'label' => 'DORCHESTER CENTER',
                    ],
                    14 => [
                        'value' => 'DORCHESTER CENTER/',
                        'label' => 'DORCHESTER CENTER/',
                    ],
                    15 => [
                        'value' => 'DORCHESTER/',
                        'label' => 'DORCHESTER/',
                    ],
                    16 => [
                        'value' => 'DOWNTOWN/FINANCIAL DISTRICT',
                        'label' => 'DOWNTOWN/FINANCIAL DISTRICT',
                    ],
                    17 => [
                        'value' => 'EAST BOSTON',
                        'label' => 'EAST BOSTON',
                    ],
                    18 => [
                        'value' => 'FENWAY',
                        'label' => 'FENWAY',
                    ],
                    19 => [
                        'value' => 'FENWAY/',
                        'label' => 'FENWAY/',
                    ],
                    20 => [
                        'value' => 'FINANCIAL DISTRICT',
                        'label' => 'FINANCIAL DISTRICT',
                    ],
                    21 => [
                        'value' => 'FINANCIAL DISTRICT/',
                        'label' => 'FINANCIAL DISTRICT/',
                    ],
                    22 => [
                        'value' => 'HYDE PARK',
                        'label' => 'HYDE PARK',
                    ],
                    23 => [
                        'value' => 'JAMAICA PLAIN',
                        'label' => 'JAMAICA PLAIN',
                    ],
                    24 => [
                        'value' => 'MATTAPAN',
                        'label' => 'MATTAPAN',
                    ],
                    25 => [
                        'value' => 'MATTAPAN/',
                        'label' => 'MATTAPAN/',
                    ],
                    26 => [
                        'value' => 'MISSION HILL',
                        'label' => 'MISSION HILL',
                    ],
                    27 => [
                        'value' => 'MISSION HILL/',
                        'label' => 'MISSION HILL/',
                    ],
                    28 => [
                        'value' => 'ROSLINDALE',
                        'label' => 'ROSLINDALE',
                    ],
                    29 => [
                        'value' => 'ROSLINDALE/',
                        'label' => 'ROSLINDALE/',
                    ],
                    30 => [
                        'value' => 'ROXBURY',
                        'label' => 'ROXBURY',
                    ],
                    31 => [
                        'value' => 'ROXBURY CROSSIN',
                        'label' => 'ROXBURY CROSSIN',
                    ],
                    32 => [
                        'value' => 'ROXBURY/BOSTON',
                        'label' => 'ROXBURY/BOSTON',
                    ],
                    33 => [
                        'value' => 'SOUTH BOSTON',
                        'label' => 'SOUTH BOSTON',
                    ],
                    34 => [
                        'value' => 'SOUTH BOSTON/',
                        'label' => 'SOUTH BOSTON/',
                    ],
                    35 => [
                        'value' => 'SOUTH END/',
                        'label' => 'SOUTH END/',
                    ],
                    36 => [
                        'value' => 'WEST ROXBURY',
                        'label' => 'WEST ROXBURY',
                    ],
                    37 => [
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
                'type' => 'select',
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
                'description' => 'Start date for \'resultdttm\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'resultdttm\' (YYYY-MM-DD)',
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
                'description' => 'Filter by Licstatus. Possible values: Active, Complete, Deleted, Inactive.',
            ],
            'licensecat' => [
                'type' => 'string',
                'description' => 'Filter by Licensecat. Possible values: FS, FT, MFW, RF.',
            ],
            'descript' => [
                'type' => 'string',
                'description' => 'Filter by Descript.',
            ],
            'result' => [
                'type' => 'string',
                'description' => 'Filter by Result. Possible values: Closed, DATAERR, Fail, Failed, HE_Closure, HE_Fail, HE_FailExt, HE_FAILNOR, HE_Filed, HE_Hearing, HE_Hold, HE_Misc, HE_NotReq, HE_OutBus, HE_Pass, HE_TSOP, HE_VolClos, NoViol, Pass, PassViol.',
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
                'description' => 'Filter by Viol Status. Possible values:  , Fail, Pass.',
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
                'description' => 'Filter by City. Possible values:  , ALLSTON, BACK BAY/, BOSTON, BOSTON/CHINATOWN, BOSTON/WEST END, BRIGHTON, BRIGHTON/, Cambridge, CHARLESTOWN, CHARLESTOWN/, CHESTNUT HILL, DORCHESTER, DORCHESTER CENTER, DORCHESTER CENTER/, DORCHESTER/, DOWNTOWN/FINANCIAL DISTRICT, EAST BOSTON, FENWAY, FENWAY/, FINANCIAL DISTRICT, FINANCIAL DISTRICT/, HYDE PARK, JAMAICA PLAIN, MATTAPAN, MATTAPAN/, MISSION HILL, MISSION HILL/, ROSLINDALE, ROSLINDALE/, ROXBURY, ROXBURY CROSSIN, ROXBURY/BOSTON, SOUTH BOSTON, SOUTH BOSTON/, SOUTH END/, WEST ROXBURY, WEST ROXBURY//.',
            ],
            'state' => [
                'type' => 'string',
                'description' => 'Filter by State.',
            ],
            'zip' => [
                'type' => 'string',
                'description' => 'Filter by Zip. Possible values: 00000, 02050, 02108, 02109, 02110, 02111, 02113, 02114, 02115, 02116, 02118, 02119, 02119-3212, 02120, 02121, 02122, 02124, 02125, 02125-1663, 02126, 02127, 02128, 02129, 02130, 02131, 02132, 02134, 02135, 02136, 02145, 02148, 02163, 02188, 02199, 02201, 02205, 02210, 02215, 02446, 02467.',
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
                'type' => 'select',
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
                'type' => 'select',
                'placeholder' => 'Select Status',
                'options' => [
                    0 => [
                        'value' => 'Active',
                        'label' => 'Active',
                    ],
                    1 => [
                        'value' => 'Closed',
                        'label' => 'Closed',
                    ],
                    2 => [
                        'value' => 'Complete',
                        'label' => 'Complete',
                    ],
                    3 => [
                        'value' => 'Issued',
                        'label' => 'Issued',
                    ],
                    4 => [
                        'value' => 'Open',
                        'label' => 'Open',
                    ],
                    5 => [
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
                'type' => 'select',
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
                        'value' => 'Accessory',
                        'label' => 'Accessory',
                    ],
                    13 => [
                        'value' => 'Comm',
                        'label' => 'Comm',
                    ],
                    14 => [
                        'value' => 'Commercial / Mixed Use',
                        'label' => 'Commercial / Mixed Use',
                    ],
                    15 => [
                        'value' => 'Mixed',
                        'label' => 'Mixed',
                    ],
                    16 => [
                        'value' => 'Multi',
                        'label' => 'Multi',
                    ],
                    17 => [
                        'value' => 'Multi-Family (3 units or greater)',
                        'label' => 'Multi-Family (3 units or greater)',
                    ],
                    18 => [
                        'value' => 'One-Family',
                        'label' => 'One-Family',
                    ],
                    19 => [
                        'value' => 'Other',
                        'label' => 'Other',
                    ],
                    20 => [
                        'value' => 'Townhouse',
                        'label' => 'Townhouse',
                    ],
                    21 => [
                        'value' => 'Two-Family',
                        'label' => 'Two-Family',
                    ],
                    22 => [
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
                'description' => 'Start date for \'issued_date\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'issued_date\' (YYYY-MM-DD)',
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
                'description' => 'Filter by Permittypedescr. Possible values: Amendment to a Long Form, Certificate of Occupancy, Electrical Fire Alarms, Electrical Low Voltage, Electrical Permit, Electrical Temporary Service, Erect/New Construction, Foundation Permit, Gas Permit, Long Form/Alteration Permit, Plumbing Permit, Short Form Bldg Permit, Use of Premises.',
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
                'description' => 'Filter by Status. Possible values: Active, Closed, Complete, Issued, Open, Stop Work.',
            ],
            'language_code' => [
                'type' => 'string',
                'description' => 'Filter by Language Code.',
            ],
            'occupancytype' => [
                'type' => 'string',
                'description' => 'Filter by Occupancytype. Possible values: 1-2FAM, 1-3FAM, 1-4FAM, 1-7FAM, 1Unit, 2unit, 3unit, 4unit, 5unit, 6unit, 7More, 7unit, Accessory, Comm, Commercial / Mixed Use, Mixed, Multi, Multi-Family (3 units or greater), One-Family, Other, Townhouse, Two-Family, VacLd.',
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
                'type' => 'select',
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
                'description' => 'Start date for \'start_datetime\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'start_datetime\' (YYYY-MM-DD)',
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
                'description' => 'Filter by Ward. Possible values: 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22.',
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
                'name' => 'shooting',
                'label' => 'Shooting',
                'type' => 'boolean',
            ],
            10 => [
                'name' => 'year',
                'label' => 'Year',
                'type' => 'select',
                'placeholder' => 'Select Year',
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
                    12 => [
                        'value' => '2021',
                        'label' => '2021',
                    ],
                    13 => [
                        'value' => '2022',
                        'label' => '2022',
                    ],
                    14 => [
                        'value' => '2023',
                        'label' => '2023',
                    ],
                    15 => [
                        'value' => '2024',
                        'label' => '2024',
                    ],
                    16 => [
                        'value' => '2025',
                        'label' => '2025',
                    ],
                ],
            ],
            11 => [
                'name' => 'month',
                'label' => 'Month',
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
                'placeholder' => 'Select Source City',
                'options' => [
                    0 => [
                        'value' => 'Boston',
                        'label' => 'Boston',
                    ],
                    1 => [
                        'value' => 'Cambridge',
                        'label' => 'Cambridge',
                    ],
                    2 => [
                        'value' => 'Everett',
                        'label' => 'Everett',
                    ],
                ],
            ],
            22 => [
                'name' => 'crime_details',
                'label' => 'Crime Details',
                'type' => 'text',
                'placeholder' => 'Enter Crime Details',
            ],
            23 => [
                'name' => 'crime_start_time_start',
                'label' => 'Crime Start Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Crime Start Time',
            ],
            24 => [
                'name' => 'crime_start_time_end',
                'label' => 'Crime Start Time End',
                'type' => 'date',
                'placeholder' => 'End date for Crime Start Time',
            ],
            25 => [
                'name' => 'crime_end_time_start',
                'label' => 'Crime End Time Start',
                'type' => 'date',
                'placeholder' => 'Start date for Crime End Time',
            ],
            26 => [
                'name' => 'crime_end_time_end',
                'label' => 'Crime End Time End',
                'type' => 'date',
                'placeholder' => 'End date for Crime End Time',
            ],
        ],
        'contextData' => 'Dataset of Crime Datas. Filter by attributes like incident number, language code, offense code group.',
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
        ],
        'gptSchemaProperties' => [
            'search_term' => [
                'type' => 'string',
                'description' => 'A general search term to query across multiple text fields.',
            ],
            'start_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'Start date for \'occurred_on_date\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'occurred_on_date\' (YYYY-MM-DD)',
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
            'shooting' => [
                'type' => 'boolean',
                'description' => 'Filter by Shooting (true/false).',
            ],
            'year' => [
                'type' => 'string',
                'description' => 'Filter by Year. Possible values: 2009, 2010, 2011, 2012, 2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025.',
            ],
            'month' => [
                'type' => 'string',
                'description' => 'Filter by Month. Possible values: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12.',
            ],
            'day_of_week' => [
                'type' => 'string',
                'description' => 'Filter by Day Of Week. Possible values: Friday, Monday, Saturday, Sunday, Thursday, Tuesday, Wednesday.',
            ],
            'hour' => [
                'type' => 'string',
                'description' => 'Filter by Hour. Possible values: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23.',
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
                'description' => 'Filter by Source City. Possible values: Boston, Cambridge, Everett.',
            ],
            'crime_details' => [
                'type' => 'string',
                'description' => 'Filter by Crime Details.',
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
                'type' => 'select',
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
                'type' => 'select',
                'placeholder' => 'Select Case Status',
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
                'type' => 'select',
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
                        'value' => 'Animal Issues',
                        'label' => 'Animal Issues',
                    ],
                    4 => [
                        'value' => 'Billing',
                        'label' => 'Billing',
                    ],
                    5 => [
                        'value' => 'Boston Bikes',
                        'label' => 'Boston Bikes',
                    ],
                    6 => [
                        'value' => 'Bridge Maintenance',
                        'label' => 'Bridge Maintenance',
                    ],
                    7 => [
                        'value' => 'Building',
                        'label' => 'Building',
                    ],
                    8 => [
                        'value' => 'Catchbasin',
                        'label' => 'Catchbasin',
                    ],
                    9 => [
                        'value' => 'Cemetery',
                        'label' => 'Cemetery',
                    ],
                    10 => [
                        'value' => 'Code Enforcement',
                        'label' => 'Code Enforcement',
                    ],
                    11 => [
                        'value' => 'Employee & General Comments',
                        'label' => 'Employee & General Comments',
                    ],
                    12 => [
                        'value' => 'Enforcement & Abandoned Vehicles',
                        'label' => 'Enforcement & Abandoned Vehicles',
                    ],
                    13 => [
                        'value' => 'Environmental Services',
                        'label' => 'Environmental Services',
                    ],
                    14 => [
                        'value' => 'Fire Hydrant',
                        'label' => 'Fire Hydrant',
                    ],
                    15 => [
                        'value' => 'Generic Noise Disturbance',
                        'label' => 'Generic Noise Disturbance',
                    ],
                    16 => [
                        'value' => 'Graffiti',
                        'label' => 'Graffiti',
                    ],
                    17 => [
                        'value' => 'Health',
                        'label' => 'Health',
                    ],
                    18 => [
                        'value' => 'Highway Maintenance',
                        'label' => 'Highway Maintenance',
                    ],
                    19 => [
                        'value' => 'Housing',
                        'label' => 'Housing',
                    ],
                    20 => [
                        'value' => 'Massport',
                        'label' => 'Massport',
                    ],
                    21 => [
                        'value' => 'Needle Program',
                        'label' => 'Needle Program',
                    ],
                    22 => [
                        'value' => 'Neighborhood Services Issues',
                        'label' => 'Neighborhood Services Issues',
                    ],
                    23 => [
                        'value' => 'Noise Disturbance',
                        'label' => 'Noise Disturbance',
                    ],
                    24 => [
                        'value' => 'Notification',
                        'label' => 'Notification',
                    ],
                    25 => [
                        'value' => 'Office of The Parking Clerk',
                        'label' => 'Office of The Parking Clerk',
                    ],
                    26 => [
                        'value' => 'Operations',
                        'label' => 'Operations',
                    ],
                    27 => [
                        'value' => 'Park Maintenance & Safety',
                        'label' => 'Park Maintenance & Safety',
                    ],
                    28 => [
                        'value' => 'Parking Complaints',
                        'label' => 'Parking Complaints',
                    ],
                    29 => [
                        'value' => 'Pothole',
                        'label' => 'Pothole',
                    ],
                    30 => [
                        'value' => 'Programs',
                        'label' => 'Programs',
                    ],
                    31 => [
                        'value' => 'Recycling',
                        'label' => 'Recycling',
                    ],
                    32 => [
                        'value' => 'Sanitation',
                        'label' => 'Sanitation',
                    ],
                    33 => [
                        'value' => 'Sidewalk Cover / Manhole',
                        'label' => 'Sidewalk Cover / Manhole',
                    ],
                    34 => [
                        'value' => 'Signs & Signals',
                        'label' => 'Signs & Signals',
                    ],
                    35 => [
                        'value' => 'Street Cleaning',
                        'label' => 'Street Cleaning',
                    ],
                    36 => [
                        'value' => 'Street Lights',
                        'label' => 'Street Lights',
                    ],
                    37 => [
                        'value' => 'Traffic Management & Engineering',
                        'label' => 'Traffic Management & Engineering',
                    ],
                    38 => [
                        'value' => 'Trees',
                        'label' => 'Trees',
                    ],
                    39 => [
                        'value' => 'Valet',
                        'label' => 'Valet',
                    ],
                    40 => [
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
                'type' => 'select',
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
                        'value' => 'GEN_',
                        'label' => 'GEN_',
                    ],
                    9 => [
                        'value' => 'GRNi',
                        'label' => 'GRNi',
                    ],
                    10 => [
                        'value' => 'INFO',
                        'label' => 'INFO',
                    ],
                    11 => [
                        'value' => 'ISD',
                        'label' => 'ISD',
                    ],
                    12 => [
                        'value' => 'ONS_',
                        'label' => 'ONS_',
                    ],
                    13 => [
                        'value' => 'PARK',
                        'label' => 'PARK',
                    ],
                    14 => [
                        'value' => 'PROP',
                        'label' => 'PROP',
                    ],
                    15 => [
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
                'type' => 'select',
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
                'type' => 'select',
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
                        'value' => '7',
                        'label' => '7',
                    ],
                    19 => [
                        'value' => '8',
                        'label' => '8',
                    ],
                    20 => [
                        'value' => '9',
                        'label' => '9',
                    ],
                ],
            ],
            20 => [
                'name' => 'city_council_district',
                'label' => 'City Council District',
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'select',
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
                'type' => 'text',
                'placeholder' => 'Enter Source',
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
                'type' => 'select',
                'placeholder' => 'Select Source City',
                'options' => [
                    0 => [
                        'value' => 'Boston',
                        'label' => 'Boston',
                    ],
                    1 => [
                        'value' => 'Cambridge',
                        'label' => 'Cambridge',
                    ],
                ],
            ],
        ],
        'contextData' => 'Dataset of Three One One Cases. Filter by attributes like date (Open Dt), sla target dt, on time.',
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
                'description' => 'Start date for \'open_dt\' (YYYY-MM-DD)',
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date',
                'description' => 'End date for \'open_dt\' (YYYY-MM-DD)',
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
                'description' => 'Filter by On Time. Possible values: ONTIME, OVERDUE.',
            ],
            'case_status' => [
                'type' => 'string',
                'description' => 'Filter by Case Status. Possible values: Acknowledged, Archived, Closed, Open.',
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
                'description' => 'Filter by Reason. Possible values: Abandoned Bicycle, Administrative & General Requests, Air Pollution Control, Animal Issues, Billing, Boston Bikes, Bridge Maintenance, Building, Catchbasin, Cemetery, Code Enforcement, Employee & General Comments, Enforcement & Abandoned Vehicles, Environmental Services, Fire Hydrant, Generic Noise Disturbance, Graffiti, Health, Highway Maintenance, Housing, Massport, Needle Program, Neighborhood Services Issues, Noise Disturbance, Notification, Office of The Parking Clerk, Operations, Park Maintenance & Safety, Parking Complaints, Pothole, Programs, Recycling, Sanitation, Sidewalk Cover / Manhole, Signs & Signals, Street Cleaning, Street Lights, Traffic Management & Engineering, Trees, Valet, Weights and Measures.',
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
                'description' => 'Filter by Department. Possible values: ANML, BHA_, BPD_, BPS_, BTDT, BWSC, DISB, DND_, GEN_, GRNi, INFO, ISD, ONS_, PARK, PROP, PWDx.',
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
                'description' => 'Filter by Fire District. Possible values: 1, 10, 11, 12, 3, 4, 6, 7, 8, 9.',
            ],
            'pwd_district' => [
                'type' => 'string',
                'description' => 'Filter by Pwd District. Possible values:  , 02, 03, 04, 05, 06, 07, 08, 09, 10A, 10B, 1A, 1B, 1C, 2, 3, 4, 5, 7, 8, 9.',
            ],
            'city_council_district' => [
                'type' => 'string',
                'description' => 'Filter by City Council District. Possible values:  , 0, 1, 2, 3, 4, 5, 6, 7, 8, 9.',
            ],
            'police_district' => [
                'type' => 'string',
                'description' => 'Filter by Police District. Possible values:  , A1, A15, A7, B2, B3, C11, C6, D14, D4, E13, E18, E5.',
            ],
            'neighborhood' => [
                'type' => 'string',
                'description' => 'Filter by Neighborhood. Possible values: Allston, Allston / Brighton, Back Bay, Beacon Hill, Boston, Brighton, Charlestown, Chestnut Hill, Dorchester, Downtown / Financial District, East Boston, Fenway / Kenmore / Audubon Circle / Longwood, Greater Mattapan, Hyde Park, Jamaica Plain, Mattapan, Mission Hill, Roslindale, Roxbury, South Boston, South Boston / South Boston Waterfront, South End, West Roxbury.',
            ],
            'neighborhood_services_district' => [
                'type' => 'string',
                'description' => 'Filter by Neighborhood Services District. Possible values:  , 0, 1, 10, 11, 12, 13, 14, 15, 2, 3, 4, 5, 6, 7, 8, 9.',
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
                'type' => 'string',
                'description' => 'Filter by Location Zipcode. Possible values: 2108, 2109, 2110, 2111, 2113, 2114, 2115, 2116, 2118, 2119, 2120, 2121, 2122, 2124, 2125, 2126, 2127, 2128, 2129, 2130, 2131, 2132, 2133, 2134, 2135, 2136, 2163, 2199, 2201, 2203, 2210, 2215, 2446, 2467.',
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
                'description' => 'Filter by Source City. Possible values: Boston, Cambridge.',
            ],
        ],
    ],
];
