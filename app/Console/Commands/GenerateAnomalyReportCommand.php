<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateAnomalyReportCommand extends Command
{
    protected $signature = 'app:generate-anomaly-report';
    protected $description = 'Generates a human-readable report for cases where the absolute value of the z-score is 2 or greater.';

    public function handle()
    {
        $jsonFilePath = storage_path('statistical_metrics.json');

        if (!File::exists($jsonFilePath)) {
            $this->error("File not found: {$jsonFilePath}");
            return 1;
        }

        $data = json_decode(File::get($jsonFilePath), true);

        if (!$data || !is_array($data)) {
            $this->error("Invalid JSON data in {$jsonFilePath}");
            return 1;
        }

        $this->info("Processing anomalies from {$jsonFilePath}...");

        $anomalies = [];

        foreach ($data as $model => $fields) {
            foreach ($fields as $field => $values) {
                foreach ($values as $value => $metrics) {
                    if (isset($metrics['z_scores_last_4_weeks']) && is_array($metrics['z_scores_last_4_weeks'])) {
                        foreach ($metrics['z_scores_last_4_weeks'] as $weekIndex => $zScore) {
                            if ($zScore !== null && abs($zScore) >= 2) {
                                $anomalies[] = [
                                    'model' => $model,
                                    'field' => $field,
                                    'value' => $value,
                                    'week_index' => $weekIndex,
                                    'z_score' => $zScore,
                                ];
                            }
                        }
                    }
                }
            }
        }

        if (empty($anomalies)) {
            $this->info("No anomalies found with z-scores >= 2.");
            return 0;
        }

        $reportFilePath = storage_path('anomaly_report.txt');
        $reportContent = "Anomaly Report\n\n";

        foreach ($anomalies as $anomaly) {
            $reportContent .= "Model: {$anomaly['model']}\n";
            $reportContent .= "Field: {$anomaly['field']}\n";
            $reportContent .= "Value: {$anomaly['value']}\n";
            $reportContent .= "Week Index: {$anomaly['week_index']}\n";
            $reportContent .= "Z-Score: {$anomaly['z_score']}\n";
            $reportContent .= "-------------------------\n";
        }

        File::put($reportFilePath, $reportContent);

        $this->info("Anomaly report generated successfully at: {$reportFilePath}");
        return 0;
    }
}
