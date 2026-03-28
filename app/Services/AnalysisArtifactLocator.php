<?php

namespace App\Services;

use App\Models\AnalysisReportSnapshot;
use App\Models\Trend;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\FileAttributes;

class AnalysisArtifactLocator
{
    private const STAGE4_S3_CACHE_KEY = 'analysis_artifact_locator.stage4_s3_v1';
    private const STAGE6_S3_CACHE_KEY = 'analysis_artifact_locator.stage6_s3_v1';

    public function findPreferredTrendContext(?string $modelClass): ?array
    {
        if (!$modelClass) {
            return null;
        }

        $candidate = $this->selectPreferredCandidate(
            $this->trendCandidatesForModel($modelClass),
            $this->preferredAnalysisColumn($modelClass),
        );

        if (!$candidate) {
            return null;
        }

        return [
            'job_id' => $candidate['job_id'],
            'column_name' => $candidate['column_name'],
            'h3_resolution' => $candidate['h3_resolution'],
            'updated_at' => $this->formatCandidateDate($candidate),
            'summary' => TrendSummaryService::compute(
                $candidate['job_id'],
                (int) $candidate['h3_resolution'],
                (float) ($candidate['p_value_anomaly'] ?? 0.05),
                (float) ($candidate['p_value_trend'] ?? 0.05),
            ),
        ];
    }

    public function findPreferredScoreReport(?string $modelClass): ?array
    {
        if (!$modelClass) {
            return null;
        }

        $candidate = $this->selectPreferredCandidate(
            $this->scoreCandidatesForModel($modelClass),
            $this->preferredAnalysisColumn($modelClass),
        );

        if (!$candidate) {
            return null;
        }

        return [
            'job_id' => $candidate['job_id'],
            'artifact_name' => $candidate['artifact_name'],
            'resolution' => $candidate['h3_resolution'],
            'analysis_period_weeks' => $candidate['analysis_period_weeks'],
            'source_job_id' => $candidate['source_job_id'],
        ];
    }

    protected function preferredAnalysisColumn(string $modelClass): ?string
    {
        foreach (config('analysis_schedule.stage6.jobs', []) as $job) {
            if (($job['model'] ?? null) === $modelClass) {
                return $job['column'] ?? null;
            }
        }

        return null;
    }

    protected function trendCandidatesForModel(string $modelClass): Collection
    {
        $fromTrendRows = Trend::query()
            ->where('model_class', $modelClass)
            ->get()
            ->map(fn (Trend $trend) => $this->buildTrendCandidateFromTrend($trend))
            ->filter();

        $fromSnapshots = AnalysisReportSnapshot::query()
            ->where('artifact_name', 'stage4_h3_anomaly.json')
            ->get()
            ->map(fn (AnalysisReportSnapshot $snapshot) => $this->buildTrendCandidateFromPayload(
                $snapshot->job_id,
                $snapshot->artifact_name,
                $snapshot->payload ?? [],
                $snapshot->s3_last_modified,
                optional($snapshot->updated_at)->timestamp,
            ))
            ->filter(fn (?array $candidate) => ($candidate['model_class'] ?? null) === $modelClass);

        if ($fromSnapshots->isNotEmpty() || $fromTrendRows->isNotEmpty()) {
            return $fromTrendRows->concat($fromSnapshots)->values();
        }

        return collect($this->stage4CandidatesFromS3())
            ->where('model_class', $modelClass)
            ->values();
    }

    protected function scoreCandidatesForModel(string $modelClass): Collection
    {
        $fromSnapshots = AnalysisReportSnapshot::query()
            ->where(function ($query) {
                $query->where('artifact_name', 'like', 'stage6%')
                    ->orWhere('artifact_name', 'like', 'scoring_results%');
            })
            ->get()
            ->map(fn (AnalysisReportSnapshot $snapshot) => $this->buildScoreCandidateFromPayload(
                $snapshot->job_id,
                $snapshot->artifact_name,
                $snapshot->payload ?? [],
                $snapshot->s3_last_modified,
                optional($snapshot->updated_at)->timestamp,
            ))
            ->filter(fn (?array $candidate) => ($candidate['model_class'] ?? null) === $modelClass);

        if ($fromSnapshots->isNotEmpty()) {
            return $fromSnapshots->values();
        }

        return collect($this->stage6CandidatesFromS3())
            ->where('model_class', $modelClass)
            ->values();
    }

    protected function selectPreferredCandidate(Collection $candidates, ?string $preferredColumn): ?array
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($preferredColumn) {
            $matchingPreferredColumn = $candidates
                ->filter(fn (array $candidate) => ($candidate['column_name'] ?? null) === $preferredColumn)
                ->values();

            if ($matchingPreferredColumn->isNotEmpty()) {
                $candidates = $matchingPreferredColumn;
            }
        }

        $nonUnified = $candidates
            ->filter(fn (array $candidate) => ($candidate['column_name'] ?? null) !== 'unified')
            ->values();

        if ($nonUnified->isNotEmpty()) {
            $candidates = $nonUnified;
        }

        $items = $candidates->values()->all();

        usort($items, function (array $a, array $b) {
            $resolutionCompare = ((int) ($b['h3_resolution'] ?? 0)) <=> ((int) ($a['h3_resolution'] ?? 0));
            if ($resolutionCompare !== 0) {
                return $resolutionCompare;
            }

            return ((int) ($b['sort_timestamp'] ?? 0)) <=> ((int) ($a['sort_timestamp'] ?? 0));
        });

        return $items[0] ?? null;
    }

    protected function buildTrendCandidateFromTrend(Trend $trend): array
    {
        return [
            'job_id' => $trend->job_id,
            'artifact_name' => 'stage4_h3_anomaly.json',
            'model_class' => $trend->model_class,
            'column_name' => $trend->column_name,
            'h3_resolution' => (int) $trend->h3_resolution,
            'p_value_anomaly' => (float) $trend->p_value_anomaly,
            'p_value_trend' => (float) $trend->p_value_trend,
            'analysis_weeks_trend' => $trend->analysis_weeks_trend,
            'analysis_weeks_anomaly' => $trend->analysis_weeks_anomaly,
            'sort_timestamp' => optional($trend->updated_at)->timestamp ?? 0,
            'updated_at' => optional($trend->updated_at)?->toDateString(),
        ];
    }

    protected function buildTrendCandidateFromPayload(
        string $jobId,
        string $artifactName,
        array $payload,
        ?int $s3LastModified = null,
        ?int $fallbackTimestamp = null
    ): ?array {
        $parameters = $payload['parameters'] ?? data_get($payload, 'config.parameters.stage4_h3_anomaly', []);
        ['model_class' => $modelClass, 'column_name' => $columnName] = $this->resolveStage4Meta(
            $jobId,
            $parameters['model_class'] ?? null,
            $parameters['column_name'] ?? null,
        );

        if (!$modelClass) {
            return null;
        }

        return [
            'job_id' => $jobId,
            'artifact_name' => $artifactName,
            'model_class' => $modelClass,
            'column_name' => $columnName ?? 'unified',
            'h3_resolution' => (int) ($parameters['h3_resolution'] ?? 8),
            'p_value_anomaly' => (float) ($parameters['p_value_anomaly'] ?? 0.05),
            'p_value_trend' => (float) ($parameters['p_value_trend'] ?? 0.05),
            'analysis_weeks_trend' => $parameters['analysis_weeks_trend'] ?? [4, 26, 52],
            'analysis_weeks_anomaly' => (int) ($parameters['analysis_weeks_anomaly'] ?? 4),
            'sort_timestamp' => (int) ($s3LastModified ?? $fallbackTimestamp ?? 0),
            'updated_at' => $this->timestampToDateString($s3LastModified ?? $fallbackTimestamp),
        ];
    }

    protected function buildScoreCandidateFromPayload(
        string $jobId,
        string $artifactName,
        array $payload,
        ?int $s3LastModified = null,
        ?int $fallbackTimestamp = null
    ): ?array {
        $parameters = $payload['parameters'] ?? ($payload['config'] ?? []);
        ['model_class' => $modelClass, 'column_name' => $columnName] = $this->resolveStage6Meta(
            $jobId,
            $parameters['model_class'] ?? null,
            $parameters['column_name'] ?? null,
        );

        if (!$modelClass) {
            return null;
        }

        return [
            'job_id' => $jobId,
            'artifact_name' => $artifactName,
            'model_class' => $modelClass,
            'column_name' => $columnName,
            'h3_resolution' => (int) ($parameters['h3_resolution'] ?? 8),
            'analysis_period_weeks' => $parameters['analysis_period_weeks'] ?? null,
            'source_job_id' => $payload['source_job_id'] ?? null,
            'sort_timestamp' => (int) ($s3LastModified ?? $fallbackTimestamp ?? 0),
            'updated_at' => $this->timestampToDateString($s3LastModified ?? $fallbackTimestamp),
        ];
    }

    protected function stage4CandidatesFromS3(): array
    {
        return Cache::remember(self::STAGE4_S3_CACHE_KEY, now()->addMinutes(10), fn () => $this->scanS3Candidates(
            static fn (string $artifactName): bool => $artifactName === 'stage4_h3_anomaly.json',
            fn (string $jobId, string $artifactName, array $payload, ?int $lastModified) => $this->buildTrendCandidateFromPayload(
                $jobId,
                $artifactName,
                $payload,
                $lastModified,
            ),
        ));
    }

    protected function stage6CandidatesFromS3(): array
    {
        return Cache::remember(self::STAGE6_S3_CACHE_KEY, now()->addMinutes(10), fn () => $this->scanS3Candidates(
            static fn (string $artifactName): bool => Str::startsWith($artifactName, ['stage6', 'scoring_results']),
            fn (string $jobId, string $artifactName, array $payload, ?int $lastModified) => $this->buildScoreCandidateFromPayload(
                $jobId,
                $artifactName,
                $payload,
                $lastModified,
            ),
        ));
    }

    protected function scanS3Candidates(callable $includeArtifact, callable $mapPayload): array
    {
        $candidates = [];

        try {
            $flysystem = Storage::disk('s3')->getDriver();

            foreach ($flysystem->listContents('', true) as $item) {
                if (!($item instanceof FileAttributes)) {
                    continue;
                }

                $parts = explode('/', $item->path(), 2);
                if (count($parts) !== 2) {
                    continue;
                }

                [$jobId, $artifactName] = $parts;

                if (!$includeArtifact($artifactName)) {
                    continue;
                }

                $payload = json_decode(Storage::disk('s3')->get($item->path()), true);
                if (!is_array($payload)) {
                    continue;
                }

                $candidate = $mapPayload($jobId, $artifactName, $payload, $item->lastModified());
                if ($candidate) {
                    $candidates[] = $candidate;
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('AnalysisArtifactLocator S3 scan failed.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $candidates;
    }

    protected function resolveStage4Meta(string $jobId, ?string $modelClass, ?string $columnName): array
    {
        if ($modelClass) {
            return ['model_class' => $modelClass, 'column_name' => $columnName ?? 'unified'];
        }

        return $this->parseStage4JobIdForMeta($jobId);
    }

    protected function resolveStage6Meta(string $jobId, ?string $modelClass, ?string $columnName): array
    {
        if ($modelClass) {
            return ['model_class' => $modelClass, 'column_name' => $columnName];
        }

        return $this->parseStage6JobIdForMeta($jobId);
    }

    protected function parseStage4JobIdForMeta(string $jobId): array
    {
        if (!preg_match('/^laravel-(.+)-res\d+-\d+$/', $jobId, $matches)) {
            return ['model_class' => null, 'column_name' => null];
        }

        return $this->resolveModelAndColumnFromSuffix($matches[1]);
    }

    protected function parseStage6JobIdForMeta(string $jobId): array
    {
        if (!preg_match('/^laravel-hist-score-(.+)-res\d+-\d+$/', $jobId, $matches)) {
            return ['model_class' => null, 'column_name' => null];
        }

        return $this->resolveModelAndColumnFromSuffix($matches[1]);
    }

    protected function resolveModelAndColumnFromSuffix(string $modelAndColumn): array
    {
        foreach ($this->modelKeyMap() as $modelKey => $modelClass) {
            if ($modelAndColumn === $modelKey) {
                return ['model_class' => $modelClass, 'column_name' => 'unified'];
            }

            if (str_starts_with($modelAndColumn, $modelKey . '-')) {
                return [
                    'model_class' => $modelClass,
                    'column_name' => substr($modelAndColumn, strlen($modelKey) + 1),
                ];
            }
        }

        return ['model_class' => null, 'column_name' => null];
    }

    protected function modelKeyMap(): array
    {
        $keyMap = [];

        foreach (config('cities.cities', []) as $cityConfig) {
            foreach ($cityConfig['models'] ?? [] as $modelClass) {
                $keyMap[Str::kebab(class_basename($modelClass))] = $modelClass;
            }
        }

        uksort($keyMap, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        return $keyMap;
    }

    protected function formatCandidateDate(array $candidate): ?string
    {
        return $candidate['updated_at'] ?? $this->timestampToDateString($candidate['sort_timestamp'] ?? null);
    }

    protected function timestampToDateString(?int $timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp)->toDateString();
    }
}
