<?php

return array (
  'last_updated' => '2033-04-08 06:00:00',
  'data' => 
  array (
    0 => 
    array (
      'modelName' => 'Boston Crime',
      'tableName' => 'crime_data',
      'totalRecords' => 252565,
      'minDate' => '2023-01-01',
      'maxDate' => '2026-03-17',
      'recordsLast30Days' => 5706,
      'recordsLast90Days' => 18084,
      'recordsLast1Year' => 80491,
      'offenseGroupDistribution' => 
      array (
        0 => 
        array (
          'offense_description' => 'INVESTIGATE PERSON',
          'total' => 25297,
        ),
        1 => 
        array (
          'offense_description' => 'SICK ASSIST',
          'total' => 22888,
        ),
        2 => 
        array (
          'offense_description' => 'M/V - LEAVING SCENE - PROPERTY DAMAGE',
          'total' => 15066,
        ),
        3 => 
        array (
          'offense_description' => 'LARCENY SHOPLIFTING',
          'total' => 11815,
        ),
        4 => 
        array (
          'offense_description' => 'TOWED MOTOR VEHICLE',
          'total' => 11377,
        ),
        5 => 
        array (
          'offense_description' => 'INVESTIGATE PROPERTY',
          'total' => 11025,
        ),
        6 => 
        array (
          'offense_description' => 'ASSAULT - SIMPLE',
          'total' => 10115,
        ),
        7 => 
        array (
          'offense_description' => 'VANDALISM',
          'total' => 8777,
        ),
        8 => 
        array (
          'offense_description' => 'PROPERTY - LOST/ MISSING',
          'total' => 8726,
        ),
        9 => 
        array (
          'offense_description' => 'DRUGS - POSSESSION/ SALE/ MANUFACTURING/ USE',
          'total' => 6874,
        ),
      ),
      'shootingIncidents' => 1727,
    ),
    1 => 
    array (
      'modelName' => 'Boston 311 Cases',
      'tableName' => 'three_one_one_cases',
      'totalRecords' => 351716,
      'minDate' => '2025-01-01',
      'maxDate' => '2026-03-18',
      'recordsLast30Days' => 31020,
      'recordsLast90Days' => 80114,
      'recordsLast1Year' => 295168,
      'caseStatusDistribution' => 
      array (
        0 => 
        array (
          'case_status' => 'Closed',
          'total' => 253940,
        ),
        1 => 
        array (
          'case_status' => 'Open',
          'total' => 97776,
        ),
      ),
      'averageClosureTimeHours' => 100.56,
    ),
    2 => 
    array (
      'modelName' => 'Boston Food Inspections',
      'tableName' => 'food_inspections',
      'totalRecords' => 876657,
      'minDate' => '2006-04-04',
      'maxDate' => '2026-03-16',
      'recordsLast30Days' => 4188,
      'recordsLast90Days' => 11871,
      'recordsLast1Year' => 46732,
      'resultDistribution' => 
      array (
        0 => 
        array (
          'result' => 'HE_Fail',
          'total' => 366351,
        ),
        1 => 
        array (
          'result' => 'HE_Pass',
          'total' => 279730,
        ),
        2 => 
        array (
          'result' => 'HE_Filed',
          'total' => 92546,
        ),
        3 => 
        array (
          'result' => 'HE_FailExt',
          'total' => 71675,
        ),
        4 => 
        array (
          'result' => 'HE_Hearing',
          'total' => 27282,
        ),
      ),
      'violationLevelDistribution' => 
      array (
        0 => 
        array (
          'viol_level' => 'Low',
          'total' => 575055,
        ),
        1 => 
        array (
          'viol_level' => 'High',
          'total' => 125756,
        ),
        2 => 
        array (
          'viol_level' => 'Medium',
          'total' => 108847,
        ),
        3 => 
        array (
          'viol_level' => 'Other',
          'total' => 6572,
        ),
      ),
    ),
    3 => 
    array (
      'modelName' => 'Boston Property Violations',
      'tableName' => 'property_violations',
      'totalRecords' => 16900,
      'minDate' => '2009-12-01',
      'maxDate' => '2026-03-12',
      'recordsLast30Days' => 42,
      'recordsLast90Days' => 148,
      'recordsLast1Year' => 659,
      'statusDistribution' => 
      array (
        0 => 
        array (
          'status' => 'Closed',
          'total' => 15970,
        ),
        1 => 
        array (
          'status' => 'Open',
          'total' => 929,
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
          'total' => 4185,
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
          'total' => 1251,
        ),
        3 => 
        array (
          'code' => '1001.3.2',
          'description' => 'Testing & Certification',
          'total' => 912,
        ),
        4 => 
        array (
          'code' => '116.1',
          'description' => 'Unsafe and Dangerous',
          'total' => 852,
        ),
        5 => 
        array (
          'code' => '116',
          'description' => 'Unsafe Structures',
          'total' => 653,
        ),
        6 => 
        array (
          'code' => '104.6',
          'description' => 'Right of Entry',
          'total' => 553,
        ),
        7 => 
        array (
          'code' => '107.4',
          'description' => 'Failed to comply w permit term',
          'total' => 467,
        ),
        8 => 
        array (
          'code' => '110.1',
          'description' => 'Inspections',
          'total' => 456,
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
      'totalRecords' => 645144,
      'minDate' => '2006-09-26',
      'maxDate' => '2026-03-17',
      'recordsLast30Days' => 2437,
      'recordsLast90Days' => 7381,
      'recordsLast1Year' => 36446,
      'workTypeDistribution' => 
      array (
        0 => 
        array (
          'worktype' => 'ELECTRICAL',
          'total' => 130469,
        ),
        1 => 
        array (
          'worktype' => 'PLUMBING',
          'total' => 87457,
        ),
        2 => 
        array (
          'worktype' => 'GAS',
          'total' => 63649,
        ),
        3 => 
        array (
          'worktype' => 'INTREN',
          'total' => 55219,
        ),
        4 => 
        array (
          'worktype' => 'LVOLT',
          'total' => 43682,
        ),
        5 => 
        array (
          'worktype' => 'INTEXT',
          'total' => 38882,
        ),
        6 => 
        array (
          'worktype' => 'FA',
          'total' => 34347,
        ),
        7 => 
        array (
          'worktype' => 'OTHER',
          'total' => 24573,
        ),
        8 => 
        array (
          'worktype' => 'ROOF',
          'total' => 24132,
        ),
        9 => 
        array (
          'worktype' => 'EXTREN',
          'total' => 22452,
        ),
      ),
      'permitStatusDistribution' => 
      array (
        0 => 
        array (
          'status' => 'Open',
          'total' => 372843,
        ),
        1 => 
        array (
          'status' => 'Closed',
          'total' => 272296,
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
      'totalRecords' => 503,
      'minDate' => '2022-06-20',
      'maxDate' => '2033-04-08',
      'recordsLast30Days' => 45,
      'recordsLast90Days' => 85,
      'recordsLast1Year' => 502,
    ),
    6 => 
    array (
      'modelName' => 'Everett Crime',
      'tableName' => 'everett_crime_data',
      'totalRecords' => 134350,
      'minDate' => '2021-04-04',
      'maxDate' => '2026-03-19',
      'recordsLast30Days' => 2092,
      'recordsLast90Days' => 5917,
      'recordsLast1Year' => 26052,
    ),
    7 => 
    array (
      'modelName' => 'Cambridge 311 Service Request',
      'tableName' => 'cambridge_311_service_requests',
      'totalRecords' => 143765,
      'minDate' => '2009-02-10',
      'maxDate' => '2026-03-19',
      'recordsLast30Days' => 2336,
      'recordsLast90Days' => 6439,
      'recordsLast1Year' => 18835,
    ),
    8 => 
    array (
      'modelName' => 'Cambridge Building Permit',
      'tableName' => 'cambridge_building_permits_data',
      'totalRecords' => 12801,
      'minDate' => '2018-07-03',
      'maxDate' => '2025-08-29',
      'recordsLast30Days' => 0,
      'recordsLast90Days' => 0,
      'recordsLast1Year' => 828,
    ),
    9 => 
    array (
      'modelName' => 'Cambridge Crime Report',
      'tableName' => 'cambridge_crime_reports_data',
      'totalRecords' => 113833,
      'minDate' => '1924-08-07',
      'maxDate' => '2026-03-02',
      'recordsLast30Days' => 189,
      'recordsLast90Days' => 1559,
      'recordsLast1Year' => 11443,
    ),
    10 => 
    array (
      'modelName' => 'Cambridge Housing Violation',
      'tableName' => 'cambridge_housing_violation_data',
      'totalRecords' => 4807,
      'minDate' => '2018-11-30',
      'maxDate' => '2025-08-29',
      'recordsLast30Days' => 0,
      'recordsLast90Days' => 0,
      'recordsLast1Year' => 578,
    ),
    11 => 
    array (
      'modelName' => 'Cambridge Sanitary Inspection',
      'tableName' => 'cambridge_sanitary_inspection_data',
      'totalRecords' => 9757,
      'minDate' => '2021-05-12',
      'maxDate' => '2025-06-06',
      'recordsLast30Days' => 0,
      'recordsLast90Days' => 0,
      'recordsLast1Year' => 393,
    ),
  ),
);
