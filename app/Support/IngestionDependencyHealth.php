<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IngestionDependencyHealth
{
    public function check(): array
    {
        $snapshot = [
            'checked_at' => Carbon::now()->toIso8601String(),
            'scraper' => $this->checkScraper(),
            'dns_sync' => $this->checkDnsSync(),
            'queue_worker' => AdminLongWorkerHeartbeat::freshness(Carbon::now()),
        ];

        $blockingIssues = [];
        $warnings = [];
        $informationalIssues = [];

        if (($snapshot['scraper']['status'] ?? null) === 'failed') {
            $blockingIssues[] = 'scraper_unreachable';
        }

        if (($snapshot['dns_sync']['status'] ?? null) === 'failed') {
            $informationalIssues[] = 'dns_sync_mismatch';
        }

        if (($snapshot['dns_sync']['status'] ?? null) === 'unknown') {
            $informationalIssues[] = 'dns_sync_unknown';
        }

        if (($snapshot['queue_worker']['status'] ?? null) === 'warning') {
            $warnings[] = 'worker_evidence_stale';
        }

        if (($snapshot['queue_worker']['status'] ?? null) === 'unknown') {
            $warnings[] = 'worker_evidence_missing';
        }

        $snapshot['blocking_issues'] = $blockingIssues;
        $snapshot['warnings'] = $warnings;
        $snapshot['informational_issues'] = $informationalIssues;
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

    private function checkScraper(): array
    {
        $baseUrl = (string) config('services.scraper_service.base_url');
        $timeout = (int) config('backend_admin.dependency_health.scraper_timeout_seconds', 5);
        $healthPath = config('backend_admin.dependency_health.scraper_health_path');
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);

        if ($baseUrl === '' || $host === '') {
            return [
                'status' => 'failed',
                'label' => 'Scraper base URL missing',
                'base_url' => $baseUrl,
                'host' => $host ?: null,
                'resolved_ips' => [],
                'reachable' => false,
                'http_status' => null,
                'message' => 'SCRAPER_API_BASE_URL is not configured correctly.',
            ];
        }

        $resolvedIps = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        $resolveStatus = !empty($resolvedIps) ? 'healthy' : 'failed';
        $probeUrl = $healthPath
            ? rtrim($baseUrl, '/') . '/' . ltrim((string) $healthPath, '/')
            : $baseUrl;

        $reachable = false;
        $httpStatus = null;
        $message = !empty($resolvedIps) ? 'Scraper host resolved.' : 'Scraper host did not resolve.';

        try {
            $response = Http::timeout($timeout)->get($probeUrl);
            $httpStatus = $response->status();
            $reachable = $response->successful();
            $message = $reachable
                ? 'Scraper health probe returned a successful response.'
                : "Scraper health probe returned HTTP {$httpStatus}.";
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
        }

        $status = ($resolveStatus === 'healthy' && $reachable) ? 'healthy' : 'failed';
        $label = $status === 'healthy' ? 'Scraper reachable' : 'Scraper unreachable';

        return [
            'status' => $status,
            'label' => $label,
            'base_url' => $baseUrl,
            'probe_url' => $probeUrl,
            'host' => $host,
            'resolved_ips' => $resolvedIps,
            'reachable' => $reachable,
            'http_status' => $httpStatus,
            'message' => $message,
        ];
    }

    private function checkDnsSync(): array
    {
        $key = (string) config('backend_admin.dependency_health.dns_status_s3_key');

        if ($key === '') {
            return [
                'status' => 'unknown',
                'label' => 'DNS status key not configured',
                'message' => 'No S3 key configured for DNS sync status.',
            ];
        }

        try {
            if (!Storage::disk('s3')->exists($key)) {
                return [
                    'status' => 'unknown',
                    'label' => 'DNS sync status missing',
                    'message' => 'No DNS sync status artifact found in S3.',
                    's3_key' => $key,
                ];
            }

            $decoded = json_decode(Storage::disk('s3')->get($key), true);

            if (!is_array($decoded)) {
                return [
                    'status' => 'unknown',
                    'label' => 'DNS sync status unreadable',
                    'message' => 'DNS sync artifact could not be decoded.',
                    's3_key' => $key,
                ];
            }

            $checkedAt = isset($decoded['checked_at']) ? Carbon::parse($decoded['checked_at']) : null;
            $maxAge = (int) config('backend_admin.dependency_health.dns_status_max_age_minutes', 60);
            $ageMinutes = $checkedAt ? $checkedAt->diffInMinutes(Carbon::now()) : null;
            $isStale = $ageMinutes === null || $ageMinutes > $maxAge;

            $status = ($decoded['dns_ip'] ?? null) === ($decoded['ec2_ip'] ?? null) ? 'healthy' : 'failed';
            $label = $status === 'healthy' ? 'DNS matches EC2' : 'DNS does not match EC2';

            if ($isStale) {
                $status = 'unknown';
                $label = 'DNS status is stale';
            }

            return [
                'status' => $status,
                'label' => $label,
                'message' => $decoded['status'] ?? null,
                'checked_at' => $decoded['checked_at'] ?? null,
                'age_minutes' => $ageMinutes,
                'record_label' => $decoded['record_label'] ?? null,
                'dns_ip' => $decoded['dns_ip'] ?? null,
                'ec2_ip' => $decoded['ec2_ip'] ?? null,
                'changed' => $decoded['changed'] ?? null,
                's3_key' => $key,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'unknown',
                'label' => 'DNS sync status unavailable',
                'message' => $exception->getMessage(),
                's3_key' => $key,
            ];
        }
    }
}
