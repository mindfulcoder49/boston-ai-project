<?php

namespace App\Console\Commands;

use App\Jobs\RunArtisanCommandJob;
use App\Support\IngestionDependencyHealth;
use App\Support\PipelineRunStore;
use Illuminate\Console\Command;

class DispatchDailyPipelineCommand extends Command
{
    protected $signature = 'app:dispatch-daily-pipeline
                            {--dry-run : Show what would happen without dispatching}
                            {--force : Dispatch even if dependency health has blocking issues}
                            {--skip-dependency-check : Skip dependency preflight checks}';

    protected $description = 'Dispatches the daily data pipeline onto the long-running admin queue.';

    public function __construct(
        private readonly PipelineRunStore $pipelineRunStore,
        private readonly IngestionDependencyHealth $dependencyHealth,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $activeRun = $this->pipelineRunStore->activeRun();
        if ($activeRun) {
            $this->warn('A pipeline run is already active. No new run will be dispatched.');
            $this->line('Active run ID: ' . ($activeRun['run_id'] ?? 'unknown'));
            return 0;
        }

        if (!$this->option('skip-dependency-check')) {
            $snapshot = $this->dependencyHealth->check();
            if (!empty($snapshot['blocking_issues']) && !$this->option('force')) {
                $this->error('Daily pipeline dispatch blocked by dependency health issues.');
                $this->line(json_encode($snapshot['blocking_issues'], JSON_PRETTY_PRINT));
                return 1;
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: the daily pipeline would be dispatched.');
            return 0;
        }

        RunArtisanCommandJob::dispatch('app:run-all-data-pipeline');
        $this->info('Daily pipeline dispatched to the long-running admin queue.');

        return 0;
    }
}
