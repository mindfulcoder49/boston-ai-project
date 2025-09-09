<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Trend;

class DispatchStatisticalAnalysisJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-statistical-analysis-jobs {model? : The model class to process (e.g., Crime)} {--fresh : Force regeneration of all data exports} {--plots : Generate plots for the analysis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discovers models and fields for analysis, uses cached exports or creates new ones, and dispatches jobs to the Python analysis API.';

    private $exportDirectory = 'analysis_exports';

    public function handle()
    {
        $this->info('Starting statistical analysis job dispatch process...');

        if ($this->option('fresh')) {
            $this->warn('The --fresh option was used. Deleting all cached data exports.');
            Storage::disk('public')->deleteDirectory($this->exportDirectory);
            $this->info('Cached exports cleared.');
        }

        $apiBaseUrl = config('services.analysis_api.url');
        if (!$apiBaseUrl) {
            $this->error('Analysis API URL is not configured. Please set ANALYSIS_API_URL in your .env file.');
            return 1;
        }

        Storage::disk('public')->makeDirectory($this->exportDirectory);

        $allModelClasses = $this->getModelClasses();
        $specificModel = $this->argument('model');
        $modelClasses = [];

        if ($specificModel) {
            $resolvedModelClass = null;
            if (class_exists($specificModel)) {
                $resolvedModelClass = $specificModel;
            } elseif (class_exists('App\\Models\\' . $specificModel)) {
                $resolvedModelClass = 'App\\Models\\' . $specificModel;
            }

            if ($resolvedModelClass && in_array($resolvedModelClass, $allModelClasses)) {
                $this->info("Processing only specified model: {$resolvedModelClass}");
                $modelClasses = [$resolvedModelClass];
            } else {
                $this->error("The specified model '{$specificModel}' is not a valid or analyzable model.");
                $this->line('Available models: ' . implode(', ', array_map('class_basename', $allModelClasses)));
                return 1;
            }
        } else {
            $this->info('No specific model provided. Processing all discoverable models.');
            $modelClasses = $allModelClasses;
        }

        $allModelMetadata = config('model_metadata_suggestions', []);

        foreach ($modelClasses as $modelClass) {
            $this->line("Processing model: <fg=cyan>{$modelClass}</fg=cyan>");

            if (!isset($allModelMetadata[$modelClass])) {
                $this->warn("No metadata found for model {$modelClass} in config/model_metadata_suggestions.php. Skipping.");
                continue;
            }
            $modelMetadata = $allModelMetadata[$modelClass];

            $modelInstance = new $modelClass();
            $connectionName = $modelInstance->getConnectionName();
            //use the connection name or default to 'mysql'
            if (!$connectionName) {
                $connectionName = config('database.default');
            }
            $tableName = $modelInstance->getTable();
            $dateField = $modelInstance::getDateField();
            $latField = $modelInstance::getLatitudeField();
            $lonField = $modelInstance::getLongitudeField();

            if (!$dateField || !$latField || !$lonField) {
                $this->warn("Model {$modelClass} is missing a required Mappable field (date, lat, or lon). Skipping.");
                continue;
            }

            // Always include a unified analysis.
            $fieldsForAnalysis = ['__unified__'];
            $dbExportColumns = [];

            if (property_exists($modelClass, 'statisticalAnalysisColumns') && !empty($modelClass::$statisticalAnalysisColumns)) {
                $specificColumns = $modelClass::$statisticalAnalysisColumns;
                $this->line("    Found explicit analysis columns: <fg=yellow>" . implode(', ', $specificColumns) . "</fg=yellow>");
                // Add specific columns to the list of jobs to run and columns to export.
                $fieldsForAnalysis = array_merge($fieldsForAnalysis, $specificColumns);
                $dbExportColumns = $specificColumns;
            } else {
                $this->line("    No explicit analysis columns found. Will perform a single unified analysis for the model.");
            }

            $modelKey = Str::kebab(class_basename($modelClass));

            // Define all columns needed for the export from the database.
            // This now correctly includes only the specific columns for analysis.
            // The unified 'source_dataset' column is added dynamically during export.

            // 1. Create a single export for all fields for this model
            $exportFilename = "{$this->exportDirectory}/{$modelKey}_all_fields.csv";

            if (Storage::disk('public')->exists($exportFilename)) {
                $this->line("    Using existing data export for all fields: <fg=gray>{$exportFilename}</fg=gray>");
            } else {
                $this->line('    No existing export found for all fields. Generating new CSV...');
                $this->exportDataForModel($tableName, $dateField, $latField, $lonField, $dbExportColumns, $exportFilename, $modelClass, $connectionName);
                $this->info("    Successfully generated new data export for all fields.");
            }
            $publicUrl = Storage::url($exportFilename);
            $this->line("    Public URL for all jobs for this model: <fg=blue>" . url($publicUrl) . "</fg=blue>");


            foreach ($fieldsForAnalysis as $field) {
                $isUnifiedAnalysis = $field === '__unified__';
                $analysisField = $isUnifiedAnalysis ? 'source_dataset' : $field;
                $jobSuffix = $isUnifiedAnalysis ? 'unified' : $field;

                $this->info("--> Preparing jobs for analysis field: <fg=yellow>{$analysisField}</fg=yellow>");

                $resolutions = [9, 8, 7, 6, 5]; // Run analysis for multiple resolutions

                foreach ($resolutions as $resolution) {
                    $this->info("  --> Preparing job for resolution: <fg=cyan>{$resolution}</fg=cyan>");

                    // 2. Prepare and Dispatch API Job for each field
                    $this->line('    Dispatching job to analysis API...');
                    $jobId = "laravel-{$modelKey}-{$jobSuffix}-res{$resolution}-" . time();

                    $generatePlots = $this->option('plots');

                    $analysisParameters = [
                        'h3_resolution' => $resolution,
                        'p_value_anomaly' => 0.05,
                        'p_value_trend' => 0.05,
                        'analysis_weeks_trend' => [4, 26, 52],
                        'analysis_weeks_anomaly' => 4,
                        'generate_plots' => $generatePlots,
                        'plot_generation' => $generatePlots ? 'both' : 'none',
                    ];

                    $payload = [
                        'job_id' => $jobId,
                        'data_sources' => [
                            [
                                'data_url' => url($publicUrl),
                                'timestamp_col' => $dateField,
                                'lat_col' => $latField,
                                'lon_col' => $lonField,
                                'secondary_group_col' => $analysisField,
                            ],
                        ],
                        'config' => [
                            'analysis_stages' => ['stage4_h3_anomaly'],
                            'parameters' => [
                                'stage4_h3_anomaly' => $analysisParameters,
                            ],
                        ],
                    ];

                    $response = Http::timeout(30)->post("{$apiBaseUrl}/api/v1/jobs", $payload);

                    if ($response->successful() && $response->status() === 202) {
                        $this->info("    Successfully dispatched job. Job ID: <fg=green>{$jobId}</fg=green>");
                        // 3. Update the database with the new job
                        $this->info("    Updating trends database for {$modelClass} -> {$jobSuffix} at resolution {$resolution}...");

                        $searchCriteria = [
                            'model_class' => $modelClass,
                            'column_name' => $jobSuffix,
                            'h3_resolution' => $analysisParameters['h3_resolution'],
                            'p_value_anomaly' => $analysisParameters['p_value_anomaly'],
                            'p_value_trend' => $analysisParameters['p_value_trend'],
                            'analysis_weeks_anomaly' => $analysisParameters['analysis_weeks_anomaly'],
                        ];

                        Trend::updateOrCreate(
                            $searchCriteria,
                            [
                                'job_id' => $jobId,
                                'analysis_weeks_trend' => $analysisParameters['analysis_weeks_trend'],
                            ]
                        );
                        $this->info("    Trends database updated successfully.");
                    } else {
                        $this->error("    Failed to dispatch job for {$modelClass} -> {$jobSuffix} at resolution {$resolution}. Status: {$response->status()}");
                        $this->error("    Response: " . $response->body());
                    }
                }
            }
        }

        $this->info('Statistical analysis job dispatch process completed.');
        return 0;
    }

    private function exportDataForModel(string $tableName, string $dateField, string $latField, string $lonField, array $fieldsToAnalyze, string $filename, string $modelClass, string $connectionName)
    {
        $filePath = Storage::disk('public')->path($filename);

        $this->line("      DB columns to export: <fg=yellow>" . (empty($fieldsToAnalyze) ? 'None' : implode(', ', $fieldsToAnalyze)) . "</fg=yellow>");

        $this->line("      Counting rows to export for {$tableName}...");
        $query = DB::connection($connectionName)->table($tableName)->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField);
        $totalRows = $query->count();

        if ($totalRows === 0) {
            $this->warn("      No rows to export. Creating an empty file with a header.");
            $fileHandle = fopen($filePath, 'w');
            // Header includes core fields, specific analysis fields, and the unified field
            $header = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze, ['source_dataset']);
            fputcsv($fileHandle, $header);
            fclose($fileHandle);
            return;
        }

        $this->info("      Total rows to export: {$totalRows}");

        $fileHandle = fopen($filePath, 'w');
        // The final CSV header will always include source_dataset for the unified analysis.
        $header = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze, ['source_dataset']);
        fputcsv($fileHandle, $header);

        $primaryKey = DB::connection($connectionName)->getSchemaBuilder()->getIndexes($tableName)[0]['columns'][0] ?? 'id';

        $progressBar = $this->output->createProgressBar($totalRows);
        $progressBar->start();

        // Select only the columns that exist in the database.
        $selectColumns = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze);
        $query = DB::connection($connectionName)->table($tableName)->select($selectColumns)
            ->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField);

        $unifiedValue = $modelClass::getHumanName();

        $query->orderBy($primaryKey)->lazy(50000)
            ->each(function ($row) use ($fileHandle, $selectColumns, $progressBar, $unifiedValue) {
                $rowData = [];
                foreach ($selectColumns as $col) {
                    $rowData[] = $row->$col;
                }
                // Always append the unified value for the source_dataset column.
                $rowData[] = $unifiedValue;
                fputcsv($fileHandle, $rowData);
                $progressBar->advance();
            });

        $progressBar->finish();
        $this->newLine();

        fclose($fileHandle);
    }

    private function getModelClasses(): array
    {
        $path = app_path('Models');
        $modelClasses = [];
        if (!File::isDirectory($path)) return [];

        $files = File::files($path);
        foreach ($files as $file) {
            $className = 'App\\Models\\' . $file->getBasename('.php');
            if (class_exists($className) && method_exists($className, 'getMappableTraitUsageCheck') && property_exists($className, 'statisticalAnalysisColumns')) {
                $modelClasses[] = $className;
            }
        }
        return $modelClasses;
    }

    private function getFieldsForAnalysisFromMetadata(array $modelMetadata): array
    {
        $fields = [];
        if (empty($modelMetadata['filterableFieldsDescription'])) {
            return [];
        }

        foreach ($modelMetadata['filterableFieldsDescription'] as $fieldInfo) {
            if (isset($fieldInfo['type']) && in_array($fieldInfo['type'], ['multiselect', 'boolean'])) {
                if (isset($fieldInfo['name'])) {
                    $fields[] = $fieldInfo['name'];
                }
            }
        }
        return array_unique($fields);
    }
}