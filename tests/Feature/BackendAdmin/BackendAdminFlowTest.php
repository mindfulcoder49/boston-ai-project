<?php

namespace Tests\Feature\BackendAdmin;

use App\Console\Commands\RunAdminLongWorkerCommand;
use App\Jobs\RunArtisanCommandJob;
use App\Mail\BackendHealthAlertMail;
use App\Support\BackendHealthSnapshot;
use App\Support\IngestionDependencyHealth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Response as InertiaResponse;
use Tests\TestCase;

class BackendAdminFlowTest extends TestCase
{
    private string $testRoot;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('route:clear');

        $this->testRoot = storage_path('framework/testing/backend-admin');
        File::deleteDirectory($this->testRoot);
        File::ensureDirectoryExists($this->testRoot);

        config([
            'backend_admin.pipeline_runs.root_path' => $this->testRoot . '/pipeline_runs',
            'backend_admin.pipeline_runs.history_path' => $this->testRoot . '/pipeline_runs_history.json',
            'backend_admin.long_running_connection' => 'database_long_running',
            'backend_admin.dependency_health.snapshot_path' => $this->testRoot . '/ingestion_dependency_health.json',
            'backend_admin.dependency_health.scraper_health_path' => 'health',
            'backend_admin.dependency_health.worker_heartbeat_path' => $this->testRoot . '/admin_long_worker_heartbeat.json',
            'backend_admin.alerts.state_path' => $this->testRoot . '/backend_health_alert_state.json',
            'backend_admin.alerts.email' => 'ops@example.com',
            'services.scraper_service.base_url' => 'https://scraper.example.test',
        ]);

        Http::fake([
            'https://scraper.example.test/health' => Http::response(['status' => 'ok'], 200),
        ]);
    }

    public function test_schedule_list_includes_backend_admin_runtime_commands(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('app:run-admin-long-worker')
            ->expectsOutputToContain('app:check-ingestion-dependencies')
            ->expectsOutputToContain('app:dispatch-daily-pipeline')
            ->expectsOutputToContain('app:evaluate-backend-health-alerts')
            ->assertExitCode(0);
    }

    public function test_admin_long_worker_command_records_scheduler_heartbeat(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:work', \Mockery::on(function (array $parameters) {
                return ($parameters['connection'] ?? null) === 'database_long_running'
                    && ($parameters['--stop-when-empty'] ?? false) === true
                    && ($parameters['--queue'] ?? null) === 'admin-long,default'
                    && ($parameters['--timeout'] ?? null) === 7200
                    && ($parameters['--tries'] ?? null) === 1;
            }))
            ->andReturn(0);

        $exitCode = app(RunAdminLongWorkerCommand::class)->handle();

        $this->assertSame(0, $exitCode);

        $heartbeat = json_decode(
            File::get(config('backend_admin.dependency_health.worker_heartbeat_path')),
            true
        );

        $this->assertSame('queue:work', $heartbeat['command'] ?? null);
        $this->assertSame('completed', $heartbeat['status'] ?? null);
        $this->assertSame('database_long_running', $heartbeat['connection'] ?? null);
        $this->assertSame('admin-long,default', $heartbeat['queue'] ?? null);
        $this->assertSame(7200, $heartbeat['timeout'] ?? null);
        $this->assertSame(1, $heartbeat['tries'] ?? null);
        $this->assertArrayHasKey('last_seen_at', $heartbeat);
    }

    public function test_dispatch_daily_pipeline_blocks_on_dependency_failure_and_dispatches_when_forced(): void
    {
        Queue::fake();
        config(['services.playwright.node_path' => '/definitely-missing-node']);

        $this->artisan('app:dispatch-daily-pipeline')
            ->expectsOutputToContain('Daily pipeline dispatch blocked by dependency health issues.')
            ->assertExitCode(1);

        Queue::assertNothingPushed();

        $this->artisan('app:dispatch-daily-pipeline --skip-dependency-check')
            ->expectsOutputToContain('Daily pipeline dispatched to the long-running admin queue.')
            ->assertExitCode(0);

        Queue::assertPushed(RunArtisanCommandJob::class, function (RunArtisanCommandJob $job) {
            return $job->queue === 'admin-long'
                && $job->connection === 'database_long_running';
        });
    }

    public function test_dispatch_daily_pipeline_ignores_stale_running_history_entries(): void
    {
        Queue::fake();

        $oldStart = Carbon::now()->subDays(10);
        $runId = $oldStart->format('YmdHis') . '_11111111-1111-1111-1111-111111111111';
        $runDir = config('backend_admin.pipeline_runs.root_path') . '/' . $runId;
        File::ensureDirectoryExists($runDir);
        File::put($runDir . '/run_summary.json', json_encode([
            'summary_version' => 2,
            'run_id' => $runId,
            'name' => 'app:run-all-data-pipeline',
            'start_time' => $oldStart->toIso8601String(),
            'end_time' => null,
            'status' => 'running',
            'summary_file_path' => $runDir . '/run_summary.json',
            'commands' => [],
            'stages' => [],
        ], JSON_PRETTY_PRINT));
        File::put(config('backend_admin.pipeline_runs.history_path'), json_encode([
            [
                'run_id' => $runId,
                'name' => 'app:run-all-data-pipeline',
                'start_time' => $oldStart->toIso8601String(),
                'end_time' => null,
                'status' => 'running',
                'summary_file_path' => str_replace(storage_path(), '', $runDir . '/run_summary.json'),
            ],
        ], JSON_PRETTY_PRINT));

        $this->artisan('app:dispatch-daily-pipeline --skip-dependency-check')
            ->expectsOutputToContain('Daily pipeline dispatched to the long-running admin queue.')
            ->assertExitCode(0);

        Queue::assertPushed(RunArtisanCommandJob::class, function (RunArtisanCommandJob $job) {
            return $job->queue === 'admin-long'
                && $job->connection === 'database_long_running';
        });
    }

    public function test_dependency_health_command_reports_healthy_snapshot(): void
    {
        File::put(
            config('backend_admin.dependency_health.worker_heartbeat_path'),
            json_encode([
                'last_seen_at' => Carbon::now()->toIso8601String(),
                'command' => 'app:run-all-data-pipeline',
                'status' => 'completed',
            ], JSON_PRETTY_PRINT)
        );

        $snapshot = app(IngestionDependencyHealth::class)->check();

        $this->assertSame('healthy', $snapshot['overall_status']);
        $this->assertSame('healthy', $snapshot['local_ingestion_runtime']['status']);
        $this->assertSame('healthy', $snapshot['scraper']['status']);
        $this->assertSame([], $snapshot['informational_issues']);
    }

    public function test_local_ingestion_runtime_probe_requires_available_node_runtime(): void
    {
        config(['services.playwright.node_path' => '/definitely-missing-node']);

        File::put(
            config('backend_admin.dependency_health.worker_heartbeat_path'),
            json_encode([
                'last_seen_at' => Carbon::now()->toIso8601String(),
                'command' => 'app:run-all-data-pipeline',
                'status' => 'completed',
            ], JSON_PRETTY_PRINT)
        );

        $snapshot = app(IngestionDependencyHealth::class)->check();

        $this->assertSame('failed', $snapshot['overall_status']);
        $this->assertSame('failed', $snapshot['local_ingestion_runtime']['status']);
        $this->assertSame(['local_ingestion_runtime_unavailable'], $snapshot['blocking_issues']);
    }

    public function test_missing_worker_heartbeat_is_a_warning_when_local_runtime_is_healthy(): void
    {
        $snapshot = app(IngestionDependencyHealth::class)->check();

        $this->assertSame('warning', $snapshot['overall_status']);
        $this->assertSame('healthy', $snapshot['local_ingestion_runtime']['status']);
        $this->assertSame('healthy', $snapshot['scraper']['status']);
        $this->assertSame('unknown', $snapshot['queue_worker']['status']);
        $this->assertSame(['worker_evidence_missing'], $snapshot['warnings']);
    }

    public function test_scraper_probe_failure_is_blocking(): void
    {
        File::put(
            config('backend_admin.dependency_health.worker_heartbeat_path'),
            json_encode([
                'last_seen_at' => Carbon::now()->toIso8601String(),
                'command' => 'app:run-all-data-pipeline',
                'status' => 'completed',
            ], JSON_PRETTY_PRINT)
        );

        config(['services.scraper_service.base_url' => 'https://scraper-down.example.test']);

        Http::fake([
            'https://scraper-down.example.test/health' => Http::response(['status' => 'down'], 503),
        ]);

        $snapshot = app(IngestionDependencyHealth::class)->check();

        $this->assertSame('failed', $snapshot['overall_status']);
        $this->assertSame('failed', $snapshot['scraper']['status']);
        $this->assertSame('https://scraper-down.example.test/health', $snapshot['scraper']['endpoint']);
        $this->assertSame(['scraper_unreachable'], $snapshot['blocking_issues']);
    }

    public function test_backend_health_dashboard_renders_for_admin(): void
    {
        Cache::put('h3_location_names_map', collect());

        $response = app(\App\Http\Controllers\AdminController::class)
            ->backendHealthIndex(app(BackendHealthSnapshot::class));

        $this->assertInstanceOf(InertiaResponse::class, $response);

        $reflection = new \ReflectionClass($response);
        $componentProperty = $reflection->getProperty('component');
        $componentProperty->setAccessible(true);

        $this->assertSame('Admin/BackendHealth', $componentProperty->getValue($response));
    }

    public function test_backend_health_alert_command_sends_email_for_new_critical_alert(): void
    {
        Mail::fake();
        $this->writeRunHistoryWithRepeatedFailure();

        $this->artisan('app:evaluate-backend-health-alerts')
            ->expectsOutputToContain('Backend health alerts evaluated.')
            ->expectsOutputToContain('Active alerts:')
            ->assertExitCode(0);

        Mail::assertSent(BackendHealthAlertMail::class, 1);

        $this->artisan('app:evaluate-backend-health-alerts')
            ->expectsOutputToContain('Backend health alerts evaluated.')
            ->expectsOutputToContain('New alerts: 0')
            ->assertExitCode(0);

        Mail::assertSent(BackendHealthAlertMail::class, 1);
    }

    private function writeRunHistoryWithRepeatedFailure(): void
    {
        $root = config('backend_admin.pipeline_runs.root_path');
        File::ensureDirectoryExists($root);

        $runs = [];
        foreach ([0, 1] as $index) {
            $started = Carbon::now()->subHours($index + 1);
            $ended = (clone $started)->addMinutes(12);
            $runId = $started->format('YmdHis') . '_00000000-0000-0000-0000-00000000000' . $index;
            $runDir = $root . '/' . $runId;
            File::ensureDirectoryExists($runDir);

            $summary = [
                'summary_version' => 2,
                'run_id' => $runId,
                'name' => 'app:run-all-data-pipeline',
                'start_time' => $started->toIso8601String(),
                'end_time' => $ended->toIso8601String(),
                'status' => 'failed',
                'summary_file_path' => $runDir . '/run_summary.json',
                'commands' => [
                    [
                        'command_name' => 'db:seed',
                        'stage_name' => 'Daily Aggregation',
                        'parameters' => ['--class' => 'DataPointSeeder'],
                        'start_time' => $started->toIso8601String(),
                        'end_time' => $ended->toIso8601String(),
                        'duration_seconds' => 720,
                        'status' => 'failed',
                        'log_file' => 'cmd_db-seed.log',
                        'failure_excerpt' => 'DataPointSeeder failed for testing.',
                        'summary_events' => [],
                    ],
                ],
                'stages' => [
                    [
                        'stage_name' => 'Daily Aggregation',
                        'start_time' => $started->toIso8601String(),
                        'end_time' => $ended->toIso8601String(),
                        'duration_seconds' => 720,
                        'status' => 'failed',
                    ],
                ],
            ];

            File::put($runDir . '/run_summary.json', json_encode($summary, JSON_PRETTY_PRINT));

            $runs[] = [
                'run_id' => $runId,
                'name' => 'app:run-all-data-pipeline',
                'start_time' => $started->toIso8601String(),
                'end_time' => $ended->toIso8601String(),
                'status' => 'failed',
                'summary_file_path' => str_replace(storage_path(), '', $runDir . '/run_summary.json'),
            ];
        }

        File::put(
            config('backend_admin.pipeline_runs.history_path'),
            json_encode($runs, JSON_PRETTY_PRINT)
        );
    }
}
