<?php

namespace App\Console\Commands;

use App\Support\DataRetentionReview;
use Illuminate\Console\Command;

class ReviewDataRetentionCommand extends Command
{
    protected $signature = 'app:review-data-retention
                            {--group=* : Limit review to one or more retention groups}
                            {--sample=5 : Number of candidate rows to sample per table}
                            {--json : Emit JSON instead of human-readable tables}';

    protected $description = 'Preview database retention candidates without deleting anything.';

    public function handle(DataRetentionReview $review): int
    {
        $groups = (array) $this->option('group');
        $sample = max((int) $this->option('sample'), 1);
        $results = $review->review($groups, $sample);

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return 0;
        }

        if (empty($results)) {
            $this->warn('No retention rules matched the requested groups.');
            return 0;
        }

        $totalCandidates = 0;

        foreach ($results as $result) {
            $this->newLine();
            $this->info($result['name']);
            $this->line("Group: {$result['group']}");
            $this->line("Target: {$result['connection']}.{$result['table']}");
            $this->line("Retention window: {$result['retention_days']} days");
            $this->line("Cutoff: {$result['cutoff']}");

            if (($result['status'] ?? 'ok') !== 'ok') {
                $this->error("Review failed: {$result['error']}");
                continue;
            }

            $candidateCount = (int) ($result['candidate_count'] ?? 0);
            $totalCandidates += $candidateCount;

            $this->line("Would delete: {$candidateCount} row(s)");
            $this->line('Oldest candidate: ' . ($result['oldest_candidate'] ?? 'N/A'));
            $this->line('Newest candidate: ' . ($result['newest_candidate'] ?? 'N/A'));

            if (!empty($result['sample_rows'])) {
                $tableRows = collect($result['sample_rows'])
                    ->map(fn (array $row) => array_map(
                        fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value),
                        $row
                    ))
                    ->all();

                $this->table($result['sample_columns'], $tableRows);
            } else {
                $this->line('No sample rows matched this rule.');
            }
        }

        $this->newLine();
        $this->info("Dry run complete. Total candidates across reviewed tables: {$totalCandidates} row(s).");

        return 0;
    }
}
