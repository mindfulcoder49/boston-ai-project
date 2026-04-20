<?php

namespace Tests\Feature\Commands;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataRetentionCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys=ON');

        Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00'));

        Schema::create('retention_events', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable();
            $table->dateTime('event_at')->nullable();
            $table->timestamps();
        });

        Schema::create('retention_other', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable();
            $table->dateTime('event_at')->nullable();
            $table->timestamps();
        });

        config()->set('data_retention.database_apply_batch_size', 2);
        config()->set('data_retention.automation', [
            'enabled' => false,
            'day_of_week' => 0,
            'time' => '03:30',
            'timezone' => 'America/New_York',
            'groups' => ['main_raw_source_tables'],
            'batch_size' => 2,
        ]);
        config()->set('data_retention.database_rules', [
            [
                'slug' => 'retention-events',
                'name' => 'Retention Events',
                'group' => 'main_raw_source_tables',
                'connection' => 'sqlite',
                'table' => 'retention_events',
                'date_field' => 'event_at',
                'retention_days' => 365,
                'delete_key' => 'id',
                'sample_columns' => ['id', 'external_id', 'event_at'],
            ],
            [
                'slug' => 'retention-other',
                'name' => 'Retention Other',
                'group' => 'secondary_tables',
                'connection' => 'sqlite',
                'table' => 'retention_other',
                'date_field' => 'event_at',
                'retention_days' => 365,
                'delete_key' => 'id',
                'sample_columns' => ['id', 'external_id', 'event_at'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('retention_other');
        Schema::dropIfExists('retention_events');

        parent::tearDown();
    }

    public function test_review_command_limits_results_by_group_and_reports_candidates_in_json(): void
    {
        $this->seedRetentionRows();

        Artisan::call('app:review-data-retention', [
            '--group' => ['main_raw_source_tables'],
            '--sample' => 1,
            '--json' => true,
        ]);

        $results = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $results);
        $this->assertSame('retention-events', $results[0]['slug']);
        $this->assertSame(3, $results[0]['total_rows']);
        $this->assertSame(2, $results[0]['candidate_count']);
        $this->assertSame('2025-04-20 12:00:00', $results[0]['cutoff']);
        $this->assertCount(1, $results[0]['sample_rows']);
    }

    public function test_apply_command_requires_explicit_scope(): void
    {
        $this->seedRetentionRows();

        $this->artisan('app:apply-data-retention', ['--force' => true])
            ->expectsOutputToContain('Destructive retention requires at least one explicit --group or --rule.')
            ->assertExitCode(1);

        $this->assertSame(3, DB::table('retention_events')->count());
        $this->assertSame(2, DB::table('retention_other')->count());
    }

    public function test_apply_command_deletes_only_reviewed_rows_in_batches_and_outputs_json(): void
    {
        $this->seedRetentionRows();

        Artisan::call('app:apply-data-retention', [
            '--group' => ['main_raw_source_tables'],
            '--batch' => 1,
            '--force' => true,
            '--json' => true,
        ]);

        $results = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $results);
        $this->assertSame('retention-events', $results[0]['slug']);
        $this->assertSame(2, $results[0]['deleted_count']);
        $this->assertSame(2, $results[0]['batches']);
        $this->assertSame(0, $results[0]['remaining_candidate_count']);
        $this->assertSame(1, DB::table('retention_events')->count());
        $this->assertSame('new-event', DB::table('retention_events')->value('external_id'));
        $this->assertSame(2, DB::table('retention_other')->count());
    }

    public function test_schedule_list_includes_retention_apply_command_when_automation_is_enabled(): void
    {
        config()->set('data_retention.automation.enabled', true);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('app:apply-data-retention')
            ->assertExitCode(0);
    }

    private function seedRetentionRows(): void
    {
        $now = Carbon::now();

        DB::table('retention_events')->insert([
            [
                'external_id' => 'old-event-a',
                'event_at' => $now->copy()->subDays(500)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'external_id' => 'old-event-b',
                'event_at' => $now->copy()->subDays(400)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'external_id' => 'new-event',
                'event_at' => $now->copy()->subDays(30)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
        ]);

        DB::table('retention_other')->insert([
            [
                'external_id' => 'old-other',
                'event_at' => $now->copy()->subDays(600)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
            [
                'external_id' => 'new-other',
                'event_at' => $now->copy()->subDays(20)->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ],
        ]);
    }
}
