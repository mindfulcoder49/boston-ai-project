<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class PipelineRunStore
{
    public function history(): array
    {
        $historyFilePath = $this->historyPath();

        if (!File::exists($historyFilePath)) {
            return [];
        }

        $history = json_decode(File::get($historyFilePath), true) ?: [];

        usort($history, fn (array $a, array $b) => strtotime((string) ($b['start_time'] ?? '')) <=> strtotime((string) ($a['start_time'] ?? '')));

        return array_values($history);
    }

    public function latestHistoryEntry(): ?array
    {
        return $this->history()[0] ?? null;
    }

    public function latestRun(): ?array
    {
        $entry = $this->latestHistoryEntry();

        if (!$entry) {
            return null;
        }

        $summary = $this->loadSummaryFromHistoryEntry($entry);

        if ($summary) {
            $summary = PipelineRunSummary::enrich($summary);
            $summary['freshness'] = PipelineRunSummary::freshness($summary, Carbon::now());

            return $summary;
        }

        $entry['freshness'] = PipelineRunSummary::freshness($entry, Carbon::now());

        return $entry;
    }

    public function latestSuccessfulRun(): ?array
    {
        foreach ($this->history() as $entry) {
            $summary = $this->loadSummaryFromHistoryEntry($entry);
            $candidate = $summary ? PipelineRunSummary::enrich($summary) : $entry;

            if (($candidate['status'] ?? null) === 'completed') {
                $candidate['freshness'] = PipelineRunSummary::freshness($candidate, Carbon::now());

                return $candidate;
            }
        }

        return null;
    }

    public function activeRun(): ?array
    {
        $maxAgeHours = (int) config('backend_admin.daily_pipeline.active_run_max_age_hours', 6);
        $cutoff = Carbon::now()->subHours($maxAgeHours);

        foreach ($this->history() as $entry) {
            $summary = $this->loadSummaryFromHistoryEntry($entry);
            $candidate = $summary ? PipelineRunSummary::enrich($summary) : $entry;

            $referenceTime = $candidate['start_time'] ?? null;

            if (
                ($candidate['status'] ?? null) === 'running'
                && $referenceTime
                && Carbon::parse($referenceTime)->gte($cutoff)
            ) {
                return $candidate;
            }
        }

        return null;
    }

    public function recentRuns(int $limit = 5): array
    {
        $runs = [];

        foreach (array_slice($this->history(), 0, $limit) as $entry) {
            $summary = $this->loadSummaryFromHistoryEntry($entry);
            $candidate = $summary ? PipelineRunSummary::enrich($summary) : $entry;
            $candidate['freshness'] = PipelineRunSummary::freshness($candidate, Carbon::now());
            $runs[] = $candidate;
        }

        return $runs;
    }

    public function loadSummaryFromHistoryEntry(array $run): ?array
    {
        $relativePath = ltrim((string) ($run['summary_file_path'] ?? ''), '/');
        $summaryFilePath = $relativePath !== ''
            ? storage_path($relativePath)
            : $this->summaryPath((string) ($run['run_id'] ?? ''));

        if (!File::exists($summaryFilePath)) {
            return null;
        }

        $decoded = json_decode(File::get($summaryFilePath), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function summaryPath(string $runId): string
    {
        return rtrim((string) config('backend_admin.pipeline_runs.root_path', storage_path('logs/pipeline_runs')), '/')
            . '/'
            . $runId
            . '/run_summary.json';
    }

    public function runDirectory(string $runId): string
    {
        return rtrim((string) config('backend_admin.pipeline_runs.root_path', storage_path('logs/pipeline_runs')), '/')
            . '/'
            . $runId;
    }

    private function historyPath(): string
    {
        return (string) config('backend_admin.pipeline_runs.history_path', storage_path('logs/pipeline_runs_history.json'));
    }
}
