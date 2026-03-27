<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\AnalysisReportSnapshot;
use App\Models\Trend;
use App\Services\StatisticalAnalysisViewService;
use Illuminate\Http\Request;

class StatisticalAnalysisReportController extends Controller
{
    public function show(string $jobId)
    {
        $reportData = null;

        $modelClass = null;
        $columnName = null;

        // Fast path: Trend DB record has model metadata without needing the file
        $trend = Trend::where('job_id', $jobId)->first();
        if ($trend && class_exists($trend->model_class)) {
            $modelClass = $trend->model_class;
            $columnName = $trend->column_name;
        } else {
            // Resolve artifact data only when the Trend row is unavailable.
            $reportData = AnalysisReportSnapshot::resolve($jobId, 'stage4_h3_anomaly.json');
        }

        if (!$trend && $reportData) {
            $params     = $reportData['parameters'] ?? [];
            $modelClass = $params['model_class'] ?? null;
            $columnName = $params['column_name'] ?? 'unified';
        }

        if (!$modelClass || !class_exists($modelClass)) {
            Log::warning("Could not resolve model class for job.", ['jobId' => $jobId]);
            abort(404, "Analysis report not found.");
        }

        $reportTitle = $modelClass::getHumanName();
        if ($columnName !== 'unified') {
            $reportTitle .= ' by ' . Str::of($columnName)->replace('_', ' ')->title();
        } else {
            $reportTitle .= ' - Unified Analysis';
        }

        // Find related scoring reports (Stage 5) derived from this Stage 4 job
        $scoringCache = Cache::get('scoring_reports_listing_v2', []);
        $relatedScoringReports = [];
        foreach ($scoringCache as $city => $dateGroups) {
            foreach ($dateGroups as $dateKey => $reportsInGroup) {
                foreach ((array) $reportsInGroup as $scoringReport) {
                    if (($scoringReport['source_job_id'] ?? null) === $jobId) {
                        $relatedScoringReports[] = [
                            'job_id' => $scoringReport['job_id'],
                            'artifact_name' => $scoringReport['artifact_name'],
                            'city' => $scoringReport['city'],
                            'resolution' => $scoringReport['resolution'],
                        ];
                    }
                }
            }
        }

        Log::info("Rendering report view.", ['job_id' => $jobId, 'reportTitle' => $reportTitle, 'data_found' => !is_null($reportData)]);

        return Inertia::render('Reports/StatisticalAnalysisViewer', [
            'jobId' => $jobId,
            'apiBaseUrl' => config('services.analysis_api.url'),
            'reportSummary' => StatisticalAnalysisViewService::summarize($jobId, $reportData),
            'reportTitle' => $reportTitle,
            'relatedScoringReports' => $relatedScoringReports,
        ]);
    }

    public function groupDetail(Request $request, string $jobId)
    {
        $secondaryGroup = trim((string) $request->query('secondary_group', ''));

        if ($secondaryGroup === '') {
            return response()->json(['error' => 'secondary_group is required.'], 422);
        }

        $detail = StatisticalAnalysisViewService::groupDetail($jobId, $secondaryGroup);

        if ($detail === null) {
            return response()->json(['error' => 'Analysis detail not found.'], 404);
        }

        return response()->json($detail);
    }
}
