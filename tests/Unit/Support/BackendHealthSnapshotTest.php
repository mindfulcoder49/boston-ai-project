<?php

namespace Tests\Unit\Support;

use App\Support\BackendHealthSnapshot;
use Carbon\Carbon;
use Tests\TestCase;

class BackendHealthSnapshotTest extends TestCase
{
    public function test_it_flags_future_metrics_timestamps_as_warning(): void
    {
        $snapshot = new class extends BackendHealthSnapshot
        {
            public function exposedMetricsFreshnessSnapshot(?string $metricsLastUpdated, ?Carbon $now = null): ?array
            {
                return $this->metricsFreshnessSnapshot($metricsLastUpdated, $now);
            }
        };

        $freshness = $snapshot->exposedMetricsFreshnessSnapshot(
            '2033-04-08 06:00:00',
            Carbon::parse('2026-03-26 12:00:00')
        );

        $this->assertSame('warning', $freshness['status']);
        $this->assertSame('Timestamp is in the future', $freshness['age_human']);
        $this->assertSame(0, $freshness['age_hours']);
    }
}
