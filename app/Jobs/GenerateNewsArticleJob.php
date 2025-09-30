<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\NewsArticle;
use App\Models\JobRun;
use App\Http\Controllers\AiAssistantController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Trend;
use App\Models\YearlyCountComparison;
use Throwable;

class GenerateNewsArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutes timeout
    public $failOnTimeout = true;

    protected NewsArticle $article;
    protected bool $fresh;

    public function __construct(NewsArticle $article, bool $fresh = false)
    {
        $this->article = $article;
        $this->fresh = $fresh;
    }

    public function handle(): void
    {
        $jobRun = JobRun::where('job_id', $this->job->getJobId())->first();
        if ($jobRun) {
            $jobRun->update(['status' => 'running', 'started_at' => now()]);
        }

        $this->article->update(['status' => 'generating', 'content' => 'AI generation in progress...']);

        $report = $this->article->sourceReport;
        if (!$report) {
            throw new \Exception("Source report not found for NewsArticle ID: {$this->article->id}");
        }

        try {
            $reportData = $this->fetchReportData($report);

            if (!$reportData) {
                throw new \Exception("Could not fetch or process report data for " . get_class($report) . " #{$report->id}.");
            }

            $reportContext = $this->getReportContext($report);
            $articleData = AiAssistantController::generateNewsArticle($reportContext['title'], $reportData, $reportContext['parameters']);

            if ($articleData) {
                $baseSlug = Str::slug($articleData['headline']);
                $slug = $baseSlug;
                $counter = 1;
                while (NewsArticle::where('slug', $slug)->where('id', '!=', $this->article->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $this->article->update([
                    'title' => $articleData['headline'],
                    'slug' => $slug,
                    'headline' => $articleData['headline'],
                    'summary' => $articleData['summary'],
                    'content' => $articleData['content'],
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                if ($jobRun) {
                    $jobRun->update(['status' => 'completed', 'completed_at' => now(), 'output' => 'Article generated successfully.']);
                }
            } else {
                throw new \Exception("AI generation failed. Check logs for details from AiAssistantController.");
            }
        } catch (Throwable $e) {
            // Let the failed() method handle the rest
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $jobRun = JobRun::where('job_id', $this->job->getJobId())->first();
        if ($jobRun) {
            $jobRun->update([
                'status' => 'failed',
                'completed_at' => now(),
                'output' => $exception->getMessage(),
            ]);
        }

        $this->article->update([
            'status' => 'error',
            'content' => 'AI generation failed: ' . $exception->getMessage(),
        ]);
    }

    private function fetchReportData($report)
    {
        $apiBaseUrl = config('services.analysis_api.url');
        $apiUrl = null;

        if ($report instanceof Trend) {
            $apiUrl = "{$apiBaseUrl}/api/v1/jobs/{$report->job_id}/results/stage4_h3_anomaly.json";
        } elseif ($report instanceof YearlyCountComparison) {
            $apiUrl = "{$apiBaseUrl}/api/v1/jobs/{$report->job_id}/results/stage2_yearly_count_comparison.json";
        }

        if (!$apiUrl) return null;

        $response = Http::timeout(120)->get($apiUrl);
        if ($response->successful()) {
            $data = $response->json();
            if (empty($data)) {
                Log::warning("Fetched report data is empty for article generation.", ['report_class' => get_class($report), 'report_id' => $report->id]);
                return null;
            }
            // Data slimming logic from the original command
            if ($report instanceof Trend && isset($data['city_wide_results'])) {
                $significant_trends = array_filter($data['city_wide_results'], function ($item) use ($report) {
                    if (!isset($item['trend_analysis'])) return false;
                    foreach ($item['trend_analysis'] as $trend_period) {
                        if (isset($trend_period['p_value']) && $trend_period['p_value'] < $report->p_value_trend) return true;
                    }
                    return false;
                });
                $anomalous_hexes = $data['results'] ?? [];
                usort($anomalous_hexes, function ($a, $b) {
                    $p_value_a = 1.0; if (!empty($a['trend_analysis'])) { $p_values_a = array_column(array_values($a['trend_analysis']), 'p_value'); $numeric_p_values_a = array_filter($p_values_a, 'is_numeric'); if (!empty($numeric_p_values_a)) { $p_value_a = min($numeric_p_values_a); } }
                    $p_value_b = 1.0; if (!empty($b['trend_analysis'])) { $p_values_b = array_column(array_values($b['trend_analysis']), 'p_value'); $numeric_p_values_b = array_filter($p_values_b, 'is_numeric'); if (!empty($numeric_p_values_b)) { $p_value_b = min($numeric_p_values_b); } }
                    return $p_value_a <=> $p_value_b;
                });
                return [
                    'city_wide_significant_trends' => array_values($significant_trends),
                    'top_5_most_significant_anomalous_hexes' => array_slice($anomalous_hexes, 0, 5),
                    'parameters' => $data['parameters'] ?? null,
                ];
            }
            if ($report instanceof YearlyCountComparison && isset($data['results'])) {
                $results = $data['results'];
                $current_year = $data['parameters']['analysis_current_year'] ?? null;
                if ($current_year && !empty($results)) {
                    $filtered_results = array_filter($results, fn($item) => isset($item['to_date'][$current_year]['change_pct']));
                    usort($filtered_results, fn($a, $b) => ($b['to_date'][$current_year]['change_pct'] ?? 0) <=> ($a['to_date'][$current_year]['change_pct'] ?? 0));
                    return [
                        'summary_of_changes' => [
                            'top_5_increases_ytd' => array_slice($filtered_results, 0, 5),
                            'top_5_decreases_ytd' => array_slice(array_reverse($filtered_results), 0, 5),
                        ],
                        'parameters' => $data['parameters'] ?? null,
                    ];
                }
            }
            return $data;
        }

        Log::error("Failed to fetch report data for article generation.", ['report_class' => get_class($report), 'report_id' => $report->id, 'status' => $response->status(), 'response' => $response->body()]);
        return null;
    }

    private function getReportContext($report): array
    {
        $title = "Report " . $report->id;
        $parameters = [];

        if ($report instanceof Trend) {
            $sourceModel = $report->sourceModel();
            $modelName = $sourceModel ? $sourceModel->getHumanName() : 'Data';
            $columnLabel = Str::of($report->column_name)->replace('_', ' ')->title();
            $title = "Trend Analysis for {$modelName} by {$columnLabel}";
            $parameters = [
                'Analysis Type' => 'Trend and Anomaly Detection',
                'H3 Resolution' => $report->h3_resolution,
                'Anomaly P-Value Threshold' => $report->p_value_anomaly,
                'Trend P-Value Threshold' => $report->p_value_trend,
            ];
        } elseif ($report instanceof YearlyCountComparison) {
            $modelName = class_exists($report->model_class) ? $report->model_class::getHumanName() : 'Data';
            $groupLabel = Str::of($report->group_by_col)->replace('_', ' ')->title();
            $title = "{$modelName} Yearly Comparison by {$groupLabel} (Baseline {$report->baseline_year})";
            $parameters = [
                'Analysis Type' => 'Yearly Count Comparison',
                'Grouped By' => $groupLabel,
                'Baseline Year' => $report->baseline_year,
            ];
        }

        return ['title' => $title, 'parameters' => $parameters];
    }
}
