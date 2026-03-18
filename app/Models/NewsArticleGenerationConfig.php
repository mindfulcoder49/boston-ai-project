<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticleGenerationConfig extends Model
{
    protected $fillable = [
        'source_type',
        'trend_id',
        'h3_index',
        'h3_resolution',
        'location_name',
        'city',
        'intro_prompt',
        'included_categories',
        'included_finding_types',
        'included_hotspot_reports',
        'status',
        'last_generated_at',
        'last_news_article_id',
    ];

    protected $casts = [
        'included_categories'     => 'array',
        'included_finding_types'  => 'array',
        'included_hotspot_reports'=> 'array',
        'last_generated_at'       => 'datetime',
    ];

    public function trend(): BelongsTo
    {
        return $this->belongsTo(Trend::class);
    }

    public function lastArticle(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'last_news_article_id');
    }

    /**
     * Apply category and finding-type filters to a trend summary array.
     * Returns the filtered summary ready to be passed to the AI.
     */
    public function applyTrendFilters(array $summary): array
    {
        $categories   = $this->included_categories;
        $findingTypes = $this->included_finding_types;

        if ($categories !== null) {
            $summary['top_anomalies'] = array_values(array_filter(
                $summary['top_anomalies'] ?? [],
                fn($a) => in_array($a['secondary_group'] ?? '', $categories)
            ));
            $summary['top_trends_by_window'] = array_map(
                fn($trends) => array_values(array_filter(
                    $trends,
                    fn($t) => in_array($t['secondary_group'] ?? '', $categories)
                )),
                $summary['top_trends_by_window'] ?? []
            );
        }

        if ($findingTypes !== null) {
            if (!in_array('anomaly', $findingTypes)) {
                $summary['top_anomalies'] = [];
                $summary['anomaly_count'] = 0;
            }
            $summary['top_trends_by_window'] = array_filter(
                $summary['top_trends_by_window'] ?? [],
                fn($key) => in_array($key, $findingTypes),
                ARRAY_FILTER_USE_KEY
            );
            $summary['trend_count'] = array_sum(
                array_map(fn($t) => count($t), $summary['top_trends_by_window'])
            );
        }

        return $summary;
    }

    /**
     * Filter hotspot context to only included report types, with per-report category filtering.
     * Expects included_hotspot_reports as [{key, categories}] where key = 'ModelClass::col'.
     */
    public function applyHotspotFilters(array $hotspotContext): array
    {
        $reports = $this->included_hotspot_reports;
        if ($reports === null) return $hotspotContext;

        // Build key map: 'ModelClass::col' => categories[]|null
        $keyMap = [];
        foreach ($reports as $r) {
            $key = is_array($r) ? ($r['key'] ?? null) : $r;
            if ($key) $keyMap[$key] = is_array($r) ? ($r['categories'] ?? null) : null;
        }

        $filtered = [];
        foreach ($hotspotContext['report_types'] ?? [] as $rt) {
            $key = ($rt['model_class'] ?? '') . '::' . ($rt['column_name'] ?? '');
            // Also try just model_class for backwards compat
            if (!array_key_exists($key, $keyMap)) {
                $key = $rt['model_class'] ?? '';
            }
            if (!array_key_exists($key, $keyMap)) continue;

            $categories = $keyMap[$key];
            if ($categories !== null) {
                $rt['top_anomalies'] = array_values(array_filter($rt['top_anomalies'] ?? [], fn($a) => in_array($a['secondary_group'] ?? '', $categories)));
                $rt['top_trends']    = array_values(array_filter($rt['top_trends']    ?? [], fn($t) => in_array($t['secondary_group'] ?? '', $categories)));
            }
            $filtered[] = $rt;
        }

        $hotspotContext['report_types']    = $filtered;
        $hotspotContext['total_reports']   = count($filtered);
        $hotspotContext['total_anomalies'] = array_sum(array_column($filtered, 'anomalies'));
        $hotspotContext['total_trends']    = array_sum(array_column($filtered, 'trends'));

        return $hotspotContext;
    }
}
