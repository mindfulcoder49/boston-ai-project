<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Mail\BackendHealthAlertMail;

class BackendHealthAlertEvaluator
{
    public function __construct(
        private readonly PipelineRunStore $pipelineRunStore = new PipelineRunStore(),
        private readonly IngestionDependencyHealth $dependencyHealth = new IngestionDependencyHealth(),
    ) {
    }

    public function evaluate(): array
    {
        $alerts = [];
        $latestRun = $this->pipelineRunStore->latestRun() ?? [];
        $latestSuccessfulRun = $this->pipelineRunStore->latestSuccessfulRun();
        $recentRuns = $this->pipelineRunStore->recentRuns(2);
        $dependencySnapshot = $this->dependencyHealth->latest();
        $windowHours = (int) config('backend_admin.alerts.success_window_hours', 24);

        if (!$latestSuccessfulRun || $this->runReferenceTime($latestSuccessfulRun)?->lt(Carbon::now()->subHours($windowHours))) {
            $alerts[] = $this->makeAlert(
                'critical',
                'no-successful-run-24h',
                'No successful pipeline run in 24 hours',
                'The backend has not recorded a successful pipeline run within the configured freshness window.'
            );
        }

        if (($latestRun['core_freshness']['components']['data_point_seeder']['status'] ?? null) === 'failed') {
            $alerts[] = $this->makeAlert(
                'critical',
                'data-point-seeder-failed:' . ($latestRun['run_id'] ?? 'unknown'),
                'DataPointSeeder failed',
                'The latest run did not complete the shared data point aggregation step.'
            );
        }

        if (count($recentRuns) >= 2) {
            $latestFailure = $recentRuns[0]['first_failed_command']['command_name'] ?? null;
            $previousFailure = $recentRuns[1]['first_failed_command']['command_name'] ?? null;

            if (
                ($recentRuns[0]['status'] ?? null) === 'failed'
                && ($recentRuns[1]['status'] ?? null) === 'failed'
                && $latestFailure
                && $latestFailure === $previousFailure
            ) {
                $alerts[] = $this->makeAlert(
                    'critical',
                    'repeated-command-failure:' . $latestFailure,
                    'Same command failed twice in a row',
                    "The command {$latestFailure} failed in the two most recent pipeline runs."
                );
            }
        }

        $scraperDependentCommands = [
            'app:download-boston-dataset-via-scraper',
            'app:download-everett-pdf-markdown',
        ];

        $latestFailedCommand = $latestRun['first_failed_command']['command_name'] ?? null;
        if ($latestFailedCommand && in_array($latestFailedCommand, $scraperDependentCommands, true)) {
            $alerts[] = $this->makeAlert(
                'critical',
                'scraper-command-failure:' . $latestFailedCommand,
                'Scraper-dependent acquisition failed',
                "The latest run failed on scraper-dependent acquisition command {$latestFailedCommand}."
            );
        }

        if (($dependencySnapshot['scraper']['status'] ?? null) === 'failed') {
            $alerts[] = $this->makeAlert(
                'critical',
                'scraper-health-failed',
                'Scraper dependency is unreachable',
                $dependencySnapshot['scraper']['message'] ?? 'The scraper service did not respond to the health probe.'
            );
        }

        usort($alerts, fn (array $a, array $b) => $this->severityScore($b['severity']) <=> $this->severityScore($a['severity']));

        return array_values($alerts);
    }

    public function topAlert(): ?array
    {
        return $this->evaluate()[0] ?? null;
    }

    public function dispatchNotifications(bool $dryRun = false): array
    {
        $alerts = $this->evaluate();
        $state = $this->loadState();
        $previousSignatures = collect($state['active_signatures'] ?? [])->flip();
        $currentSignatures = collect($alerts)->pluck('signature')->all();
        $newAlerts = array_values(array_filter($alerts, fn (array $alert) => !$previousSignatures->has($alert['signature'])));

        if (!$dryRun && !empty($newAlerts)) {
            $recipient = config('backend_admin.alerts.email');
            if ($recipient) {
                Mail::to($recipient)->send(new BackendHealthAlertMail($alerts));
            }
        }

        if (!$dryRun) {
            $newState = [
                'evaluated_at' => Carbon::now()->toIso8601String(),
                'active_signatures' => $currentSignatures,
                'alerts' => $alerts,
            ];

            $this->storeState($newState);
        }

        return [
            'alerts' => $alerts,
            'new_alerts' => $newAlerts,
            'dry_run' => $dryRun,
        ];
    }

    public function latestStoredState(): ?array
    {
        $path = config('backend_admin.alerts.state_path');

        if (!File::exists($path)) {
            return null;
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function loadState(): array
    {
        return $this->latestStoredState() ?? [];
    }

    private function storeState(array $state): void
    {
        $path = config('backend_admin.alerts.state_path');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($state, JSON_PRETTY_PRINT));
    }

    private function makeAlert(string $severity, string $signature, string $title, string $message): array
    {
        return [
            'severity' => $severity,
            'signature' => $signature,
            'title' => $title,
            'message' => $message,
        ];
    }

    private function severityScore(string $severity): int
    {
        return match ($severity) {
            'critical' => 3,
            'warning' => 2,
            default => 1,
        };
    }

    private function runReferenceTime(array $run): ?Carbon
    {
        $reference = $run['end_time'] ?? $run['start_time'] ?? null;

        return $reference ? Carbon::parse($reference) : null;
    }
}
