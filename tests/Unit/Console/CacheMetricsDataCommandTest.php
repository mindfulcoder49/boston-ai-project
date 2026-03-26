<?php

namespace Tests\Unit\Console;

use App\Console\Commands\CacheMetricsDataCommand;
use App\Models\ChicagoCrime;
use App\Models\ConstructionOffHour;
use App\Models\MontgomeryCountyMdCrime;
use App\Models\NewYork311;
use App\Models\SanFranciscoCrime;
use App\Models\SeattleCrime;
use Carbon\Carbon;
use Tests\TestCase;

class CacheMetricsDataCommandTest extends TestCase
{
    public function test_it_derives_metric_model_coverage_from_configured_cities(): void
    {
        $command = new class extends CacheMetricsDataCommand
        {
            public function exposedMetricModelClasses(): array
            {
                return $this->metricModelClasses();
            }
        };

        $models = $command->exposedMetricModelClasses();

        $this->assertContains(ConstructionOffHour::class, $models);
        $this->assertContains(ChicagoCrime::class, $models);
        $this->assertContains(SanFranciscoCrime::class, $models);
        $this->assertContains(SeattleCrime::class, $models);
        $this->assertContains(MontgomeryCountyMdCrime::class, $models);
        $this->assertContains(NewYork311::class, $models);
        $this->assertSame($models, array_values(array_unique($models)));
    }

    public function test_it_clamps_future_metric_timestamps(): void
    {
        $command = new class extends CacheMetricsDataCommand
        {
            public function exposedNormalizeMetricTimestamp(mixed $rawDate, Carbon $capturedAt, string $modelName): ?Carbon
            {
                return $this->normalizeMetricTimestamp($rawDate, $capturedAt, $modelName);
            }
        };

        $capturedAt = Carbon::parse('2026-03-26 12:00:00');
        $expectedPastTimestamp = Carbon::parse('2026-03-19 08:15:00');

        $futureTimestamp = $command->exposedNormalizeMetricTimestamp('2033-04-08 06:00:00', $capturedAt, 'Construction Off Hours');
        $pastTimestamp = $command->exposedNormalizeMetricTimestamp('2026-03-19 08:15:00', $capturedAt, 'Everett Crime');

        $this->assertSame($capturedAt->toIso8601String(), $futureTimestamp?->toIso8601String());
        $this->assertSame($expectedPastTimestamp->toIso8601String(), $pastTimestamp?->toIso8601String());
        $this->assertNull($command->exposedNormalizeMetricTimestamp(null, $capturedAt, 'Unknown'));
    }
}
