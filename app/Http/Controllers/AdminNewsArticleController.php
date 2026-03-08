<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateHotspotArticleJob;
use App\Jobs\GenerateNewsArticleJob;
use App\Models\HotspotFinding;
use App\Models\JobRun;
use App\Models\NewsArticle;
use App\Models\Trend;
use App\Services\TrendSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminNewsArticleController extends Controller
{
    private const CITY_PREFIXES = [
        'Cambridge'          => 'Cambridge',
        'Everett'            => 'Everett',
        'Chicago'            => 'Chicago',
        'SanFrancisco'       => 'San Francisco',
        'Seattle'            => 'Seattle',
        'MontgomeryCountyMd' => 'Montgomery County MD',
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
        $trends = Trend::orderBy('model_class')->orderBy('column_name')->get()->map(function ($trend) {
            $summary     = Cache::get(TrendSummaryService::cacheKey($trend->job_id));
            $article     = NewsArticle::where('source_model_class', Trend::class)
                ->where('source_report_id', $trend->id)
                ->first();
            $sourceModel = $trend->sourceModel();
            $modelName   = $sourceModel ? $sourceModel->getHumanName() : class_basename($trend->model_class);
            $columnLabel = Str::of($trend->column_name)->replace('_', ' ')->title()->toString();

            return [
                'id'            => $trend->id,
                'job_id'        => $trend->job_id,
                'title'         => "{$modelName} — {$columnLabel}",
                'model_class'   => $trend->model_class,
                'column_name'   => $trend->column_name,
                'h3_resolution' => $trend->h3_resolution,
                'summary_cached'=> $summary !== null,
                'summary'       => $summary ? [
                    'total_findings'    => $summary['total_findings']    ?? 0,
                    'anomaly_count'     => $summary['anomaly_count']     ?? 0,
                    'trend_count'       => $summary['trend_count']       ?? 0,
                    'affected_h3_count' => $summary['affected_h3_count'] ?? 0,
                    'top_categories'    => $summary['top_categories']    ?? [],
                ] : null,
                'article' => $article ? [
                    'id'         => $article->id,
                    'status'     => $article->status,
                    'title'      => $article->title,
                    'slug'       => $article->slug,
                    'updated_at' => $article->updated_at?->toISOString(),
                ] : null,
            ];
        });

        $hotspots       = $this->buildTopHotspots();
        $recentArticles = NewsArticle::orderByDesc('updated_at')
            ->limit(30)
            ->get(['id', 'title', 'slug', 'status', 'updated_at', 'source_model_class']);

        return Inertia::render('Admin/NewsArticleGenerator', [
            'trends'         => $trends,
            'hotspots'       => $hotspots,
            'recentArticles' => $recentArticles,
        ]);
    }

    /**
     * Queue a news article generation job for a Trend.
     */
    public function generateFromTrend(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['trend_id' => 'required|integer']);
        $trend = Trend::findOrFail($request->trend_id);

        $sourceModel = $trend->sourceModel();
        $modelName   = $sourceModel ? $sourceModel->getHumanName() : class_basename($trend->model_class);
        $columnLabel = Str::of($trend->column_name)->replace('_', ' ')->title()->toString();

        $article = NewsArticle::updateOrCreate(
            ['source_model_class' => Trend::class, 'source_report_id' => $trend->id],
            [
                'title'        => "Queued: {$modelName} — {$columnLabel}",
                'slug'         => 'temp-' . Str::uuid()->toString(),
                'headline'     => 'Queued for generation...',
                'summary'      => 'Generation in progress.',
                'content'      => 'Generation in progress.',
                'status'       => 'draft',
                'published_at' => null,
            ]
        );

        $job   = new GenerateNewsArticleJob($article, true);
        $jobId = app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatch($job);

        JobRun::create([
            'job_id'             => $jobId,
            'job_class'          => GenerateNewsArticleJob::class,
            'status'             => 'pending',
            'related_model_type' => NewsArticle::class,
            'related_model_id'   => $article->id,
            'payload'            => ['trend_id' => $trend->id, 'fresh' => true],
        ]);

        $article->update(['job_id' => $jobId]);

        return response()->json([
            'success'    => true,
            'article_id' => $article->id,
            'message'    => "Article generation queued.",
        ]);
    }

    /**
     * Synchronously generate a news article from a hotspot hexagon and save it.
     */
    public function generateFromHexagon(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'h3_index'      => 'required|string',
            'location_name' => 'nullable|string|max:255',
        ]);

        $h3Index      = $request->h3_index;
        $locationName = $request->location_name ?? $h3Index;

        $findings = HotspotFinding::where('h3_index', $h3Index)->get();
        if ($findings->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No hotspot data found for this hexagon.'], 404);
        }

        $hotspotContext = [
            'h3_index'        => $h3Index,
            'location'        => $locationName,
            'total_reports'   => $findings->count(),
            'total_anomalies' => $findings->sum('anomaly_count'),
            'total_trends'    => $findings->sum('trend_count'),
            'report_types'    => $findings->map(fn($f) => [
                'type'          => class_exists($f->model_class)
                    ? $f->model_class::getHumanName() . ' — ' . Str::of($f->column_name)->replace('_', ' ')->title()
                    : class_basename($f->model_class) . ' — ' . $f->column_name,
                'anomalies'     => $f->anomaly_count,
                'trends'        => $f->trend_count,
                'top_anomalies' => $f->top_anomalies ?? [],
                'top_trends'    => $f->top_trends    ?? [],
            ])->sortByDesc(fn($r) => $r['anomalies'] + $r['trends'])->values()->toArray(),
        ];

        $article = NewsArticle::create([
            'title'              => "Queued: Hotspot — {$locationName}",
            'slug'               => 'temp-' . Str::uuid()->toString(),
            'headline'           => 'Queued for generation...',
            'summary'            => 'Generation in progress.',
            'content'            => 'Generation in progress.',
            'source_model_class' => null,
            'source_report_id'   => null,
            'status'             => 'draft',
            'published_at'       => null,
        ]);

        GenerateHotspotArticleJob::dispatch($article, $h3Index, $locationName, $hotspotContext);

        return response()->json([
            'success'    => true,
            'article_id' => $article->id,
            'message'    => "Article generation queued.",
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function inferCity(string $modelClass): string
    {
        $base = class_basename($modelClass);
        foreach (self::CITY_PREFIXES as $prefix => $city) {
            if (str_starts_with($base, $prefix)) return $city;
        }
        return 'Boston';
    }

    private function buildTopHotspots(): array
    {
        $allFindings = HotspotFinding::all();
        $hexagons    = [];

        foreach ($allFindings as $f) {
            $city = $this->inferCity($f->model_class);
            $res  = $f->h3_resolution;
            $idx  = $f->h3_index;

            if (!isset($hexagons[$city][$res][$idx])) {
                $hexagons[$city][$res][$idx] = [
                    'h3_index'      => $idx,
                    'report_count'  => 0,
                    'anomaly_count' => 0,
                    'trend_count'   => 0,
                ];
            }

            $hexagons[$city][$res][$idx]['report_count']++;
            $hexagons[$city][$res][$idx]['anomaly_count'] += $f->anomaly_count;
            $hexagons[$city][$res][$idx]['trend_count']   += $f->trend_count;
        }

        $result = [];
        foreach ($hexagons as $city => $resByHex) {
            $flat = [];
            foreach ($resByHex as $res => $hexMap) {
                foreach ($hexMap as $hex) {
                    $flat[] = array_merge($hex, ['resolution' => $res]);
                }
            }
            usort($flat, fn($a, $b) =>
                $b['report_count'] !== $a['report_count']
                    ? $b['report_count'] - $a['report_count']
                    : ($b['anomaly_count'] + $b['trend_count']) - ($a['anomaly_count'] + $a['trend_count'])
            );
            $result[$city] = array_slice($flat, 0, 25);
        }

        ksort($result);
        return $result;
    }
}
