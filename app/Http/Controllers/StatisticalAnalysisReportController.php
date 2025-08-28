<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Trend;

class StatisticalAnalysisReportController extends Controller
{
    public function show($modelKey, $columnName)
    {
        Log::info("Attempting to show statistical analysis report.", ['modelKey' => $modelKey, 'columnName' => $columnName]);

        $modelClass = $this->getModelClassFromKey($modelKey);

        if (!$modelClass) {
            Log::error("Model not found for modelKey.", ['modelKey' => $modelKey]);
            abort(404, "Model not found.");
        }
        Log::info("Resolved model class.", ['modelKey' => $modelKey, 'modelClass' => $modelClass]);

        $trend = Trend::where('model_class', $modelClass)
            ->where('column_name', $columnName)
            ->first();

        if (!$trend) {
            Log::warning("Analysis report trend record not found in database.", ['modelClass' => $modelClass, 'columnName' => $columnName]);
            abort(404, "Analysis report not found for this model and column.");
        }

        $jobId = $trend->job_id;
        Log::info("Found trend record in database.", ['job_id' => $jobId]);

        $apiBaseUrl = config('services.analysis_api.url');
        $reportData = null;
        $apiUrl = "{$apiBaseUrl}/api/v1/jobs/{$jobId}/results/stage4_h3_anomaly.json";

        Log::info("Fetching report data from analysis API.", ['job_id' => $jobId, 'url' => $apiUrl]);

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $reportData = $response->json();
            Log::info("Successfully fetched report data.", ['job_id' => $jobId]);
        } else {
            Log::error("Failed to fetch report data from analysis API.", [
                'job_id' => $jobId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        $reportTitle = $modelClass::getHumanName() . ' by ' . Str::of($columnName)->replace('_', ' ')->title();
        Log::info("Rendering report view.", ['job_id' => $jobId, 'reportTitle' => $reportTitle, 'data_found' => !is_null($reportData)]);

        return Inertia::render('Reports/StatisticalAnalysisViewer', [
            'jobId' => $jobId,
            'apiBaseUrl' => $apiBaseUrl,
            'reportData' => $reportData,
            'reportTitle' => $reportTitle,
        ]);
    }

    private function getModelClassFromKey(string $modelKey): ?string
    {
        $modelsPath = app_path('Models');
        $files = File::files($modelsPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . $file->getBasename('.php');
            if (class_exists($className) && Str::kebab(class_basename($className)) === $modelKey) {
                return $className;
            }
        }

        return null;
    }
}