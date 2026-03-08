<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RunWeeklyAnalysisCommand extends Command
{
    protected $signature = 'app:run-weekly-analysis
                            {--stage2   : Run Stage 2 only}
                            {--stage4   : Run Stage 4 only}
                            {--stage6   : Run Stage 6 only}
                            {--models=  : Comma-separated model short names to target (e.g. CrimeData,ChicagoCrime)}
                            {--dry-run  : Preview jobs without dispatching}
                            {--fresh    : Force new data exports (passed through to sub-commands)}';

    protected $description = 'Orchestrates the weekly analysis run using config/analysis_schedule.php.';

    public function handle(): int
    {
        $cfg = config('analysis_schedule');

        if (!$cfg) {
            $this->error('config/analysis_schedule.php not found or returned null.');
            return 1;
        }

        $anyStage = $this->option('stage2') || $this->option('stage4') || $this->option('stage6');
        $runStage2 = $this->option('stage2') || !$anyStage;
        $runStage4 = $this->option('stage4') || !$anyStage;
        $runStage6 = $this->option('stage6') || !$anyStage;
        $dryRun    = $this->option('dry-run');
        $fresh     = $this->option('fresh');

        $modelsOption = $this->option('models');
        $modelFilter  = $modelsOption
            ? array_map('trim', explode(',', $modelsOption))
            : [];

        if ($modelFilter) {
            $this->info('Model filter active: ' . implode(', ', $modelFilter));
        }

        $exitCode = 0;

        if ($runStage2) {
            $exitCode |= $this->runStage2($cfg['stage2'] ?? [], $dryRun, $fresh, $modelFilter);
        }

        if ($runStage4) {
            $exitCode |= $this->runStage4($cfg['stage4'] ?? [], $dryRun, $fresh, $modelFilter);
        }

        if ($runStage6) {
            $exitCode |= $this->runStage6($cfg['stage6'] ?? [], $dryRun, $fresh, $modelFilter);
        }

        $this->info($dryRun ? 'Dry run complete. No jobs were dispatched.' : 'Weekly analysis run complete.');

        return $exitCode;
    }

    // -------------------------------------------------------------------------

    private function runStage2(array $cfg, bool $dryRun, bool $fresh, array $modelFilter = []): int
    {
        if (empty($cfg['enabled'])) {
            $this->warn('[Stage 2] Disabled in config. Skipping.');
            return 0;
        }

        $baselineYear = $cfg['baseline_year'] ?? 2019;
        $label = $modelFilter ? implode(', ', $modelFilter) : 'all auto-discoverable models';
        $this->info("[Stage 2] Dispatching yearly count comparisons for {$label}.");
        $this->line("  baseline_year={$baselineYear}");

        if ($dryRun) {
            $this->warn('[Stage 2] Dry run — skipping dispatch.');
            return 0;
        }

        $baseArgs = ['--baseline-year' => $baselineYear];
        if ($fresh) {
            $baseArgs['--fresh'] = true;
        }

        if (empty($modelFilter)) {
            return $this->call('app:dispatch-yearly-count-comparison-jobs', $baseArgs);
        }

        $exitCode = 0;
        foreach ($modelFilter as $modelName) {
            $this->line("  [Stage 2] → {$modelName}");
            $exitCode |= $this->call('app:dispatch-yearly-count-comparison-jobs', array_merge($baseArgs, ['model' => $modelName]));
        }
        return $exitCode;
    }

    private function runStage4(array $cfg, bool $dryRun, bool $fresh, array $modelFilter = []): int
    {
        if (empty($cfg['enabled'])) {
            $this->warn('[Stage 4] Disabled in config. Skipping.');
            return 0;
        }

        $resolutions  = implode(',', $cfg['resolutions']   ?? [9, 8, 7, 6, 5]);
        $pAnomaly     = $cfg['p_anomaly']     ?? 0.05;
        $pTrend       = $cfg['p_trend']       ?? 0.05;
        $trendWeeks   = implode(',', $cfg['trend_weeks']   ?? [4, 26, 52]);
        $anomalyWeeks = $cfg['anomaly_weeks'] ?? 4;
        $exportSpan   = $cfg['export_timespan'] ?? 108;

        $label = $modelFilter ? implode(', ', $modelFilter) : 'all auto-discoverable models';
        $this->info("[Stage 4] Dispatching for {$label}.");
        $this->line("  resolutions={$resolutions}, p_anomaly={$pAnomaly}, p_trend={$pTrend}");
        $this->line("  trend_weeks={$trendWeeks}, anomaly_weeks={$anomalyWeeks}, export_timespan={$exportSpan}");

        if ($dryRun) {
            $this->warn('[Stage 4] Dry run — skipping dispatch.');
            return 0;
        }

        $baseArgs = [
            '--resolutions'     => $resolutions,
            '--p-anomaly'       => $pAnomaly,
            '--p-trend'         => $pTrend,
            '--trend-weeks'     => $trendWeeks,
            '--anomaly-weeks'   => $anomalyWeeks,
            '--export-timespan' => $exportSpan,
        ];

        if ($fresh) {
            $baseArgs['--fresh'] = true;
        }

        if (empty($modelFilter)) {
            return $this->call('app:dispatch-statistical-analysis-jobs', $baseArgs);
        }

        $exitCode = 0;
        foreach ($modelFilter as $modelName) {
            $this->line("  [Stage 4] → {$modelName}");
            $exitCode |= $this->call('app:dispatch-statistical-analysis-jobs', array_merge($baseArgs, ['model' => $modelName]));
        }
        return $exitCode;
    }

    private function runStage6(array $cfg, bool $dryRun, bool $fresh, array $modelFilter = []): int
    {
        if (empty($cfg['enabled'])) {
            $this->warn('[Stage 6] Disabled in config. Skipping.');
            return 0;
        }

        $jobs = $cfg['jobs'] ?? [];

        if (!empty($modelFilter)) {
            $jobs = array_values(array_filter($jobs, function ($job) use ($modelFilter) {
                $basename = class_basename($job['model'] ?? '');
                return in_array($basename, $modelFilter, true);
            }));
        }

        if (empty($jobs)) {
            $this->warn('[Stage 6] No jobs configured in analysis_schedule.php. Skipping.');
            return 0;
        }

        $this->info('[Stage 6] Running ' . count($jobs) . ' configured scoring job(s).');

        $exitCode = 0;

        foreach ($jobs as $i => $job) {
            $modelClass = $job['model'] ?? null;
            $column     = $job['column'] ?? null;

            if (!$modelClass || !$column) {
                $this->error("[Stage 6] Job #{$i}: 'model' and 'column' are required. Skipping.");
                $exitCode = 1;
                continue;
            }

            if (!class_exists($modelClass)) {
                $this->error("[Stage 6] Job #{$i}: Model class '{$modelClass}' not found. Skipping.");
                $exitCode = 1;
                continue;
            }

            $modelName    = class_basename($modelClass);
            $resolutions  = implode(',', $job['resolutions']   ?? $cfg['resolutions']   ?? [8]);
            $analysisWeeks = $job['analysis_weeks']  ?? $cfg['analysis_weeks']  ?? 52;
            $defaultWeight = $job['default_weight']  ?? $cfg['default_weight']  ?? 1.0;
            $exportSpan   = $job['export_timespan']  ?? $cfg['export_timespan'] ?? 0;
            $weights      = $job['weights']          ?? [];
            $weightsJson  = $weights ? json_encode($weights) : '{}';

            $this->info("[Stage 6] Job #{$i}: {$modelName} / column={$column}, resolutions={$resolutions}");

            if ($dryRun) {
                $this->warn("[Stage 6] Dry run — skipping dispatch for job #{$i}.");
                continue;
            }

            $args = [
                'model'            => $modelName,
                '--column'         => $column,
                '--resolution'     => $resolutions,
                '--analysis-weeks' => $analysisWeeks,
                '--default-weight' => $defaultWeight,
                '--export-timespan' => $exportSpan,
                '--group-weights'  => $weightsJson,
            ];

            if ($fresh) {
                $args['--fresh'] = true;
            }

            $exitCode |= $this->call('app:dispatch-historical-scoring-jobs', $args);
        }

        return $exitCode;
    }
}
