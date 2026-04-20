<?php

namespace App\Console\Commands;

use App\Support\DataRetentionReview;
use Illuminate\Console\Command;

class ApplyDataRetentionCommand extends Command
{
    protected $signature = 'app:apply-data-retention
                            {--group=* : Limit deletion to one or more retention groups}
                            {--rule=* : Limit deletion to one or more retention rules by slug}
                            {--batch= : Delete batch size per query}
                            {--json : Emit JSON instead of human-readable tables}
                            {--force : Skip the interactive confirmation prompt}';

    protected $description = 'Apply database retention rules and delete matching rows.';

    public function handle(DataRetentionReview $review): int
    {
        $groups = (array) $this->option('group');
        $rules = (array) $this->option('rule');

        if ($this->noExplicitScope($groups, $rules)) {
            $this->error('Destructive retention requires at least one explicit --group or --rule.');
            $this->line('Run `php artisan app:review-data-retention --group=main_raw_source_tables` first, then apply a reviewed scope.');

            return 1;
        }

        $batchOption = $this->option('batch');
        $batchSize = max(
            $batchOption !== null ? (int) $batchOption : (int) config('data_retention.database_apply_batch_size', 1000),
            1
        );

        $preflight = $review->review($groups, $rules, 0);

        if (empty($preflight)) {
            $this->warn('No retention rules matched the requested groups or rules.');

            return 0;
        }

        $erroredRules = collect($preflight)->filter(fn (array $result) => ($result['status'] ?? 'ok') !== 'ok');

        if ($erroredRules->isNotEmpty()) {
            foreach ($erroredRules as $result) {
                $this->error("Preflight failed for {$result['slug']}: {$result['error']}");
            }

            return 1;
        }

        $totalCandidates = collect($preflight)->sum(fn (array $result) => (int) ($result['candidate_count'] ?? 0));

        if (!$this->option('json')) {
            $this->info('Retention preflight');
            $this->line('Batch size: ' . number_format($batchSize));
            $this->table(
                ['Rule', 'Target', 'Candidates', 'Approx Size'],
                collect($preflight)
                    ->map(fn (array $result) => [
                        $result['slug'],
                        $result['connection'] . '.' . $result['table'],
                        number_format((int) ($result['candidate_count'] ?? 0)),
                        $this->formatBytes($result['approx_candidate_bytes'] ?? null),
                    ])
                    ->all()
            );
            $this->line('Total candidates: ' . number_format($totalCandidates) . ' row(s)');
        }

        if ($totalCandidates === 0) {
            if ($this->option('json')) {
                $this->line(json_encode([], JSON_PRETTY_PRINT));
            } else {
                $this->info('No rows matched the reviewed retention scope.');
            }

            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->input->isInteractive()) {
                $this->error('Non-interactive retention apply requires --force.');

                return 1;
            }

            if (!$this->confirm('Delete the reviewed retention candidates now?')) {
                $this->info('Retention apply cancelled.');

                return 0;
            }
        }

        $results = $review->apply($groups, $rules, $batchSize);

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return collect($results)->contains(fn (array $result) => ($result['status'] ?? 'ok') !== 'ok') ? 1 : 0;
        }

        $this->newLine();
        $this->info('Retention apply complete');
        $this->table(
            ['Rule', 'Deleted', 'Remaining', 'Batches', 'Approx Freed', 'Status'],
            collect($results)
                ->map(fn (array $result) => [
                    $result['slug'],
                    number_format((int) ($result['deleted_count'] ?? 0)),
                    $result['remaining_candidate_count'] === null
                        ? 'N/A'
                        : number_format((int) $result['remaining_candidate_count']),
                    number_format((int) ($result['batches'] ?? 0)),
                    $this->formatBytes($result['approx_deleted_bytes'] ?? null),
                    $result['status'],
                ])
                ->all()
        );

        foreach ($results as $result) {
            if (($result['status'] ?? 'ok') !== 'ok') {
                $this->error("Retention apply failed for {$result['slug']}: {$result['error']}");
            }
        }

        return collect($results)->contains(fn (array $result) => ($result['status'] ?? 'ok') !== 'ok') ? 1 : 0;
    }

    private function noExplicitScope(array $groups, array $rules): bool
    {
        $normalizedGroups = collect($groups)
            ->filter(fn ($group) => is_string($group) && trim($group) !== '')
            ->all();
        $normalizedRules = collect($rules)
            ->filter(fn ($rule) => is_string($rule) && trim($rule) !== '')
            ->all();

        return empty($normalizedGroups) && empty($normalizedRules);
    }

    private function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return 'N/A';
        }

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        foreach ($units as $unit) {
            $value /= 1024;

            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, 2) . ' ' . $unit;
            }
        }

        return number_format($value, 2) . ' TB';
    }
}
