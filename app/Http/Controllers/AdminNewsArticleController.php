<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateHotspotArticleJob;
use App\Jobs\GenerateNewsArticleJob;
use App\Models\HotspotFinding;
use App\Models\JobRun;
use App\Models\NewsArticle;
use App\Models\NewsArticleGenerationConfig;
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

    // ── Index ──────────────────────────────────────────────────────────────────

    public function index()
    {
        $existingConfigs = NewsArticleGenerationConfig::where('source_type', 'trend')
            ->get(['trend_id', 'id', 'status', 'last_generated_at'])
            ->keyBy('trend_id');

        $trends = Trend::orderBy('model_class')->orderBy('column_name')->get()->map(function ($trend) use ($existingConfigs) {
            $summary     = Cache::get(TrendSummaryService::cacheKey($trend->job_id));
            $sourceModel = $trend->sourceModel();
            $modelName   = $sourceModel ? $sourceModel->getHumanName() : class_basename($trend->model_class);
            $columnLabel = Str::of($trend->column_name)->replace('_', ' ')->title()->toString();
            $config      = $existingConfigs->get($trend->id);

            return [
                'id'            => $trend->id,
                'job_id'        => $trend->job_id,
                'title'         => "{$modelName} — {$columnLabel}",
                'model_name'    => $modelName,
                'model_key'     => Str::kebab(class_basename($trend->model_class)),
                'column_name'   => $trend->column_name,
                'column_label'  => $columnLabel,
                'h3_resolution' => $trend->h3_resolution,
                'city'          => $this->inferCity($trend->model_class),
                'summary_cached'=> $summary !== null,
                'summary'       => $summary ? [
                    'total_findings'    => $summary['total_findings']    ?? 0,
                    'anomaly_count'     => $summary['anomaly_count']     ?? 0,
                    'trend_count'       => $summary['trend_count']       ?? 0,
                    'affected_h3_count' => $summary['affected_h3_count'] ?? 0,
                    'top_categories'    => $summary['top_categories']    ?? [],
                ] : null,
                'config_id'     => $config?->id,
                'config_status' => $config?->status,
                'config_last_generated' => $config?->last_generated_at?->toISOString(),
            ];
        });

        $hotspots       = $this->buildTopHotspotsForMap();
        $recentArticles = NewsArticle::orderByDesc('updated_at')
            ->limit(30)
            ->get(['id', 'title', 'slug', 'status', 'updated_at', 'source_model_class']);

        return Inertia::render('Admin/NewsArticleGenerator', [
            'trends'         => $trends,
            'hotspots'       => $hotspots,
            'recentArticles' => $recentArticles,
        ]);
    }

    // ── Trend configure page ───────────────────────────────────────────────────

    public function configureTrend(Trend $trend): \Inertia\Response
    {
        $summary = Cache::get(TrendSummaryService::cacheKey($trend->job_id));
        if (!$summary) {
            $summary = TrendSummaryService::computeAndCache(
                $trend->job_id,
                $trend->h3_resolution,
                $trend->p_value_anomaly,
                $trend->p_value_trend
            );
        }

        $config = NewsArticleGenerationConfig::where('source_type', 'trend')
            ->where('trend_id', $trend->id)
            ->first();

        $existingArticle = $config?->last_news_article_id
            ? $config->lastArticle
            : NewsArticle::where('source_model_class', Trend::class)
                ->where('source_report_id', $trend->id)
                ->first();

        $sourceModel = $trend->sourceModel();
        $modelName   = $sourceModel ? $sourceModel->getHumanName() : class_basename($trend->model_class);
        $columnLabel = Str::of($trend->column_name)->replace('_', ' ')->title()->toString();

        return Inertia::render('Admin/TrendArticleConfig', [
            'trend' => [
                'id'                     => $trend->id,
                'job_id'                 => $trend->job_id,
                'title'                  => "{$modelName} — {$columnLabel}",
                'model_name'             => $modelName,
                'column_label'           => $columnLabel,
                'h3_resolution'          => $trend->h3_resolution,
                'analysis_weeks_trend'   => $trend->analysis_weeks_trend,
                'analysis_weeks_anomaly' => $trend->analysis_weeks_anomaly,
                'city'                   => $this->inferCity($trend->model_class),
                'last_run'               => $trend->updated_at?->toDateString(),
                'p_value_anomaly'        => $trend->p_value_anomaly,
                'p_value_trend'          => $trend->p_value_trend,
            ],
            'summary' => $summary,
            'config' => $config ? [
                'id'                     => $config->id,
                'intro_prompt'           => $config->intro_prompt,
                'included_categories'    => $config->included_categories,
                'included_finding_types' => $config->included_finding_types,
                'status'                 => $config->status,
                'last_generated_at'      => $config->last_generated_at?->toISOString(),
            ] : null,
            'existingArticle' => $existingArticle ? [
                'id'     => $existingArticle->id,
                'status' => $existingArticle->status,
                'title'  => $existingArticle->title,
                'slug'   => $existingArticle->slug,
            ] : null,
            'defaultPrompt' => AiAssistantController::defaultTrendSystemPrompt(),
        ]);
    }

    public function saveTrendConfig(Request $request, Trend $trend): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'intro_prompt'             => 'nullable|string|max:10000',
            'included_categories'      => 'nullable|array',
            'included_categories.*'    => 'string',
            'included_finding_types'   => 'nullable|array',
            'included_finding_types.*' => 'string',
            'status'                   => 'required|in:draft,finalized,active_auto',
        ]);

        $config = NewsArticleGenerationConfig::updateOrCreate(
            ['source_type' => 'trend', 'trend_id' => $trend->id],
            [
                'intro_prompt'           => $validated['intro_prompt'] ?: null,
                'included_categories'    => !empty($validated['included_categories'])    ? $validated['included_categories']    : null,
                'included_finding_types' => !empty($validated['included_finding_types']) ? $validated['included_finding_types'] : null,
                'status'                 => $validated['status'],
            ]
        );

        return response()->json(['success' => true, 'message' => 'Configuration saved.', 'config_id' => $config->id]);
    }

    // ── Hotspot configure page ─────────────────────────────────────────────────

    public function configureHotspot(Request $request, string $h3): \Inertia\Response
    {
        $resolution = (int) $request->query('resolution', 8);

        $findings = HotspotFinding::where('h3_index', $h3)
            ->where('h3_resolution', $resolution)
            ->get();

        if ($findings->isEmpty()) {
            abort(404, 'No hotspot findings for this hexagon.');
        }

        $config = NewsArticleGenerationConfig::where('source_type', 'hotspot')
            ->where('h3_index', $h3)
            ->where('h3_resolution', $resolution)
            ->first();

        $city         = $this->inferCity($findings->first()->model_class);
        $locationName = $config?->location_name ?? '';

        // Deduplicate by model_class::column_name, keeping highest anomaly+trend
        $deduped = [];
        foreach ($findings as $f) {
            $key = $f->model_class . '::' . $f->column_name;
            if (!isset($deduped[$key]) || ($f->anomaly_count + $f->trend_count) > ($deduped[$key]->anomaly_count + $deduped[$key]->trend_count)) {
                $deduped[$key] = $f;
            }
        }

        $formattedFindings = [];
        foreach ($deduped as $key => $f) {
            $cats = [];
            foreach (array_merge($f->top_anomalies ?? [], $f->top_trends ?? []) as $item) {
                $sg = $item['secondary_group'] ?? null;
                if ($sg !== null && $sg !== '') $cats[$sg] = true;
            }
            ksort($cats);

            $formattedFindings[] = [
                'key'                  => $key,
                'model_class'          => $f->model_class,
                'type_label'           => class_exists($f->model_class)
                    ? $f->model_class::getHumanName() . ' — ' . Str::of($f->column_name)->replace('_', ' ')->title()
                    : class_basename($f->model_class) . ' — ' . $f->column_name,
                'anomaly_count'        => $f->anomaly_count,
                'trend_count'          => $f->trend_count,
                'available_categories' => array_keys($cats),
            ];
        }
        usort($formattedFindings, fn($a, $b) => ($b['anomaly_count'] + $b['trend_count']) - ($a['anomaly_count'] + $a['trend_count']));

        return Inertia::render('Admin/HotspotArticleConfig', [
            'h3Index'      => $h3,
            'h3Resolution' => $resolution,
            'city'         => $city,
            'locationName' => $locationName,
            'findings'     => $formattedFindings,
            'config' => $config ? [
                'id'                       => $config->id,
                'intro_prompt'             => $config->intro_prompt,
                'included_hotspot_reports' => $config->included_hotspot_reports,
                'location_name'            => $config->location_name,
                'status'                   => $config->status,
                'last_generated_at'        => $config->last_generated_at?->toISOString(),
            ] : null,
            'existingArticle' => $config?->lastArticle ? [
                'id'     => $config->lastArticle->id,
                'status' => $config->lastArticle->status,
                'title'  => $config->lastArticle->title,
                'slug'   => $config->lastArticle->slug,
            ] : null,
            'defaultPrompt' => AiAssistantController::defaultHotspotSystemPrompt(),
        ]);
    }

    public function saveHotspotConfig(Request $request, string $h3): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'h3_resolution'                           => 'required|integer',
            'location_name'                           => 'nullable|string|max:255',
            'city'                                    => 'nullable|string|max:100',
            'intro_prompt'                            => 'nullable|string|max:10000',
            'included_hotspot_reports'                => 'nullable|array',
            'included_hotspot_reports.*.key'          => 'required|string',
            'included_hotspot_reports.*.categories'   => 'nullable|array',
            'included_hotspot_reports.*.categories.*' => 'string',
            'status'                                  => 'required|in:draft,finalized,active_auto',
        ]);

        $config = NewsArticleGenerationConfig::updateOrCreate(
            [
                'source_type'   => 'hotspot',
                'h3_index'      => $h3,
                'h3_resolution' => $validated['h3_resolution'],
            ],
            [
                'location_name'            => $validated['location_name']             ?: null,
                'city'                     => $validated['city']                      ?: null,
                'intro_prompt'             => $validated['intro_prompt']              ?: null,
                'included_hotspot_reports' => !empty($validated['included_hotspot_reports'])
                    ? $validated['included_hotspot_reports'] : null,
                'status'                   => $validated['status'],
            ]
        );

        return response()->json(['success' => true, 'message' => 'Configuration saved.', 'config_id' => $config->id]);
    }

    // ── Generate from saved config ─────────────────────────────────────────────

    public function generateFromConfig(NewsArticleGenerationConfig $config): \Illuminate\Http\JsonResponse
    {
        if ($config->source_type === 'trend') {
            $trend = $config->trend;
            if (!$trend) {
                return response()->json(['success' => false, 'message' => 'Trend not found.'], 404);
            }

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

            GenerateNewsArticleJob::dispatch($article, true, $config);

            $config->update(['last_generated_at' => now(), 'last_news_article_id' => $article->id]);

            return response()->json(['success' => true, 'message' => 'Article generation queued.', 'article_id' => $article->id]);
        }

        // hotspot
        $findings = HotspotFinding::where('h3_index', $config->h3_index)
            ->where('h3_resolution', $config->h3_resolution)
            ->get();

        if ($findings->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No hotspot findings for this hexagon.'], 404);
        }

        $locationName   = $config->location_name ?? $config->h3_index;
        $hotspotContext = $this->buildHotspotContext($config->h3_index, $locationName, $findings, $config->included_hotspot_reports);

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

        GenerateHotspotArticleJob::dispatch($article, $config->h3_index, $locationName, $hotspotContext, $config);

        $config->update(['last_generated_at' => now(), 'last_news_article_id' => $article->id]);

        return response()->json(['success' => true, 'message' => 'Hotspot article generation queued.', 'article_id' => $article->id]);
    }

    // ── Token estimation ───────────────────────────────────────────────────────

    public function estimateTokensForConfig(NewsArticleGenerationConfig $config): \Illuminate\Http\JsonResponse
    {
        try {
            return response()->json($this->buildTokenEstimate($config));
        } catch (\Exception $e) {
            Log::error("[AdminNewsArticleController] Token estimation failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function estimateTokensPreview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'source_type'                  => 'required|in:trend,hotspot',
            'trend_id'                     => 'nullable|integer',
            'h3_index'                     => 'nullable|string',
            'h3_resolution'                => 'nullable|integer',
            'intro_prompt'                 => 'nullable|string|max:10000',
            'included_categories'          => 'nullable|array',
            'included_finding_types'       => 'nullable|array',
            'included_hotspot_reports'     => 'nullable|array',
        ]);

        $config = new NewsArticleGenerationConfig($request->only([
            'source_type', 'trend_id', 'h3_index', 'h3_resolution',
            'intro_prompt', 'included_categories', 'included_finding_types', 'included_hotspot_reports',
        ]));

        try {
            return response()->json($this->buildTokenEstimate($config));
        } catch (\Exception $e) {
            Log::error("[AdminNewsArticleController] Token estimation preview failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Legacy single-dispatch (keep for backwards compat) ────────────────────

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

        return response()->json(['success' => true, 'article_id' => $article->id, 'message' => 'Article generation queued.']);
    }

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
            return response()->json(['success' => false, 'message' => 'No hotspot data found.'], 404);
        }

        $hotspotContext = $this->buildHotspotContext($h3Index, $locationName, $findings);

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

        return response()->json(['success' => true, 'article_id' => $article->id, 'message' => 'Article generation queued.']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function inferCity(string $modelClass): string
    {
        $base = class_basename($modelClass);
        foreach (self::CITY_PREFIXES as $prefix => $city) {
            if (str_starts_with($base, $prefix)) return $city;
        }
        return 'Boston';
    }

    private function buildHotspotContext(string $h3Index, string $locationName, $findings, ?array $includedReports = null): array
    {
        // Build key map: 'ModelClass::col' => categories[]|null
        $keyMap = null;
        if ($includedReports !== null) {
            $keyMap = [];
            foreach ($includedReports as $r) {
                $key = is_array($r) ? ($r['key'] ?? null) : $r;
                if ($key) $keyMap[$key] = is_array($r) ? ($r['categories'] ?? null) : null;
            }
        }

        // Deduplicate by model_class::column_name, keeping highest anomaly+trend
        $deduped = [];
        foreach ($findings as $f) {
            $key = $f->model_class . '::' . $f->column_name;
            if (!isset($deduped[$key]) || ($f->anomaly_count + $f->trend_count) > ($deduped[$key]->anomaly_count + $deduped[$key]->trend_count)) {
                $deduped[$key] = $f;
            }
        }

        $reportTypes = [];
        foreach ($deduped as $key => $f) {
            if ($keyMap !== null && !array_key_exists($key, $keyMap)) continue;

            $topAnomalies = $f->top_anomalies ?? [];
            $topTrends    = $f->top_trends    ?? [];
            $categories   = $keyMap[$key] ?? null;

            if ($categories !== null) {
                $topAnomalies = array_values(array_filter($topAnomalies, fn($a) => in_array($a['secondary_group'] ?? '', $categories)));
                $topTrends    = array_values(array_filter($topTrends,    fn($t) => in_array($t['secondary_group'] ?? '', $categories)));
            }

            $reportTypes[] = [
                'model_class'   => $f->model_class,
                'type'          => class_exists($f->model_class)
                    ? $f->model_class::getHumanName() . ' — ' . Str::of($f->column_name)->replace('_', ' ')->title()
                    : class_basename($f->model_class) . ' — ' . $f->column_name,
                'anomalies'     => $f->anomaly_count,
                'trends'        => $f->trend_count,
                'top_anomalies' => $topAnomalies,
                'top_trends'    => $topTrends,
            ];
        }

        usort($reportTypes, fn($a, $b) => ($b['anomalies'] + $b['trends']) - ($a['anomalies'] + $a['trends']));

        return [
            'h3_index'        => $h3Index,
            'location'        => $locationName,
            'total_reports'   => count($reportTypes),
            'total_anomalies' => array_sum(array_column($reportTypes, 'anomalies')),
            'total_trends'    => array_sum(array_column($reportTypes, 'trends')),
            'report_types'    => $reportTypes,
        ];
    }

    private function buildTokenEstimate(NewsArticleGenerationConfig $config): array
    {
        if ($config->source_type === 'trend') {
            $trend = $config->trend ?? Trend::findOrFail($config->trend_id);
            $summary = Cache::get(TrendSummaryService::cacheKey($trend->job_id))
                ?? TrendSummaryService::computeAndCache(
                    $trend->job_id,
                    $trend->h3_resolution,
                    $trend->p_value_anomaly,
                    $trend->p_value_trend
                );
            $summary = $config->applyTrendFilters($summary);

            $sourceModel = $trend->sourceModel();
            $modelName   = $sourceModel ? $sourceModel->getHumanName() : class_basename($trend->model_class);
            $columnLabel = Str::of($trend->column_name)->replace('_', ' ')->title()->toString();
            $reportTitle = "Trend Analysis for {$modelName} by {$columnLabel}";

            // Must match GenerateNewsArticleJob::getReportContext() exactly
            $parameters = [
                'Analysis Type'             => 'Trend and Anomaly Detection',
                'H3 Resolution'             => $trend->h3_resolution,
                'Anomaly P-Value Threshold' => $trend->p_value_anomaly,
                'Trend P-Value Threshold'   => $trend->p_value_trend,
            ];

            $systemPrompt = $config->intro_prompt ?? AiAssistantController::defaultTrendSystemPrompt();

            // Must match AiAssistantController::generateNewsArticle() exactly
            $contextBlock = "The analysis was generated with the following parameters. Use them to provide context in the article:\n" . json_encode($parameters, JSON_PRETTY_PRINT);
            $userPrompt   = "Write a news article about the following report titled '{$reportTitle}'.\n\n{$contextBlock}\n\nHere is the data: " . json_encode($summary);
        } else {
            $findings = HotspotFinding::where('h3_index', $config->h3_index)
                ->where('h3_resolution', $config->h3_resolution)
                ->get();

            $locationName   = $config->location_name ?? $config->h3_index;
            $hotspotContext = $this->buildHotspotContext(
                $config->h3_index,
                $locationName,
                $findings,
                $config->included_hotspot_reports
            );

            $systemPrompt = $config->intro_prompt ?? AiAssistantController::defaultHotspotSystemPrompt();

            // Must match AiAssistantController::generateNewsArticleFromHexagon() exactly
            $userPrompt = "Write a news article about the following hotspot location: {$locationName} (H3 index: {$config->h3_index}).\n\nHotspot data:\n" . json_encode($hotspotContext, JSON_PRETTY_PRINT);
        }

        // Try the OpenAI token count API; fall back to character-based estimate (~4 chars/token)
        $tokens = 0;
        try {
            $tokens = AiAssistantController::estimateInputTokens('gpt-5', $systemPrompt, $userPrompt);
        } catch (\Exception $e) {
            Log::warning("[AdminNewsArticleController] Token estimation API failed, using char estimate: " . $e->getMessage());
        }
        if ($tokens === 0) {
            $tokens = (int) ceil((strlen($systemPrompt) + strlen($userPrompt)) / 4);
        }

        return [
            'input_tokens'  => $tokens,
            'system_prompt' => $systemPrompt,
            'user_prompt'   => $userPrompt,
        ];
    }

    private function buildTopHotspotsForMap(): array
    {
        $allFindings = HotspotFinding::all();
        $hexagons    = [];

        foreach ($allFindings as $f) {
            $city = $this->inferCity($f->model_class);
            $res  = (string) $f->h3_resolution;
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
        foreach ($hexagons as $city => $resByRes) {
            foreach ($resByRes as $res => $hexMap) {
                $flat = array_values($hexMap);
                usort($flat, fn($a, $b) =>
                    $b['report_count'] !== $a['report_count']
                        ? $b['report_count'] - $a['report_count']
                        : ($b['anomaly_count'] + $b['trend_count']) - ($a['anomaly_count'] + $a['trend_count'])
                );
                $result[$city][$res] = array_slice($flat, 0, 100);
            }
        }

        ksort($result);
        return $result;
    }
}
