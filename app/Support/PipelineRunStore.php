<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
            $summary = $this->normalizePossiblyAbandonedRun(PipelineRunSummary::enrich($summary));
            $summary['freshness'] = PipelineRunSummary::freshness($summary, Carbon::now());

            return $summary;
        }

        $entry = $this->normalizePossiblyAbandonedRun($entry);
        $entry['freshness'] = PipelineRunSummary::freshness($entry, Carbon::now());

        return $entry;
    }

    public function latestSuccessfulRun(): ?array
    {
        foreach ($this->history() as $entry) {
            $summary = $this->loadSummaryFromHistoryEntry($entry);
            $candidate = $summary ? PipelineRunSummary::enrich($summary) : $entry;
            $candidate = $this->normalizePossiblyAbandonedRun($candidate);

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
            $candidate = $this->normalizePossiblyAbandonedRun($candidate);

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
            $candidate = $this->normalizePossiblyAbandonedRun($candidate);
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

    private function normalizePossiblyAbandonedRun(array $run): array
    {
        if (($run['status'] ?? null) !== 'running') {
            return $run;
        }

        $staleAfterMinutes = (int) config('backend_admin.pipeline_runs.stale_running_after_minutes', 120);
        if ($staleAfterMinutes < 1) {
            return $run;
        }

        $lastActivityAt = $this->lastActivityAt($run);
        if (!$lastActivityAt || $lastActivityAt->gte(Carbon::now()->subMinutes($staleAfterMinutes))) {
            return $run;
        }

        $message = sprintf(
            'Marked failed after %d minutes without pipeline activity; likely interrupted externally.',
            $lastActivityAt->diffInMinutes(Carbon::now())
        );

        $normalized = $this->markRunFailed($run, $lastActivityAt, $message);
        $this->persistRun($normalized);

        return $normalized;
    }

    private function lastActivityAt(array $run): ?Carbon
    {
        $candidates = [];

        foreach (['end_time', 'start_time'] as $key) {
            if (!empty($run[$key])) {
                $candidates[] = Carbon::parse((string) $run[$key]);
            }
        }

        foreach (($run['commands'] ?? []) as $command) {
            foreach (['end_time', 'start_time'] as $key) {
                if (!empty($command[$key])) {
                    $candidates[] = Carbon::parse((string) $command[$key]);
                }
            }

            $commandLogPath = $this->commandLogPath($run, $command);
            if ($commandLogPath && File::exists($commandLogPath)) {
                $candidates[] = Carbon::createFromTimestamp(File::lastModified($commandLogPath));
            }
        }

        $summaryPath = $this->resolveSummaryPath($run);
        if ($summaryPath && File::exists($summaryPath)) {
            $candidates[] = Carbon::createFromTimestamp(File::lastModified($summaryPath));
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn (Carbon $a, Carbon $b) => $a->getTimestamp() <=> $b->getTimestamp());

        return end($candidates) ?: null;
    }

    private function markRunFailed(array $run, Carbon $endedAt, string $message): array
    {
        $isoEndedAt = $endedAt->toIso8601String();
        $run['status'] = 'failed';
        $run['end_time'] = $run['end_time'] ?? $isoEndedAt;

        foreach (($run['commands'] ?? []) as $index => $command) {
            if (($command['status'] ?? null) !== 'running') {
                continue;
            }

            $startTime = !empty($command['start_time']) ? Carbon::parse((string) $command['start_time']) : $endedAt;
            $run['commands'][$index]['status'] = 'failed';
            $run['commands'][$index]['end_time'] = $isoEndedAt;
            $run['commands'][$index]['duration_seconds'] = $endedAt->diffInSeconds($startTime);
            $run['commands'][$index]['failure_excerpt'] = $run['commands'][$index]['failure_excerpt'] ?? Str::limit($message, 240);
        }

        foreach (($run['stages'] ?? []) as $index => $stage) {
            if (($stage['status'] ?? null) !== 'running') {
                continue;
            }

            $startTime = !empty($stage['start_time']) ? Carbon::parse((string) $stage['start_time']) : $endedAt;
            $run['stages'][$index]['status'] = 'failed';
            $run['stages'][$index]['end_time'] = $isoEndedAt;
            $run['stages'][$index]['duration_seconds'] = $endedAt->diffInSeconds($startTime);
        }

        return PipelineRunSummary::enrich($run);
    }

    private function persistRun(array $run): void
    {
        $summaryPath = $this->resolveSummaryPath($run);
        if ($summaryPath) {
            File::ensureDirectoryExists(dirname($summaryPath));
            File::put($summaryPath, json_encode($run, JSON_PRETTY_PRINT));
        }

        $history = [];
        $historyFilePath = $this->historyPath();
        if (File::exists($historyFilePath)) {
            $history = json_decode(File::get($historyFilePath), true) ?: [];
        }

        $historyEntry = PipelineRunSummary::historyEntry($run);
        $found = false;

        foreach ($history as $index => $entry) {
            if (($entry['run_id'] ?? null) === ($run['run_id'] ?? null)) {
                $history[$index] = $historyEntry;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $history[] = $historyEntry;
        }

        usort($history, fn (array $a, array $b) => strtotime((string) ($b['start_time'] ?? '')) <=> strtotime((string) ($a['start_time'] ?? '')));
        File::put($historyFilePath, json_encode(array_values($history), JSON_PRETTY_PRINT));
    }

    private function resolveSummaryPath(array $run): ?string
    {
        $summaryPath = (string) ($run['summary_file_path'] ?? '');
        if ($summaryPath === '') {
            $runId = (string) ($run['run_id'] ?? '');
            return $runId !== '' ? $this->summaryPath($runId) : null;
        }

        if (Str::startsWith($summaryPath, storage_path()) || Str::startsWith($summaryPath, '/')) {
            return $summaryPath;
        }

        return storage_path(ltrim($summaryPath, '/'));
    }

    private function commandLogPath(array $run, array $command): ?string
    {
        $logFile = (string) ($command['log_file'] ?? '');
        $runId = (string) ($run['run_id'] ?? '');

        if ($logFile === '' || $runId === '') {
            return null;
        }

        return $this->runDirectory($runId) . '/' . ltrim($logFile, '/');
    }
}
