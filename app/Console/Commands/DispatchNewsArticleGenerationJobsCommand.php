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
            $this->warn('The --fresh option was used. Existing articles will be regenerated.');
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

            if (!$this->option('fresh') && $existingArticle && $existingArticle->status === 'published') {
                continue; // Skip if already published, unless --fresh is used
            }

            try {
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
            } catch (\Exception $e) {
                $this->error("\n<fg=red>An unexpected error occurred while processing report {$modelClass} #{$report->id}:</> " . $e->getMessage());
                Log::error("Unexpected error in DispatchNewsArticleGenerationJobsCommand", [
                    'exception' => $e,
                    'model' => $modelClass,
                    'report_id' => $report->id,
                ]);
            } finally {
                // Add a delay to avoid hitting API rate limits
                $this->info("\nWaiting for 61 seconds to respect API rate limits...");
                sleep(61);
            }
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
            if ($report instanceof Trend && isset($data['city_wide_results'])) {
                // Filter city-wide results for significant trends only
                $significant_trends = array_filter($data['city_wide_results'], function ($item) use ($report) {
                    if (!isset($item['trend_analysis'])) return false;
                    foreach ($item['trend_analysis'] as $trend_period) {
                        if (isset($trend_period['p_value']) && $trend_period['p_value'] < $report->p_value_trend) {
                            return true;
                        }
                    }
                    return false;
                });

                // Sort anomalous hexes by the lowest p-value to get the most significant ones.
                $anomalous_hexes = $data['results'] ?? [];
                usort($anomalous_hexes, function ($a, $b) {
                    $p_value_a = 1.0;
                    if (!empty($a['trend_analysis'])) {
                        $p_values_a = array_column(array_values($a['trend_analysis']), 'p_value');
                        $numeric_p_values_a = array_filter($p_values_a, 'is_numeric');
                        if (!empty($numeric_p_values_a)) {
                            $p_value_a = min($numeric_p_values_a);
                        }
                    }
                    $p_value_b = 1.0;
                    if (!empty($b['trend_analysis'])) {
                        $p_values_b = array_column(array_values($b['trend_analysis']), 'p_value');
                        $numeric_p_values_b = array_filter($p_values_b, 'is_numeric');
                        if (!empty($numeric_p_values_b)) {
                            $p_value_b = min($numeric_p_values_b);
                        }
                    }
                    return $p_value_a <=> $p_value_b;
                });

                // Limit to the top 5 most significant hexes.
                $top_anomalous_hexes = array_slice($anomalous_hexes, 0, 5);

                return [
                    'city_wide_significant_trends' => array_values($significant_trends),
                    'top_5_most_significant_anomalous_hexes' => $top_anomalous_hexes,
                    'parameters' => $data['parameters'] ?? null,
                ];
            }

            // For YearlyCountComparison, select top/bottom 5 changes
            if ($report instanceof YearlyCountComparison && isset($data['results'])) {
                $results = $data['results'];
                $current_year = $data['parameters']['analysis_current_year'] ?? null;

                if ($current_year && !empty($results)) {
                    // Filter out groups with no change or insufficient data
                    $filtered_results = array_filter($results, function ($item) use ($current_year) {
                        return isset($item['to_date'][$current_year]['change_pct']);
                    });

                    // Sort by year-to-date percentage change
                    usort($filtered_results, function ($a, $b) use ($current_year) {
                        $change_a = $a['to_date'][$current_year]['change_pct'] ?? 0;
                        $change_b = $b['to_date'][$current_year]['change_pct'] ?? 0;
                        return $change_b <=> $change_a; // Sort descending
                    });

                    $top_increases = array_slice($filtered_results, 0, 5);
                    $top_decreases = array_slice(array_reverse($filtered_results), 0, 5);

                    return [
                        'summary_of_changes' => [
                            'top_5_increases_ytd' => $top_increases,
                            'top_5_decreases_ytd' => $top_decreases,
                        ],
                        'parameters' => $data['parameters'] ?? null,
                    ];
                }
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
