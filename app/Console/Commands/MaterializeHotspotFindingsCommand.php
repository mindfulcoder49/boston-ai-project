<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Trend;
use App\Models\HotspotFinding;

class MaterializeHotspotFindingsCommand extends Command
{
    protected $signature = 'app:materialize-hotspot-findings
                            {--trend-id= : Only materialize a specific trend by ID}
                            {--force    : Re-materialize even if findings already exist}';

    protected $description = 'Reads stage4 S3 results for all trends and upserts into the h3_hotspot_findings table.';

    public function handle(): int
    {
        $trendId = $this->option('trend-id');
        $force   = $this->option('force');

        $query = Trend::query();
        if ($trendId) {
            $query->where('id', $trendId);
        }
        $trends = $query->get();

        if ($trends->isEmpty()) {
            $this->error('No trends found.');
            return 1;
        }

        $this->info("Materializing hotspot findings for {$trends->count()} trend(s)...");

        $s3 = Storage::disk('s3');

        foreach ($trends as $trend) {
            $label = class_basename($trend->model_class) . ' / ' . $trend->column_name . ' (res ' . $trend->h3_resolution . ')';

            // Skip if already materialized and not forcing
            if (!$force && HotspotFinding::where('trend_id', $trend->id)->exists()) {
                $this->line("  <fg=gray>Skipping {$label} — already materialized. Use --force to refresh.</>");
                continue;
            }

            $path = "{$trend->job_id}/stage4_h3_anomaly.json";
            if (!$s3->exists($path)) {
                $this->warn("  Skipping {$label} — S3 file not found (job may still be running).");
                continue;
            }

            try {
                $data = json_decode($s3->get($path), true);
            } catch (\Exception $e) {
                $this->error("  Failed to read S3 for {$label}: " . $e->getMessage());
                Log::warning("MaterializeHotspotFindings: S3 read failed for trend {$trend->id}: " . $e->getMessage());
                continue;
            }

            if (empty($data['results'])) {
                $this->line("  <fg=gray>No results in {$label}.</>");
                continue;
            }

            $h3Col     = "h3_index_{$trend->h3_resolution}";
            $pAnomaly  = $trend->p_value_anomaly;
            $pTrend    = $trend->p_value_trend;

            // Aggregate per h3_index for this trend
            $hexFindings = []; // [h3_index => [anomaly_count, trend_count, top_anomalies[], top_trends[]]]

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

                $rowTrends    = 0;
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
                // No significant findings — clear any existing rows
                HotspotFinding::where('trend_id', $trend->id)->delete();
                $this->line("  <fg=gray>{$label} — no significant findings.</>");
                continue;
            }

            // Sort and slice top findings, then upsert
            $upsertRows = [];
            $now = now();

            foreach ($hexFindings as $h3Index => $agg) {
                usort($agg['top_anomalies'], fn($a, $b) => abs($b['z_score']) <=> abs($a['z_score']));
                usort($agg['top_trends'],    fn($a, $b) => abs($b['slope'])   <=> abs($a['slope']));

                $upsertRows[] = [
                    'trend_id'      => $trend->id,
                    'h3_index'      => $h3Index,
                    'h3_resolution' => $trend->h3_resolution,
                    'anomaly_count' => $agg['anomaly_count'],
                    'trend_count'   => $agg['trend_count'],
                    'top_anomalies' => json_encode(array_slice($agg['top_anomalies'], 0, 5)),
                    'top_trends'    => json_encode(array_slice($agg['top_trends'],    0, 5)),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            // Delete stale rows for this trend, then bulk insert
            HotspotFinding::where('trend_id', $trend->id)->delete();

            foreach (array_chunk($upsertRows, 500) as $chunk) {
                HotspotFinding::insert($chunk);
            }

            $this->info("  <fg=green>✓</> {$label} — {$trend->h3_resolution} hexagons materialized: " . count($hexFindings));
        }

        $this->info('Done.');
        return 0;
    }
}
