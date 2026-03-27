<?php

namespace App\Services;

use App\Models\AnalysisReportSnapshot;
use Illuminate\Support\Facades\Cache;

class StatisticalAnalysisViewService
{
    public static function summaryCacheKey(string $jobId): string
    {
        return "statistical_analysis_view_summary_v1_{$jobId}";
    }

    public static function groupDetailCacheKey(string $jobId, string $secondaryGroup): string
    {
        return 'statistical_analysis_view_group_v1_' . $jobId . '_' . sha1($secondaryGroup);
    }

    public static function summarize(string $jobId, ?array $reportData = null): ?array
    {
        $cacheKey = static::summaryCacheKey($jobId);

        if ($reportData === null) {
            return Cache::rememberForever($cacheKey, fn () => static::buildSummary(
                AnalysisReportSnapshot::resolve($jobId, 'stage4_h3_anomaly.json')
            ));
        }

        $summary = static::buildSummary($reportData);
        if ($summary !== null) {
            Cache::forever($cacheKey, $summary);
        }

        return $summary;
    }

    public static function groupDetail(string $jobId, string $secondaryGroup, ?array $reportData = null): ?array
    {
        $cacheKey = static::groupDetailCacheKey($jobId, $secondaryGroup);

        if ($reportData === null) {
            return Cache::rememberForever($cacheKey, fn () => static::buildGroupDetail(
                AnalysisReportSnapshot::resolve($jobId, 'stage4_h3_anomaly.json'),
                $secondaryGroup
            ));
        }

        $detail = static::buildGroupDetail($reportData, $secondaryGroup);
        if ($detail !== null) {
            Cache::forever($cacheKey, $detail);
        }

        return $detail;
    }

    private static function buildSummary(?array $reportData): ?array
    {
        if (!$reportData || empty($reportData['results'])) {
            return null;
        }

        $parameters = static::parameters($reportData);
        $h3Key = 'h3_index_' . $parameters['h3_resolution'];
        $groupCounts = [];
        $topAnomalies = [];
        $topTrendsByWindow = [];
        $anomalyCount = 0;
        $trendCount = 0;

        foreach ($reportData['results'] as $row) {
            $secondaryGroup = $row['secondary_group'] ?? 'Unknown';
            $groupCounts[$secondaryGroup] ??= [
                'anomaly_count' => 0,
                'trend_count' => 0,
                'total' => 0,
            ];

            foreach ($row['anomaly_analysis'] ?? [] as $week) {
                if (!static::isSignificantAnomaly($row, $week, $parameters['p_value_anomaly'])) {
                    continue;
                }

                $finding = [
                    'type' => 'Anomaly',
                    'details' => static::findingDetails($row, $h3Key),
                    'week_details' => static::anomalyWeekDetails($week),
                ];

                $topAnomalies[] = $finding;
                $groupCounts[$secondaryGroup]['anomaly_count']++;
                $groupCounts[$secondaryGroup]['total']++;
                $anomalyCount++;
            }

            foreach ($row['trend_analysis'] ?? [] as $window => $trendData) {
                if (!static::isSignificantTrend($trendData, $parameters['p_value_trend'])) {
                    continue;
                }

                $finding = [
                    'type' => 'Trend',
                    'details' => static::findingDetails($row, $h3Key),
                    'trend_details' => static::trendDetails($trendData),
                    'trend_window' => $window,
                ];

                $topTrendsByWindow[$window][] = $finding;
                $groupCounts[$secondaryGroup]['trend_count']++;
                $groupCounts[$secondaryGroup]['total']++;
                $trendCount++;
            }
        }

        usort(
            $topAnomalies,
            fn (array $a, array $b) => ($b['week_details']['z_score'] ?? 0) <=> ($a['week_details']['z_score'] ?? 0)
        );
        $topAnomalies = array_slice($topAnomalies, 0, 8);

        foreach ($topTrendsByWindow as &$windowFindings) {
            usort(
                $windowFindings,
                fn (array $a, array $b) => abs($b['trend_details']['slope'] ?? 0) <=> abs($a['trend_details']['slope'] ?? 0)
            );
            $windowFindings = array_slice($windowFindings, 0, 8);
        }
        unset($windowFindings);
        static::sortTrendWindows($topTrendsByWindow);

        uasort(
            $groupCounts,
            fn (array $a, array $b) => $b['total'] <=> $a['total']
        );

        return [
            'parameters' => $parameters,
            'total_findings' => $anomalyCount + $trendCount,
            'anomaly_count' => $anomalyCount,
            'trend_count' => $trendCount,
            'group_counts' => $groupCounts,
            'group_order' => array_keys($groupCounts),
            'top_anomalies' => $topAnomalies,
            'top_trends_by_window' => $topTrendsByWindow,
        ];
    }

    private static function buildGroupDetail(?array $reportData, string $secondaryGroup): ?array
    {
        if (!$reportData || empty($reportData['results'])) {
            return null;
        }

        $parameters = static::parameters($reportData);
        $h3Key = 'h3_index_' . $parameters['h3_resolution'];
        $filteredRows = array_values(array_filter(
            $reportData['results'],
            fn (array $row) => ($row['secondary_group'] ?? 'Unknown') === $secondaryGroup
        ));

        if (empty($filteredRows)) {
            return [
                'secondary_group' => $secondaryGroup,
                'parameters' => $parameters,
                'anomalies' => [],
                'trends_by_window' => [],
                'findings_by_h3' => [],
                'anomaly_cells' => [],
                'trend_cells_by_window' => [],
            ];
        }

        $anomalies = [];
        $trendsByWindow = [];
        $findingsByH3 = [];
        $anomalyCells = [];
        $trendCellsByWindow = [];

        foreach ($filteredRows as $row) {
            $details = static::findingDetails($row, $h3Key);
            $h3Index = $details[$h3Key] ?? null;

            foreach ($row['anomaly_analysis'] ?? [] as $week) {
                if (!static::isSignificantAnomaly($row, $week, $parameters['p_value_anomaly'])) {
                    continue;
                }

                $finding = [
                    'type' => 'Anomaly',
                    'details' => $details,
                    'week_details' => static::anomalyWeekDetails($week),
                ];

                $anomalies[] = $finding;

                if ($h3Index) {
                    $findingsByH3[$h3Index]['findingsBySecGroup'][$secondaryGroup][] = $finding;
                    $anomalyCells[$h3Index] ??= [
                        'h3_index' => $h3Index,
                        'lat' => $row['lat'] ?? null,
                        'lon' => $row['lon'] ?? null,
                        'anomalies' => [],
                    ];
                    $anomalyCells[$h3Index]['anomalies'][] = [
                        'secondary_group' => $secondaryGroup,
                        'week' => $finding['week_details']['week'],
                        'count' => $finding['week_details']['count'],
                        'anomaly_p_value' => $finding['week_details']['anomaly_p_value'],
                        'z_score' => $finding['week_details']['z_score'],
                    ];
                }
            }

            foreach ($row['trend_analysis'] ?? [] as $window => $trendData) {
                if (!static::isSignificantTrend($trendData, $parameters['p_value_trend'])) {
                    continue;
                }

                $finding = [
                    'type' => 'Trend',
                    'details' => $details,
                    'trend_details' => static::trendDetails($trendData),
                    'trend_window' => $window,
                ];

                $trendsByWindow[$window][] = $finding;

                if ($h3Index) {
                    $findingsByH3[$h3Index]['findingsBySecGroup'][$secondaryGroup][] = $finding;
                    $trendCellsByWindow[$window][$h3Index] ??= [
                        'h3_index' => $h3Index,
                        'lat' => $row['lat'] ?? null,
                        'lon' => $row['lon'] ?? null,
                        'total_slope' => 0,
                        'trends' => [],
                    ];
                    $trendCellsByWindow[$window][$h3Index]['total_slope'] += $finding['trend_details']['slope'] ?? 0;
                    $trendCellsByWindow[$window][$h3Index]['trends'][] = [
                        'secondary_group' => $secondaryGroup,
                        'description' => $finding['trend_details']['description'],
                        'p_value' => $finding['trend_details']['p_value'],
                        'slope' => $finding['trend_details']['slope'],
                    ];
                }
            }
        }

        usort(
            $anomalies,
            fn (array $a, array $b) => ($b['week_details']['z_score'] ?? 0) <=> ($a['week_details']['z_score'] ?? 0)
        );

        foreach ($trendsByWindow as &$windowFindings) {
            usort(
                $windowFindings,
                fn (array $a, array $b) => abs($b['trend_details']['slope'] ?? 0) <=> abs($a['trend_details']['slope'] ?? 0)
            );
        }
        unset($windowFindings);

        static::sortTrendWindows($trendsByWindow);
        static::sortTrendWindows($trendCellsByWindow);

        foreach ($trendCellsByWindow as &$cells) {
            $cells = array_values(array_map(function (array $cell) {
                $trendCount = count($cell['trends']);
                $cell['avg_slope'] = $trendCount > 0 ? $cell['total_slope'] / $trendCount : 0;
                unset($cell['total_slope']);
                return $cell;
            }, $cells));
        }
        unset($cells);

        ksort($findingsByH3);

        return [
            'secondary_group' => $secondaryGroup,
            'parameters' => $parameters,
            'anomalies' => $anomalies,
            'trends_by_window' => $trendsByWindow,
            'findings_by_h3' => $findingsByH3,
            'anomaly_cells' => array_values($anomalyCells),
            'trend_cells_by_window' => $trendCellsByWindow,
        ];
    }

    private static function parameters(array $reportData): array
    {
        $parameters = $reportData['parameters'] ?? [];

        return [
            'h3_resolution' => (int) ($parameters['h3_resolution'] ?? 8),
            'p_value_anomaly' => (float) ($parameters['p_value_anomaly'] ?? 0.05),
            'p_value_trend' => (float) ($parameters['p_value_trend'] ?? 0.05),
            'analysis_weeks_trend' => $parameters['analysis_weeks_trend'] ?? [],
            'analysis_weeks_anomaly' => (int) ($parameters['analysis_weeks_anomaly'] ?? 4),
        ];
    }

    private static function findingDetails(array $row, string $h3Key): array
    {
        return [
            'secondary_group' => $row['secondary_group'] ?? 'Unknown',
            $h3Key => $row[$h3Key] ?? null,
            'historical_weekly_avg' => $row['historical_weekly_avg'] ?? 0,
        ];
    }

    private static function anomalyWeekDetails(array $week): array
    {
        return [
            'week' => $week['week'] ?? null,
            'count' => $week['count'] ?? null,
            'z_score' => isset($week['z_score']) ? round((float) $week['z_score'], 2) : null,
            'anomaly_p_value' => $week['anomaly_p_value'] ?? null,
        ];
    }

    private static function trendDetails(array $trendData): array
    {
        return [
            'description' => $trendData['description'] ?? '',
            'p_value' => $trendData['p_value'] ?? null,
            'slope' => isset($trendData['slope']) ? round((float) $trendData['slope'], 2) : null,
        ];
    }

    private static function isSignificantAnomaly(array $row, array $week, float $threshold): bool
    {
        if (($week['anomaly_p_value'] ?? 1) >= $threshold) {
            return false;
        }

        return !(($row['historical_weekly_avg'] ?? 0) < 1 && ($week['count'] ?? 0) === 1);
    }

    private static function isSignificantTrend(array $trendData, float $threshold): bool
    {
        return ($trendData['p_value'] ?? 1) < $threshold;
    }

    private static function sortTrendWindows(array &$windows): void
    {
        uksort(
            $windows,
            fn (string $a, string $b) => (int) preg_replace('/[^0-9]/', '', $a) <=> (int) preg_replace('/[^0-9]/', '', $b)
        );
    }
}
