<?php

namespace Tests\Unit;

use App\Support\PipelineRunStore;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PipelineRunStoreTest extends TestCase
{
    private string $testRoot;
    private string $historyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot = storage_path('framework/testing/pipeline-run-store');
        $this->historyPath = $this->testRoot . '/pipeline_runs_history.json';

        File::deleteDirectory($this->testRoot);
        File::ensureDirectoryExists($this->testRoot);

        Config::set('backend_admin.pipeline_runs.root_path', $this->testRoot . '/runs');
        Config::set('backend_admin.pipeline_runs.history_path', $this->historyPath);
        Config::set('backend_admin.pipeline_runs.stale_running_after_minutes', 5);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testRoot);
        parent::tearDown();
    }

    public function test_it_marks_stale_running_runs_as_failed_when_activity_is_old(): void
    {
        $runId = 'stale-run';
        $runDir = Config::get('backend_admin.pipeline_runs.root_path') . '/' . $runId;
        File::ensureDirectoryExists($runDir);

        $startedAt = Carbon::now()->subMinutes(30);
        $lastActivityAt = Carbon::now()->subMinutes(20);

        $summary = [
            'summary_version' => 2,
            'run_id' => $runId,
            'name' => 'app:run-all-data-pipeline',
            'start_time' => $startedAt->toIso8601String(),
            'end_time' => null,
            'status' => 'running',
            'commands' => [
                [
                    'command_name' => 'app:cache-metrics-data',
                    'stage_name' => 'Post-Seeding Aggregation & Caching',
                    'parameters' => [],
                    'start_time' => $startedAt->copy()->addMinutes(10)->toIso8601String(),
                    'end_time' => null,
                    'duration_seconds' => null,
                    'status' => 'running',
                    'log_file' => 'cmd_appcache-metrics-data.log',
                    'failure_excerpt' => null,
                    'summary_events' => [],
                    'latest_summary_event' => null,
                ],
            ],
            'stages' => [
                [
                    'stage_name' => 'Post-Seeding Aggregation & Caching',
                    'start_time' => $startedAt->copy()->addMinutes(10)->toIso8601String(),
                    'end_time' => null,
                    'duration_seconds' => null,
                    'status' => 'running',
                ],
            ],
            'summary_file_path' => $runDir . '/run_summary.json',
        ];

        File::put($runDir . '/run_summary.json', json_encode($summary, JSON_PRETTY_PRINT));
        File::put($runDir . '/cmd_appcache-metrics-data.log', "Starting to calculate metrics\n");
        touch($runDir . '/run_summary.json', $lastActivityAt->timestamp);
        touch($runDir . '/cmd_appcache-metrics-data.log', $lastActivityAt->timestamp);

        File::put($this->historyPath, json_encode([
            [
                'run_id' => $runId,
                'name' => 'app:run-all-data-pipeline',
                'start_time' => $startedAt->toIso8601String(),
                'status' => 'running',
                'summary_file_path' => str_replace(storage_path(), '', $runDir . '/run_summary.json'),
            ],
        ], JSON_PRETTY_PRINT));

        $run = app(PipelineRunStore::class)->latestRun();

        $this->assertSame('failed', $run['status']);
        $this->assertSame('failed', $run['commands'][0]['status']);
        $this->assertSame('failed', $run['stages'][0]['status']);

        $history = json_decode(File::get($this->historyPath), true);
        $this->assertSame('failed', $history[0]['status']);
    }

    public function test_it_keeps_recent_running_runs_running(): void
    {
        $runId = 'fresh-run';
        $runDir = Config::get('backend_admin.pipeline_runs.root_path') . '/' . $runId;
        File::ensureDirectoryExists($runDir);

        $startedAt = Carbon::now()->subMinutes(3);

        $summary = [
            'summary_version' => 2,
            'run_id' => $runId,
            'name' => 'app:run-all-data-pipeline',
            'start_time' => $startedAt->toIso8601String(),
            'end_time' => null,
            'status' => 'running',
            'commands' => [],
            'stages' => [],
            'summary_file_path' => $runDir . '/run_summary.json',
        ];

        File::put($runDir . '/run_summary.json', json_encode($summary, JSON_PRETTY_PRINT));
        touch($runDir . '/run_summary.json', Carbon::now()->subMinute()->timestamp);

        File::put($this->historyPath, json_encode([
            [
                'run_id' => $runId,
                'name' => 'app:run-all-data-pipeline',
                'start_time' => $startedAt->toIso8601String(),
                'status' => 'running',
                'summary_file_path' => str_replace(storage_path(), '', $runDir . '/run_summary.json'),
            ],
        ], JSON_PRETTY_PRINT));

        $run = app(PipelineRunStore::class)->latestRun();

        $this->assertSame('running', $run['status']);
    }
}
