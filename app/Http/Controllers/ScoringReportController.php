<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class ScoringReportController extends Controller
{
    /**
     * List available scoring reports.
     */
    public function index()
    {
        try {
            $reports = Cache::rememberForever('scoring_reports_listing', function () {
                Log::info('Rebuilding scoring reports cache from S3.');
                $s3 = Storage::disk('s3');
                $allJobDirs = $s3->directories(); // Assumes jobs are in top-level directories

                $reportList = [];
                foreach ($allJobDirs as $jobDir) {
                    $scoringFiles = $s3->files($jobDir);
                    foreach ($scoringFiles as $file) {
                        if (str_starts_with(basename($file), 'scoring_results')) {
                            $fileContent = json_decode($s3->get($file), true);
                            $reportList[] = [
                                'job_id' => $jobDir,
                                'artifact_name' => basename($file),
                                'title' => 'Neighborhood Scoring Report',
                                'generated_at' => $s3->lastModified($file),
                                'parameters' => $fileContent['parameters'] ?? [],
                            ];
                        } 
                        // also for stage6_historical_scores.json files
                        elseif (basename($file) === 'stage6_historical_score.json') {
                            $fileContent = json_decode($s3->get($file), true);
                            $reportList[] = [
                                'job_id' => $jobDir,
                                'artifact_name' => basename($file),
                                'title' => 'Historical Scoring Report',
                                'generated_at' => $s3->lastModified($file),
                                'parameters' => $fileContent['parameters'] ?? [],
                            ];
                        }    
                        
                    }
                }

                // Sort by date, newest first
                usort($reportList, fn($a, $b) => $b['generated_at'] <=> $a['generated_at']);
                return $reportList;
            });

            return Inertia::render('Reports/Scoring/Index', [
                'reports' => $reports,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list scoring reports from S3/Cache', ['error' => $e->getMessage()]);
            // Return view with an error message
            return Inertia::render('Reports/Scoring/Index', ['reports' => [], 'error' => 'Could not retrieve reports from storage.']);
        }
    }

    /**
     * Clear the scoring reports cache and redirect back to the index.
     */
    public function refreshIndex()
    {
        Cache::forget('scoring_reports_listing');
        Log::info('Scoring reports cache has been cleared by user.');
        return redirect()->route('scoring-reports.index')->with('status', 'Report listing has been updated.');
    }

    /**
     * Show the viewer for a specific scoring report.
     */
    public function show($jobId, $artifactName)
    {
        $s3 = Storage::disk('s3');
        $scoringPath = "{$jobId}/{$artifactName}";
        
        if (!$s3->exists($scoringPath)) {
            abort(404, 'Scoring report not found.');
        }

        $scoringData = json_decode($s3->get($scoringPath), true);
        // We will no longer load analysis data here. It will be fetched on demand.

        return Inertia::render('Reports/Scoring/Viewer', [
            'jobId' => $jobId,
            'artifactName' => $artifactName,
            'scoringData' => $scoringData,
            'analysisData' => null, // Pass null, as it's no longer pre-loaded
            'reportTitle' => 'Neighborhood Scoring Report Viewer',
        ]);
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
