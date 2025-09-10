<?php

namespace App\Http\Controllers;

use App\Models\YearlyCountComparison;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class YearlyCountComparisonController extends Controller
{
    public function index()
    {
        $reports = YearlyCountComparison::all()->groupBy('model_class');
        $reportsByModel = [];

        foreach ($reports as $modelClass => $analyses) {
            if (!class_exists($modelClass)) continue;

            $modelData = [
                'model_name' => $modelClass::getHumanName(),
                'model_key' => Str::kebab(class_basename($modelClass)),
                'analyses' => [],
            ];

            foreach ($analyses as $analysis) {
                $modelData['analyses'][] = [
                    'report_id' => $analysis->id,
                    'group_by_col' => $analysis->group_by_col,
                    'group_by_label' => Str::of($analysis->group_by_col)->replace('_', ' ')->title(),
                    'baseline_year' => $analysis->baseline_year,
                ];
            }
            $reportsByModel[] = $modelData;
        }

        return Inertia::render('YearlyCountComparison/Index', [
            'reportsByModel' => $reportsByModel,
        ]);
    }

    public function show($reportId)
    {
        $report = YearlyCountComparison::findOrFail($reportId);
        $modelClass = $report->model_class;
        if (!class_exists($modelClass)) abort(404, "Model class not found.");

        $jobId = $report->job_id;
        $apiBaseUrl = config('services.analysis_api.url');
        $apiUrl = "{$apiBaseUrl}/api/v1/jobs/{$jobId}/results/stage2_yearly_count_comparison.json";
        $reportData = null;

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $reportData = $response->json();
        } else {
            Log::error("Failed to fetch yearly count comparison report data.", [
                'job_id' => $jobId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        $reportTitle = sprintf(
            '%s Yearly Comparison by %s (Baseline %d)',
            $modelClass::getHumanName(),
            Str::of($report->group_by_col)->replace('_', ' ')->title(),
            $report->baseline_year
        );

        return Inertia::render('Reports/YearlyCountComparisonViewer', [
            'jobId' => $jobId,
            'apiBaseUrl' => $apiBaseUrl,
            'reportData' => $reportData,
            'reportTitle' => $reportTitle,
        ]);
    }
}
