<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AnalysisReportSnapshot;
use App\Services\TrendSummaryService;
use League\Flysystem\FileAttributes;

class PullAnalysisReportsCommand extends Command
{
    protected $signature = 'app:pull-analysis-reports
                            {--fresh          : Re-pull even if a snapshot already exists}
                            {--skip-hotspots  : Skip re-materializing h3_hotspot_findings}';

    protected $description = 'Pull all analysis report artifacts from S3 into the analysis_report_snapshots table, then rebuild derived data and clear caches.';

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
        $fresh        = $this->option('fresh');
        $skipHotspots = $this->option('skip-hotspots');

        // ── 1. Discover all relevant artifacts in S3 ─────────────────────────
        $this->info('Scanning S3 for artifacts...');
        $artifacts = $this->discoverArtifacts();

        if (empty($artifacts)) {
            $this->warn('No artifacts found in S3.');
            return 0;
        }

        $total = count($artifacts);
        $this->info("Found {$total} artifact file(s).");

        // ── 2. Pull into snapshot table ───────────────────────────────────────
        $pulled    = 0;
        $skipped   = 0;
        $failed    = 0;
        $pulledJobIds = [];

        $s3 = Storage::disk('s3');

        foreach ($artifacts as $artifact) {
            ['job_id' => $jobId, 'artifact_name' => $artifactName, 's3_last_modified' => $lastMod] = $artifact;

            if (!$fresh && AnalysisReportSnapshot::where('job_id', $jobId)->where('artifact_name', $artifactName)->exists()) {
                $skipped++;
                continue;
            }

            try {
                $raw = $s3->get("{$jobId}/{$artifactName}");
                $data = json_decode($raw, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->warn("  Bad JSON: {$jobId}/{$artifactName}");
                    Log::warning("PullAnalysisReports: JSON error for {$jobId}/{$artifactName}: " . json_last_error_msg());
                    $failed++;
                    continue;
                }

                AnalysisReportSnapshot::updateOrCreate(
                    ['job_id' => $jobId, 'artifact_name' => $artifactName],
                    ['payload' => $data, 's3_last_modified' => $lastMod, 'pulled_at' => now()]
                );

                $pulledJobIds[] = $jobId;
                $pulled++;
                $this->line("  <fg=green>✓</> {$jobId}/{$artifactName}");
            } catch (\Exception $e) {
                $this->warn("  Failed: {$jobId}/{$artifactName}: " . $e->getMessage());
                Log::warning("PullAnalysisReports: pull failed for {$jobId}/{$artifactName}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Pull complete — pulled: {$pulled}, skipped: {$skipped}, failed: {$failed}.");

        // ── 3. Re-materialize h3_hotspot_findings from snapshots ─────────────
        if (!$skipHotspots) {
            $this->info('Re-materializing hotspot findings...');
            $this->call('app:materialize-hotspot-findings', ['--force' => true]);
        }

        // ── 4. Warm trend summaries (compute + cache all stage4 summaries) ────
        $this->warmTrendSummaries($fresh);

        // ── 5. Clear listing caches so they rebuild with fresh summaries ───────
        $this->info('Clearing listing caches...');
        $this->clearListingCaches();

        $this->info('Done.');

        return $failed > 0 ? 1 : 0;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function discoverArtifacts(): array
    {
        $relevant = [];

        try {
            $flysystem = Storage::disk('s3')->getDriver();

            foreach ($flysystem->listContents('', true) as $item) {
                if (!($item instanceof FileAttributes)) {
                    continue;
                }

                $parts = explode('/', $item->path(), 2);
                if (count($parts) !== 2) {
                    continue;
                }

                [$jobId, $artifactName] = $parts;

                $include = in_array($artifactName, self::EXACT_ARTIFACTS, true);

                if (!$include) {
                    foreach (self::ARTIFACT_PREFIXES as $prefix) {
                        if (str_starts_with($artifactName, $prefix)) {
                            $include = true;
                            break;
                        }
                    }
                }

                if ($include) {
                    $relevant[] = [
                        'job_id'          => $jobId,
                        'artifact_name'   => $artifactName,
                        's3_last_modified' => $item->lastModified(),
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->error('S3 listing failed: ' . $e->getMessage());
            Log::error('PullAnalysisReports: S3 listing failed: ' . $e->getMessage());
        }

        return $relevant;
    }

    /**
     * Pre-compute and cache trend summaries for all stage4 snapshots.
     * Skips already-cached summaries unless --fresh was passed.
     */
    private function warmTrendSummaries(bool $fresh): void
    {
        $snapshots = AnalysisReportSnapshot::where('artifact_name', 'stage4_h3_anomaly.json')->get();

        if ($snapshots->isEmpty()) {
            return;
        }

        $this->info("Warming {$snapshots->count()} trend summaries...");
        $bar = $this->output->createProgressBar($snapshots->count());
        $bar->start();

        $warmed  = 0;
        $skipped = 0;

        foreach ($snapshots as $snapshot) {
            $cacheKey = TrendSummaryService::cacheKey($snapshot->job_id);

            if (!$fresh && Cache::has($cacheKey)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $params       = $snapshot->payload['parameters'] ?? [];
            $h3Resolution = (int)   ($params['h3_resolution']   ?? 8);
            $pAnomaly     = (float) ($params['p_value_anomaly'] ?? 0.05);
            $pTrend       = (float) ($params['p_value_trend']   ?? 0.05);

            TrendSummaryService::computeAndCache($snapshot->job_id, $h3Resolution, $pAnomaly, $pTrend);
            $warmed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Summaries warmed: {$warmed}, already cached (skipped): {$skipped}.");
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

