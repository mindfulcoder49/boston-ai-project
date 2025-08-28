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
    protected $signature = 'app:dispatch-statistical-analysis-jobs {--fresh : Force regeneration of all data exports}';

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

        $modelClasses = $this->getModelClasses();
        $allModelMetadata = config('model_metadata_suggestions', []);

        foreach ($modelClasses as $modelClass) {
            $this->line("Processing model: <fg=cyan>{$modelClass}</fg=cyan>");

            if (!isset($allModelMetadata[$modelClass])) {
                $this->warn("No metadata found for model {$modelClass} in config/model_metadata_suggestions.php. Skipping.");
                continue;
            }
            $modelMetadata = $allModelMetadata[$modelClass];

            $modelInstance = new $modelClass();
            $tableName = $modelInstance->getTable();
            $dateField = $modelInstance::getDateField();
            $latField = $modelInstance::getLatitudeField();
            $lonField = $modelInstance::getLongitudeField();

            if (!$dateField || !$latField || !$lonField) {
                $this->warn("Model {$modelClass} is missing a required Mappable field (date, lat, or lon). Skipping.");
                continue;
            }

            $fieldsForAnalysis = $this->getFieldsForAnalysisFromMetadata($modelMetadata);
            if (empty($fieldsForAnalysis)) {
                $this->warn("No suitable fields found for analysis in model {$modelClass} based on metadata.");
                continue;
            }

            $modelKey = Str::kebab(class_basename($modelClass));

            // 1. Create a single export for all fields for this model
            $exportFilename = "{$this->exportDirectory}/{$modelKey}_all_fields.csv";

            if (Storage::disk('public')->exists($exportFilename)) {
                $this->line("    Using existing data export for all fields: <fg=gray>{$exportFilename}</fg=gray>");
            } else {
                $this->line('    No existing export found for all fields. Generating new CSV...');
                $this->exportDataForModel($tableName, $dateField, $latField, $lonField, $fieldsForAnalysis, $exportFilename);
                $this->info("    Successfully generated new data export for all fields.");
            }
            $publicUrl = Storage::url($exportFilename);
            $this->line("    Public URL for all jobs for this model: <fg=blue>" . url($publicUrl) . "</fg=blue>");


            foreach ($fieldsForAnalysis as $field) {
                $this->info("--> Preparing job for analysis field: <fg=yellow>{$field}</fg=yellow>");

                // 2. Prepare and Dispatch API Job for each field
                $this->line('    Dispatching job to analysis API...');
                $jobId = "laravel-{$modelKey}-{$field}-" . time();

                $payload = [
                    'job_id' => $jobId,
                    'data_sources' => [
                        [
                            'data_url' => url($publicUrl),
                            'timestamp_col' => $dateField,
                            'lat_col' => $latField,
                            'lon_col' => $lonField,
                            'secondary_group_col' => $field,
                        ],
                    ],
                    'config' => [
                        'analysis_stages' => ['stage4_h3_anomaly'],
                        'parameters' => [
                            'stage4_h3_anomaly' => [
                                'h3_resolution' => 8,
                                'p_value_anomaly' => 0.05,
                                'p_value_trend' => 0.05,
                                'analysis_weeks_trend' => 4,
                                'analysis_weeks_anomaly' => 4,
                                'generate_plots' => false,
                            ],
                        ],
                    ],
                ];

                $response = Http::timeout(30)->post("{$apiBaseUrl}/api/v1/jobs", $payload);

                if ($response->successful() && $response->status() === 202) {
                    $this->info("    Successfully dispatched job. Job ID: <fg=green>{$jobId}</fg=green>");
                    // 3. Update the database with the new job
                    $this->info("    Updating trends database for {$modelClass} -> {$field}...");
                    Trend::updateOrCreate(
                        ['model_class' => $modelClass, 'column_name' => $field],
                        ['job_id' => $jobId]
                    );
                    $this->info("    Trends database updated successfully.");
                } else {
                    $this->error("    Failed to dispatch job for {$modelClass} -> {$field}. Status: {$response->status()}");
                    $this->error("    Response: " . $response->body());
                }
            }
        }

        $this->info('Statistical analysis job dispatch process completed.');
        return 0;
    }

    private function exportDataForModel(string $tableName, string $dateField, string $latField, string $lonField, array $fieldsToAnalyze, string $filename)
    {
        $filePath = Storage::disk('public')->path($filename);

        $this->line("      Fields to be included in export: <fg=yellow>" . implode(', ', $fieldsToAnalyze) . "</fg=yellow>");

        $this->line("      Counting rows to export for {$tableName}...");
        $query = DB::table($tableName)->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField);
        $totalRows = $query->count();

        if ($totalRows === 0) {
            $this->warn("      No rows to export. Creating an empty file with a header.");
            $fileHandle = fopen($filePath, 'w');
            fputcsv($fileHandle, array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze));
            fclose($fileHandle);
            return;
        }

        $this->info("      Total rows to export: {$totalRows}");

        $fileHandle = fopen($filePath, 'w');
        $header = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze);
        fputcsv($fileHandle, $header);

        $primaryKey = DB::getSchemaBuilder()->getIndexes($tableName)[0]['columns'][0] ?? 'id';

        $progressBar = $this->output->createProgressBar($totalRows);
        $progressBar->start();

        $selectColumns = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze);
        $query = DB::table($tableName)->select($selectColumns)
            ->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField);

        $query->orderBy($primaryKey)->lazy()
            ->each(function ($row) use ($fileHandle, $header, $progressBar) {
                $rowData = [];
                foreach ($header as $col) {
                    $rowData[] = $row->$col;
                }
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
            if (class_exists($className) && method_exists($className, 'getMappableTraitUsageCheck')) {
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