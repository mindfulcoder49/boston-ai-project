<?php

namespace App\Support;

use Carbon\Carbon;

class BackendHealthSnapshot
{
    public function __construct(
        private readonly PipelineRunStore $pipelineRunStore = new PipelineRunStore(),
        private readonly IngestionDependencyHealth $dependencyHealth = new IngestionDependencyHealth(),
        private readonly BackendHealthAlertEvaluator $alertEvaluator = new BackendHealthAlertEvaluator(),
    ) {
    }

    public function build(): array
    {
        $latestRun = $this->pipelineRunStore->latestRun();
        $latestSuccessfulRun = $this->pipelineRunStore->latestSuccessfulRun();
        $dependencySnapshot = $this->dependencyHealth->latest();

        $metricsLastUpdated = config('metrics.last_updated');
        $metricsFreshness = null;
        if ($metricsLastUpdated) {
            $metricsTimestamp = Carbon::parse($metricsLastUpdated);
            $metricsFreshness = [
                'last_updated' => $metricsTimestamp->toIso8601String(),
                'age_hours' => $metricsTimestamp->diffInHours(Carbon::now()),
                'age_human' => $metricsTimestamp->diffForHumans(Carbon::now()),
                'status' => $metricsTimestamp->diffInHours(Carbon::now()) < 24 ? 'healthy' : 'warning',
            ];
        }

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
}
