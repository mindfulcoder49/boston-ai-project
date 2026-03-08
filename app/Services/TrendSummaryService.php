<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\AnalysisReportSnapshot;

class TrendSummaryService
{
    public static function cacheKey(string $jobId): string
    {
        return "trend_summary_v5_{$jobId}";
    }

    public static function computeAndCache(string $jobId, int $h3Resolution, float $pAnomaly, float $pTrend): array
    {
        $result = static::compute($jobId, $h3Resolution, $pAnomaly, $pTrend);
        Cache::forever(static::cacheKey($jobId), $result);
        return $result;
    }

    public static function compute(string $jobId, int $h3Resolution, float $pAnomaly, float $pTrend): array
    {
        try {
            $data = AnalysisReportSnapshot::resolve($jobId, 'stage4_h3_anomaly.json');
            if (!$data || empty($data['results'])) {
                return ['status' => 'no_data', 'total_findings' => 0];
            }

            $anomalyCount      = 0;
            $trendCount        = 0;
            $affectedH3        = [];
            $categoryFindings  = [];
            $topAnomalies      = [];
            $topTrendsByWindow = [];

            foreach ($data['results'] as $row) {
                $secGroup = $row['secondary_group'] ?? 'Unknown';
                $h3Index  = $row["h3_index_{$h3Resolution}"] ?? null;
                $rowHasFindings = false;

                foreach ($row['anomaly_analysis'] ?? [] as $week) {
                    if (($week['anomaly_p_value'] ?? 1) < $pAnomaly) {
                        if (($row['historical_weekly_avg'] ?? 0) < 1 && ($week['count'] ?? 0) === 1) {
                            continue;
                        }
                        $anomalyCount++;
                        $rowHasFindings = true;
                        $topAnomalies[] = [
                            'h3_index'        => $h3Index,
                            'secondary_group' => $secGroup,
                            'week'            => $week['week'] ?? null,
                            'count'           => $week['count'] ?? null,
                            'z_score'         => isset($week['z_score']) ? round($week['z_score'], 1) : null,
                            'historical_avg'  => round($row['historical_weekly_avg'] ?? 0, 1),
                        ];
                    }
                }

                foreach ($row['trend_analysis'] ?? [] as $window => $trendData) {
                    if (($trendData['p_value'] ?? 1) < $pTrend) {
                        $trendCount++;
                        $rowHasFindings = true;
                        $topTrendsByWindow[$window][] = [
                            'h3_index'        => $h3Index,
                            'secondary_group' => $secGroup,
                            'slope'           => isset($trendData['slope']) ? round($trendData['slope'], 2) : null,
                            'p_value'         => $trendData['p_value'] ?? null,
                        ];
                    }
                }

                if ($rowHasFindings) {
                    if ($h3Index) {
                        $affectedH3[$h3Index] = true;
                    }
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
                'status'               => 'ok',
                'anomaly_count'        => $anomalyCount,
                'trend_count'          => $trendCount,
                'affected_h3_count'    => count($affectedH3),
                'top_categories'       => $topCategories,
                'total_findings'       => $anomalyCount + $trendCount,
                'top_anomalies'        => $topAnomalies,
                'top_trends_by_window' => $topTrendsByWindow,
            ];
        } catch (\Exception $e) {
            Log::error("[TrendSummaryService] compute({$jobId}): " . $e->getMessage());
            return ['status' => 'error', 'total_findings' => 0];
        }
    }
}
