<?php

return array (
  'last_updated' => '2033-04-08 06:00:00',
  'data' => 
  array (
    0 => 
    array (
      'modelName' => 'Boston Crime',
      'tableName' => 'crime_data',
      'totalRecords' => 249007,
      'minDate' => '2023-01-01',
      'maxDate' => '2026-03-02',
      'recordsLast30Days' => 5243,
      'recordsLast90Days' => 17467,
      'recordsLast1Year' => 79959,
      'offenseGroupDistribution' => 
      array (
        0 => 
        array (
          'offense_description' => 'INVESTIGATE PERSON',
          'total' => 24891,
        ),
        1 => 
        array (
          'offense_description' => 'SICK ASSIST',
          'total' => 22436,
        ),
        2 => 
        array (
          'offense_description' => 'M/V - LEAVING SCENE - PROPERTY DAMAGE',
          'total' => 14871,
        ),
        3 => 
        array (
          'offense_description' => 'LARCENY SHOPLIFTING',
          'total' => 11646,
        ),
        4 => 
        array (
          'offense_description' => 'TOWED MOTOR VEHICLE',
          'total' => 11230,
        ),
        5 => 
        array (
          'offense_description' => 'INVESTIGATE PROPERTY',
          'total' => 10898,
        ),
        6 => 
        array (
          'offense_description' => 'ASSAULT - SIMPLE',
          'total' => 9968,
        ),
        7 => 
        array (
          'offense_description' => 'VANDALISM',
          'total' => 8657,
        ),
        8 => 
        array (
          'offense_description' => 'PROPERTY - LOST/ MISSING',
          'total' => 8587,
        ),
        9 => 
        array (
          'offense_description' => 'DRUGS - POSSESSION/ SALE/ MANUFACTURING/ USE',
          'total' => 6743,
        ),
      ),
      'shootingIncidents' => 1714,
    ),
    1 => 
    array (
      'modelName' => 'Boston 311 Cases',
      'tableName' => 'three_one_one_cases',
      'totalRecords' => 337211,
      'minDate' => '2025-01-01',
      'maxDate' => '2026-03-03',
      'recordsLast30Days' => 28167,
      'recordsLast90Days' => 73286,
      'recordsLast1Year' => 291295,
      'caseStatusDistribution' => 
      array (
        0 => 
        array (
          'case_status' => 'Closed',
          'total' => 242929,
        ),
        1 => 
        array (
          'case_status' => 'Open',
          'total' => 94282,
        ),
      ),
      'averageClosureTimeHours' => 101.84,
    ),
    2 => 
    array (
      'modelName' => 'Boston Food Inspections',
      'tableName' => 'food_inspections',
      'totalRecords' => 874757,
      'minDate' => '2006-04-04',
      'maxDate' => '2026-03-03',
      'recordsLast30Days' => 4163,
      'recordsLast90Days' => 12082,
      'recordsLast1Year' => 46117,
      'resultDistribution' => 
      array (
        0 => 
        array (
          'result' => 'HE_Fail',
          'total' => 365513,
        ),
        1 => 
        array (
          'result' => 'HE_Pass',
          'total' => 279221,
        ),
        2 => 
        array (
          'result' => 'HE_Filed',
          'total' => 92499,
        ),
        3 => 
        array (
          'result' => 'HE_FailExt',
          'total' => 71307,
        ),
        4 => 
        array (
          'result' => 'HE_Hearing',
          'total' => 27233,
        ),
      ),
      'violationLevelDistribution' => 
      array (
        0 => 
        array (
          'viol_level' => 'Low',
          'total' => 574049,
        ),
        1 => 
        array (
          'viol_level' => 'High',
          'total' => 125549,
        ),
        2 => 
        array (
          'viol_level' => 'Medium',
          'total' => 108319,
        ),
        3 => 
        array (
          'viol_level' => 'Other',
          'total' => 6498,
        ),
      ),
    ),
    3 => 
    array (
      'modelName' => 'Boston Property Violations',
      'tableName' => 'property_violations',
      'totalRecords' => 16879,
      'minDate' => '2009-12-01',
      'maxDate' => '2026-03-03',
      'recordsLast30Days' => 68,
      'recordsLast90Days' => 150,
      'recordsLast1Year' => 698,
      'statusDistribution' => 
      array (
        0 => 
        array (
          'status' => 'Closed',
          'total' => 15928,
        ),
        1 => 
        array (
          'status' => 'Open',
          'total' => 950,
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
          'total' => 4170,
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
          'total' => 1248,
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
          'total' => 851,
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
          'total' => 552,
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
          'total' => 457,
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
      'totalRecords' => 643937,
      'minDate' => '2006-09-26',
      'maxDate' => '2026-03-04',
      'recordsLast30Days' => 2489,
      'recordsLast90Days' => 7571,
      'recordsLast1Year' => 36558,
      'workTypeDistribution' => 
      array (
        0 => 
        array (
          'worktype' => 'ELECTRICAL',
          'total' => 130227,
        ),
        1 => 
        array (
          'worktype' => 'PLUMBING',
          'total' => 87273,
        ),
        2 => 
        array (
          'worktype' => 'GAS',
          'total' => 63527,
        ),
        3 => 
        array (
          'worktype' => 'INTREN',
          'total' => 55103,
        ),
        4 => 
        array (
          'worktype' => 'LVOLT',
          'total' => 43630,
        ),
        5 => 
        array (
          'worktype' => 'INTEXT',
          'total' => 38810,
        ),
        6 => 
        array (
          'worktype' => 'FA',
          'total' => 34277,
        ),
        7 => 
        array (
          'worktype' => 'OTHER',
          'total' => 24536,
        ),
        8 => 
        array (
          'worktype' => 'ROOF',
          'total' => 24096,
        ),
        9 => 
        array (
          'worktype' => 'EXTREN',
          'total' => 22425,
        ),
      ),
      'permitStatusDistribution' => 
      array (
        0 => 
        array (
          'status' => 'Open',
          'total' => 372456,
        ),
        1 => 
        array (
          'status' => 'Closed',
          'total' => 271476,
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
      'totalRecords' => 485,
      'minDate' => '2022-06-20',
      'maxDate' => '2033-04-08',
      'recordsLast30Days' => 58,
      'recordsLast90Days' => 100,
      'recordsLast1Year' => 484,
    ),
    6 => 
    array (
      'modelName' => 'Everett Crime',
      'tableName' => 'everett_crime_data',
      'totalRecords' => 133304,
      'minDate' => '2021-04-04',
      'maxDate' => '2026-03-05',
      'recordsLast30Days' => 1872,
      'recordsLast90Days' => 5683,
      'recordsLast1Year' => 26014,
    ),
    7 => 
    array (
      'modelName' => 'Cambridge 311 Service Request',
      'tableName' => 'cambridge_311_service_requests',
      'totalRecords' => 132619,
      'minDate' => '2009-02-10',
      'maxDate' => '2025-09-02',
      'recordsLast30Days' => 0,
      'recordsLast90Days' => 0,
      'recordsLast1Year' => 8240,
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
      'recordsLast1Year' => 896,
    ),
    9 => 
    array (
      'modelName' => 'Cambridge Crime Report',
      'tableName' => 'cambridge_crime_reports_data',
      'totalRecords' => 113833,
      'minDate' => '1924-08-07',
      'maxDate' => '2026-03-02',
      'recordsLast30Days' => 433,
      'recordsLast90Days' => 2072,
      'recordsLast1Year' => 11724,
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
      'recordsLast1Year' => 703,
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
      'recordsLast1Year' => 512,
    ),
  ),
);
