<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use League\Flysystem\FileAttributes;
use App\Models\AnalysisReportSnapshot;

class YearlyCountComparisonController extends Controller
{
    private const CACHE_KEY = 'yearly_count_comparison_listing_v1';

    public function index()
    {
        $reportsByModel = Cache::rememberForever(self::CACHE_KEY, fn() => $this->buildListing());

        return Inertia::render('YearlyCountComparison/Index', [
            'reportsByModel' => $reportsByModel,
        ]);
    }

    public function show(string $jobId)
    {
        if (!preg_match('/^[\w\-]+$/', $jobId)) {
            abort(400, 'Invalid job ID.');
        }

        $reportData = AnalysisReportSnapshot::resolve($jobId, 'stage2_yearly_count_comparison.json');

        if (!$reportData) {
            Log::warning("[YearlyCountComparisonController] Artifact not found for {$jobId}.");
        }

        $params      = $reportData['parameters'] ?? [];
        $modelClass  = $params['model_class'] ?? null;
        $groupByCol  = $params['group_by_col'] ?? $params['column_name'] ?? 'unknown';
        $baselineYear = $params['baseline_year'] ?? '';

        $modelName = ($modelClass && class_exists($modelClass))
            ? $modelClass::getHumanName()
            : ($modelClass ? class_basename($modelClass) : 'Unknown');

        $reportTitle = sprintf(
            '%s Yearly Comparison by %s (Baseline %s)',
            $modelName,
            Str::of($groupByCol)->replace('_', ' ')->title(),
            $baselineYear
        );

        return Inertia::render('Reports/YearlyCountComparisonViewer', [
            'jobId'       => $jobId,
            'apiBaseUrl'  => config('services.analysis_api.url'),
            'reportData'  => $reportData,
            'reportTitle' => $reportTitle,
            'newsArticle' => null,
        ]);
    }

    public function refresh()
    {
        Cache::forget(self::CACHE_KEY);
        return redirect()->route('yearly-comparisons.index')->with('status', 'Yearly comparison listing refreshed.');
    }

    // -------------------------------------------------------------------------

    private function buildListing(): array
    {
        // Fast path: snapshot table (populated by app:pull-analysis-reports)
        $snapshots = AnalysisReportSnapshot::where('artifact_name', 'stage2_yearly_count_comparison.json')->get();
        if ($snapshots->isNotEmpty()) {
            return $this->buildListingFromSnapshots($snapshots);
        }

        $s3     = Storage::disk('s3');
        $jobIds = $this->discoverStage2JobIds($s3);

        $byModel = [];

        foreach ($jobIds as $jobId) {
            $path = "{$jobId}/stage2_yearly_count_comparison.json";

            try {
                if (!$s3->exists($path)) continue;

                $data   = json_decode($s3->get($path), true);
                $params = $data['parameters'] ?? [];

                $modelClass  = $params['model_class'] ?? null;
                $groupByCol  = $params['group_by_col'] ?? $params['column_name'] ?? null;
                $baselineYear = $params['baseline_year'] ?? null;

                if (!$modelClass || !class_exists($modelClass) || !$groupByCol) {
                    Log::warning("[YearlyCountComparisonController] Skipping {$jobId} — missing metadata in artifact parameters.");
                    continue;
                }

                $modelKey  = Str::kebab(class_basename($modelClass));
                $modelName = $modelClass::getHumanName();

                $byModel[$modelKey] ??= [
                    'model_name' => $modelName,
                    'model_key'  => $modelKey,
                    'analyses'   => [],
                ];

                $byModel[$modelKey]['analyses'][] = [
                    'job_id'         => $jobId,
                    'group_by_col'   => $groupByCol,
                    'group_by_label' => Str::of($groupByCol)->replace('_', ' ')->title()->value(),
                    'baseline_year'  => $baselineYear,
                ];
            } catch (\Exception $e) {
                Log::error("[YearlyCountComparisonController] Error reading {$jobId}: " . $e->getMessage());
            }
        }

        return array_values($byModel);
    }

    private function buildListingFromSnapshots($snapshots): array
    {
        $byModel = [];

        foreach ($snapshots as $snapshot) {
            $params       = $snapshot->payload['parameters'] ?? [];
            $modelClass   = $params['model_class'] ?? null;
            $groupByCol   = $params['group_by_col'] ?? $params['column_name'] ?? null;
            $baselineYear = $params['baseline_year'] ?? null;

            // Fallback for artifacts missing model_class (Pydantic stripping issue pre-fix)
            if (!$modelClass || !class_exists($modelClass)) {
                $parsed       = $this->parseJobIdForMeta($snapshot->job_id);
                $modelClass   = $parsed['model_class'] ?? null;
                $groupByCol   = $groupByCol ?? $parsed['column_name'] ?? null;
                $baselineYear = $baselineYear ?? $parsed['baseline_year'] ?? null;
            }

            if (!$modelClass || !class_exists($modelClass) || !$groupByCol) {
                continue;
            }

            $modelKey  = Str::kebab(class_basename($modelClass));
            $modelName = $modelClass::getHumanName();

            $byModel[$modelKey] ??= [
                'model_name' => $modelName,
                'model_key'  => $modelKey,
                'analyses'   => [],
            ];

            $byModel[$modelKey]['analyses'][] = [
                'job_id'         => $snapshot->job_id,
                'group_by_col'   => $groupByCol,
                'group_by_label' => Str::of($groupByCol)->replace('_', ' ')->title()->value(),
                'baseline_year'  => $baselineYear,
            ];
        }

        return array_values($byModel);
    }

    /**
     * Parse model_class, column_name, and baseline_year from a Stage 2 job ID.
     * Format: laravel-{model-key}-{column_name}-yearly-{baselineYear}-{timestamp}
     */
    private function parseJobIdForMeta(string $jobId): array
    {
        if (!preg_match('/^laravel-(.+)-yearly-(\d+)-\d+$/', $jobId, $m)) {
            return ['model_class' => null, 'column_name' => null, 'baseline_year' => null];
        }

        $modelAndCol  = $m[1];
        $baselineYear = (int) $m[2];

        $keyMap = [];
        foreach (config('cities.cities', []) as $cityConfig) {
            foreach ($cityConfig['models'] ?? [] as $mc) {
                $keyMap[Str::kebab(class_basename($mc))] = $mc;
            }
        }
        uksort($keyMap, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($keyMap as $key => $mc) {
            if ($modelAndCol === $key) {
                return ['model_class' => $mc, 'column_name' => 'unified', 'baseline_year' => $baselineYear];
            }
            if (str_starts_with($modelAndCol, $key . '-')) {
                return ['model_class' => $mc, 'column_name' => substr($modelAndCol, strlen($key) + 1), 'baseline_year' => $baselineYear];
            }
        }

        return ['model_class' => null, 'column_name' => null, 'baseline_year' => null];
    }

    private function discoverStage2JobIds($s3): array
    {
        $jobIds = [];

        try {
            $flysystem = $s3->getDriver();
            foreach ($flysystem->listContents('', true) as $item) {
                if (!($item instanceof FileAttributes)) continue;
                $parts = explode('/', $item->path(), 2);
                if (count($parts) === 2 && $parts[1] === 'stage2_yearly_count_comparison.json') {
                    $jobIds[] = $parts[0];
                }
            }
        } catch (\Exception $e) {
            Log::error('[YearlyCountComparisonController] S3 listing failed: ' . $e->getMessage());
        }

        return $jobIds;
    }
}
