<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use App\Models\AnalysisReportSnapshot;
use App\Services\TrendSummaryService;

class AdminCacheController extends Controller
{
    private const LISTING_CACHES = [
        'trend_listing_v1' => [
            'label'       => 'Trends Listing',
            'description' => 'Powers the /trends page. Contains the full list of analyses with pre-computed summaries sorted by finding count. Rebuilt automatically after pulling reports. Clear manually if the listing looks stale after a cache warm.',
        ],
        'yearly_count_comparison_listing_v1' => [
            'label'       => 'Yearly Comparisons Listing',
            'description' => 'Powers the yearly count comparison reports page. Lists all year-over-year comparison jobs grouped by model and column.',
        ],
        'scoring_reports_listing_v2' => [
            'label'       => 'Neighborhood Scoring Reports Listing',
            'description' => 'Powers the neighborhood scores page. Scanned from S3 on miss — can be slow on large buckets. Clear after new scoring jobs complete.',
        ],
        's3_bucket_listing_v1' => [
            'label'       => 'S3 Bucket Browser',
            'description' => 'Powers the admin S3 browser. Full recursive directory listing of all job artifacts in S3. Expensive to rebuild on large buckets — only clear if you need a fresh view of S3 contents.',
        ],
    ];

    private const GLOBAL_CACHES = [
        'h3_location_names_map' => [
            'label'       => 'H3 Location Names',
            'description' => 'Shared on every page load via Inertia shared data. Maps H3 hex indices to human-readable location names (e.g. "Jamaica Plain Neighborhood, Boston, MA"). Has a 1-hour TTL. Clear after syncing new geocoded names to production — it will rebuild on next page request.',
            'ttl'         => 3600,
        ],
    ];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || Auth::user()->email !== config('admin.email')) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $listingCaches = [];
        foreach (self::LISTING_CACHES as $key => $meta) {
            $listingCaches[] = array_merge($meta, [
                'key'    => $key,
                'exists' => Cache::has($key),
            ]);
        }

        $globalCaches = [];
        foreach (self::GLOBAL_CACHES as $key => $meta) {
            $globalCaches[] = array_merge($meta, [
                'key'    => $key,
                'exists' => Cache::has($key),
            ]);
        }

        $stage4JobIds       = AnalysisReportSnapshot::jobIdsForArtifact('stage4_h3_anomaly.json');
        $cachedSummaryCount = 0;
        foreach ($stage4JobIds as $jobId) {
            if (Cache::has(TrendSummaryService::cacheKey($jobId))) {
                $cachedSummaryCount++;
            }
        }
        $summaryStats = [
            'total'   => count($stage4JobIds),
            'cached'  => $cachedSummaryCount,
            'missing' => count($stage4JobIds) - $cachedSummaryCount,
        ];

        $snapshotCounts = [
            'stage4'  => AnalysisReportSnapshot::where('artifact_name', 'stage4_h3_anomaly.json')->count(),
            'stage2'  => AnalysisReportSnapshot::where('artifact_name', 'stage2_yearly_count_comparison.json')->count(),
            'scoring' => AnalysisReportSnapshot::where('artifact_name', 'LIKE', 'scoring_results%')->count(),
            'stage6'  => AnalysisReportSnapshot::where('artifact_name', 'LIKE', 'stage6%')->count(),
        ];

        return Inertia::render('Admin/CacheManager', [
            'listingCaches'  => $listingCaches,
            'globalCaches'   => $globalCaches,
            'summaryStats'   => $summaryStats,
            'snapshotCounts' => $snapshotCounts,
        ]);
    }

    // ── Cache operations (Inertia, instant) ───────────────────────────────────

    public function forgetCache(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        $allowed = array_merge(array_keys(self::LISTING_CACHES), array_keys(self::GLOBAL_CACHES));
        if (!in_array($request->key, $allowed)) {
            return back()->with('error', "Unknown cache key: {$request->key}");
        }

        Cache::forget($request->key);
        $label = self::LISTING_CACHES[$request->key]['label'] ?? self::GLOBAL_CACHES[$request->key]['label'] ?? $request->key;
        return back()->with('success', "Cleared: {$label}");
    }

    public function forgetAllListingCaches()
    {
        foreach (array_keys(self::LISTING_CACHES) as $key) {
            Cache::forget($key);
        }
        return back()->with('success', 'All listing caches cleared. They will rebuild on next page visit.');
    }

    public function forgetAllSummaryCaches()
    {
        $jobIds = AnalysisReportSnapshot::jobIdsForArtifact('stage4_h3_anomaly.json');
        foreach ($jobIds as $jobId) {
            Cache::forget(TrendSummaryService::cacheKey($jobId));
        }
        Cache::forget('trend_listing_v1');
        return back()->with('success', 'Cleared ' . count($jobIds) . ' trend summary cache(s) and the trends listing.');
    }

    // ── Command endpoints (JSON, fetched directly) ────────────────────────────

    /**
     * SSE stream for Pull Analysis Reports.
     * Runs the artisan command as a subprocess and streams output line-by-line.
     * Accepts GET query params: fresh=1, skip_hotspots=1
     */
    public function pullReportsStream(Request $request): StreamedResponse
    {
        $cmd = [PHP_BINARY, base_path('artisan'), 'app:pull-analysis-reports', '--no-ansi'];
        if ($request->boolean('fresh')) {
            $cmd[] = '--fresh';
        }
        if ($request->boolean('skip_hotspots')) {
            $cmd[] = '--skip-hotspots';
        }

        $process = new Process($cmd, base_path());
        $process->setTimeout(300);
        $process->start();

        return response()->stream(function () use ($process) {
            // Disable output buffering as much as possible
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $this->sseEvent(['type' => 'start']);

            while ($process->isRunning()) {
                $out = $process->getIncrementalOutput();
                $err = $process->getIncrementalErrorOutput();

                foreach ($this->splitLines($out) as $line) {
                    $this->sseEvent(['type' => 'line', 'text' => $line]);
                }
                foreach ($this->splitLines($err) as $line) {
                    $this->sseEvent(['type' => 'line', 'text' => $line, 'stderr' => true]);
                }

                usleep(150_000); // 150ms poll interval
            }

            // Flush any remaining output after process ends
            foreach ($this->splitLines($process->getIncrementalOutput()) as $line) {
                $this->sseEvent(['type' => 'line', 'text' => $line]);
            }
            foreach ($this->splitLines($process->getIncrementalErrorOutput()) as $line) {
                $this->sseEvent(['type' => 'line', 'text' => $line, 'stderr' => true]);
            }

            $this->sseEvent(['type' => 'done', 'exitCode' => $process->getExitCode()]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Re-materialize hotspot findings. Returns JSON with captured output.
     */
    public function materializeHotspots(): \Illuminate\Http\JsonResponse
    {
        try {
            Artisan::call('app:materialize-hotspot-findings', ['--force' => true]);
            return response()->json([
                'success' => true,
                'output'  => trim(Artisan::output()) ?: 'Done.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'output' => $e->getMessage()], 500);
        }
    }

    /**
     * Warm the dashboard metrics cache. Returns JSON with captured output.
     */
    public function warmMetrics(): \Illuminate\Http\JsonResponse
    {
        try {
            Artisan::call('app:cache-metrics-data');
            return response()->json([
                'success' => true,
                'output'  => trim(Artisan::output()) ?: 'Done.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'output' => $e->getMessage()], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sseEvent(array $data): void
    {
        echo 'data: ' . json_encode($data) . "\n\n";
        flush();
    }

    /** Split raw process output into non-empty trimmed lines, stripping ANSI codes. */
    private function splitLines(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        // Strip ANSI escape codes
        $clean = preg_replace('/\x1B\[[0-9;]*[a-zA-Z]/', '', $raw);
        return array_values(array_filter(
            array_map('trim', explode("\n", $clean)),
            fn($l) => $l !== ''
        ));
    }
}
