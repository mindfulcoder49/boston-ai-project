<?php

namespace App\Support;

use Carbon\Carbon;

class BackendHealthSnapshot
{
    public function __construct(
        private readonly PipelineRunStore $pipelineRunStore = new PipelineRunStore(),
        private readonly IngestionDependencyHealth $dependencyHealth = new IngestionDependencyHealth(),
        private readonly BackendHealthAlertEvaluator $alertEvaluator = new BackendHealthAlertEvaluator(),
        private readonly MetricsSnapshotStore $metricsSnapshotStore = new MetricsSnapshotStore(),
    ) {
    }

    public function build(): array
    {
        $latestRun = $this->pipelineRunStore->latestRun();
        $latestSuccessfulRun = $this->pipelineRunStore->latestSuccessfulRun();
        $dependencySnapshot = $this->dependencyHealth->latest();

        $metricsFreshness = $this->metricsFreshnessSnapshot($this->metricsSnapshotStore->lastUpdated());

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'latestRun' => $latestRun,
            'latestSuccessfulRun' => $latestSuccessfulRun,
            'dependencyHealth' => $dependencySnapshot,
            'metricsFreshness' => $metricsFreshness,
            'storagePressure' => BackendStorageSnapshot::build(),
            'alerts' => $this->alertEvaluator->evaluate(),
            'topAlert' => $this->alertEvaluator->topAlert(),
        ];
    }

    protected function metricsFreshnessSnapshot(?string $metricsLastUpdated, ?Carbon $now = null): ?array
    {
        if (!$metricsLastUpdated) {
            return null;
        }

        $now ??= Carbon::now();

        try {
            $metricsTimestamp = Carbon::parse($metricsLastUpdated);
        } catch (\Throwable $throwable) {
            return [
                'last_updated' => $metricsLastUpdated,
                'age_hours' => null,
                'age_human' => 'Timestamp could not be parsed',
                'status' => 'warning',
            ];
        }

        if ($metricsTimestamp->gt($now)) {
            return [
                'last_updated' => $metricsTimestamp->toIso8601String(),
                'age_hours' => 0,
                'age_human' => 'Timestamp is in the future',
                'status' => 'warning',
            ];
        }

        $ageHours = $metricsTimestamp->diffInHours($now);

        return [
            'last_updated' => $metricsTimestamp->toIso8601String(),
            'age_hours' => $ageHours,
            'age_human' => $metricsTimestamp->diffForHumans($now),
            'status' => $ageHours < 24 ? 'healthy' : 'warning',
        ];
    }
}
