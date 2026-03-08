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
use App\Services\TrendSummaryService;
use Illuminate\Support\Facades\Cache;
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
                    'completion_job_id' => null, // Explicitly set to null
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
        // Trend: use the cached summary (compact, pre-computed, no analysis API dependency)
        if ($report instanceof Trend) {
            $summary = Cache::get(TrendSummaryService::cacheKey($report->job_id))
                ?? TrendSummaryService::compute($report->job_id, $report->h3_resolution, $report->p_value_anomaly, $report->p_value_trend);

            if (empty($summary) || ($summary['status'] ?? '') !== 'ok') {
                Log::warning("Trend summary unavailable for article generation.", ['trend_id' => $report->id, 'job_id' => $report->job_id]);
                return null;
            }
            return $summary;
        }

        // YearlyCountComparison: fetch from analysis API and slim the results
        if (!($report instanceof YearlyCountComparison)) return null;

        $apiUrl  = config('services.analysis_api.url') . "/api/v1/jobs/{$report->job_id}/results/stage2_yearly_count_comparison.json";
        $response = Http::timeout(120)->get($apiUrl);

        if ($response->successful()) {
            $data = $response->json();
            if (empty($data)) {
                Log::warning("Fetched report data is empty for article generation.", ['report_class' => get_class($report), 'report_id' => $report->id]);
                return null;
            }
            if (isset($data['results'])) {
                $current_year     = $data['parameters']['analysis_current_year'] ?? null;
                $filtered_results = array_filter($data['results'], fn($item) => isset($item['to_date'][$current_year]['change_pct']));
                usort($filtered_results, fn($a, $b) => ($b['to_date'][$current_year]['change_pct'] ?? 0) <=> ($a['to_date'][$current_year]['change_pct'] ?? 0));
                return [
                    'summary_of_changes' => [
                        'top_5_increases_ytd' => array_slice($filtered_results, 0, 5),
                        'top_5_decreases_ytd' => array_slice(array_reverse($filtered_results), 0, 5),
                    ],
                    'parameters' => $data['parameters'] ?? null,
                ];
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
