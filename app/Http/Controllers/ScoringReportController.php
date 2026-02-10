<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;

class ScoringReportController extends Controller
{
    /**
     * List available scoring reports.
     */
    public function index()
    {
        try {
            $reports = Cache::rememberForever('scoring_reports_listing_grouped', function () {
                Log::info('Rebuilding scoring reports cache from S3 (grouped).');
                $s3 = Storage::disk('s3');
                $allJobDirs = $s3->directories();

                $reportList = [];
                foreach ($allJobDirs as $jobDir) {
                    $scoringFiles = $s3->files($jobDir);
                    foreach ($scoringFiles as $file) {
                        if (str_starts_with(basename($file), 'scoring_results') || str_starts_with(basename($file), 'stage6')) {
                            $fileContent = json_decode($s3->get($file), true);
                            $parameters = $fileContent['parameters'] ?? ($fileContent['config'] ?? []);
                            $city = $parameters['city'] ?? 'Unknown';
                            $dateRange = 'N/A';
                            if (isset($parameters['date_range']['start_date']) && isset($parameters['date_range']['end_date'])) {
                                $dateRange = $parameters['date_range']['start_date'] . ' to ' . $parameters['date_range']['end_date'];
                            }

                            $reportList[] = [
                                'job_id' => $jobDir,
                                'artifact_name' => basename($file),
                                'title' => str_starts_with(basename($file), 'stage6') ? 'Historical Scoring Report' : 'Neighborhood Scoring Report',
                                'generated_at' => $s3->lastModified($file),
                                'parameters' => $parameters,
                                'city' => $city,
                                'date_range_key' => $dateRange,
                                'resolution' => $parameters['h3_resolution'] ?? 'N/A',
                            ];
                        }
                    }
                }

                // Group by city and then by date range
                $grouped = collect($reportList)->groupBy(['city', 'date_range_key']);

                // Sort groups by the latest report date within each group
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

    /**
     * Clear the scoring reports cache and redirect back to the index.
     */
    public function refreshIndex()
    {
        Cache::forget('scoring_reports_listing_grouped');
        Log::info('Scoring reports cache has been cleared by user.');
        return redirect()->route('scoring-reports.index')->with('status', 'Report listing has been updated.');
    }

    /**
     * Show the viewer for a specific scoring report.
     */
    public function show($jobId, $artifactName)
    {
        $s3 = Storage::disk('s3');
        $allReportsGrouped = Cache::get('scoring_reports_listing_grouped', []);
        
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

        $reportsWithData = [];
        foreach ($reportGroup as $reportItem) {
            $path = "{$reportItem['job_id']}/{$reportItem['artifact_name']}";
            if ($s3->exists($path)) {
                $reportItem['scoring_data'] = json_decode($s3->get($path), true);
                $reportsWithData[] = $reportItem;
            }
        }
        
        if (empty($reportsWithData)) {
            abort(404, 'Scoring report data not found.');
        }

        // Sort by resolution ascending
        usort($reportsWithData, fn($a, $b) => ($a['resolution'] ?? 99) <=> ($b['resolution'] ?? 99));

        return Inertia::render('Reports/Scoring/Viewer', [
            'reportGroup' => $reportsWithData,
            'initialReport' => collect($reportsWithData)->firstWhere('artifact_name', $artifactName),
            'reportTitle' => 'Neighborhood Scoring Report Viewer',
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
                Cache::forget('scoring_reports_listing_grouped');
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
        $s3 = Storage::disk('s3');
        $analysisPath = "{$jobId}/stage4_h3_anomaly.json";
        $cacheKey = "analysis_data_{$jobId}";

        // Use the same caching logic as getScoreForLocation to serve the file.
        $analysisData = Cache::rememberForever($cacheKey, function () use ($s3, $analysisPath, $jobId) {
            if ($s3->exists($analysisPath)) {
                Log::info("Caching analysis data for source job ID: {$jobId}");
                return json_decode($s3->get($analysisPath), true);
            }
            Log::warning("Analysis data file not found in S3 for caching.", ['path' => $analysisPath]);
            return null;
        });

        if ($analysisData) {
            return response()->json($analysisData);
        }

        return response()->json(['error' => 'Source analysis data not found.'], 404);
    }

    /**
     * API endpoint to get score and analysis for a given H3 index.
     * This now includes server-side caching for the analysis data.
     */
    public function getScoreForLocation(Request $request)
    {
        $request->validate([
            'h3_index' => 'required|string',
            'job_id' => 'required|string',
            'artifact_name' => 'required|string',
        ]);

        $s3 = Storage::disk('s3');
        $scoringPath = "{$request->job_id}/{$request->artifact_name}";
        $h3Index = $request->h3_index;

        if (!$s3->exists($scoringPath)) {
            return response()->json(['error' => 'Scoring report not found.'], 404);
        }

        $scoringData = json_decode($s3->get($scoringPath), true);

        $scoreResult = collect($scoringData['results'] ?? [])->firstWhere('h3_index', $h3Index);

        $analysisResult = null;
        $analysisParameters = null;
        // Check if this is an anomaly-based report by looking for source_job_id
        $sourceJobId = $scoringData['source_job_id'] ?? null;

        if ($sourceJobId) {
            $analysisPath = "{$sourceJobId}/stage4_h3_anomaly.json";
            $cacheKey = "analysis_data_{$sourceJobId}";

            // Cache the entire analysis file forever to avoid repeated S3 reads.
            $analysisData = Cache::rememberForever($cacheKey, function () use ($s3, $analysisPath, $sourceJobId) {
                if ($s3->exists($analysisPath)) {
                    Log::info("Caching analysis data for source job ID: {$sourceJobId}");
                    return json_decode($s3->get($analysisPath), true);
                }
                Log::warning("Analysis data file not found in S3 for caching.", ['path' => $analysisPath]);
                return null;
            });

            if ($analysisData) {

                $h3Resolution = $analysisData['parameters']['h3_resolution'] ?? 8;
                $analysisParameters = $analysisData['parameters'] ?? null;
                $analysisResult = collect($analysisData['results'] ?? [])->where("h3_index_{$h3Resolution}", $h3Index)->values();
            }
        }

        return response()->json([
            'h3_index' => $h3Index,
            'score_details' => $scoreResult,
            'analysis_details' => $analysisResult,
            'analysis_parameters' => $analysisParameters, // Pass parameters to the frontend
        ]);
    }
}
