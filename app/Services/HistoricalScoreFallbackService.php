<?php

namespace App\Services;

use App\Models\AnalysisReportSnapshot;
use Illuminate\Support\Collection;

class HistoricalScoreFallbackService
{
    public function scoreForLocation(
        string $modelClass,
        string $sourceJobId,
        string $h3Index,
        ?string $columnName = null
    ): ?array {
        $stage4Data = AnalysisReportSnapshot::resolve($sourceJobId, 'stage4_h3_anomaly.json');
        if (!$stage4Data) {
            return null;
        }

        $parameters = $stage4Data['parameters'] ?? data_get($stage4Data, 'config.parameters.stage4_h3_anomaly', []);
        $resolvedColumn = $columnName ?? $this->stage6ConfigForModel($modelClass)['column'] ?? ($parameters['column_name'] ?? null);
        $artifactColumn = $parameters['column_name'] ?? null;

        if ($resolvedColumn && $artifactColumn && $resolvedColumn !== $artifactColumn) {
            return null;
        }

        $resolution = (int) ($parameters['h3_resolution'] ?? 8);
        $h3Key = "h3_index_{$resolution}";
        $matchingRows = collect($stage4Data['results'] ?? [])
            ->filter(fn (array $row) => ($row[$h3Key] ?? null) === $h3Index)
            ->values();

        $config = $this->stage6ConfigForModel($modelClass);
        $weights = $config['weights'] ?? [];
        $defaultWeight = (float) ($config['default_weight'] ?? config('analysis_schedule.stage6.default_weight', 1.0));

        $composition = $matchingRows
            ->groupBy(fn (array $row) => (string) ($row['secondary_group'] ?? 'Unknown'))
            ->map(function (Collection $rows, string $secondaryGroup) use ($weights, $defaultWeight) {
                $averageWeeklyCount = (float) $rows->sum(function (array $row) {
                    return (float) ($row['historical_weekly_avg'] ?? $row['avg_weekly_count'] ?? 0.0);
                });
                $weight = (float) ($weights[$secondaryGroup] ?? $defaultWeight);

                return [
                    'secondary_group' => $secondaryGroup,
                    'avg_weekly_count' => $averageWeeklyCount,
                    'weight' => $weight,
                    'weighted_score' => $averageWeeklyCount * $weight,
                ];
            })
            ->sortByDesc('weighted_score')
            ->values();

        return [
            'h3_index' => $h3Index,
            'score_details' => [
                'h3_index' => $h3Index,
                'score' => round((float) $composition->sum('weighted_score'), 4),
                'score_composition' => $composition->all(),
                'source' => 'stage4_fallback',
            ],
            'analysis_details' => $matchingRows->all(),
            'analysis_parameters' => $parameters,
        ];
    }

    protected function stage6ConfigForModel(string $modelClass): array
    {
        foreach (config('analysis_schedule.stage6.jobs', []) as $job) {
            if (($job['model'] ?? null) === $modelClass) {
                return $job;
            }
        }

        return [];
    }
}
