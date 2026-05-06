<?php

namespace App\Support;

use App\Services\NodePdfMarkdownConverter;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Throwable;

class IngestionDependencyHealth
{
    public function check(): array
    {
        $snapshot = [
            'checked_at' => Carbon::now()->toIso8601String(),
            'local_ingestion_runtime' => $this->checkLocalIngestionRuntime(),
            'queue_worker' => AdminLongWorkerHeartbeat::freshness(Carbon::now()),
        ];

        $blockingIssues = [];
        $warnings = [];

        if (($snapshot['local_ingestion_runtime']['status'] ?? null) === 'failed') {
            $blockingIssues[] = 'local_ingestion_runtime_unavailable';
        }

        if (($snapshot['queue_worker']['status'] ?? null) === 'warning') {
            $warnings[] = 'worker_evidence_stale';
        }

        if (($snapshot['queue_worker']['status'] ?? null) === 'unknown') {
            $warnings[] = 'worker_evidence_missing';
        }

        $snapshot['blocking_issues'] = $blockingIssues;
        $snapshot['warnings'] = $warnings;
        $snapshot['informational_issues'] = [];
        $snapshot['overall_status'] = !empty($blockingIssues)
            ? 'failed'
            : (!empty($warnings) ? 'warning' : 'healthy');

        $this->store($snapshot);

        return $snapshot;
    }

    public function latest(): ?array
    {
        $path = (string) config('backend_admin.dependency_health.snapshot_path');

        if (!File::exists($path)) {
            return null;
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function store(array $snapshot): void
    {
        $path = (string) config('backend_admin.dependency_health.snapshot_path');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($snapshot, JSON_PRETTY_PRINT));
    }

    private function checkLocalIngestionRuntime(): array
    {
        try {
            $runtime = (new NodePdfMarkdownConverter())->describeRuntime();

            return [
                'status' => 'healthy',
                'label' => 'Local ingestion runtime available',
                'node_binary' => $runtime['node_binary'] ?? null,
                'node_version' => $runtime['node_version'] ?? null,
                'extractor_script_path' => $runtime['extractor_script_path'] ?? null,
                'pdf_parse_version' => $runtime['pdf_parse_version'] ?? null,
                'message' => 'The local Everett PDF extraction runtime is available.',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'failed',
                'label' => 'Local ingestion runtime unavailable',
                'node_binary' => null,
                'node_version' => null,
                'extractor_script_path' => null,
                'pdf_parse_version' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
