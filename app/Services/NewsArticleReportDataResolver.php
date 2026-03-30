<?php

namespace App\Services;

use App\Models\AnalysisReportSnapshot;
use App\Models\Trend;
use App\Models\YearlyCountComparison;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsArticleReportDataResolver
{
    public function resolve($report): ?array
    {
        if ($report instanceof Trend) {
            return $this->resolveTrend($report);
        }

        if ($report instanceof YearlyCountComparison) {
            return $this->resolveYearlyComparison($report);
        }

        return null;
    }

    private function resolveTrend(Trend $report): ?array
    {
        $summary = Cache::get(TrendSummaryService::cacheKey($report->job_id))
            ?? TrendSummaryService::compute(
                $report->job_id,
                $report->h3_resolution,
                $report->p_value_anomaly,
                $report->p_value_trend
            );

        if (empty($summary) || ($summary['status'] ?? '') !== 'ok') {
            Log::warning('Trend summary unavailable for article generation.', [
                'trend_id' => $report->id,
                'job_id' => $report->job_id,
            ]);

            return null;
        }

        return $summary;
    }

    private function resolveYearlyComparison(YearlyCountComparison $report): ?array
    {
        $data = AnalysisReportSnapshot::resolve($report->job_id, 'stage2_yearly_count_comparison.json');

        if (!$data) {
            $data = $this->fetchYearlyComparisonFromApi($report);
        }

        if (empty($data)) {
            Log::warning('Yearly comparison data unavailable for article generation.', [
                'report_id' => $report->id,
                'job_id' => $report->job_id,
            ]);

            return null;
        }

        return $this->slimYearlyComparisonData($report, $data);
    }

    private function fetchYearlyComparisonFromApi(YearlyCountComparison $report): ?array
    {
        $baseUrl = rtrim((string) config('services.analysis_api.url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        $apiUrl = "{$baseUrl}/api/v1/jobs/{$report->job_id}/results/stage2_yearly_count_comparison.json";

        try {
            $response = Http::timeout(120)->get($apiUrl);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch yearly comparison data for article generation.', [
                'report_id' => $report->id,
                'job_id' => $report->job_id,
                'api_url' => $apiUrl,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful()) {
            Log::error('Yearly comparison API returned an error for article generation.', [
                'report_id' => $report->id,
                'job_id' => $report->job_id,
                'api_url' => $apiUrl,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    private function slimYearlyComparisonData(YearlyCountComparison $report, array $data): ?array
    {
        if (!isset($data['results'])) {
            return $data;
        }

        $currentYear = $data['parameters']['analysis_current_year'] ?? null;

        if (!$currentYear) {
            return null;
        }

        $filteredResults = array_values(array_filter(
            $data['results'],
            fn ($item) => isset($item['to_date'][$currentYear]['change_pct'])
        ));

        usort(
            $filteredResults,
            fn ($a, $b) => ($b['to_date'][$currentYear]['change_pct'] ?? 0) <=> ($a['to_date'][$currentYear]['change_pct'] ?? 0)
        );

        return [
            'summary_of_changes' => [
                'top_5_increases_ytd' => array_slice($filteredResults, 0, 5),
                'top_5_decreases_ytd' => array_slice(array_reverse($filteredResults), 0, 5),
            ],
            'parameters' => $data['parameters'] ?? null,
        ];
    }
}
