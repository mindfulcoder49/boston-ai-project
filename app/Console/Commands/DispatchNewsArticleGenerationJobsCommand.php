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

class DispatchNewsArticleGenerationJobsCommand extends Command
{
    protected $signature = 'app:dispatch-news-article-generation-jobs {--model=all} {--fresh}';
    protected $description = 'Generates news articles for statistical reports using AI.';

    protected $supportedModels = [
        'Trend' => Trend::class,
        'YearlyCountComparison' => YearlyCountComparison::class,
    ];

    public function handle()
    {
        $this->info('Starting news article generation process...');

        if ($this->option('fresh')) {
            $this->warn('The --fresh option was used. Deleting all existing news articles.');
            NewsArticle::truncate();
            $this->info('All news articles have been deleted.');
        }

        $modelOption = $this->option('model');
        $modelsToProcess = [];

        if ($modelOption === 'all') {
            $modelsToProcess = $this->supportedModels;
        } elseif (isset($this->supportedModels[$modelOption])) {
            $modelsToProcess = [$modelOption => $this->supportedModels[$modelOption]];
        } else {
            $this->error("Invalid model specified. Available models: " . implode(', ', array_keys($this->supportedModels)));
            return 1;
        }

        foreach ($modelsToProcess as $modelName => $modelClass) {
            $this->line("Processing reports from: <fg=cyan>{$modelName}</fg=cyan>");
            $this->processReports($modelClass);
        }

        $this->info('News article generation process completed.');
        return 0;
    }

    private function processReports(string $modelClass)
    {
        $reports = $modelClass::all();
        $progressBar = $this->output->createProgressBar(count($reports));
        $progressBar->start();

        foreach ($reports as $report) {
            $progressBar->advance();

            // Check if an article already exists and is published
            $existingArticle = NewsArticle::where('source_model_class', $modelClass)
                ->where('source_report_id', $report->id)
                ->first();

            if ($existingArticle && $existingArticle->status === 'published') {
                continue; // Skip if already published
            }

            $reportData = $this->fetchReportData($report);

            if (!$reportData) {
                $this->warn("\n<fg=yellow>Warning:</> Could not fetch or process report data for {$modelClass} #{$report->id}. Skipping.");
                if ($existingArticle) {
                    $existingArticle->update(['status' => 'error', 'content' => 'Failed to fetch source report data.']);
                }
                continue;
            }

            $reportContext = $this->getReportContext($report);
            $jobId = Str::uuid()->toString();

            $article = NewsArticle::updateOrCreate(
                [
                    'source_model_class' => $modelClass,
                    'source_report_id' => $report->id,
                ],
                [
                    'title' => "Generating article for: {$reportContext['title']}",
                    'slug' => "temp-{$jobId}",
                    'headline' => 'Generating...',
                    'summary' => 'Generating...',
                    'content' => 'Generating...',
                    'status' => 'draft',
                    'job_id' => $jobId,
                ]
            );

            $articleData = AiAssistantController::generateNewsArticle($reportContext['title'], $reportData, $reportContext['parameters']);

            if ($articleData) {
                // Check for slug uniqueness
                $baseSlug = Str::slug($articleData['headline']);
                $slug = $baseSlug;
                $counter = 1;
                while (NewsArticle::where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                $articleData['slug'] = $slug;

                $article->update([
                    'title' => $articleData['headline'],
                    'slug' => $articleData['slug'],
                    'headline' => $articleData['headline'],
                    'summary' => $articleData['summary'],
                    'content' => $articleData['content'],
                    'status' => 'published',
                    'published_at' => Carbon::now(),
                ]);
            } else {
                $article->update(['status' => 'error', 'content' => 'AI generation failed. Check logs for details.']);
                $this->error("\n<fg=red>Error:</> Failed to generate article for {$modelClass} #{$report->id}. Check laravel.log for details from AiAssistantController.");
            }

            // Add a delay to avoid hitting API rate limits (TPM: 1,000,000 for Tier 1)
            $this->info("\nWaiting for 61 seconds to respect API rate limits...");
            sleep(61);
        }
        $progressBar->finish();
        $this->newLine(2);
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

        $response = Http::timeout(60)->get($apiUrl); // Increased timeout
        if ($response->successful()) {
            $data = $response->json();
            if (empty($data)) {
                Log::warning("Fetched report data is empty for article generation.", [
                    'report_class' => get_class($report),
                    'report_id' => $report->id,
                    'job_id' => $report->job_id,
                ]);
                return null;
            }

            // If it's a Trend report, extract only the necessary summary data to avoid MAX_TOKENS error.
            if ($report instanceof Trend && isset($data['summary_statistics'], $data['anomalous_hexes'])) {
                // Sort anomalous hexes by the lowest p-value to get the most significant ones.
                $anomalous_hexes = $data['anomalous_hexes'];
                usort($anomalous_hexes, function ($a, $b) {
                    $p_value_a = 1.0;
                    if (!empty($a['trend_analysis_results'])) {
                        $p_values_a = array_column($a['trend_analysis_results'], 'p_value_mann_kendall');
                        $p_value_a = min($p_values_a);
                    }
                    $p_value_b = 1.0;
                    if (!empty($b['trend_analysis_results'])) {
                        $p_values_b = array_column($b['trend_analysis_results'], 'p_value_mann_kendall');
                        $p_value_b = min($p_values_b);
                    }
                    return $p_value_a <=> $p_value_b;
                });

                // Limit to the top 25 most significant hexes.
                $top_anomalous_hexes = array_slice($anomalous_hexes, 0, 25);

                // Further simplify the data for each hex to send only the most significant trend.
                $simplified_hexes = [];
                foreach ($top_anomalous_hexes as $hex) {
                    $most_significant_trend = null;
                    $lowest_p_value = 1.0;

                    if (!empty($hex['trend_analysis_results'])) {
                        foreach ($hex['trend_analysis_results'] as $result) {
                            if (isset($result['p_value_mann_kendall']) && $result['p_value_mann_kendall'] < $lowest_p_value) {
                                $lowest_p_value = $result['p_value_mann_kendall'];
                                $most_significant_trend = $result;
                            }
                        }
                    }

                    if ($most_significant_trend) {
                        $simplified_hexes[] = [
                            'h3_index' => $hex['h3_index'],
                            'historical_average' => $hex['historical_average'],
                            'most_significant_trend' => $most_significant_trend,
                        ];
                    }
                }

                return [
                    'summary_statistics' => $data['summary_statistics'],
                    'anomalous_hexes' => $simplified_hexes, // Send the simplified data
                    'parameters' => $data['parameters'] ?? null,
                ];
            }

            return $data;
        }

        Log::error("Failed to fetch report data for article generation.", [
            'report_class' => get_class($report),
            'report_id' => $report->id,
            'job_id' => $report->job_id,
            'status' => $response->status(),
            'response' => $response->body(),
        ]);
        return null;
    }

    private function getReportContext($report): array
    {
        $title = "Report " . $report->id;
        $parameters = [];

        if ($report instanceof Trend) {
            $modelName = class_exists($report->model_class) ? $report->model_class::getHumanName() : 'Data';
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
