<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewsArticle;
use App\Models\Trend;
use App\Models\YearlyCountComparison;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DispatchLocalNewsArticle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-local-news-article {articleId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches a news article generation job to the local AI service.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $articleId = $this->argument('articleId');
        $article = NewsArticle::find($articleId);

        if (!$article) {
            $this->error("NewsArticle with ID {$articleId} not found.");
            return 1;
        }

        $this->info("Processing NewsArticle ID: {$article->id}");

        $report = $article->sourceReport;
        if (!$report) {
            $this->error("Source report not found for NewsArticle ID: {$article->id}");
            $article->update(['status' => 'error', 'content' => 'Source report not found.']);
            return 1;
        }

        try {
            $reportData = $this->fetchReportDataFromS3($report);

            if (!$reportData) {
                $this->error("Could not fetch or process report data for " . get_class($report) . " #{$report->id}.");
                $article->update(['status' => 'error', 'content' => 'Could not fetch report data from S3.']);
                return 1;
            }

            $reportContext = $this->getReportContext($report);
            $prompt = $this->buildPrompt($reportContext['title'], $reportData, $reportContext['parameters']);

            $apiBaseUrl = config('services.analysis_api.url');
            $jobId = 'completion-article-' . $article->id . '-' . time();

            $this->info("Dispatching completion job to local AI service with Job ID: {$jobId}");

            $response = Http::timeout(60)->post("{$apiBaseUrl}/api/v1/completions", [
                'job_id' => $jobId,
                'prompt' => $prompt,
                'model' => 'mannix/llama3.1-8b-abliterated:latest', // Or make this configurable
            ]);

            if ($response->successful() && $response->status() === 202) {
                $this->info("Successfully dispatched completion job.");
                $article->update([
                    'status' => 'generating',
                    'content' => 'Local AI generation in progress...',
                    'completion_job_id' => $jobId,
                ]);
                $this->info("NewsArticle {$article->id} updated with completion job ID: {$jobId}");
            } else {
                $errorMessage = "Failed to dispatch completion job. Status: {$response->status()}. Body: {$response->body()}";
                $this->error($errorMessage);
                $article->update(['status' => 'error', 'content' => $errorMessage]);
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error("An error occurred: " . $e->getMessage());
            $article->update(['status' => 'error', 'content' => 'An error occurred during dispatch: ' . $e->getMessage()]);
            Log::error('DispatchLocalNewsArticle failed', ['exception' => $e]);
            return 1;
        }

        return 0;
    }

    private function fetchReportDataFromS3($report)
    {
        $jobId = $report->job_id;
        $s3Path = null;

        if ($report instanceof Trend) {
            $s3Path = "{$jobId}/stage4_h3_anomaly.json";
        } elseif ($report instanceof YearlyCountComparison) {
            $s3Path = "{$jobId}/stage2_yearly_count_comparison.json";
        }

        if (!$s3Path) {
            $this->warn("Unsupported report type: " . get_class($report));
            return null;
        }

        $this->info("Fetching report data from S3 path: {$s3Path}");

        try {
            $s3 = Storage::disk('s3');
            if ($s3->exists($s3Path)) {
                $jsonContent = $s3->get($s3Path);
                $data = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error("Failed to decode JSON from S3: " . json_last_error_msg());
                    return null;
                }
                
                $this->info("Successfully fetched and decoded report data from S3.");
                // Apply the same data slimming logic as in GenerateNewsArticleJob
                return $this->slimData($report, $data);
            } else {
                $this->warn("Report file not found in S3 at path: {$s3Path}");
                return null;
            }
        } catch (\Exception $e) {
            $this->error("Failed to fetch report data from S3: " . $e->getMessage());
            Log::error("S3 fetch failed for {$s3Path}", ['exception' => $e]);
            return null;
        }
    }

    private function slimData($report, $data)
    {
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

    private function buildPrompt(string $title, array $reportData, array $parameters): string
    {
        $prettyJsonData = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $prettyJsonParams = json_encode($parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are a data journalist AI. Your task is to write a compelling, insightful, and clear news article based on the provided data analysis report.

**Report Title:**
{$title}

**Analysis Parameters:**
```json
{$prettyJsonParams}
```

**Analysis Results Data:**
```json
{$prettyJsonData}
```

**Instructions:**

1.  **Analyze the Data:** Carefully examine the provided JSON data. Identify the most significant findings, trends, anomalies, or comparisons. Look for the story in the numbers.
2.  **Write a News Article:** Based on your analysis, write a news article in Markdown format. The article must include:
    *   A compelling and descriptive `headline`.
    *   A concise `summary` of the key findings (2-3 sentences).
    *   The full `content` of the article, explaining the findings in detail. Use paragraphs, lists, and bold text to structure the article and improve readability.
3.  **Output Format:** Your entire response MUST be a single, valid JSON object. The JSON object should have three keys: `headline` (string), `summary` (string), and `content` (string). Do not include any text or formatting outside of this JSON object.

**Example JSON Output Structure:**
```json
{
  "headline": "This is the Headline of the Article",
  "summary": "This is a brief summary of the main points of the article.",
  "content": "This is the full content of the article, written in Markdown. It can include multiple paragraphs, lists, etc."
}
```
PROMPT;
    }
}
