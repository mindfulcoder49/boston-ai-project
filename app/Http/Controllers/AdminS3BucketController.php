<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use League\Flysystem\FileAttributes;
use App\Models\AnalysisReportSnapshot;
use App\Models\Trend;

class AdminS3BucketController extends Controller
{
    private const CACHE_KEY = 's3_bucket_listing_v1';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $adminEmail = config('admin.email');
            if (!Auth::check() || Auth::user()->email !== $adminEmail) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        Log::info('[S3Browser] index() called');
        try {
            $cached = Cache::has(self::CACHE_KEY);
            Log::info('[S3Browser] Cache ' . ($cached ? 'HIT — serving from cache' : 'MISS — will build listing'));
            $jobs = Cache::rememberForever(self::CACHE_KEY, fn() => $this->buildListing());
            Log::info('[S3Browser] Rendering page with ' . count($jobs) . ' jobs');
            return Inertia::render('Admin/S3BucketBrowser', ['jobs' => $jobs]);
        } catch (\Exception $e) {
            Log::error('[S3Browser] index() failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Inertia::render('Admin/S3BucketBrowser', [
                'jobs' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function refresh()
    {
        Cache::forget(self::CACHE_KEY);
        return redirect()->route('admin.s3-bucket.index')->with('success', 'S3 listing refreshed.');
    }

    public function destroyDirectory(string $jobId)
    {
        if (!preg_match('/^[\w-]+$/', $jobId)) {
            return back()->with('error', 'Invalid job ID.');
        }

        try {
            $s3 = Storage::disk('s3');
            if ($s3->directoryExists($jobId)) {
                $s3->deleteDirectory($jobId);
            }

            // Clean up DB record and related caches
            $trend = Trend::where('job_id', $jobId)->first();
            if ($trend) {
                Cache::forget("trend_summary_v5_{$jobId}");
                $trend->delete();
            }

            Cache::forget(self::CACHE_KEY);
            Cache::forget('scoring_reports_listing_v2');
            Cache::forget("analysis_data_{$jobId}");

            Log::info("Admin deleted S3 job directory: {$jobId}");
            return back()->with('success', "Deleted job directory: {$jobId}");
        } catch (\Exception $e) {
            Log::error("Failed to delete S3 job directory {$jobId}: " . $e->getMessage());
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function destroyFile(string $jobId, string $filename)
    {
        if (!preg_match('/^[\w-]+$/', $jobId)) {
            return back()->with('error', 'Invalid job ID.');
        }
        // Allow word chars, dots, hyphens in filenames
        if (!preg_match('/^[\w.\-]+$/', $filename)) {
            return back()->with('error', 'Invalid filename.');
        }

        try {
            $s3 = Storage::disk('s3');
            $path = "{$jobId}/{$filename}";
            if ($s3->exists($path)) {
                $s3->delete($path);
            }

            // If the directory is now empty, remove it and clean up DB record too
            $remaining = $s3->files($jobId);
            if (empty($remaining)) {
                $s3->deleteDirectory($jobId);
                $trend = Trend::where('job_id', $jobId)->first();
                if ($trend) {
                    Cache::forget("trend_summary_v5_{$jobId}");
                    $trend->delete();
                }
                Cache::forget("analysis_data_{$jobId}");
            }

            Cache::forget(self::CACHE_KEY);
            Cache::forget('scoring_reports_listing_v2');

            Log::info("Admin deleted S3 file: {$path}");
            return back()->with('success', "Deleted: {$filename}");
        } catch (\Exception $e) {
            Log::error("Failed to delete S3 file {$jobId}/{$filename}: " . $e->getMessage());
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['job_ids' => 'required|array|min:1', 'job_ids.*' => 'string']);

        $errors = [];

        // Validate all job IDs upfront
        $validJobIds = [];
        foreach ($request->job_ids as $jobId) {
            if (!preg_match('/^[\w-]+$/', $jobId)) {
                $errors[] = "Skipped invalid job ID: {$jobId}";
            } else {
                $validJobIds[] = $jobId;
            }
        }

        if (empty($validJobIds)) {
            return back()->with('error', 'No valid job IDs. ' . implode('; ', $errors));
        }

        // ── S3: collect all object keys per job, then batch-delete in one call ─
        $s3Cfg  = config('filesystems.disks.s3');
        $bucket = $s3Cfg['bucket'];
        $client = new \Aws\S3\S3Client([
            'version'                 => 'latest',
            'region'                  => $s3Cfg['region'],
            'credentials'             => ['key' => $s3Cfg['key'], 'secret' => $s3Cfg['secret']],
            'endpoint'                => $s3Cfg['endpoint'] ?? null,
            'use_path_style_endpoint' => $s3Cfg['use_path_style_endpoint'] ?? false,
        ]);

        $objectsToDelete = [];
        foreach ($validJobIds as $jobId) {
            try {
                $paginator = $client->getPaginator('ListObjectsV2', [
                    'Bucket' => $bucket,
                    'Prefix' => "{$jobId}/",
                ]);
                foreach ($paginator as $page) {
                    foreach ($page['Contents'] ?? [] as $obj) {
                        $objectsToDelete[] = ['Key' => $obj['Key']];
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to list {$jobId}: " . $e->getMessage();
                Log::error("Bulk delete list failed for {$jobId}: " . $e->getMessage());
            }
        }

        $deletedObjects = 0;
        if (!empty($objectsToDelete)) {
            foreach (array_chunk($objectsToDelete, 1000) as $chunk) {
                try {
                    $result = $client->deleteObjects([
                        'Bucket' => $bucket,
                        'Delete' => ['Objects' => $chunk],
                    ]);
                    $deletedObjects += count($result['Deleted'] ?? []);
                    foreach ($result['Errors'] ?? [] as $err) {
                        $errors[] = "S3 error on {$err['Key']}: {$err['Message']}";
                    }
                } catch (\Exception $e) {
                    $errors[] = 'S3 batch delete error: ' . $e->getMessage();
                    Log::error('Bulk S3 deleteObjects error: ' . $e->getMessage());
                }
            }
        }

        // ── DB: batch delete in two queries ───────────────────────────────────
        Trend::whereIn('job_id', $validJobIds)->delete();
        AnalysisReportSnapshot::whereIn('job_id', $validJobIds)->delete();

        // ── Cache: clear per-job and listing caches ───────────────────────────
        foreach ($validJobIds as $jobId) {
            Cache::forget("trend_summary_v5_{$jobId}");
            Cache::forget("analysis_data_{$jobId}");
        }
        Cache::forget(self::CACHE_KEY);
        Cache::forget('trend_listing_v1');
        Cache::forget('yearly_count_comparison_listing_v1');
        Cache::forget('scoring_reports_listing_v2');

        $jobCount = count($validJobIds);
        $message  = "Deleted {$deletedObjects} S3 object" . ($deletedObjects === 1 ? '' : 's')
                  . " across {$jobCount} job" . ($jobCount === 1 ? '' : 's') . '.';
        if ($errors) {
            $message .= ' Errors: ' . implode('; ', $errors);
        }

        return back()->with($errors ? 'error' : 'success', $message);
    }

    // -------------------------------------------------------------------------

    private function buildListing(): array
    {
        $t0 = microtime(true);
        Log::info('[S3Browser] buildListing() start');

        set_time_limit(120);

        Log::info('[S3Browser] Getting Flysystem driver');
        $flysystem = Storage::disk('s3')->getDriver();
        Log::info('[S3Browser] Got driver (' . get_class($flysystem) . '), starting listContents');

        $jobMap    = [];
        $itemCount = 0;

        try {
            foreach ($flysystem->listContents('', true) as $item) {
                $itemCount++;
                if ($itemCount % 50 === 0) {
                    Log::info("[S3Browser] listContents progress: {$itemCount} items so far");
                }

                if (!($item instanceof FileAttributes)) {
                    continue;
                }

                $parts = explode('/', $item->path(), 2);
                if (count($parts) !== 2 || $parts[1] === '') {
                    continue;
                }

                [$jobDir, $filename] = $parts;
                $jobMap[$jobDir][] = [
                    'name'          => $filename,
                    'size'          => $item->fileSize() ?? 0,
                    'last_modified' => $item->lastModified() ?? 0,
                ];
            }
        } catch (\Exception $e) {
            Log::error('[S3Browser] listContents threw: ' . $e->getMessage());
            throw $e;
        }

        Log::info("[S3Browser] listContents done: {$itemCount} items, " . count($jobMap) . " directories in " . round(microtime(true) - $t0, 2) . "s");

        $trendsMap = Trend::all()->keyBy('job_id');
        Log::info('[S3Browser] Loaded ' . $trendsMap->count() . ' Trend records');

        $jobs = [];
        foreach ($jobMap as $jobDir => $files) {
            usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));

            $fileNames = array_column($files, 'name');
            $trend     = $trendsMap->get($jobDir);

            $jobs[] = [
                'job_id'        => $jobDir,
                'files'         => $files,
                'file_count'    => count($files),
                'total_size'    => array_sum(array_column($files, 'size')),
                'last_modified' => max(array_column($files, 'last_modified')),
                'has_stage4'    => in_array('stage4_h3_anomaly.json', $fileNames),
                'has_scoring'   => (bool) array_filter($fileNames, fn($n) => str_starts_with($n, 'scoring_results')),
                'has_stage6'    => (bool) array_filter($fileNames, fn($n) => str_starts_with($n, 'stage6')),
                'trend_id'      => $trend?->id,
                'model_class'   => $trend?->model_class,
                'column_name'   => $trend?->column_name,
                'parsed'        => $this->parseJobId($jobDir),
            ];
        }

        usort($jobs, fn($a, $b) => ($b['last_modified'] ?? 0) <=> ($a['last_modified'] ?? 0));

        Log::info('[S3Browser] buildListing() done: ' . count($jobs) . ' jobs in ' . round(microtime(true) - $t0, 2) . 's');

        return $jobs;
    }

    /**
     * Best-effort parse of a job ID of the form:
     *   laravel-{model_key}-{job_suffix}-res{N}-{timestamp}
     */
    private function parseJobId(string $jobId): array
    {
        if (preg_match('/^laravel-(.+)-res(\d+)-(\d+)$/', $jobId, $m)) {
            return [
                'model_col'    => $m[1],
                'resolution'   => (int) $m[2],
                'dispatched_at' => (int) $m[3],
            ];
        }
        return [];
    }
}
