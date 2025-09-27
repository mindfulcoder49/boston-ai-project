<?php

use App\Models\Trend;
use App\Models\YearlyCountComparison;

// Import the specific data models for Trend analysis
use App\Models\BuildingPermit;
use App\Models\CambridgeCrime;
use App\Models\Cambridge311;
use App\Models\ChicagoCrime;
use App\Models\BostonCrime;
use App\Models\EverettCrime;
use App\Models\FoodInspection;
use App\Models\ThreeOneOneCase;

return [

    /*
    |--------------------------------------------------------------------------
    | News Generation Report Sets
    |--------------------------------------------------------------------------
    |
    | Define sets of reports to be processed by the
    | `app:dispatch-news-article-generation-jobs` command when using the
    | `--run-config` flag. Each set is an array of criteria.
    |
    | For Trend models, you can specify:
    | - 'model_class' (the report model, e.g., Trend::class)
    | - 'source_model_class' (the data model, e.g., BostonCrime::class)
    | - 'column_name'
    | - 'h3_resolution'
    |
    | For YearlyCountComparison models, you can specify:
    | - 'model_class' (e.g., YearlyCountComparison::class)
    | - 'source_model_class' (the data model, e.g., BostonCrime::class)
    | - 'group_by_col'
    |
    */

    'report_sets' => [

        'default' => [
            // Building Permits by Work Type
            [
                'model_class' => Trend::class,
                'source_model_class' => BuildingPermit::class,
                'column_name' => 'worktype',
                'h3_resolution' => 7,
            ],
            // Cambridge Crime by Crime Type
            [
                'model_class' => Trend::class,
                'source_model_class' => CambridgeCrime::class,
                'column_name' => 'crime',
                'h3_resolution' => 7,
            ],
            // Cambridge 311 by Issue Type
            [
                'model_class' => Trend::class,
                'source_model_class' => Cambridge311::class,
                'column_name' => 'issue_type',
                'h3_resolution' => 7,
            ],
            // Chicago Crime by Primary Type
            [
                'model_class' => Trend::class,
                'source_model_class' => ChicagoCrime::class,
                'column_name' => 'primary_type',
                'h3_resolution' => 7,
            ],
            // Boston Crime by Offense Group
            [
                'model_class' => Trend::class,
                'source_model_class' => BostonCrime::class,
                'column_name' => 'offense_code_group',
                'h3_resolution' => 7,
            ],
            // Everett Crime by Incident Type
            [
                'model_class' => Trend::class,
                'source_model_class' => EverettCrime::class,
                'column_name' => 'incident_type',
                'h3_resolution' => 7,
            ],
            // Boston Food Inspections by Violation Level
            [
                'model_class' => Trend::class,
                'source_model_class' => FoodInspection::class,
                'column_name' => 'viol_level',
                'h3_resolution' => 7,
            ],
            // Boston 311 Cases by Reason
            [
                'model_class' => Trend::class,
                'source_model_class' => ThreeOneOneCase::class,
                'column_name' => 'reason',
                'h3_resolution' => 7,
            ],
        ],

        // You can define other sets here, e.g., 'daily_run', 'full_suite'
        // 'my_custom_set' => [
        //     ...
        // ],
    ],

];
