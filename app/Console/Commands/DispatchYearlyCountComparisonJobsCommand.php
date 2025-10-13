<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\YearlyCountComparison;

class DispatchYearlyCountComparisonJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-yearly-count-comparison-jobs 
                            {model? : The model class to process} 
                            {--columns= : Comma-separated list of specific columns to analyze}
                            {--fresh : Force regeneration of all data exports} 
                            {--baseline-year=2019 : The baseline year for comparison}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches jobs for yearly count comparison analysis to the Python API.';

    private $exportDirectory = 'analysis_exports';

    public function handle()
    {
        $this->info('Starting yearly count comparison job dispatch process...');

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
            $resolvedModelClass = $this->resolveModelClass($specificModel);
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

        foreach ($modelClasses as $modelClass) {
            $this->line("Processing model: <fg=cyan>{$modelClass}</fg=cyan>");

            $modelInstance = new $modelClass();
            $tableName = $modelInstance->getTable();
            $dateField = $modelInstance::getDateField();
            $latField = $modelInstance::getLatitudeField();
            $lonField = $modelInstance::getLongitudeField();

            if (!$dateField || !$latField || !$lonField) {
                $this->warn("Model {$modelClass} is missing a required Mappable field (date, lat, or lon). Skipping.");
                continue;
            }

            $availableColumns = $modelClass::$statisticalAnalysisColumns ?? [];
            if (empty($availableColumns)) {
                $this->line("    No explicit 'statisticalAnalysisColumns' found for this model. Skipping.");
                continue;
            }

            $fieldsForAnalysis = [];
            if ($this->option('columns')) {
                $requestedColumns = explode(',', $this->option('columns'));
                $fieldsForAnalysis = array_intersect($requestedColumns, $availableColumns);
                $this->line("    Running analysis for specified columns: <fg=yellow>" . implode(', ', $fieldsForAnalysis) . "</fg=yellow>");
            } else {
                $fieldsForAnalysis = $availableColumns;
                $this->line("    Running analysis for all available columns: <fg=yellow>" . implode(', ', $fieldsForAnalysis) . "</fg=yellow>");
            }

            $modelKey = Str::kebab(class_basename($modelClass));
            $exportFilename = "{$this->exportDirectory}/{$modelKey}_all_fields.csv";

            if (!Storage::disk('public')->exists($exportFilename)) {
                $this->line('    No existing export found. Generating new CSV...');
                $this->exportDataForModel($tableName, $dateField, $latField, $lonField, $availableColumns, $exportFilename, $modelClass);
                $this->info("    Successfully generated new data export.");
            } else {
                $this->line("    Using existing data export: <fg=gray>{$exportFilename}</fg=gray>");
            }
            $publicUrl = Storage::url($exportFilename);
            $this->line("    Public URL for jobs: <fg=blue>" . url($publicUrl) . "</fg=blue>");

            foreach ($fieldsForAnalysis as $field) {
                $this->info("--> Preparing job for analysis field: <fg=yellow>{$field}</fg=yellow>");

                $baselineYear = (int) $this->option('baseline-year');
                $jobId = "laravel-{$modelKey}-{$field}-yearly-{$baselineYear}-" . time();

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
                        'analysis_stages' => ['stage2_yearly_count_comparison'],
                        'parameters' => [
                            'stage2_yearly_count_comparison' => [
                                'group_by_col' => $field,
                                'baseline_year' => $baselineYear,
                                'timestamp_col' => $dateField, // Add timestamp_col here
                            ],
                        ],
                    ],
                ];

                $response = Http::timeout(30)->post("{$apiBaseUrl}/api/v1/jobs", $payload);

                if ($response->successful() && $response->status() === 202) {
                    $this->info("    Successfully dispatched job. Job ID: <fg=green>{$jobId}</fg=green>");
                    YearlyCountComparison::updateOrCreate(
                        [
                            'model_class' => $modelClass,
                            'group_by_col' => $field,
                            'baseline_year' => $baselineYear,
                        ],
                        ['job_id' => $jobId]
                    );
                    $this->info("    Database updated successfully.");
                } else {
                    $this->error("    Failed to dispatch job for {$modelClass} -> {$field}. Status: {$response->status()}");
                    $this->error("    Response: " . $response->body());
                }
            }
        }

        $this->info('Yearly count comparison job dispatch process completed.');
        return 0;
    }

    private function exportDataForModel(string $tableName, string $dateField, string $latField, string $lonField, array $fieldsToAnalyze, string $filename, string $modelClass)
    {
        $filePath = Storage::disk('public')->path($filename);
        $connectionName = (new $modelClass())->getConnectionName() ?? config('database.default');

        $this->line("      Counting rows to export for {$tableName}...");
        $totalRows = DB::connection($connectionName)->table($tableName)->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField)->count();

        if ($totalRows === 0) {
            $this->warn("      No rows to export. Creating an empty file with a header.");
            File::put($filePath, implode(',', array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze)) . "\n");
            return;
        }

        $this->info("      Total rows to export: {$totalRows}");
        $fileHandle = fopen($filePath, 'w');
        $header = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze);
        fputcsv($fileHandle, $header);

        $primaryKey = DB::connection($connectionName)->getSchemaBuilder()->getIndexes($tableName)[0]['columns'][0] ?? 'id';
        $progressBar = $this->output->createProgressBar($totalRows);
        $progressBar->start();

        DB::connection($connectionName)->table($tableName)->select($header)
            ->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField)
            ->orderBy($primaryKey)->lazy(50000)
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

        foreach (File::files($path) as $file) {
            $className = 'App\\Models\\' . $file->getBasename('.php');
            if (
                class_exists($className) &&
                method_exists($className, 'getMappableTraitUsageCheck') &&
                property_exists($className, 'statisticalAnalysisColumns') &&
                !empty($className::$statisticalAnalysisColumns)
            ) {
                $modelClasses[] = $className;
            }
        }
        return $modelClasses;
    }

    private function resolveModelClass(string $modelName): ?string
    {
        if (class_exists($modelName)) return $modelName;
        $fqcn = 'App\\Models\\' . $modelName;
        if (class_exists($fqcn)) return $fqcn;
        return null;
    }
}
