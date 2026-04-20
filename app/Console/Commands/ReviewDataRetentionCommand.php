<?php

namespace App\Console\Commands;

use App\Support\DataRetentionReview;
use Illuminate\Console\Command;

class ReviewDataRetentionCommand extends Command
{
    protected $signature = 'app:review-data-retention
                            {--group=* : Limit review to one or more retention groups}
                            {--rule=* : Limit review to one or more retention rules by slug}
                            {--sample=5 : Number of candidate rows to sample per table}
                            {--json : Emit JSON instead of human-readable tables}';

    protected $description = 'Preview database retention candidates without deleting anything.';

    public function handle(DataRetentionReview $review): int
    {
        $groups = (array) $this->option('group');
        $rules = (array) $this->option('rule');
        $sample = max((int) $this->option('sample'), 1);
        $results = $review->review($groups, $rules, $sample);

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return 0;
        }

        if (empty($results)) {
            $this->warn('No retention rules matched the requested groups or rules.');

            return 0;
        }

        $totalCandidates = 0;
        $totalApproxCandidateBytes = 0;
        $hasApproximateSize = false;

        foreach ($results as $result) {
            $this->newLine();
            $this->info($result['name']);
            $this->line("Rule: {$result['slug']}");
            $this->line("Group: {$result['group']}");
            $this->line("Target: {$result['connection']}.{$result['table']}");
            $this->line("Retention window: {$result['retention_days']} days");
            $this->line("Cutoff: {$result['cutoff']}");
            $this->line("Delete key: {$result['delete_key']}");

            if (($result['status'] ?? 'ok') !== 'ok') {
                $this->error("Review failed: {$result['error']}");
                continue;
            }

            $candidateCount = (int) ($result['candidate_count'] ?? 0);
            $candidatePct = $result['candidate_pct'];
            $totalCandidates += $candidateCount;

            if (is_int($result['approx_candidate_bytes'] ?? null)) {
                $hasApproximateSize = true;
                $totalApproxCandidateBytes += $result['approx_candidate_bytes'];
            }

            $this->line('Total rows: ' . number_format((int) ($result['total_rows'] ?? 0)));
            $this->line(sprintf(
                'Would delete: %s row(s)%s',
                number_format($candidateCount),
                $candidatePct !== null ? sprintf(' (%s%%)', number_format((float) $candidatePct, 2)) : ''
            ));
            $this->line('Approx table size: ' . $this->formatBytes($result['approx_table_bytes'] ?? null));
            $this->line('Approx reclaimable size: ' . $this->formatBytes($result['approx_candidate_bytes'] ?? null));
            $this->line('Oldest candidate: ' . ($result['oldest_candidate'] ?? 'N/A'));
            $this->line('Newest candidate: ' . ($result['newest_candidate'] ?? 'N/A'));

            if (!empty($result['notes'])) {
                $this->line('Notes: ' . $result['notes']);
            }

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
        $this->info('Dry run complete.');
        $this->line('Total candidates across reviewed tables: ' . number_format($totalCandidates) . ' row(s)');

        if ($hasApproximateSize) {
            $this->line('Approx reclaimable size across reviewed tables: ' . $this->formatBytes($totalApproxCandidateBytes));
        }

        return 0;
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
