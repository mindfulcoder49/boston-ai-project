<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AnalyzeStatisticalAnomaliesCommand extends Command
{
    protected $signature = 'app:analyze-statistical-anomalies';
    protected $description = 'Counts cases per week for discrete values, computes averages, standard deviations, and z-scores for the last four weeks.';

    private $uniqueValueThreshold = 100;

    public function handle()
    {
        $this->info('Starting statistical analysis for all models...');
        $modelClasses = $this->getModelClasses();
        $results = [];

        foreach ($modelClasses as $modelClass) {
            $this->line("Processing model: {$modelClass}");
            $modelInstance = new $modelClass();
            $dateField = $modelInstance::getDateField();

            if (!$dateField) {
                $this->warn("Model {$modelClass} does not have a date field. Skipping.");
                continue;
            }

            $tableName = $modelInstance->getTable();
            $fields = $this->discoverFieldsForAnalysis($tableName);

            if (empty($fields)) {
                $this->warn("No applicable fields found for model {$modelClass}. Skipping.");
                continue;
            }

            $results[$modelClass] = $this->calculateMetrics($tableName, $dateField, $fields);

            // Save results incrementally after processing each model
            $this->saveResults($results);
        }

        $this->info('Statistical analysis completed successfully.');
    }

    private function getModelClasses(): array
    {
        $path = app_path('Models');
        $modelClasses = [];

        if (!File::isDirectory($path)) {
            return [];
        }

        $files = File::files($path);
        foreach ($files as $file) {
            $className = 'App\\Models\\' . $file->getBasename('.php');
            if (class_exists($className) && method_exists($className, 'getDateField')) {
                $modelClasses[] = $className;
            }
        }

        return $modelClasses;
    }

    private function discoverFieldsForAnalysis(string $tableName): array
    {
        $columns = Schema::getColumnListing($tableName);
        $fields = [];

        foreach ($columns as $column) {
            $distinctCount = DB::table($tableName)->distinct()->count($column);

            if ($distinctCount > 0 && $distinctCount <= $this->uniqueValueThreshold) {
                $fields[] = $column;
            }
        }

        return $fields;
    }

    private function calculateMetrics(string $tableName, string $dateField, array $fields): array
    {
        $metrics = [];
        foreach ($fields as $field) {
            $this->line("Calculating metrics for field: {$field}");
            $metrics[$field] = $this->calculateWeeklyMetrics($tableName, $dateField, $field);
        }
        return $metrics;
    }

    private function calculateWeeklyMetrics(string $tableName, string $dateField, string $field): array
    {
        $weeklyCounts = DB::table($tableName)
            ->selectRaw("YEARWEEK({$dateField}) as week, {$field} as value, COUNT(*) as count")
            ->groupBy('week', 'value')
            ->orderBy('week', 'desc')
            ->get();

        $groupedData = $weeklyCounts->groupBy('value')->map(function ($rows) {
            return $rows->pluck('count', 'week')->toArray();
        });

        $metrics = [];
        foreach ($groupedData as $value => $weeklyData) {
            $weeks = array_keys($weeklyData);
            $counts = array_values($weeklyData);

            $average = count($counts) > 0 ? array_sum($counts) / count($counts) : 0;
            $stddev = count($counts) > 1 ? sqrt(array_sum(array_map(fn($count) => pow($count - $average, 2), $counts)) / (count($counts) - 1)) : 0;

            $zScores = [];
            foreach (array_slice($counts, -4) as $count) {
                $zScores[] = $stddev > 0 ? ($count - $average) / $stddev : null;
            }

            $metrics[$value] = [
                'average' => $average,
                'stddev' => $stddev,
                'z_scores_last_4_weeks' => $zScores,
            ];
        }

        return $metrics;
    }

    private function saveResults(array $results)
    {
        $phpFilePath = config_path('statistical_metrics.php');
        $jsonFilePath = storage_path('statistical_metrics.json');

        $phpContent = "<?php\n\nreturn " . var_export($results, true) . ";\n";
        File::put($phpFilePath, $phpContent);

        $jsonContent = json_encode($results, JSON_PRETTY_PRINT);
        File::put($jsonFilePath, $jsonContent);

        $this->info("Results saved to {$phpFilePath} and {$jsonFilePath}");
    }
}
