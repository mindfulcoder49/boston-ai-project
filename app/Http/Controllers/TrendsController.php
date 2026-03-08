<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use League\Flysystem\FileAttributes;
use App\Models\AnalysisReportSnapshot;
use App\Models\Trend;
use App\Services\TrendSummaryService;

class TrendsController extends Controller
{
    private const CACHE_KEY = 'trend_listing_v1';

    public function index()
    {
        $analyses = Cache::rememberForever(self::CACHE_KEY, fn() => $this->buildListing());

        return Inertia::render('Trends/Index', [
            'analyses' => $analyses,
            'isAdmin'  => Auth::check() && Auth::user()?->email === config('admin.email'),
        ]);
    }

    public function refresh()
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('s3_bucket_listing_v1');
        return redirect()->route('trends.index')->with('status', 'Trend listing refreshed.');
    }

    /**
     * API: compute (and permanently cache) the summary for one job.
     * Called lazily by the frontend for each card whose summary wasn't cached at page-load time.
     */
    public function getSummary(string $jobId): \Illuminate\Http\JsonResponse
    {
        if (!preg_match('/^[\w\-]+$/', $jobId)) {
            return response()->json(['error' => 'Invalid job ID.'], 400);
        }

        $cacheKey = TrendSummaryService::cacheKey($jobId);

        $summary = Cache::rememberForever($cacheKey, function () use ($jobId) {
            $trend = Trend::where('job_id', $jobId)->first();

            if ($trend) {
                return TrendSummaryService::compute(
                    $jobId,
                    (int)   $trend->h3_resolution,
                    (float) $trend->p_value_anomaly,
                    (float) $trend->p_value_trend
                );
            }

            $meta = $this->extractMetaFromFile($jobId);
            if (!$meta) {
                return ['status' => 'no_data', 'total_findings' => 0];
            }

            return TrendSummaryService::compute(
                $jobId,
                (int)   $meta['h3_resolution'],
                (float) $meta['p_value_anomaly'],
                (float) $meta['p_value_trend']
            );
        });

        return response()->json($summary);
    }

    // -------------------------------------------------------------------------

    private function buildListing(): array
    {
        $stage4JobIds = $this->discoverStage4JobIds();
        $trendsMap    = Trend::all()->keyBy('job_id');

        $allAnalyses = [];

        foreach ($stage4JobIds as $jobId) {
            $trend = $trendsMap->get($jobId);

            if ($trend && class_exists($trend->model_class)) {
                // Fast path: DB record has all the metadata we need
                $meta = [
                    'model_class'            => $trend->model_class,
                    'column_name'            => $trend->column_name,
                    'h3_resolution'          => $trend->h3_resolution,
                    'p_value_anomaly'        => $trend->p_value_anomaly,
                    'p_value_trend'          => $trend->p_value_trend,
                    'analysis_weeks_trend'   => $trend->analysis_weeks_trend,
                    'analysis_weeks_anomaly' => $trend->analysis_weeks_anomaly,
                    'last_run'               => $trend->updated_at->toDateString(),
                    'trend_id'               => $trend->id,
                ];
            } else {
                // Slow path: read parameters from the S3 file itself.
                // Only needed for jobs dispatched on another environment (no local DB record).
                $meta = $this->extractMetaFromFile($jobId);
                if (!$meta) {
                    continue;
                }
                $meta['trend_id'] = null;
            }

            $modelClass = $meta['model_class'];
            if (!class_exists($modelClass)) {
                continue;
            }

            $summary = Cache::get(TrendSummaryService::cacheKey($jobId)); // null = not yet computed

            $allAnalyses[] = [
                'trend_id'               => $meta['trend_id'],
                'job_id'                 => $jobId,
                'city'                   => $this->inferCity($modelClass),
                'model_name'             => $modelClass::getHumanName(),
                'model_key'              => Str::kebab(class_basename($modelClass)),
                'column_name'            => $meta['column_name'],
                'column_label'           => Str::of($meta['column_name'])->replace('_', ' ')->title(),
                'h3_resolution'          => $meta['h3_resolution'],
                'analysis_weeks_trend'   => $meta['analysis_weeks_trend'],
                'analysis_weeks_anomaly' => $meta['analysis_weeks_anomaly'],
                'p_value_anomaly'        => $meta['p_value_anomaly'],
                'p_value_trend'          => $meta['p_value_trend'],
                'last_run'               => $meta['last_run'] ?? null,
                'summary'                => $summary,
            ];
        }

        usort($allAnalyses, fn($a, $b) =>
            ($b['summary']['total_findings'] ?? 0) <=> ($a['summary']['total_findings'] ?? 0)
        );

        return $allAnalyses;
    }

    /**
     * Find all job_ids that have a stage4_h3_anomaly.json artifact.
     * Priority: snapshot table → S3 bucket cache → direct S3 scan.
     */
    private function discoverStage4JobIds(): array
    {
        // Fastest: snapshot table (populated by app:pull-analysis-reports)
        $fromSnapshots = AnalysisReportSnapshot::jobIdsForArtifact('stage4_h3_anomaly.json');
        if (!empty($fromSnapshots)) {
            return $fromSnapshots;
        }

        // S3 browser cache (warm if admin has visited the S3 browser)
        $bucketCache = Cache::get('s3_bucket_listing_v1');
        if ($bucketCache !== null) {
            return collect($bucketCache)
                ->where('has_stage4', true)
                ->pluck('job_id')
                ->values()
                ->all();
        }

        // Last resort: direct S3 scan
        $flysystem = Storage::disk('s3')->getDriver();
        $jobIds    = [];
        foreach ($flysystem->listContents('', true) as $item) {
            if (!($item instanceof FileAttributes)) {
                continue;
            }
            $parts = explode('/', $item->path(), 2);
            if (count($parts) === 2 && $parts[1] === 'stage4_h3_anomaly.json') {
                $jobIds[] = $parts[0];
            }
        }
        return $jobIds;
    }

    /**
     * Extract listing metadata from the stage4 artifact for jobs with no local Trend DB record.
     * Uses the snapshot table first, falls back to S3.
     */
    private function extractMetaFromFile(string $jobId): ?array
    {
        try {
            $data = AnalysisReportSnapshot::resolve($jobId, 'stage4_h3_anomaly.json');
            if (!$data) {
                return null;
            }

            $params = $data['parameters'] ?? [];

            // Phase 1+ jobs have model_class embedded in parameters
            $modelClass = $params['model_class'] ?? null;
            $columnName = $params['column_name'] ?? null;

            // Pre-Phase-1 jobs: parse from the job_id pattern
            if (!$modelClass) {
                ['model_class' => $modelClass, 'column_name' => $columnName]
                    = $this->parseJobIdForMeta($jobId);
            }

            if (!$modelClass) {
                Log::warning("[TrendsController] Could not resolve model_class for job {$jobId}");
                return null;
            }

            return [
                'model_class'            => $modelClass,
                'column_name'            => $columnName ?? 'unified',
                'h3_resolution'          => (int)   ($params['h3_resolution']          ?? 8),
                'p_value_anomaly'        => (float) ($params['p_value_anomaly']        ?? 0.05),
                'p_value_trend'          => (float) ($params['p_value_trend']          ?? 0.05),
                'analysis_weeks_trend'   =>          $params['analysis_weeks_trend']   ?? [4, 26, 52],
                'analysis_weeks_anomaly' => (int)   ($params['analysis_weeks_anomaly'] ?? 4),
                'last_run'               => null,
            ];
        } catch (\Exception $e) {
            Log::error("[TrendsController] extractMetaFromFile({$jobId}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse a job ID to infer model_class and column_name for pre-Phase-1 jobs.
     * Format: laravel-{modelKey}-{jobSuffix}-res{N}-{timestamp}
     */
    private function parseJobIdForMeta(string $jobId): array
    {
        if (!preg_match('/^laravel-(.+)-res\d+-\d+$/', $jobId, $m)) {
            return ['model_class' => null, 'column_name' => null];
        }

        $modelAndCol = $m[1]; // e.g. "crime-data-unified" or "crime-data-offense_description"

        // Build reverse map: kebab-model-key → model_class, longest key first
        $keyMap = [];
        foreach (config('cities.cities', []) as $cityConfig) {
            foreach ($cityConfig['models'] ?? [] as $mc) {
                $keyMap[Str::kebab(class_basename($mc))] = $mc;
            }
        }
        uksort($keyMap, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($keyMap as $key => $mc) {
            if ($modelAndCol === $key) {
                return ['model_class' => $mc, 'column_name' => 'unified'];
            }
            if (str_starts_with($modelAndCol, $key . '-')) {
                return ['model_class' => $mc, 'column_name' => substr($modelAndCol, strlen($key) + 1)];
            }
        }

        return ['model_class' => null, 'column_name' => null];
    }

    private function inferCity(string $modelClass): string
    {
        foreach (config('cities.cities', []) as $cityConfig) {
            if (in_array($modelClass, $cityConfig['models'] ?? [])) {
                return $cityConfig['name'];
            }
        }
        $default = config('cities.default', 'boston');
        return config("cities.cities.{$default}.name", 'Boston');
    }

}
