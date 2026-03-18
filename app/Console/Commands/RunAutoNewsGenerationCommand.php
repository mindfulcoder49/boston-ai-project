<?php

namespace App\Console\Commands;

use App\Jobs\GenerateHotspotArticleJob;
use App\Jobs\GenerateNewsArticleJob;
use App\Models\HotspotFinding;
use App\Models\NewsArticle;
use App\Models\NewsArticleGenerationConfig;
use App\Models\Trend;
use App\Services\TrendSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RunAutoNewsGenerationCommand extends Command
{
    protected $signature = 'app:run-auto-news-generation {--dry-run : List configs without dispatching}';
    protected $description = 'Dispatch news article generation jobs for all active_auto configs.';

    public function handle(): int
    {
        $configs = NewsArticleGenerationConfig::where('status', 'active_auto')->get();

        if ($configs->isEmpty()) {
            $this->info('No active_auto configs found.');
            return 0;
        }

        $dryRun = $this->option('dry-run');
        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$configs->count()} active_auto config(s).");

        foreach ($configs as $config) {
            $label = $config->source_type === 'trend'
                ? "Trend #{$config->trend_id}"
                : "Hotspot {$config->h3_index} res{$config->h3_resolution}";

            if ($dryRun) {
                $this->line("  Would dispatch: {$label}");
                continue;
            }

            try {
                if ($config->source_type === 'trend') {
                    $this->dispatchTrend($config);
                } else {
                    $this->dispatchHotspot($config);
                }
                $this->line("  Dispatched: {$label}");
            } catch (\Exception $e) {
                $this->error("  Failed {$label}: " . $e->getMessage());
            }
        }

        return 0;
    }

    private function dispatchTrend(NewsArticleGenerationConfig $config): void
    {
        $trend = $config->trend ?? Trend::findOrFail($config->trend_id);
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
    }

    private function dispatchHotspot(NewsArticleGenerationConfig $config): void
    {
        $findings = HotspotFinding::where('h3_index', $config->h3_index)
            ->where('h3_resolution', $config->h3_resolution)
            ->get();

        if ($findings->isEmpty()) {
            throw new \Exception("No findings for {$config->h3_index}");
        }

        $locationName = $config->location_name ?? $config->h3_index;
        $included     = $config->included_hotspot_reports;

        // Build key map: 'ModelClass::col' => categories[]|null
        $keyMap = null;
        if ($included !== null) {
            $keyMap = [];
            foreach ($included as $r) {
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

        $hotspotContext = [
            'h3_index'        => $config->h3_index,
            'location'        => $locationName,
            'total_reports'   => count($reportTypes),
            'total_anomalies' => array_sum(array_column($reportTypes, 'anomalies')),
            'total_trends'    => array_sum(array_column($reportTypes, 'trends')),
            'report_types'    => $reportTypes,
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

        GenerateHotspotArticleJob::dispatch($article, $config->h3_index, $locationName, $hotspotContext, $config);
        $config->update(['last_generated_at' => now(), 'last_news_article_id' => $article->id]);
    }
}
