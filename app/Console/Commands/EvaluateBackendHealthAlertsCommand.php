<?php

namespace App\Console\Commands;

use App\Support\BackendHealthAlertEvaluator;
use Illuminate\Console\Command;

class EvaluateBackendHealthAlertsCommand extends Command
{
    protected $signature = 'app:evaluate-backend-health-alerts
                            {--dry-run : Evaluate alerts without sending email notifications}
                            {--json : Output the evaluation payload as JSON}';

    protected $description = 'Evaluates backend health alert conditions and sends notifications for new severe incidents.';

    public function __construct(private readonly BackendHealthAlertEvaluator $alertEvaluator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->alertEvaluator->dispatchNotifications((bool) $this->option('dry-run'));

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info('Backend health alerts evaluated.');
        $this->line('Active alerts: ' . count($result['alerts'] ?? []));
        $this->line('New alerts: ' . count($result['new_alerts'] ?? []));

        if ($this->option('dry-run')) {
            $this->warn('Dry run mode: no emails were sent.');
        }

        return 0;
    }
}
