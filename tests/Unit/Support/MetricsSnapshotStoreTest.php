<?php

namespace Tests\Unit\Support;

use App\Support\MetricsSnapshotStore;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MetricsSnapshotStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('metrics_snapshots');
        Schema::create('metrics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('snapshot_key')->unique();
            $table->json('data');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('metrics_snapshots');

        parent::tearDown();
    }

    public function test_it_upserts_and_reads_the_current_metrics_snapshot(): void
    {
        $store = new MetricsSnapshotStore();
        $generatedAt = Carbon::parse('2026-03-26 12:00:00');
        $lastUpdated = Carbon::parse('2026-03-19 23:08:00');

        $store->replaceCurrent([
            ['modelName' => 'Boston Crime', 'totalRecords' => 123],
        ], $lastUpdated, $generatedAt);

        $store->replaceCurrent([
            ['modelName' => 'Boston Crime', 'totalRecords' => 456],
        ], null, $generatedAt->copy()->addHour());

        $payload = $store->currentPayload();

        $this->assertSame([
            ['modelName' => 'Boston Crime', 'totalRecords' => 456],
        ], $payload['data']);
        $this->assertNull($payload['last_updated']);
        $this->assertSame('2026-03-26 13:00:00', $payload['generated_at']);
        $this->assertDatabaseCount('metrics_snapshots', 1);
        $this->assertDatabaseHas('metrics_snapshots', [
            'snapshot_key' => MetricsSnapshotStore::CURRENT_SNAPSHOT_KEY,
        ]);
    }

    public function test_it_falls_back_to_config_when_snapshot_table_is_unavailable(): void
    {
        Schema::dropIfExists('metrics_snapshots');

        Config::set('metrics.data', [
            ['modelName' => 'Legacy Metrics', 'totalRecords' => 12],
        ]);
        Config::set('metrics.last_updated', '2026-03-20 10:00:00');
        Config::set('metrics.generated_at', '2026-03-20 11:00:00');

        $payload = (new MetricsSnapshotStore())->currentPayload();

        $this->assertSame([
            ['modelName' => 'Legacy Metrics', 'totalRecords' => 12],
        ], $payload['data']);
        $this->assertSame('2026-03-20 10:00:00', $payload['last_updated']);
        $this->assertSame('2026-03-20 11:00:00', $payload['generated_at']);
    }
}
