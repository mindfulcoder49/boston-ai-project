<?php

namespace App\Console\Commands;

use App\Models\AnalysisReportSnapshot;
use App\Models\Trend;
use App\Models\YearlyCountComparison;
use App\Services\TrendSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class IngestAnalysisArtifactsCommand extends Command
{
    protected $signature = 'app:ingest-analysis-artifacts
                            {path : Local artifact root, job directory, or JSON artifact file}
                            {--fresh : Re-ingest even if a snapshot already exists}
                            {--skip-hotspots : Skip re-materializing h3_hotspot_findings}
                            {--job-id= : Only ingest artifacts for one job ID}
                            {--dry-run : Discover and validate artifacts without writing snapshots}';

    protected $description = 'Ingest local open-data-statistics artifacts into analysis_report_snapshots, then rebuild derived analysis data.';

    private const EXACT_ARTIFACTS = [
        'stage2_yearly_count_comparison.json',
        'stage4_h3_anomaly.json',
    ];

    private const ARTIFACT_PREFIXES = [
        'scoring_results',
        'stage6',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $fresh = (bool) $this->option('fresh');
        $dryRun = (bool) $this->option('dry-run');
        $jobIdFilter = $this->option('job-id') ?: null;

        if (!File::exists($path)) {
            $this->error("Path not found: {$path}");
            return 1;
        }

        $this->info("Scanning local artifacts under {$path}...");
        $artifacts = $this->discoverArtifacts($path, $jobIdFilter);

        if (empty($artifacts)) {
            $this->warn('No local analysis artifacts found.');
            return 0;
        }

        $this->info('Found ' . count($artifacts) . ' artifact file(s).');

        $ingested = 0;
        $skipped = 0;
        $failed = 0;
        $stage4JobIds = [];

        foreach ($artifacts as $artifact) {
            $jobId = $artifact['job_id'];
            $artifactName = $artifact['artifact_name'];
            $displayPath = "{$jobId}/{$artifactName}";

            if (!$dryRun && !$fresh && AnalysisReportSnapshot::where('job_id', $jobId)->where('artifact_name', $artifactName)->exists()) {
                $skipped++;
                $this->line("  <fg=gray>Skipping {$displayPath} — snapshot already exists.</>");
                continue;
            }

            $raw = File::get($artifact['path']);
            $payload = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->warn("  Bad JSON: {$displayPath}");
                Log::warning("IngestAnalysisArtifacts: JSON error for {$displayPath}: " . json_last_error_msg());
                $failed++;
                continue;
            }

            if ($dryRun) {
                $this->line("  <fg=green>✓</> {$displayPath} (valid JSON)");
                $ingested++;
                continue;
            }

            AnalysisReportSnapshot::updateOrCreate(
                ['job_id' => $jobId, 'artifact_name' => $artifactName],
                [
                    'payload' => $payload,
                    's3_last_modified' => $artifact['last_modified'],
                    'pulled_at' => now(),
                ]
            );

            $this->syncTrackingRow($jobId, $artifactName, $payload);
            $this->clearArtifactCaches($jobId);

            if ($artifactName === 'stage4_h3_anomaly.json') {
                $stage4JobIds[$jobId] = true;
            }

            $ingested++;
            $this->line("  <fg=green>✓</> {$displayPath}");
        }

        $action = $dryRun ? 'Dry run complete' : 'Ingest complete';
        $this->info("{$action} — artifacts: {$ingested}, skipped: {$skipped}, failed: {$failed}.");

        if ($dryRun) {
            return $failed > 0 ? 1 : 0;
        }

        $stage4JobIds = array_keys($stage4JobIds);

        if (!$this->option('skip-hotspots')) {
            $this->materializeHotspots($stage4JobIds);
        }

        $this->warmTrendSummaries($stage4JobIds, $fresh);

        $this->info('Clearing listing caches...');
        $this->clearListingCaches();

        $this->info('Done.');

        return $failed > 0 ? 1 : 0;
    }

    private function discoverArtifacts(string $path, ?string $jobIdFilter): array
    {
        $files = File::isFile($path) ? [new \SplFileInfo($path)] : File::allFiles($path);
        $artifacts = [];

        foreach ($files as $file) {
            $artifactName = $file->getFilename();

            if (!$this->isRelevantArtifact($artifactName)) {
                continue;
            }

            $jobId = basename($file->getPath());

            if ($jobIdFilter && $jobId !== $jobIdFilter) {
                continue;
            }

            $artifacts[] = [
                'job_id' => $jobId,
                'artifact_name' => $artifactName,
                'path' => $file->getPathname(),
                'last_modified' => $file->getMTime() ?: time(),
            ];
        }

        usort($artifacts, fn (array $a, array $b) => [$a['job_id'], $a['artifact_name']] <=> [$b['job_id'], $b['artifact_name']]);

        return $artifacts;
    }

    private function isRelevantArtifact(string $artifactName): bool
    {
        if (in_array($artifactName, self::EXACT_ARTIFACTS, true)) {
            return true;
        }

        foreach (self::ARTIFACT_PREFIXES as $prefix) {
            if (str_starts_with($artifactName, $prefix)) {
                return str_ends_with($artifactName, '.json');
            }
        }

        return false;
    }

    private function materializeHotspots(array $stage4JobIds): void
    {
        if (empty($stage4JobIds)) {
            $this->line('No newly ingested Stage 4 artifacts to materialize.');
            return;
        }

        $this->info('Re-materializing hotspot findings...');

        foreach ($stage4JobIds as $jobId) {
            $this->call('app:materialize-hotspot-findings', [
                '--job-id' => $jobId,
                '--force' => true,
            ]);
        }
    }

    private function syncTrackingRow(string $jobId, string $artifactName, array $payload): void
    {
        $params = $payload['parameters'] ?? [];

        if ($artifactName === 'stage2_yearly_count_comparison.json') {
            $modelClass = $params['model_class'] ?? null;
            $groupByCol = $params['group_by_col'] ?? $params['column_name'] ?? null;
            $baselineYear = $params['baseline_year'] ?? null;

            if (!$modelClass || !$groupByCol || !$baselineYear) {
                $this->line("  <fg=yellow>Tracking row skipped for {$jobId}/{$artifactName} — missing Stage 2 metadata.</>");
                return;
            }

            YearlyCountComparison::updateOrCreate(
                [
                    'model_class' => $modelClass,
                    'group_by_col' => $groupByCol,
                    'baseline_year' => (int) $baselineYear,
                ],
                ['job_id' => $jobId]
            );

            return;
        }

        if ($artifactName !== 'stage4_h3_anomaly.json') {
            return;
        }

        $modelClass = $params['model_class'] ?? null;
        $columnName = $params['column_name'] ?? null;

        if (!$modelClass || !$columnName) {
            $this->line("  <fg=yellow>Tracking row skipped for {$jobId}/{$artifactName} — missing Stage 4 metadata.</>");
            return;
        }

        Trend::updateOrCreate(
            [
                'model_class' => $modelClass,
                'column_name' => $columnName,
                'h3_resolution' => (int) ($params['h3_resolution'] ?? 8),
                'p_value_anomaly' => (float) ($params['p_value_anomaly'] ?? 0.05),
                'p_value_trend' => (float) ($params['p_value_trend'] ?? 0.05),
                'analysis_weeks_anomaly' => (int) ($params['analysis_weeks_anomaly'] ?? 4),
            ],
            [
                'job_id' => $jobId,
                'analysis_weeks_trend' => $params['analysis_weeks_trend'] ?? [4, 26, 52],
            ]
        );
    }

    private function warmTrendSummaries(array $stage4JobIds, bool $fresh): void
    {
        if (empty($stage4JobIds)) {
            return;
        }

        $snapshots = AnalysisReportSnapshot::where('artifact_name', 'stage4_h3_anomaly.json')
            ->whereIn('job_id', $stage4JobIds)
            ->get();

        if ($snapshots->isEmpty()) {
            return;
        }

        $this->info("Warming {$snapshots->count()} trend summaries...");
        $warmed = 0;
        $skipped = 0;

        foreach ($snapshots as $snapshot) {
            $cacheKey = TrendSummaryService::cacheKey($snapshot->job_id);

            if (!$fresh && Cache::has($cacheKey)) {
                $skipped++;
                continue;
            }

            $params = $snapshot->payload['parameters'] ?? [];
            $h3Resolution = (int) ($params['h3_resolution'] ?? 8);
            $pAnomaly = (float) ($params['p_value_anomaly'] ?? 0.05);
            $pTrend = (float) ($params['p_value_trend'] ?? 0.05);

            TrendSummaryService::computeAndCache($snapshot->job_id, $h3Resolution, $pAnomaly, $pTrend);
            $warmed++;
        }

        $this->line("  Summaries warmed: {$warmed}, already cached (skipped): {$skipped}.");
    }

    private function clearArtifactCaches(string $jobId): void
    {
        Cache::forget("analysis_data_{$jobId}");
        Cache::forget(TrendSummaryService::cacheKey($jobId));
    }

    private function clearListingCaches(): void
    {
        Cache::forget('trend_listing_v1');
        Cache::forget('yearly_count_comparison_listing_v1');
        Cache::forget('scoring_reports_listing_v2');
        Cache::forget('s3_bucket_listing_v1');

        $this->line('  Listing caches cleared.');
    }
}
