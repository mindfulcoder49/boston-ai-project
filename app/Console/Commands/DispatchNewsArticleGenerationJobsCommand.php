<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trend;
use App\Models\YearlyCountComparison;
use App\Models\NewsArticle;
use App\Http\Controllers\AiAssistantController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Jobs\GenerateNewsArticleJob;
use App\Models\JobRun;

class DispatchNewsArticleGenerationJobsCommand extends Command
{
    protected $signature = 'app:dispatch-news-article-generation-jobs 
                            {--model=all : The report model to process (Trend, YearlyCountComparison, all)} 
                            {--fresh : Regenerate articles even if they exist and are published} 
                            {--yearly-only : DEPRECATED: Use --model=YearlyCountComparison instead} 
                            {--unified-res= : Process only Trend reports with column "unified" and a specific resolution}
                            {--run-config= : Run a pre-defined set of reports from the news_generation config file}
                            {--report-class= : The specific report model class to generate for (e.g., App\Models\Trend)}
                            {--report-id= : The ID of the specific report to generate for (requires --report-class)}';
    protected $description = 'Dispatches jobs to generate news articles for statistical reports using AI.';

    protected $supportedModels = [
        'Trend' => Trend::class,
        'YearlyCountComparison' => YearlyCountComparison::class,
    ];

    public function handle()
    {
        $this->info('Starting news article generation job dispatch process...');

        if ($this->option('fresh')) {
            $this->warn('The --fresh option was used. Existing articles will be regenerated.');
        }

        if ($this->option('run-config')) {
            $configSet = $this->option('run-config');
            $this->runFromConfig($configSet);
        } else {
            $this->runFromCliOptions();
        }

        $this->info('News article generation job dispatch process completed.');
        return 0;
    }

    private function runFromCliOptions()
    {
        // Handle specific report generation
        $reportClass = $this->option('report-class');
        $reportId = $this->option('report-id');

        if ($reportClass && $reportId) {
            if (!class_exists($reportClass)) {
                $this->error("The specified report class '{$reportClass}' does not exist.");
                return;
            }
            $report = $reportClass::find($reportId);
            if (!$report) {
                $this->error("No report found for class '{$reportClass}' with ID '{$reportId}'.");
                return;
            }
            $this->info("Dispatching job for a single specified report: {$reportClass} #{$reportId}");
            $this->dispatchJobForReport($report);
            return;
        } elseif ($reportClass || $reportId) {
            $this->error('Both --report-class and --report-id must be provided together.');
            return;
        }

        $yearlyOnly = $this->option('yearly-only');
        $unifiedRes = $this->option('unified-res');

        if ($yearlyOnly && $unifiedRes) {
            $this->error('The --yearly-only and --unified-res options cannot be used together.');
            return;
        }

        $modelOption = $this->option('model');
        $modelsToProcess = [];

        if ($yearlyOnly) {
            $modelOption = 'YearlyCountComparison';
            $this->info('Processing only YearlyCountComparison reports due to --yearly-only flag.');
        } elseif ($unifiedRes) {
            $modelOption = 'Trend';
            $this->info("Processing only Trend reports with column 'unified' and resolution {$unifiedRes} due to --unified-res flag.");
        }

        if ($modelOption === 'all') {
            $modelsToProcess = $this->supportedModels;
        } elseif (isset($this->supportedModels[$modelOption])) {
            $modelsToProcess = [$modelOption => $this->supportedModels[$modelOption]];
        } else {
            $this->error("Invalid model specified. Available models: " . implode(', ', array_keys($this->supportedModels)));
            return;
        }

        foreach ($modelsToProcess as $modelName => $modelClass) {
            $this->line("Processing reports from: <fg=cyan>{$modelName}</fg=cyan>");
            $this->processReports($modelClass);
        }
    }

    private function runFromConfig($configSet)
    {
        $setName = $configSet ?: 'default';
        $this->info("Running pre-defined report set '{$setName}' from config...");

        $reportSet = config("news_generation.report_sets.{$setName}");

        if (!$reportSet) {
            $this->error("Report set '{$setName}' not found in config/news_generation.php.");
            return;
        }

        $allReports = collect();

        foreach ($reportSet as $criteria) {
            if (!isset($criteria['model_class']) || !class_exists($criteria['model_class'])) {
                $this->warn("Skipping invalid criteria in config: model_class is missing or invalid.");
                continue;
            }

            $modelClass = $criteria['model_class'];
            $query = $modelClass::query();

            // Special handling for source_model_class which is a property on the report model
            if (isset($criteria['source_model_class'])) {
                $query->where('model_class', $criteria['source_model_class']);
            }

            foreach ($criteria as $key => $value) {
                if (!in_array($key, ['model_class', 'source_model_class'])) {
                    $query->where($key, $value);
                }
            }
            $allReports = $allReports->merge($query->get());
        }

        if ($allReports->isEmpty()) {
            $this->info("\nNo reports found matching the criteria in the '{$setName}' config set.");
            return;
        }

        $progressBar = $this->output->createProgressBar($allReports->count());
        $progressBar->start();

        foreach ($allReports as $report) {
            $progressBar->advance();
            $this->dispatchJobForReport($report);
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function processReports(string $modelClass)
    {
        $query = $modelClass::query();
        $unifiedRes = $this->option('unified-res');

        if ($unifiedRes && $modelClass === Trend::class) {
            $query->where('column_name', 'unified')
                  ->where('h3_resolution', $unifiedRes);
        }

        $reports = $query->get();
        if ($reports->isEmpty()) {
            $this->info("\nNo reports found matching the specified criteria for {$modelClass}.");
            return;
        }

        $progressBar = $this->output->createProgressBar(count($reports));
        $progressBar->start();

        foreach ($reports as $report) {
            $progressBar->advance();
            $this->dispatchJobForReport($report);
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function dispatchJobForReport($report)
    {
        $modelClass = get_class($report);
        $existingArticle = NewsArticle::where('source_model_class', $modelClass)
            ->where('source_report_id', $report->id)
            ->first();

        if (!$this->option('fresh') && $existingArticle && $existingArticle->status === 'published') {
            return; // Skip if already published, unless --fresh is used
        }

        // Check if a job is already pending or running for this article
        if ($existingArticle && $existingArticle->jobRun && in_array($existingArticle->jobRun->status, ['pending', 'running'])) {
            $this->info("\nSkipping dispatch for {$modelClass} #{$report->id}: A job is already {$existingArticle->jobRun->status}.");
            return;
        }

        $article = NewsArticle::updateOrCreate(
            [
                'source_model_class' => $modelClass,
                'source_report_id' => $report->id,
            ],
            [
                'title' => "Queued article for: " . $this->getReportContext($report)['title'],
                'slug' => "temp-" . Str::uuid()->toString(),
                'headline' => 'Queued for generation...',
                'summary' => 'The generation process for this article has been queued and will start shortly.',
                'content' => 'The generation process for this article has been queued and will start shortly.',
                'status' => 'draft',
                'published_at' => null,
            ]
        );

        $job = new GenerateNewsArticleJob($article, $this->option('fresh'));
        $jobId = app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatch($job);

        JobRun::create([
            'job_id' => $jobId,
            'job_class' => GenerateNewsArticleJob::class,
            'status' => 'pending',
            'related_model_type' => NewsArticle::class,
            'related_model_id' => $article->id,
            'payload' => [
                'report_class' => get_class($report),
                'report_id' => $report->id,
                'fresh' => $this->option('fresh'),
            ],
        ]);

        $article->update(['job_id' => $jobId]);

        $this->info("\nDispatched job {$jobId} for {$modelClass} #{$report->id}.");
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
                'Trend Analysis Windows (Weeks)' => $report->analysis_weeks_trend,
                'Anomaly Analysis Window (Weeks)' => $report->analysis_weeks_anomaly,
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
