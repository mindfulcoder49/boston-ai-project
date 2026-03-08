<?php

/**
 * Weekly Analysis Schedule Configuration
 *
 * Controls what gets dispatched by `app:run-weekly-analysis`.
 * Stage 4 is fully automatic (discovers models via $statisticalAnalysisColumns).
 * Stage 6 must be configured explicitly because scoring weights are domain-specific.
 *
 * Run manually:
 *   php artisan app:run-weekly-analysis              # all stages
 *   php artisan app:run-weekly-analysis --stage2     # Stage 2 only
 *   php artisan app:run-weekly-analysis --stage4     # Stage 4 only
 *   php artisan app:run-weekly-analysis --stage6     # Stage 6 only
 *   php artisan app:run-weekly-analysis --dry-run    # preview without dispatching
 *   php artisan app:run-weekly-analysis --fresh      # force new data exports
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Stage 2 — Yearly Count Comparison
    |--------------------------------------------------------------------------
    | Runs for ALL auto-discoverable models (any model with $statisticalAnalysisColumns).
    | baseline_year is the reference year all other years are compared against.
    */
    'stage2' => [
        'enabled'       => true,
        'baseline_year' => 2025,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stage 4 — H3 Anomaly & Trend Analysis
    |--------------------------------------------------------------------------
    | Runs for ALL auto-discoverable models (any model with $statisticalAnalysisColumns).
    | Adding a new model to the system automatically includes it here.
    */
    'stage4' => [
        'enabled'         => true,
        'resolutions'     => [9, 8, 7, 6, 5],
        'p_anomaly'       => 0.05,
        'p_trend'         => 0.05,
        'trend_weeks'     => [4, 26, 52],
        'anomaly_weeks'   => 4,
        'export_timespan' => 108,   // weeks of history to include in CSV exports
    ],

    /*
    |--------------------------------------------------------------------------
    | Stage 6 — Historical Neighbourhood Scoring
    |--------------------------------------------------------------------------
    | Configure one entry per model+column you want scored.
    | Each job can override the top-level defaults (resolutions, analysis_weeks, etc.).
    |
    | Required per job:
    |   model   — Fully-qualified model class
    |   column  — The $statisticalAnalysisColumns field to group by
    |
    | Optional per job (falls back to top-level defaults if omitted):
    |   resolutions      — H3 resolutions to generate reports for
    |   analysis_weeks   — Weeks of history for the baseline average
    |   default_weight   — Weight for categories not listed in 'weights'
    |   export_timespan  — Weeks to export (0 = all data)
    |   weights          — ['Category Name' => float, ...] scoring weights
    */
    'stage6' => [
        'enabled'         => true,
        'resolutions'     => [8, 9, 10],
        'analysis_weeks'  => 52,
        'default_weight'  => 1.0,   // equal weight for all unlisted categories
        'export_timespan' => 0,     // 0 = all available data

        'jobs' => [

            // ---------------------------------------------------------------
            // BOSTON
            // ---------------------------------------------------------------

            // Boston Crime — grouped offense categories (18 multiselect values)
            [
                'model'          => \App\Models\CrimeData::class,
                'column'         => 'offense_code_group',
                'default_weight' => 1.0,
                'weights'        => [
                    'Homicide'                             => 5.0,
                    'Kidnapping & Abduction'               => 4.5,
                    'Robbery'                              => 4.0,
                    'Assault'                              => 3.5,
                    'Weapon Offenses'                      => 3.5,
                    'Arson & Property Damage'              => 3.0,
                    'Burglary'                             => 3.0,
                    'Offenses Against Family & Children'   => 2.5,
                    'Drug Offenses'                        => 2.5,
                    'Prostitution & Human Trafficking'     => 2.5,
                    'Motor Vehicle Theft'                  => 2.0,
                    'Fraud & Financial Crime'              => 2.0,
                    'Larceny & Theft'                      => 2.0,
                    'Public Order Offenses'                => 1.5,
                    'Motor Vehicle Incidents & Violations' => 1.5,
                    'Obstruction of Justice'               => 1.5,
                    'Miscellaneous Criminal Offenses'      => 1.0,
                    'Non-Criminal & Service Calls'         => 0.2,
                ],
            ],

            // Boston 311 — resident complaint categories (45 multiselect values)
            [
                'model'          => \App\Models\ThreeOneOneCase::class,
                'column'         => 'reason',
                'default_weight' => 1.0,
                'weights'        => [
                    // High — public health / safety crisis
                    'Needle Program'                    => 4.0,
                    'Health'                            => 3.5,
                    'Code Enforcement'                  => 3.0,
                    'Housing'                           => 3.0,
                    'Air Pollution Control'             => 2.5,
                    'Environmental Services'            => 2.5,
                    'Quality of Life'                   => 2.5,
                    // Medium — visible disorder / infrastructure
                    'Graffiti'                          => 2.0,
                    'Animal Issues'                     => 2.0,
                    'Enforcement & Abandoned Vehicles'  => 2.0,
                    'Neighborhood Services Issues'      => 1.5,
                    'Noise Disturbance'                 => 1.5,
                    'Generic Noise Disturbance'         => 1.5,
                    'Fire Department'                   => 1.5,
                    'Bridge Maintenance'                => 1.5,
                    'Building'                          => 1.5,
                    'Park Maintenance & Safety'         => 1.5,
                    'Sanitation'                        => 1.5,
                    // Low — administrative / routine
                    'Street Cleaning'                   => 0.5,
                    'Recycling'                         => 0.5,
                    'Office of The Parking Clerk'       => 0.4,
                    'Boston Bikes'                      => 0.3,
                    'Massport'                          => 0.3,
                    'Valet'                             => 0.3,
                    'Cemetery'                          => 0.3,
                    'Weights and Measures'              => 0.3,
                    'Alert Boston'                      => 0.3,
                    'Programs'                          => 0.2,
                    'Notification'                      => 0.2,
                    'Employee & General Comments'       => 0.1,
                    'Billing'                           => 0.1,
                    'Administrative & General Requests' => 0.1,
                ],
            ],

            // Boston Building Permits — permit type (13 multiselect values)
            [
                'model'          => \App\Models\BuildingPermit::class,
                'column'         => 'permittypedescr',
                'default_weight' => 1.0,
                'weights'        => [
                    'Erect/New Construction'       => 3.0,
                    'Foundation Permit'            => 2.5,
                    'Long Form/Alteration Permit'  => 2.0,
                    'Amendment to a Long Form'     => 1.5,
                    'Short Form Bldg Permit'       => 1.0,
                    'Use of Premises'              => 0.8,
                    'Gas Permit'                   => 0.7,
                    'Plumbing Permit'              => 0.7,
                    'Electrical Permit'            => 0.6,
                    'Electrical Fire Alarms'       => 0.6,
                    'Certificate of Occupancy'     => 0.5,
                    'Electrical Low Voltage'       => 0.4,
                    'Electrical Temporary Service' => 0.3,
                ],
            ],

            // Boston Food Inspections — violation severity level (6 multiselect values)
            [
                'model'          => \App\Models\FoodInspection::class,
                'column'         => 'viol_level',
                'default_weight' => 0.1,
                'weights'        => [
                    '***'  => 4.0,
                    '**'   => 2.5,
                    '*'    => 1.0,
                    '-'    => 0.2,
                    ' '    => 0.1,
                    '1919' => 0.1,
                ],
            ],

            // ---------------------------------------------------------------
            // CAMBRIDGE
            // ---------------------------------------------------------------

            // Cambridge 311 — issue type (free-text, equal weights)
            [
                'model'          => \App\Models\CambridgeThreeOneOneCase::class,
                'column'         => 'issue_type',
                'default_weight' => 1.0,
            ],

            // Cambridge Crime — offense type (free-text, equal weights)
            [
                'model'          => \App\Models\CambridgeCrimeReportData::class,
                'column'         => 'crime',
                'default_weight' => 1.0,
            ],

            // ---------------------------------------------------------------
            // EVERETT
            // ---------------------------------------------------------------

            // Everett Crime — incident type group (free-text, equal weights)
            [
                'model'          => \App\Models\EverettCrimeData::class,
                'column'         => 'incident_type_group',
                'default_weight' => 1.0,
            ],

            // ---------------------------------------------------------------
            // CHICAGO
            // ---------------------------------------------------------------

            // Chicago Crime — primary type (35 multiselect values)
            [
                'model'          => \App\Models\ChicagoCrime::class,
                'column'         => 'primary_type',
                'default_weight' => 1.0,
                'weights'        => [
                    'HOMICIDE'                          => 5.0,
                    'CRIMINAL SEXUAL ASSAULT'           => 5.0,
                    'CRIM SEXUAL ASSAULT'               => 5.0,
                    'KIDNAPPING'                        => 4.5,
                    'HUMAN TRAFFICKING'                 => 4.5,
                    'OFFENSE INVOLVING CHILDREN'        => 4.0,
                    'ROBBERY'                           => 4.0,
                    'ASSAULT'                           => 3.5,
                    'BATTERY'                           => 3.5,
                    'WEAPONS VIOLATION'                 => 3.5,
                    'SEX OFFENSE'                       => 3.5,
                    'ARSON'                             => 3.0,
                    'STALKING'                          => 3.0,
                    'INTIMIDATION'                      => 2.5,
                    'BURGLARY'                          => 2.5,
                    'NARCOTICS'                         => 2.0,
                    'OTHER NARCOTIC VIOLATION'          => 2.0,
                    'PROSTITUTION'                      => 2.0,
                    'THEFT'                             => 1.5,
                    'MOTOR VEHICLE THEFT'               => 1.5,
                    'CRIMINAL DAMAGE'                   => 1.5,
                    'OBSCENITY'                         => 1.0,
                    'RITUALISM'                         => 1.0,
                    'DECEPTIVE PRACTICE'                => 1.0,
                    'PUBLIC INDECENCY'                  => 0.8,
                    'PUBLIC PEACE VIOLATION'            => 0.8,
                    'GAMBLING'                          => 0.8,
                    'CONCEALED CARRY LICENSE VIOLATION' => 0.8,
                    'CRIMINAL TRESPASS'                 => 0.7,
                    'INTERFERENCE WITH PUBLIC OFFICER'  => 0.7,
                    'OTHER OFFENSE'                     => 0.5,
                    'LIQUOR LAW VIOLATION'              => 0.5,
                    'NON - CRIMINAL'                    => 0.1,
                    'NON-CRIMINAL'                      => 0.1,
                    'NON-CRIMINAL (SUBJECT SPECIFIED)'  => 0.1,
                ],
            ],

            // ---------------------------------------------------------------
            // SAN FRANCISCO
            // ---------------------------------------------------------------

            // San Francisco Crime — incident category (49 multiselect values)
            [
                'model'          => \App\Models\SanFranciscoCrime::class,
                'column'         => 'incident_category',
                'default_weight' => 0.5,
                'weights'        => [
                    'Homicide'                                       => 5.0,
                    'Rape'                                           => 5.0,
                    'Human Trafficking (A), Commercial Sex Acts'     => 4.5,
                    'Human Trafficking (B), Involuntary Servitude'   => 4.5,
                    'Human Trafficking, Commercial Sex Acts'         => 4.5,
                    'Assault'                                        => 4.0,
                    'Robbery'                                        => 4.0,
                    'Sex Offense'                                    => 3.5,
                    'Weapons Carrying Etc'                           => 3.5,
                    'Weapons Offence'                                => 3.5,
                    'Weapons Offense'                                => 3.5,
                    'Offences Against The Family And Children'       => 3.0,
                    'Arson'                                          => 3.0,
                    'Burglary'                                       => 2.5,
                    'Drug Offense'                                   => 2.5,
                    'Drug Violation'                                 => 2.5,
                    'Vandalism'                                      => 2.0,
                    'Malicious Mischief'                             => 2.0,
                    'Stolen Property'                                => 2.0,
                    'Motor Vehicle Theft'                            => 2.0,
                    'Motor Vehicle Theft?'                           => 2.0,
                    'Fraud'                                          => 1.5,
                    'Larceny Theft'                                  => 1.5,
                    'Forgery And Counterfeiting'                     => 1.5,
                    'Embezzlement'                                   => 1.0,
                    'Gambling'                                       => 0.8,
                    'Disorderly Conduct'                             => 0.8,
                    'Prostitution'                                   => 0.8,
                    'Warrant'                                        => 0.5,
                    'Traffic Violation Arrest'                       => 0.5,
                    'Traffic Collision'                              => 0.5,
                    'Civil Sidewalks'                                => 0.5,
                    'Liquor Laws'                                    => 0.4,
                    'Suicide'                                        => 0.4,
                    'Missing Person'                                 => 0.3,
                    'Suspicious'                                     => 0.3,
                    'Suspicious Occ'                                 => 0.3,
                    'Fire Report'                                    => 0.3,
                    'Vehicle Impounded'                              => 0.3,
                    'Other Offenses'                                 => 0.3,
                    'Other'                                          => 0.3,
                    'Other Miscellaneous'                            => 0.3,
                    'Vehicle Misplaced'                              => 0.2,
                    'Recovered Vehicle'                              => 0.2,
                    'Lost Property'                                  => 0.2,
                    'Courtesy Report'                                => 0.1,
                    'Miscellaneous Investigation'                    => 0.1,
                    'Case Closure'                                   => 0.1,
                    'Non-Criminal'                                   => 0.1,
                ],
            ],

            // ---------------------------------------------------------------
            // SEATTLE
            // ---------------------------------------------------------------

            // Seattle Crime — broad offense category (3 multiselect values)
            [
                'model'          => \App\Models\SeattleCrime::class,
                'column'         => 'offense_category',
                'default_weight' => 0.5,
                'weights'        => [
                    'VIOLENT CRIME'  => 4.0,
                    'PROPERTY CRIME' => 2.0,
                    'ALL OTHER'      => 0.5,
                ],
            ],

            // Seattle Crime — NIBRS crime-against category (6 multiselect values)
            [
                'model'          => \App\Models\SeattleCrime::class,
                'column'         => 'nibrs_crime_against_category',
                'default_weight' => 0.5,
                'weights'        => [
                    'PERSON'      => 4.0,
                    'SOCIETY'     => 2.0,
                    'PROPERTY'    => 2.0,
                    'ANY'         => 1.0,
                    '-'           => 0.2,
                    'NOT_A_CRIME' => 0.1,
                ],
            ],

            // ---------------------------------------------------------------
            // MONTGOMERY COUNTY, MD
            // ---------------------------------------------------------------

            // Montgomery County Crime — high-level NIBRS crime-against category (4 multiselect values)
            [
                'model'          => \App\Models\MontgomeryCountyMdCrime::class,
                'column'         => 'crimename1',
                'default_weight' => 1.0,
                'weights'        => [
                    'Crime Against Person'      => 4.0,
                    'Crime Against Society'     => 2.5,
                    'Crime Against Property'    => 2.0,
                    'Crime Against Not a Crime' => 0.1,
                ],
            ],

        ],
    ],

];
