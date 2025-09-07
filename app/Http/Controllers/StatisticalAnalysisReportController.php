<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Trend;

class StatisticalAnalysisReportController extends Controller
{
    public function show($trendId)
    {
        Log::info("Attempting to show statistical analysis report.", ['trendId' => $trendId]);

        $trend = Trend::find($trendId);

        if (!$trend) {
            Log::warning("Analysis report trend record not found in database.", ['trendId' => $trendId]);
            abort(404, "Analysis report not found.");
        }

        $modelClass = $trend->model_class;
        $columnName = $trend->column_name;

        if (!class_exists($modelClass)) {
            Log::error("Model class not found for trend.", ['trendId' => $trendId, 'modelClass' => $modelClass]);
            abort(404, "Model class not found.");
        }
        Log::info("Resolved model class from trend.", ['trendId' => $trendId, 'modelClass' => $modelClass, 'columnName' => $columnName]);

        $jobId = $trend->job_id;
        Log::info("Found trend record in database.", ['job_id' => $jobId]);

        $apiBaseUrl = config('services.analysis_api.url');
        $reportData = null;
        $cachePath = "analysis-reports/{$jobId}.json";

        if (Storage::exists($cachePath)) {
            Log::info("Found cached report data.", ['job_id' => $jobId, 'path' => $cachePath]);
            $reportData = json_decode(Storage::get($cachePath), true);
        } else {
            $apiUrl = "{$apiBaseUrl}/api/v1/jobs/{$jobId}/results/stage4_h3_anomaly/summary";

            Log::info("Fetching report summary data from analysis API.", ['job_id' => $jobId, 'url' => $apiUrl]);

            $response = Http::get($apiUrl);

            if ($response->successful()) {
                $reportData = $response->json();
                Log::info("Successfully fetched report summary data.", ['job_id' => $jobId]);
                Storage::put($cachePath, $response->body());
                Log::info("Cached report data.", ['job_id' => $jobId, 'path' => $cachePath]);
            } else {
                Log::error("Failed to fetch report summary data from analysis API.", [
                    'job_id' => $jobId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        }

        $reportTitle = $modelClass::getHumanName();
        if ($columnName !== 'unified') {
            $reportTitle .= ' by ' . Str::of($columnName)->replace('_', ' ')->title();
        } else {
            $reportTitle .= ' - Unified Analysis';
        }
        
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