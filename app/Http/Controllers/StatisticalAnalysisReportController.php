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
use App\Models\NewsArticle;
use App\Jobs\GenerateNewsArticleJob;

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

        $reportData = null;
        $s3Path = "{$jobId}/stage4_h3_anomaly.json";

        Log::info("Fetching report data from S3.", ['job_id' => $jobId, 'path' => $s3Path]);

        try {
            $s3 = Storage::disk('s3');
            if ($s3->exists($s3Path)) {
                $jsonContent = $s3->get($s3Path);
                $reportData = json_decode($jsonContent, true);
                $jsonError = json_last_error();

                if ($jsonError !== JSON_ERROR_NONE) {
                    Log::error("Failed to decode JSON from S3.", [
                        'job_id' => $jobId,
                        'path' => $s3Path,
                        'json_error_code' => $jsonError,
                        'json_error_message' => json_last_error_msg(),
                        'file_content_start' => Str::substr($jsonContent, 0, 500)
                    ]);
                    $reportData = null;
                } else {
                    Log::info("Successfully fetched and decoded report data from S3.", ['job_id' => $jobId]);
                    Log::info("Report data content.", ['job_id' => $jobId, 'reportData' => $reportData]);
                }
            } else {
                Log::warning("Report file not found in S3.", ['job_id' => $jobId, 'path' => $s3Path]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch report data from S3.", [
                'job_id' => $jobId,
                'path' => $s3Path,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
            'apiBaseUrl' => config('services.analysis_api.url'), // Kept for any other potential API interactions on the frontend
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