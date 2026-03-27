<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\AnalysisReportSnapshot;
use App\Models\Trend;

class ScoringReportController extends Controller
{
    /**
     * List available scoring reports.
     */
    public function index()
    {
        try {
            $reports = Cache::rememberForever('scoring_reports_listing_v2', function () {
                Log::info('Rebuilding scoring reports listing cache.');

                // Fast path: snapshot table (populated by app:pull-analysis-reports)
                $snapshots = AnalysisReportSnapshot::allForArtifactPrefixes(['scoring_results', 'stage6']);
                if ($snapshots->isNotEmpty()) {
                    $reportList = $this->buildReportListFromSnapshots($snapshots);
                } else {
                    // Slow path: S3 scan
                    Log::info('No scoring snapshots found, scanning S3.');
                    $reportList = $this->buildReportListFromS3();
                }

                $grouped = collect($reportList)->groupBy(['city', 'date_range_key']);

                $sortedGroups = $grouped->map(function ($cityGroup) {
                    return $cityGroup->map(function ($dateGroup) {
                        return $dateGroup->sortByDesc('generated_at');
                    });
                })->sortByDesc(function ($cityGroup) {
                    return $cityGroup->flatten(1)->max('generated_at');
                });

                return $sortedGroups->all();
            });

            return Inertia::render('Reports/Scoring/Index', [
                'reportGroups' => $reports,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list scoring reports from S3/Cache', ['error' => $e->getMessage()]);
            return Inertia::render('Reports/Scoring/Index', ['reportGroups' => [], 'error' => 'Could not retrieve reports from storage.']);
        }
    }

    // ── Listing helpers ───────────────────────────────────────────────────────

    private function buildReportListFromSnapshots($snapshots): array
    {
        $reportList = [];
        foreach ($snapshots as $snapshot) {
            $fileContent = $snapshot->payload;
            $parameters  = $fileContent['parameters'] ?? ($fileContent['config'] ?? []);
            $city        = $parameters['city'] ?? 'Unknown';
            $dateRange   = 'N/A';
            if (isset($parameters['date_range']['start_date'], $parameters['date_range']['end_date'])) {
                $dateRange = $parameters['date_range']['start_date'] . ' to ' . $parameters['date_range']['end_date'];
            }

            $reportList[] = [
                'job_id'         => $snapshot->job_id,
                'artifact_name'  => $snapshot->artifact_name,
                'title'          => str_starts_with($snapshot->artifact_name, 'stage6') ? 'Historical Scoring Report' : 'Neighborhood Scoring Report',
                'generated_at'   => $snapshot->s3_last_modified,
                'parameters'     => $parameters,
                'city'           => $city,
                'date_range_key' => $dateRange,
                'resolution'     => $parameters['h3_resolution'] ?? 'N/A',
                'source_job_id'  => $fileContent['source_job_id'] ?? null,
            ];
        }
        return $reportList;
    }

    private function buildReportListFromS3(): array
    {
        $reportList = [];
        $s3         = Storage::disk('s3');

        foreach ($s3->directories() as $jobDir) {
            foreach ($s3->files($jobDir) as $file) {
                $base = basename($file);
                if (!str_starts_with($base, 'scoring_results') && !str_starts_with($base, 'stage6')) {
                    continue;
                }

                $fileContent = json_decode($s3->get($file), true);
                $parameters  = $fileContent['parameters'] ?? ($fileContent['config'] ?? []);
                $city        = $parameters['city'] ?? 'Unknown';
                $dateRange   = 'N/A';
                if (isset($parameters['date_range']['start_date'], $parameters['date_range']['end_date'])) {
                    $dateRange = $parameters['date_range']['start_date'] . ' to ' . $parameters['date_range']['end_date'];
                }

                $reportList[] = [
                    'job_id'         => $jobDir,
                    'artifact_name'  => $base,
                    'title'          => str_starts_with($base, 'stage6') ? 'Historical Scoring Report' : 'Neighborhood Scoring Report',
                    'generated_at'   => $s3->lastModified($file),
                    'parameters'     => $parameters,
                    'city'           => $city,
                    'date_range_key' => $dateRange,
                    'resolution'     => $parameters['h3_resolution'] ?? 'N/A',
                    'source_job_id'  => $fileContent['source_job_id'] ?? null,
                ];
            }
        }
        return $reportList;
    }

    /**
     * Clear the scoring reports cache and redirect back to the index.
     */
    public function refreshIndex()
    {
        Cache::forget('scoring_reports_listing_v2');
        Log::info('Scoring reports cache has been cleared by user.');
        return redirect()->route('scoring-reports.index')->with('status', 'Report listing has been updated.');
    }

    /**
     * Show the viewer for a specific scoring report.
     */
    public function show($jobId, $artifactName)
    {
        $allReportsGrouped = Cache::get('scoring_reports_listing_v2', []);
        
        $targetReport = null;
        $reportGroup = null;

        foreach ($allReportsGrouped as $city => $dateGroups) {
            foreach ($dateGroups as $dateKey => $reports) {
                foreach ($reports as $report) {
                    if ($report['job_id'] === $jobId && $report['artifact_name'] === $artifactName) {
                        $targetReport = $report;
                        $reportGroup = $reports;
                        break 3;
                    }
                }
            }
        }

        if (!$targetReport) {
            // Fallback if cache is stale or report not found
            Log::warning("Report {$jobId}/{$artifactName} not found in cache, rebuilding.");
            $this->refreshIndex(); // This will redirect, so the next request will be correct
            return redirect()->route('scoring-reports.index')->with('status', 'Report index was refreshed. Please try again.');
        }

        $reportGroup = $this->normalizeReportGroup($reportGroup);
        usort($reportGroup, fn($a, $b) => ($a['resolution'] ?? 99) <=> ($b['resolution'] ?? 99));

        $initialData = AnalysisReportSnapshot::resolve($targetReport['job_id'], $targetReport['artifact_name']);
        if ($initialData === null) {
            abort(404, 'Scoring report data not found.');
        }
        $initial = $targetReport;
        $initial['scoring_data'] = $initialData;

        // Look up the source Stage 4 analysis for cross-linking
        $sourceJobId = $initial['scoring_data']['source_job_id'] ?? null;
        $sourceTrend = null;
        if ($sourceJobId) {
            // Try DB record first (fast path)
            $trend = Trend::where('job_id', $sourceJobId)->first();
            if ($trend && class_exists($trend->model_class)) {
                $mc    = $trend->model_class;
                $label = $trend->column_name !== 'unified'
                    ? $mc::getHumanName() . ' by ' . Str::of($trend->column_name)->replace('_', ' ')->title()
                    : $mc::getHumanName() . ' — Unified Analysis';
                $sourceTrend = ['job_id' => $sourceJobId, 'title' => $label];
            } else {
                // Fall back to the S3-discovered trend listing cache
                $listing = Cache::get('trend_listing_v1', []);
                $item    = collect($listing)->firstWhere('job_id', $sourceJobId);
                if ($item) {
                    $label = ($item['column_name'] !== 'unified')
                        ? $item['model_name'] . ' by ' . $item['column_label']
                        : $item['model_name'] . ' — Unified Analysis';
                    $sourceTrend = ['job_id' => $sourceJobId, 'title' => $label];
                }
            }
        }

        return Inertia::render('Reports/Scoring/Viewer', [
            'reportGroup' => $reportGroup,
            'initialReport' => $initial,
            'reportTitle' => 'Neighborhood Scoring Report Viewer',
            'sourceTrend' => $sourceTrend,
        ]);
    }

    /**
     * Delete a specific scoring report from storage.
     */
    public function destroy($jobId, $artifactName)
    {
        try {
            $s3 = Storage::disk('s3');
            $scoringPath = "{$jobId}/{$artifactName}";

            if ($s3->exists($scoringPath)) {
                $s3->delete($scoringPath);
                Log::info("Deleted scoring report from S3: {$scoringPath}");

                // Check if the directory is now empty and delete it if so.
                $filesInDir = $s3->files($jobId);
                if (empty($filesInDir)) {
                    $s3->deleteDirectory($jobId);
                    Log::info("Deleted empty job directory from S3: {$jobId}");
                }

                // Clear the cache to force a refresh on the index page
                Cache::forget('scoring_reports_listing_v2');
                Log::info('Scoring reports cache cleared after deletion.');

                return redirect()->route('scoring-reports.index')->with('status', 'Report deleted successfully.');
            } else {
                return redirect()->route('scoring-reports.index')->with('error', 'Report not found for deletion.');
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete scoring report {$jobId}/{$artifactName}", ['error' => $e->getMessage()]);
            return redirect()->route('scoring-reports.index')->with('error', 'An error occurred while trying to delete the report.');
        }
    }

    /**
     * API endpoint to get the full source analysis data file for a job.
     */
    public function getSourceAnalysisData($jobId)
    {
        $cacheKey = "analysis_data_{$jobId}";

        $analysisData = Cache::rememberForever($cacheKey, function () use ($jobId) {
            return AnalysisReportSnapshot::resolve($jobId, 'stage4_h3_anomaly.json');
        });

        if ($analysisData) {
            return response()->json($analysisData);
        }

        return response()->json(['error' => 'Source analysis data not found.'], 404);
    }

    /**
     * API endpoint to get a full scoring artifact for one resolution on demand.
     */
    public function getReportData(string $jobId, string $artifactName)
    {
        $scoringData = AnalysisReportSnapshot::resolve($jobId, $artifactName);

        if ($scoringData === null) {
            return response()->json(['error' => 'Scoring report not found.'], 404);
        }

        $parameters = $scoringData['parameters'] ?? ($scoringData['config'] ?? []);

        return response()->json([
            'job_id' => $jobId,
            'artifact_name' => $artifactName,
            'parameters' => $parameters,
            'resolution' => $parameters['h3_resolution'] ?? null,
            'source_job_id' => $scoringData['source_job_id'] ?? null,
            'scoring_data' => $scoringData,
        ]);
    }

    /**
     * API endpoint to get score and analysis for a given H3 index.
     */
    public function getScoreForLocation(Request $request)
    {
        $request->validate([
            'h3_index'      => 'required|string',
            'job_id'        => 'required|string',
            'artifact_name' => 'required|string',
        ]);

        $h3Index     = $request->h3_index;
        $scoringData = AnalysisReportSnapshot::resolve($request->job_id, $request->artifact_name);

        if (!$scoringData) {
            return response()->json(['error' => 'Scoring report not found.'], 404);
        }

        $scoreResult = collect($scoringData['results'] ?? [])->firstWhere('h3_index', $h3Index);

        $analysisResult     = null;
        $analysisParameters = null;
        $sourceJobId        = $scoringData['source_job_id'] ?? null;

        if ($sourceJobId) {
            $cacheKey = "analysis_data_{$sourceJobId}";

            // Cache forever — this file can be large and is hit on every hexagon click.
            $analysisData = Cache::rememberForever($cacheKey, function () use ($sourceJobId) {
                return AnalysisReportSnapshot::resolve($sourceJobId, 'stage4_h3_anomaly.json');
            });

            if ($analysisData) {
                $h3Resolution       = $analysisData['parameters']['h3_resolution'] ?? 8;
                $analysisParameters = $analysisData['parameters'] ?? null;
                $analysisResult     = collect($analysisData['results'] ?? [])->where("h3_index_{$h3Resolution}", $h3Index)->values();
            }
        }

        return response()->json([
            'h3_index' => $h3Index,
            'score_details' => $scoreResult,
            'analysis_details' => $analysisResult,
            'analysis_parameters' => $analysisParameters, // Pass parameters to the frontend
        ]);
    }

    private function normalizeReportGroup($reportGroup): array
    {
        return collect($reportGroup ?? [])->values()->all();
    }
}
