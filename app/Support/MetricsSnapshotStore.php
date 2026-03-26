<?php

namespace App\Support;

use App\Models\MetricsSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetricsSnapshotStore
{
    public const CURRENT_SNAPSHOT_KEY = 'public_dashboard_metrics';

    private ?bool $metricsTableAvailable = null;

    public function currentPayload(): array
    {
        $snapshot = $this->currentSnapshot();

        if ($snapshot) {
            return [
                'data' => is_array($snapshot->data) ? $snapshot->data : [],
                'last_updated' => $snapshot->last_updated_at?->toDateTimeString(),
                'generated_at' => $snapshot->generated_at?->toDateTimeString(),
            ];
        }

        return [
            'data' => $this->fallbackMetricsData(),
            'last_updated' => config('metrics.last_updated'),
            'generated_at' => config('metrics.generated_at'),
        ];
    }

    public function lastUpdated(): ?string
    {
        return $this->currentPayload()['last_updated'] ?? null;
    }

    public function replaceCurrent(array $metricsData, ?Carbon $lastUpdatedAt, Carbon $generatedAt): MetricsSnapshot
    {
        return MetricsSnapshot::query()->updateOrCreate(
            ['snapshot_key' => self::CURRENT_SNAPSHOT_KEY],
            [
                'data' => $metricsData,
                'last_updated_at' => $lastUpdatedAt?->toDateTimeString(),
                'generated_at' => $generatedAt->toDateTimeString(),
            ]
        );
    }

    protected function currentSnapshot(): ?MetricsSnapshot
    {
        if (!$this->metricsTableExists()) {
            return null;
        }

        return MetricsSnapshot::query()
            ->where('snapshot_key', self::CURRENT_SNAPSHOT_KEY)
            ->first();
    }

    protected function fallbackMetricsData(): array
    {
        $metricsData = config('metrics.data', []);

        return is_array($metricsData) ? $metricsData : [];
    }

    protected function metricsTableExists(): bool
    {
        if (!is_null($this->metricsTableAvailable)) {
            return $this->metricsTableAvailable;
        }

        try {
            $this->metricsTableAvailable = Schema::hasTable((new MetricsSnapshot())->getTable());
        } catch (\Throwable $throwable) {
            Log::warning('Unable to verify metrics_snapshots table availability.', [
                'message' => $throwable->getMessage(),
            ]);
            $this->metricsTableAvailable = false;
        }

        return $this->metricsTableAvailable;
    }
}
