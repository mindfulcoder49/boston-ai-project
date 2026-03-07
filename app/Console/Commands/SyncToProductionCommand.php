<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncToProductionCommand extends Command
{
    protected $signature = 'app:sync-to-production
                            {--trends         : Sync the trends table}
                            {--h3-names       : Sync the h3_location_names table}
                            {--trend-id=      : Sync only a specific trend (implies --trends)}
                            {--dry-run        : Show what would be synced without writing}
                            {--batch=50       : Rows per batch}
                            {--delay=200      : Milliseconds to sleep between batches (rate limiting)}';

    protected $description = 'Push local trends and h3_location_names to the Hostinger production database.';

    private int $batchSize;
    private int $delayMs;
    private bool $dryRun;

    public function handle(): int
    {
        $this->batchSize = max(1, (int) $this->option('batch'));
        $this->delayMs   = max(0, (int) $this->option('delay'));
        $this->dryRun    = $this->option('dry-run');

        $syncTrends   = $this->option('trends')   || $this->option('trend-id');
        $syncH3Names  = $this->option('h3-names');
        $trendsOnly   = $this->option('trend-id');

        if (!$syncTrends && !$syncH3Names) {
            $this->error('Specify at least one of --trends or --h3-names (or --trend-id=N).');
            return 1;
        }

        // Verify connection config is populated
        if (!config('database.connections.hostinger.host')) {
            $this->error('HOSTINGER_DB_HOST is not set. Add the HOSTINGER_DB_* variables to your .env file.');
            $this->line('');
            $this->line('Required .env variables:');
            $this->line('  HOSTINGER_DB_HOST=');
            $this->line('  HOSTINGER_DB_PORT=3306');
            $this->line('  HOSTINGER_DB_DATABASE=');
            $this->line('  HOSTINGER_DB_USERNAME=');
            $this->line('  HOSTINGER_DB_PASSWORD=');
            return 1;
        }

        // Test connection early so we fail fast
        try {
            DB::connection('hostinger')->getPdo();
            $this->info('Connected to Hostinger production database.');
        } catch (\Exception $e) {
            $this->error('Could not connect to Hostinger: ' . $e->getMessage());
            return 1;
        }

        if ($this->dryRun) {
            $this->warn('[DRY RUN] No data will be written.');
        }

        $exitCode = 0;

        if ($syncTrends) {
            $ok = $this->syncTrends($trendsOnly ? (int) $this->option('trend-id') : null);
            if (!$ok) $exitCode = 1;
        }

        if ($syncH3Names) {
            $ok = $this->syncH3Names();
            if (!$ok) $exitCode = 1;
        }

        return $exitCode;
    }

    // -------------------------------------------------------------------------

    private function syncTrends(?int $onlyId): bool
    {
        $this->info('');
        $this->info('── Syncing trends ──');

        $query = DB::table('trends');
        if ($onlyId !== null) {
            $query->where('id', $onlyId);
        }
        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->warn('No local trends found' . ($onlyId ? " with id={$onlyId}" : '') . '.');
            return true;
        }

        $this->line("  Found {$rows->count()} local trend(s). Batch size: {$this->batchSize}, delay: {$this->delayMs}ms.");

        $upserted = 0;
        $failed   = 0;

        foreach ($rows->chunk($this->batchSize) as $batch) {
            foreach ($batch as $row) {
                try {
                    if ($this->dryRun) {
                        $this->line("  [dry] Would upsert trend id={$row->id} job_id={$row->job_id} ({$row->model_class} / {$row->column_name})");
                    } else {
                        DB::connection('hostinger')->table('trends')->upsert(
                            [[
                                'id'                    => $row->id,
                                'model_class'           => $row->model_class,
                                'column_name'           => $row->column_name,
                                'job_id'                => $row->job_id,
                                'h3_resolution'         => $row->h3_resolution,
                                'p_value_anomaly'       => $row->p_value_anomaly,
                                'p_value_trend'         => $row->p_value_trend,
                                'analysis_weeks_trend'  => $row->analysis_weeks_trend,
                                'analysis_weeks_anomaly'=> $row->analysis_weeks_anomaly,
                                'created_at'            => $row->created_at,
                                'updated_at'            => $row->updated_at,
                            ]],
                            ['id'],
                            ['job_id', 'p_value_anomaly', 'p_value_trend', 'analysis_weeks_trend', 'analysis_weeks_anomaly', 'updated_at']
                        );
                        $this->line("  <fg=green>✓</> trend id={$row->id} {$row->model_class} / {$row->column_name}");
                    }
                    $upserted++;
                } catch (\Exception $e) {
                    $this->error("  ✕ trend id={$row->id}: " . $e->getMessage());
                    $failed++;
                }
            }

            if ($this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
        }

        $this->info("  Trends done — {$upserted} upserted, {$failed} failed.");
        return $failed === 0;
    }

    private function syncH3Names(): bool
    {
        $this->info('');
        $this->info('── Syncing h3_location_names ──');

        $total = DB::table('h3_location_names')->count();

        if ($total === 0) {
            $this->warn('  No local h3_location_names found.');
            return true;
        }

        $this->line("  Found {$total} local name(s). Batch size: {$this->batchSize}, delay: {$this->delayMs}ms.");

        $upserted = 0;
        $failed   = 0;

        DB::table('h3_location_names')->orderBy('id')->chunk($this->batchSize, function ($batch) use (&$upserted, &$failed) {
            $rows = $batch->map(fn($r) => [
                'id'                   => $r->id,
                'h3_index'             => $r->h3_index,
                'h3_resolution'        => $r->h3_resolution,
                'location_name'        => $r->location_name,
                'geocoded_at'          => $r->geocoded_at,
                'raw_geocode_response' => $r->raw_geocode_response,
                'created_at'           => $r->created_at,
                'updated_at'           => $r->updated_at,
            ])->values()->all();

            if ($this->dryRun) {
                foreach ($batch as $r) {
                    $this->line("  [dry] Would upsert h3_index={$r->h3_index} \"{$r->location_name}\"");
                }
                $upserted += count($rows);
                return;
            }

            try {
                DB::connection('hostinger')->table('h3_location_names')->upsert(
                    $rows,
                    ['h3_index'],
                    ['h3_resolution', 'location_name', 'geocoded_at', 'raw_geocode_response', 'updated_at']
                );
                foreach ($batch as $r) {
                    $this->line("  <fg=green>✓</> {$r->h3_index}  {$r->location_name}");
                }
                $upserted += count($rows);
            } catch (\Exception $e) {
                $this->error('  Batch failed: ' . $e->getMessage());
                $failed += count($rows);
            }

            if ($this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
        });

        $this->info("  H3 names done — {$upserted} upserted, {$failed} failed.");
        return $failed === 0;
    }
}
