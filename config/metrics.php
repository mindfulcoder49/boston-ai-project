<?php

return array (
  'last_updated' => '2033-04-08 06:00:00',
  'data' => 
  array (
    0 => 
    array (
      'modelName' => 'Boston Crime',
      'tableName' => 'crime_data',
      'totalRecords' => 195005,
      'minDate' => '2023-01-01',
      'maxDate' => '2025-07-05',
      'recordsLast30Days' => 5760,
      'recordsLast90Days' => 19139,
      'recordsLast1Year' => 78716,
      'offenseGroupDistribution' => 
      array (
        0 => 
        array (
          'offense_description' => 'INVESTIGATE PERSON',
          'total' => 19081,
        ),
        1 => 
        array (
          'offense_description' => 'SICK ASSIST',
          'total' => 17041,
        ),
        2 => 
        array (
          'offense_description' => 'M/V - LEAVING SCENE - PROPERTY DAMAGE',
          'total' => 11540,
        ),
        3 => 
        array (
          'offense_description' => 'INVESTIGATE PROPERTY',
          'total' => 8716,
        ),
        4 => 
        array (
          'offense_description' => 'LARCENY SHOPLIFTING',
          'total' => 8670,
        ),
        5 => 
        array (
          'offense_description' => 'TOWED MOTOR VEHICLE',
          'total' => 8488,
        ),
        6 => 
        array (
          'offense_description' => 'ASSAULT - SIMPLE',
          'total' => 7918,
        ),
        7 => 
        array (
          'offense_description' => 'VANDALISM',
          'total' => 6982,
        ),
        8 => 
        array (
          'offense_description' => 'PROPERTY - LOST/ MISSING',
          'total' => 6600,
        ),
        9 => 
        array (
          'offense_description' => 'DRUGS - POSSESSION/ SALE/ MANUFACTURING/ USE',
          'total' => 5011,
        ),
      ),
      'shootingIncidents' => 1377,
    ),
    1 => 
    array (
      'modelName' => '311 Cases',
      'tableName' => 'three_one_one_cases',
      'totalRecords' => 140306,
      'minDate' => '2025-01-01',
      'maxDate' => '2025-07-05',
      'recordsLast30Days' => 22171,
      'recordsLast90Days' => 70074,
      'recordsLast1Year' => 140306,
      'caseStatusDistribution' => 
      array (
        0 => 
        array (
          'case_status' => 'Closed',
          'total' => 100306,
        ),
        1 => 
        array (
          'case_status' => 'Open',
          'total' => 40000,
        ),
      ),
      'averageClosureTimeHours' => 80.77,
    ),
    2 => 
    array (
      'modelName' => 'Food Inspections',
      'tableName' => 'food_inspections',
      'totalRecords' => 842005,
      'minDate' => '2006-04-04',
      'maxDate' => '2025-07-04',
      'recordsLast30Days' => 2430,
      'recordsLast90Days' => 9865,
      'recordsLast1Year' => 42339,
      'resultDistribution' => 
      array (
        0 => 
        array (
          'result' => 'HE_Fail',
          'total' => 351726,
        ),
        1 => 
        array (
          'result' => 'HE_Pass',
          'total' => 268562,
        ),
        2 => 
        array (
          'result' => 'HE_Filed',
          'total' => 91021,
        ),
        3 => 
        array (
          'result' => 'HE_FailExt',
          'total' => 66307,
        ),
        4 => 
        array (
          'result' => 'HE_Hearing',
          'total' => 26147,
        ),
      ),
      'violationLevelDistribution' => 
      array (
        0 => 
        array (
          'viol_level' => 'Low',
          'total' => 556203,
        ),
        1 => 
        array (
          'viol_level' => 'High',
          'total' => 122131,
        ),
        2 => 
        array (
          'viol_level' => 'Medium',
          'total' => 99167,
        ),
        3 => 
        array (
          'viol_level' => 'Other',
          'total' => 5793,
        ),
      ),
    ),
    3 => 
    array (
      'modelName' => 'Property Violations',
      'tableName' => 'property_violations',
      'totalRecords' => 16513,
      'minDate' => '2009-12-01',
      'maxDate' => '2025-07-03',
      'recordsLast30Days' => 67,
      'recordsLast90Days' => 226,
      'recordsLast1Year' => 799,
      'statusDistribution' => 
      array (
        0 => 
        array (
          'status' => 'Closed',
          'total' => 15271,
        ),
        1 => 
        array (
          'status' => 'Open',
          'total' => 1241,
        ),
        2 => 
        array (
          'status' => 'Void',
          'total' => 1,
        ),
      ),
      'topViolationCodes' => 
      array (
        0 => 
        array (
          'code' => '105.1',
          'description' => 'Failure to Obtain Permit',
          'total' => 4053,
        ),
        1 => 
        array (
          'code' => '116.2',
          'description' => 'Unsafe and Dangerous',
          'total' => 2451,
        ),
        2 => 
        array (
          'code' => '102.8',
          'description' => 'Maintenance',
          'total' => 1148,
        ),
        3 => 
        array (
          'code' => '1001.3.2',
          'description' => 'Testing & Certification',
          'total' => 882,
        ),
        4 => 
        array (
          'code' => '116.1',
          'description' => 'Unsafe and Dangerous',
          'total' => 850,
        ),
        5 => 
        array (
          'code' => '116',
          'description' => 'Unsafe Structures',
          'total' => 601,
        ),
        6 => 
        array (
          'code' => '104.6',
          'description' => 'Right of Entry',
          'total' => 548,
        ),
        7 => 
        array (
          'code' => '110.1',
          'description' => 'Inspections',
          'total' => 456,
        ),
        8 => 
        array (
          'code' => '107.4',
          'description' => 'Failed to comply w permit term',
          'total' => 450,
        ),
        9 => 
        array (
          'code' => '101.4.4',
          'description' => 'Maintenance',
          'total' => 316,
        ),
      ),
    ),
    4 => 
    array (
      'modelName' => 'Building Permits',
      'tableName' => 'building_permits',
      'totalRecords' => 619132,
      'minDate' => '2006-09-26',
      'maxDate' => '2025-07-04',
      'recordsLast30Days' => 2634,
      'recordsLast90Days' => 8517,
      'recordsLast1Year' => 35902,
      'workTypeDistribution' => 
      array (
        0 => 
        array (
          'worktype' => 'ELECTRICAL',
          'total' => 125480,
        ),
        1 => 
        array (
          'worktype' => 'PLUMBING',
          'total' => 83877,
        ),
        2 => 
        array (
          'worktype' => 'GAS',
          'total' => 61455,
        ),
        3 => 
        array (
          'worktype' => 'INTREN',
          'total' => 53106,
        ),
        4 => 
        array (
          'worktype' => 'LVOLT',
          'total' => 42222,
        ),
        5 => 
        array (
          'worktype' => 'INTEXT',
          'total' => 37230,
        ),
        6 => 
        array (
          'worktype' => 'FA',
          'total' => 32981,
        ),
        7 => 
        array (
          'worktype' => 'OTHER',
          'total' => 23544,
        ),
        8 => 
        array (
          'worktype' => 'ROOF',
          'total' => 23056,
        ),
        9 => 
        array (
          'worktype' => 'EXTREN',
          'total' => 21767,
        ),
      ),
      'permitStatusDistribution' => 
      array (
        0 => 
        array (
          'status' => 'Open',
          'total' => 363474,
        ),
        1 => 
        array (
          'status' => 'Closed',
          'total' => 255653,
        ),
        2 => 
        array (
          'status' => 'Issued',
          'total' => 3,
        ),
        3 => 
        array (
          'status' => 'Stop Work',
          'total' => 2,
        ),
      ),
      'totalDeclaredValuation' => 0.0,
    ),
    5 => 
    array (
      'modelName' => 'Construction Off Hours',
      'tableName' => 'construction_off_hours',
      'totalRecords' => 282,
      'minDate' => '2022-06-20',
      'maxDate' => '2033-04-08',
      'recordsLast30Days' => 142,
      'recordsLast90Days' => 281,
      'recordsLast1Year' => 281,
    ),
    6 => 
    array (
      'modelName' => 'Everett Crime',
      'tableName' => 'everett_crime_data',
      'totalRecords' => 13551,
      'minDate' => '2024-12-31',
      'maxDate' => '2025-07-06',
      'recordsLast30Days' => 2342,
      'recordsLast90Days' => 6810,
      'recordsLast1Year' => 13551,
    ),
    7 => 
    array (
      'modelName' => 'Cambridge 311 Service Request',
      'tableName' => 'cambridge_311_service_requests',
      'totalRecords' => 129815,
      'minDate' => '2009-02-10',
      'maxDate' => '2025-07-07',
      'recordsLast30Days' => 1361,
      'recordsLast90Days' => 4114,
      'recordsLast1Year' => 16591,
    ),
    8 => 
    array (
      'modelName' => 'Cambridge Building Permit',
      'tableName' => 'cambridge_building_permits_data',
      'totalRecords' => 12468,
      'minDate' => '2018-07-03',
      'maxDate' => '2025-07-03',
      'recordsLast30Days' => 118,
      'recordsLast90Days' => 425,
      'recordsLast1Year' => 1674,
    ),
    9 => 
    array (
      'modelName' => 'Cambridge Crime Report',
      'tableName' => 'cambridge_crime_reports_data',
      'totalRecords' => 105341,
      'minDate' => '1924-08-07',
      'maxDate' => '2025-07-01',
      'recordsLast30Days' => 451,
      'recordsLast90Days' => 2441,
      'recordsLast1Year' => 8479,
    ),
    10 => 
    array (
      'modelName' => 'Cambridge Housing Violation',
      'tableName' => 'cambridge_housing_violation_data',
      'totalRecords' => 4639,
      'minDate' => '2018-11-30',
      'maxDate' => '2025-07-02',
      'recordsLast30Days' => 69,
      'recordsLast90Days' => 244,
      'recordsLast1Year' => 1206,
    ),
    11 => 
    array (
      'modelName' => 'Cambridge Sanitary Inspection',
      'tableName' => 'cambridge_sanitary_inspection_data',
      'totalRecords' => 9757,
      'minDate' => '2021-05-12',
      'maxDate' => '2025-06-06',
      'recordsLast30Days' => 0,
      'recordsLast90Days' => 285,
      'recordsLast1Year' => 2293,
    ),
  ),
);
