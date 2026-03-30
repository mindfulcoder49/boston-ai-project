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
use Illuminate\Support\Str;
use App\Models\NewsArticleGenerationConfig;
use App\Models\Trend;
use App\Models\YearlyCountComparison;
use App\Services\NewsArticleReportDataResolver;
use Throwable;

class GenerateNewsArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutes timeout
    public $failOnTimeout = true;

    protected NewsArticle $article;
    protected bool $fresh;
    protected ?NewsArticleGenerationConfig $config = null;

    public function __construct(NewsArticle $article, bool $fresh = false, ?NewsArticleGenerationConfig $config = null)
    {
        $this->article = $article;
        $this->fresh   = $fresh;
        $this->config  = $config;
    }

    public function handle(): void
    {
        $queueJobId = $this->job?->getJobId();
        $jobRun = $queueJobId ? JobRun::where('job_id', $queueJobId)->first() : null;
        if ($jobRun) {
            $jobRun->update(['status' => 'running', 'started_at' => now()]);
        }

        $this->article->update(['status' => 'generating', 'content' => 'AI generation in progress...']);

        $report = $this->article->source;
        if (!$report) {
            throw new \Exception("Source report not found for NewsArticle ID: {$this->article->id}");
        }

        try {
            $reportData = app(NewsArticleReportDataResolver::class)->resolve($report);

            if (!$reportData) {
                throw new \Exception("Could not fetch or process report data for " . get_class($report) . " #{$report->id}.");
            }

            // Apply config filters if a config is attached
            if ($this->config && $report instanceof Trend) {
                $reportData = $this->config->applyTrendFilters($reportData);
            }

            $reportContext = $this->getReportContext($report);
            $articleData = AiAssistantController::generateNewsArticle(
                $reportContext['title'],
                $reportData,
                $reportContext['parameters'],
                $this->config?->intro_prompt
            );

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
        $queueJobId = $this->job?->getJobId();
        $jobRun = $queueJobId ? JobRun::where('job_id', $queueJobId)->first() : null;
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
