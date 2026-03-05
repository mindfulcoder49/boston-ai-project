<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Trend;

class TrendsController extends Controller
{
    public function index()
    {
        $trends = Trend::orderBy('updated_at', 'desc')->get();
        $allAnalyses = [];

        foreach ($trends as $trend) {
            if (!class_exists($trend->model_class)) {
                continue;
            }

            $modelClass = $trend->model_class;
            $cacheKey = "trend_summary_v4_{$trend->job_id}";

            $summary = Cache::rememberForever($cacheKey, function () use ($trend) {
                return $this->computeTrendSummary($trend);
            });

            $allAnalyses[] = [
                'trend_id' => $trend->id,
                'model_name' => $modelClass::getHumanName(),
                'model_key' => Str::kebab(class_basename($modelClass)),
                'column_name' => $trend->column_name,
                'column_label' => Str::of($trend->column_name)->replace('_', ' ')->title(),
                'h3_resolution' => $trend->h3_resolution,
                'analysis_weeks_trend' => $trend->analysis_weeks_trend,
                'analysis_weeks_anomaly' => $trend->analysis_weeks_anomaly,
                'p_value_anomaly' => $trend->p_value_anomaly,
                'p_value_trend' => $trend->p_value_trend,
                'last_run' => $trend->updated_at->toDateString(),
                'summary' => $summary,
            ];
        }

        // Sort all analyses by total findings descending so reporters see the most significant first
        usort($allAnalyses, fn($a, $b) =>
            ($b['summary']['total_findings'] ?? 0) <=> ($a['summary']['total_findings'] ?? 0)
        );

        return Inertia::render('Trends/Index', [
            'analyses' => $allAnalyses,
        ]);
    }

    private function computeTrendSummary(Trend $trend): array
    {
        try {
            $s3 = Storage::disk('s3');
            $path = "{$trend->job_id}/stage4_h3_anomaly.json";

            if (!$s3->exists($path)) {
                return ['status' => 'no_data', 'total_findings' => 0];
            }

            $data = json_decode($s3->get($path), true);
            if (!$data || empty($data['results'])) {
                return ['status' => 'no_data', 'total_findings' => 0];
            }

            $pAnomaly = $trend->p_value_anomaly;
            $pTrend = $trend->p_value_trend;

            $anomalyCount = 0;
            $trendCount = 0;
            $affectedH3 = [];
            $categoryFindings = [];
            $topAnomalies = [];
            $topTrendsByWindow = [];

            foreach ($data['results'] as $row) {
                $secGroup = $row['secondary_group'] ?? 'Unknown';
                $h3Index = $row["h3_index_{$trend->h3_resolution}"] ?? null;
                $rowHasFindings = false;

                foreach ($row['anomaly_analysis'] ?? [] as $week) {
                    if (($week['anomaly_p_value'] ?? 1) < $pAnomaly) {
                        // Skip trivial anomalies (low-baseline single-incident spikes)
                        if (($row['historical_weekly_avg'] ?? 0) < 1 && ($week['count'] ?? 0) === 1) {
                            continue;
                        }
                        $anomalyCount++;
                        $rowHasFindings = true;
                        $topAnomalies[] = [
                            'secondary_group' => $secGroup,
                            'week' => $week['week'] ?? null,
                            'count' => $week['count'] ?? null,
                            'z_score' => isset($week['z_score']) ? round($week['z_score'], 1) : null,
                            'historical_avg' => round($row['historical_weekly_avg'] ?? 0, 1),
                        ];
                    }
                }

                foreach ($row['trend_analysis'] ?? [] as $window => $trendData) {
                    if (($trendData['p_value'] ?? 1) < $pTrend) {
                        $trendCount++;
                        $rowHasFindings = true;
                        $topTrendsByWindow[$window][] = [
                            'secondary_group' => $secGroup,
                            'slope' => isset($trendData['slope']) ? round($trendData['slope'], 2) : null,
                            'p_value' => $trendData['p_value'] ?? null,
                        ];
                    }
                }

                if ($rowHasFindings) {
                    if ($h3Index) $affectedH3[$h3Index] = true;
                    $categoryFindings[$secGroup] = ($categoryFindings[$secGroup] ?? 0) + 1;
                }
            }

            arsort($categoryFindings);
            $topCategories = array_slice(array_keys($categoryFindings), 0, 5);

            usort($topAnomalies, fn($a, $b) => ($b['z_score'] ?? 0) <=> ($a['z_score'] ?? 0));
            $topAnomalies = array_slice($topAnomalies, 0, 5);

            foreach ($topTrendsByWindow as &$windowTrends) {
                usort($windowTrends, fn($a, $b) => ($a['p_value'] ?? 1) <=> ($b['p_value'] ?? 1));
                $windowTrends = array_slice($windowTrends, 0, 5);
            }
            unset($windowTrends);
            uksort($topTrendsByWindow, fn($a, $b) =>
                (int) preg_replace('/[^0-9]/', '', $a) <=> (int) preg_replace('/[^0-9]/', '', $b)
            );

            return [
                'status' => 'ok',
                'anomaly_count' => $anomalyCount,
                'trend_count' => $trendCount,
                'affected_h3_count' => count($affectedH3),
                'top_categories' => $topCategories,
                'total_findings' => $anomalyCount + $trendCount,
                'top_anomalies' => $topAnomalies,
                'top_trends_by_window' => $topTrendsByWindow,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to compute trend summary for job {$trend->job_id}: " . $e->getMessage());
            return ['status' => 'error', 'total_findings' => 0];
        }
    }
}