<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DispatchHistoricalScoringJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-historical-scoring-jobs
                            {model : The model class to process (e.g., CrimeData)}
                            {--column= : The column to use for grouping}
                            {--export-columns= : Comma-separated list of columns to include in the export, besides the required ones. Defaults to all statistical columns.}
                            {--resolution=8 : Comma-separated list of H3 resolutions for aggregation}
                            {--analysis-weeks=52 : The number of weeks to include in the historical average}
                            {--group-weights= : JSON string of weights for each group}
                            {--default-weight=0.0 : Default weight for groups not in the weights JSON}
                            {--fresh : Force regeneration of the data export}
                            {--export-timespan=0 : The total number of weeks of data to export. 0 means all data.}
                            {--city= : The city name for the analysis. Defaults to the model\'s human name.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports data for a model and dispatches a historical scoring job to the Python analysis API.';

    private $exportDirectory = 'analysis_exports';

    public function handle()
    {
        Log::info('Starting historical scoring job dispatch process...');

        $apiBaseUrl = config('services.analysis_api.url');
        if (!$apiBaseUrl) {
            Log::error('Analysis API URL is not configured. Please set ANALYSIS_API_URL in your .env file.');
            return 1;
        }

        $modelName = $this->argument('model');
        $column = $this->option('column');
        if (!$column) {
            Log::error('The --column option is required.');
            return 1;
        }

        $modelClass = 'App\\Models\\' . $modelName;
        if (!class_exists($modelClass) || !method_exists($modelClass, 'getMappableTraitUsageCheck')) {
            Log::error("The specified model '{$modelName}' is not a valid or analyzable model.");
            return 1;
        }

        $modelInstance = new $modelClass();
        $tableName = $modelInstance->getTable();
        $connectionName = $modelInstance->getConnectionName() ?? config('database.default');
        $dateField = $modelInstance::getDateField();
        $latField = $modelInstance::getLatitudeField();
        $lonField = $modelInstance::getLongitudeField();

        if (!$dateField || !$latField || !$lonField) {
            Log::warning("Model {$modelClass} is missing a required Mappable field (date, lat, or lon). Skipping.");
            return 1;
        }

        if (!in_array($column, $modelClass::$statisticalAnalysisColumns ?? [])) {
            Log::error("The column '{$column}' is not listed in the statisticalAnalysisColumns for model '{$modelName}'.");
            return 1;
        }

        $exportColumnsOption = $this->option('export-columns');
        $exportColumns = $exportColumnsOption ? explode(',', $exportColumnsOption) : ($modelClass::$statisticalAnalysisColumns ?? []);
        // Ensure the grouping column is always included in the export
        if (!in_array($column, $exportColumns)) {
            $exportColumns[] = $column;
        }
        $exportColumns = array_unique($exportColumns);

        Storage::disk('public')->makeDirectory($this->exportDirectory);
        $modelKey = Str::kebab(class_basename($modelClass));
        $exportWeeks = (int) $this->option('export-timespan');
        $exportFilename = "{$this->exportDirectory}/{$modelKey}_stage6_{$exportWeeks}w.csv";

        if ($this->option('fresh') && Storage::disk('public')->exists($exportFilename)) {
            Log::warning("The --fresh option was used. Deleting cached data export for {$modelName}.");
            Storage::disk('public')->delete($exportFilename);
            Log::info('Cached export cleared.');
        }

        if (!Storage::disk('public')->exists($exportFilename)) {
            Log::info('No existing export found. Generating new CSV...');
            $this->exportDataForModel($modelInstance, $exportFilename, $exportColumns, $exportWeeks);
            Log::info("Successfully generated new data export.");
        } else {
            Log::info("Using existing data export: {$exportFilename}");
        }

        $publicUrl = Storage::url($exportFilename);
        Log::info("Public URL for job: " . url($publicUrl));

        $groupWeights = json_decode($this->option('group-weights') ?: '{}', true);
        $cityName = $this->option('city') ?: $modelInstance::getHumanName();
        $resolutions = array_filter(explode(',', $this->option('resolution')), 'strlen');

        if (empty($resolutions)) {
            Log::error('No valid resolutions provided. Please provide at least one H3 resolution.');
            return 1;
        }

        foreach ($resolutions as $resolution) {
            $resolution = (int) trim($resolution);
            Log::info("--- Preparing job for resolution: {$resolution} ---");

            $jobId = "laravel-hist-score-{$modelKey}-{$column}-res{$resolution}-" . time();

            $payload = [
                'job_id' => $jobId,
                'city' => $cityName,
                // Metadata for S3-based discovery (Phase 1)
                'model_class' => $modelClass,
                'column_name' => $column,
                'data_sources' => [
                    [
                        'data_url' => url($publicUrl),
                        'timestamp_col' => $dateField,
                        'lat_col' => $latField,
                        'lon_col' => $lonField,
                        'secondary_group_col' => $column,
                    ],
                ],
                'output_filename' => "stage6_historical_score_{$jobId}.json",
                'group_weights' => $groupWeights,
                'default_group_weight' => (float) $this->option('default-weight'),
                'h3_resolution' => $resolution,
                'analysis_period_weeks' => (int) $this->option('analysis-weeks'),
            ];

            Log::info("Dispatching historical scoring job for resolution {$resolution} with payload.", ['payload' => $payload]);

            $response = Http::timeout(30)->post("{$apiBaseUrl}/api/v1/jobs/stage6", $payload);

            if ($response->successful() && $response->status() === 202) {
                Log::info("Successfully dispatched job for resolution {$resolution}. Job ID: {$jobId}");
                // Here you would typically save the job_id to a model, similar to the Trend model in the other command.
                // For now, we just log it.
                Log::info("Dispatched historical scoring job.", ['job_id' => $jobId, 'model' => $modelClass, 'column' => $column, 'resolution' => $resolution]);
            } else {
                Log::error("Failed to dispatch job for resolution {$resolution}. Status: {$response->status()}", ['response' => $response->body()]);
            }
        }

        Log::info('Historical scoring job dispatch process completed.');
        return 0;
    }

    private function exportDataForModel($modelInstance, string $filename, array $fieldsToAnalyze, int $exportWeeks = 0)
    {
        $modelClass = get_class($modelInstance);
        $tableName = $modelInstance->getTable();
        $connectionName = $modelInstance->getConnectionName() ?? config('database.default');
        $dateField = $modelInstance::getDateField();
        $latField = $modelInstance::getLatitudeField();
        $lonField = $modelInstance::getLongitudeField();
        
        $filePath = Storage::disk('public')->path($filename);

        Log::info("      DB columns to export: " . (empty($fieldsToAnalyze) ? 'None' : implode(', ', $fieldsToAnalyze)));

        $latestRecordDateStr = DB::connection($connectionName)->table($tableName)->max($dateField);
        if (!$latestRecordDateStr) {
            Log::warning("      No records found in {$tableName}. Creating an empty file with a header.");
            $fileHandle = fopen($filePath, 'w');
            $header = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze, ['source_dataset']);
            fputcsv($fileHandle, $header);
            fclose($fileHandle);
            return;
        }

        $query = DB::connection($connectionName)->table($tableName)
            ->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField);

        if ($exportWeeks > 0) {
            $latestRecordDate = Carbon::parse($latestRecordDateStr);
            $startDate = $latestRecordDate->copy()->subWeeks($exportWeeks)->startOfDay();
            Log::info("      Exporting data from {$startDate->toDateString()} based on a {$exportWeeks}-week export window.");
            $query->where($dateField, '>=', $startDate);
        } else {
            Log::info("      Exporting all data (export-timespan is 0 or not set).");
        }

        $totalRows = $query->count();

        if ($totalRows === 0) {
            Log::warning("      No valid rows to export. Creating an empty file with a header.");
            $fileHandle = fopen($filePath, 'w');
            $header = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze, ['source_dataset']);
            fputcsv($fileHandle, $header);
            fclose($fileHandle);
            return;
        }

        Log::info("      Total rows to export: {$totalRows}");

        $fileHandle = fopen($filePath, 'w');
        $header = array_merge([$dateField, $latField, $lonField], $fieldsToAnalyze, ['source_dataset']);
        fputcsv($fileHandle, $header);

        $primaryKey = DB::connection($connectionName)->getSchemaBuilder()->getIndexes($tableName)[0]['columns'][0] ?? 'id';

        $processedRows = 0;
        $logInterval = 100000; // Log every 100,000 rows
        $nextLogThreshold = $logInterval;

        $selectColumns = array_unique(array_merge([$dateField, $latField, $lonField, $primaryKey], $fieldsToAnalyze));
        $query = DB::connection($connectionName)->table($tableName)->select($selectColumns)
            ->whereNotNull($dateField)->whereNotNull($latField)->whereNotNull($lonField);

        if ($exportWeeks > 0) {
            $latestRecordDate = Carbon::parse($latestRecordDateStr);
            $startDate = $latestRecordDate->copy()->subWeeks($exportWeeks)->startOfDay();
            $query->where($dateField, '>=', $startDate);
        }

        $unifiedValue = $modelClass::getHumanName();

        $query->orderBy($primaryKey)->chunkById(10000, function ($rows) use ($fileHandle, $selectColumns, &$processedRows, $logInterval, &$nextLogThreshold, $totalRows, $unifiedValue, $primaryKey) {
            foreach ($rows as $row) {
                $rowData = [];
                foreach ($selectColumns as $col) {
                    if ($col === $primaryKey) continue; // Don't write the primary key to the CSV
                    $rowData[] = $row->$col;
                }
                $rowData[] = $unifiedValue;
                fputcsv($fileHandle, $rowData);
                
                $processedRows++;
                if ($processedRows >= $nextLogThreshold) {
                    Log::info("      Export progress: {$processedRows} / {$totalRows} rows written.");
                    $nextLogThreshold += $logInterval;
                }
            }
        }, $primaryKey);

        Log::info("      Finished export: {$processedRows} / {$totalRows} rows written.");

        fclose($fileHandle);
    }
}
