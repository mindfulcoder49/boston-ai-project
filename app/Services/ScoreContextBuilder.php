<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ScoreContextBuilder
{
    public function build(
        ?array $currentScoreRow,
        Collection $scoredHexagons,
        array $comparisonH3Indices = [],
        ?array $parameters = null,
        ?int $resolution = null,
        string $source = 'stage6_artifact'
    ): ?array {
        if (!$currentScoreRow) {
            return null;
        }

        $currentScore = $this->extractScoreValue($currentScoreRow);
        if ($currentScore === null) {
            return null;
        }

        $normalizedHexagons = $scoredHexagons
            ->map(fn (array $row) => [
                'h3_index' => $this->extractH3Index($row),
                'score' => $this->extractScoreValue($row),
            ])
            ->filter(fn (array $row) => $row['h3_index'] && $row['score'] !== null)
            ->values();

        $sortedScores = $normalizedHexagons
            ->pluck('score')
            ->map(fn ($score) => (float) $score)
            ->sort()
            ->values();

        if ($sortedScores->isEmpty()) {
            return null;
        }

        $comparisonIndexSet = collect($comparisonH3Indices)
            ->filter(fn ($index) => is_string($index) && $index !== '')
            ->unique()
            ->values();

        $nearbyPeers = $normalizedHexagons
            ->filter(fn (array $row) => $comparisonIndexSet->contains($row['h3_index']))
            ->values();

        $driverRows = collect($this->extractComposition($currentScoreRow))
            ->map(function (array $driver) {
                $label = $driver['secondary_group']
                    ?? $driver['group']
                    ?? $driver['label']
                    ?? 'Unknown';
                $weightedScore = (float) ($driver['weighted_score'] ?? $driver['score'] ?? 0);

                return [
                    'label' => (string) $label,
                    'weighted_score' => $weightedScore,
                ];
            })
            ->filter(fn (array $driver) => $driver['weighted_score'] > 0)
            ->sortByDesc('weighted_score')
            ->values();

        $driverTotal = (float) $driverRows->sum('weighted_score');

        $topDrivers = $driverRows
            ->take(4)
            ->map(function (array $driver) use ($driverTotal) {
                return [
                    'label' => $driver['label'],
                    'weighted_score' => round($driver['weighted_score'], 2),
                    'share_percent' => $driverTotal > 0
                        ? round(($driver['weighted_score'] / $driverTotal) * 100, 1)
                        : null,
                ];
            })
            ->values()
            ->all();

        $percentile = $this->calculatePercentile($sortedScores, $currentScore);
        $band = $this->bandForPercentile($percentile);

        $nearbyScores = $nearbyPeers
            ->pluck('score')
            ->map(fn ($score) => (float) $score)
            ->sort()
            ->values();

        $nearbyMedian = $this->quantile($nearbyScores, 0.5);

        return [
            'score' => round($currentScore, 2),
            'percentile' => $percentile,
            'band' => $band,
            'distribution' => [
                'count' => $sortedScores->count(),
                'min' => round((float) $sortedScores->first(), 2),
                'median' => $this->quantile($sortedScores, 0.5),
                'max' => round((float) $sortedScores->last(), 2),
                'lower_quartile' => $this->quantile($sortedScores, 0.25),
                'upper_quartile' => $this->quantile($sortedScores, 0.75),
            ],
            'nearby_peers' => [
                'count' => $nearbyScores->count(),
                'available' => $nearbyScores->isNotEmpty(),
                'median' => $nearbyMedian,
                'min' => $nearbyScores->isNotEmpty() ? round((float) $nearbyScores->first(), 2) : null,
                'max' => $nearbyScores->isNotEmpty() ? round((float) $nearbyScores->last(), 2) : null,
                'current_vs_median' => $nearbyMedian !== null
                    ? round($currentScore - $nearbyMedian, 2)
                    : null,
            ],
            'top_drivers' => $topDrivers,
            'methodology' => [
                'source' => $source,
                'label' => $source === 'stage4_fallback'
                    ? 'Preview score estimate'
                    : 'Historical neighborhood score',
                'analysis_period_weeks' => isset($parameters['analysis_period_weeks'])
                    ? (int) $parameters['analysis_period_weeks']
                    : null,
                'aggregation_method' => $parameters['h3_aggregation_method'] ?? null,
                'resolution' => $resolution,
            ],
        ];
    }

    protected function extractH3Index(array $row): ?string
    {
        $index = $row['h3_index'] ?? data_get($row, 'score_details.h3_index');

        return is_string($index) && $index !== '' ? $index : null;
    }

    protected function extractScoreValue(array $row): ?float
    {
        $score = data_get($row, 'score_details.score', $row['score'] ?? null);

        return is_numeric($score) ? (float) $score : null;
    }

    protected function extractComposition(array $row): array
    {
        $composition = data_get($row, 'score_details.score_composition', $row['score_composition'] ?? []);

        return is_array($composition) ? $composition : [];
    }

    protected function calculatePercentile(Collection $sortedScores, float $currentScore): float
    {
        $lessThan = $sortedScores->filter(fn (float $score) => $score < $currentScore)->count();
        $equalTo = $sortedScores->filter(fn (float $score) => $score === $currentScore)->count();
        $count = max($sortedScores->count(), 1);

        return round((($lessThan + ($equalTo / 2)) / $count) * 100, 1);
    }

    protected function bandForPercentile(float $percentile): array
    {
        if ($percentile <= 25) {
            return [
                'slug' => 'lower-relative-concern',
                'label' => 'Lower relative concern',
                'description' => 'This address-area scores below most scored areas in the same city or region.',
            ];
        }

        if ($percentile >= 75) {
            return [
                'slug' => 'higher-relative-concern',
                'label' => 'Higher relative concern',
                'description' => 'This address-area scores above most scored areas in the same city or region.',
            ];
        }

        return [
            'slug' => 'typical-relative-concern',
            'label' => 'Typical relative concern',
            'description' => 'This address-area lands near the middle of the local score distribution.',
        ];
    }

    protected function quantile(Collection $sortedScores, float $quantile): ?float
    {
        if ($sortedScores->isEmpty()) {
            return null;
        }

        $values = $sortedScores->values()->all();
        $count = count($values);

        if ($count === 1) {
            return round((float) $values[0], 2);
        }

        $position = ($count - 1) * $quantile;
        $lowerIndex = (int) floor($position);
        $upperIndex = (int) ceil($position);

        if ($lowerIndex === $upperIndex) {
            return round((float) $values[$lowerIndex], 2);
        }

        $weight = $position - $lowerIndex;
        $interpolated = ((1 - $weight) * (float) $values[$lowerIndex]) + ($weight * (float) $values[$upperIndex]);

        return round($interpolated, 2);
    }
}
