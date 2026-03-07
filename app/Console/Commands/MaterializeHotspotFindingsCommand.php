<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\AnalysisReportSnapshot;
use App\Models\HotspotFinding;
use League\Flysystem\FileAttributes;

class MaterializeHotspotFindingsCommand extends Command
{
    protected $signature = 'app:materialize-hotspot-findings
                            {--job-id= : Only materialize a specific Stage 4 job ID}
                            {--force   : Re-materialize even if findings already exist}';

    protected $description = 'Reads stage4 S3 results for all jobs and upserts into the h3_hotspot_findings table.';

    public function handle(): int
    {
        $jobId = $this->option('job-id');
        $force = $this->option('force');

        // Fast path: snapshot table (populated by app:pull-analysis-reports)
        $fromSnapshots = AnalysisReportSnapshot::jobIdsForArtifact('stage4_h3_anomaly.json');
        $s3 = Storage::disk('s3');

        $jobIds = $jobId ? [$jobId] : (
            !empty($fromSnapshots) ? $fromSnapshots : $this->discoverStage4JobIds($s3)
        );

        if (empty($jobIds)) {
            $this->error('No Stage 4 jobs found in S3.');
            return 1;
        }

        $this->info('Materializing hotspot findings for ' . count($jobIds) . ' job(s)...');

        foreach ($jobIds as $jId) {
            if (!$force && HotspotFinding::where('job_id', $jId)->exists()) {
                $this->line("  <fg=gray>Skipping {$jId} — already materialized. Use --force to refresh.</>");
                continue;
            }

            $data = AnalysisReportSnapshot::resolve($jId, 'stage4_h3_anomaly.json');
            if (!$data) {
                $this->warn("  Skipping {$jId} — artifact not found in snapshots or S3.");
                continue;
            }

            $params      = $data['parameters'] ?? [];
            $modelClass  = $params['model_class'] ?? null;
            $columnName  = $params['column_name'] ?? 'unified';
            $h3Resolution = (int) ($params['h3_resolution'] ?? 8);
            $pAnomaly    = (float) ($params['p_value_anomaly'] ?? 0.05);
            $pTrend      = (float) ($params['p_value_trend']   ?? 0.05);

            if (!$modelClass || !class_exists($modelClass)) {
                $this->warn("  Skipping {$jId} — model_class missing or not found in artifact parameters.");
                continue;
            }

            if (empty($data['results'])) {
                $this->line("  <fg=gray>{$jId} — no results in artifact.</>");
                continue;
            }

            $label = class_basename($modelClass) . ' / ' . $columnName . ' (res ' . $h3Resolution . ')';
            $h3Col = "h3_index_{$h3Resolution}";

            $hexFindings = [];

            foreach ($data['results'] as $row) {
                $h3Index = $row[$h3Col] ?? null;
                if (!$h3Index) continue;

                $secondaryGroup = $row['secondary_group'] ?? null;
                $historicalAvg  = round((float) ($row['historical_weekly_avg'] ?? 0), 1);

                $rowAnomalies    = 0;
                $rowAnomalyItems = [];

                foreach ($row['anomaly_analysis'] ?? [] as $week) {
                    if (($week['anomaly_p_value'] ?? 1) >= $pAnomaly) continue;
                    if (($row['historical_weekly_avg'] ?? 0) < 1 && ($week['count'] ?? 0) === 1) continue;

                    $rowAnomalies++;
                    $rowAnomalyItems[] = [
                        'secondary_group' => $secondaryGroup,
                        'week'            => $week['week'] ?? null,
                        'count'           => $week['count'] ?? null,
                        'historical_avg'  => $historicalAvg,
                        'z_score'         => round((float) ($week['z_score'] ?? 0), 2),
                    ];
                }

                $rowTrends     = 0;
                $rowTrendItems = [];

                foreach ($row['trend_analysis'] ?? [] as $trendData) {
                    if (($trendData['p_value'] ?? 1) >= $pTrend) continue;

                    $rowTrends++;
                    $rowTrendItems[] = [
                        'secondary_group' => $secondaryGroup,
                        'slope'           => round((float) ($trendData['slope'] ?? 0), 2),
                        'p_value'         => $trendData['p_value'] ?? null,
                        'window'          => $trendData['window'] ?? null,
                    ];
                }

                if ($rowAnomalies === 0 && $rowTrends === 0) continue;

                if (!isset($hexFindings[$h3Index])) {
                    $hexFindings[$h3Index] = [
                        'anomaly_count' => 0,
                        'trend_count'   => 0,
                        'top_anomalies' => [],
                        'top_trends'    => [],
                    ];
                }

                $hexFindings[$h3Index]['anomaly_count'] += $rowAnomalies;
                $hexFindings[$h3Index]['trend_count']   += $rowTrends;
                $hexFindings[$h3Index]['top_anomalies']  = array_merge($hexFindings[$h3Index]['top_anomalies'], $rowAnomalyItems);
                $hexFindings[$h3Index]['top_trends']     = array_merge($hexFindings[$h3Index]['top_trends'],    $rowTrendItems);
            }

            if (empty($hexFindings)) {
                HotspotFinding::where('job_id', $jId)->delete();
                $this->line("  <fg=gray>{$label} — no significant findings.</>");
                continue;
            }

            $upsertRows = [];
            $now        = now();

            foreach ($hexFindings as $h3Index => $agg) {
                usort($agg['top_anomalies'], fn($a, $b) => abs($b['z_score']) <=> abs($a['z_score']));
                usort($agg['top_trends'],    fn($a, $b) => abs($b['slope'])   <=> abs($a['slope']));

                $upsertRows[] = [
                    'job_id'        => $jId,
                    'model_class'   => $modelClass,
                    'column_name'   => $columnName,
                    'h3_index'      => $h3Index,
                    'h3_resolution' => $h3Resolution,
                    'anomaly_count' => $agg['anomaly_count'],
                    'trend_count'   => $agg['trend_count'],
                    'top_anomalies' => json_encode(array_slice($agg['top_anomalies'], 0, 5)),
                    'top_trends'    => json_encode(array_slice($agg['top_trends'],    0, 5)),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            HotspotFinding::where('job_id', $jId)->delete();

            foreach (array_chunk($upsertRows, 500) as $chunk) {
                HotspotFinding::insert($chunk);
            }

            $this->info('  <fg=green>✓</> ' . $label . ' — ' . count($hexFindings) . ' hexagons materialized.');
        }

        $this->info('Done.');
        return 0;
    }

    private function discoverStage4JobIds($s3): array
    {
        $jobIds = [];

        try {
            $flysystem = $s3->getDriver();
            foreach ($flysystem->listContents('', true) as $item) {
                if (!($item instanceof FileAttributes)) continue;
                $parts = explode('/', $item->path(), 2);
                if (count($parts) === 2 && $parts[1] === 'stage4_h3_anomaly.json') {
                    $jobIds[] = $parts[0];
                }
            }
        } catch (\Exception $e) {
            $this->error('Failed to list S3 contents: ' . $e->getMessage());
        }

        return $jobIds;
    }
}
