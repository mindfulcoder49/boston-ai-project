<?php

namespace App\Console\Commands;

use App\Support\IngestionDependencyHealth;
use Illuminate\Console\Command;

class CheckIngestionDependenciesCommand extends Command
{
    protected $signature = 'app:check-ingestion-dependencies
                            {--json : Output the snapshot as JSON}';

    protected $description = 'Checks scraper reachability, optional DNS sync evidence, and queue-worker evidence for daily ingestion dependencies.';

    public function __construct(private readonly IngestionDependencyHealth $dependencyHealth)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $snapshot = $this->dependencyHealth->check();

        if ($this->option('json')) {
            $this->line(json_encode($snapshot, JSON_PRETTY_PRINT));
            return $snapshot['overall_status'] === 'failed' ? 1 : 0;
        }

        $this->info('Ingestion dependency check complete.');
        $this->line('Overall status: ' . ($snapshot['overall_status'] ?? 'unknown'));
        $this->line('Scraper: ' . ($snapshot['scraper']['label'] ?? 'Unknown'));
        $this->line('DNS sync: ' . ($snapshot['dns_sync']['label'] ?? 'Unknown'));
        $this->line('Queue worker: ' . ($snapshot['queue_worker']['label'] ?? 'Unknown'));

        if (!empty($snapshot['blocking_issues'])) {
            $this->warn('Blocking issues: ' . implode(', ', $snapshot['blocking_issues']));
        }

        if (!empty($snapshot['warnings'])) {
            $this->warn('Warnings: ' . implode(', ', $snapshot['warnings']));
        }

        if (!empty($snapshot['informational_issues'])) {
            $this->line('Informational: ' . implode(', ', $snapshot['informational_issues']));
        }

        return $snapshot['overall_status'] === 'failed' ? 1 : 0;
    }
}
