<?php

namespace App\Console\Commands;

use App\Http\Controllers\HomeController;
use App\Support\OperationalSummaryLogger;
use App\Support\MetricsSnapshotStore;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// Import Models
use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\FoodInspection;
use App\Models\PropertyViolation;
use App\Models\BuildingPermit;

class CacheMetricsDataCommand extends Command
{
    protected $signature = 'app:cache-metrics-data';
    protected $description = 'Calculates and stores the current dashboard metrics snapshot.';

    public function handle(MetricsSnapshotStore $metricsSnapshotStore)
    {
        $mappableModels = $this->metricModelClasses();
        $metricsComputedAt = Carbon::now();

        $this->info('Starting to calculate and cache metrics data...');
        OperationalSummaryLogger::emit($this, $this->getName(), 'start', [
            'model_count' => count($mappableModels),
        ]);

        $allMetrics = [];
        $overallPageLastUpdated = null;

        foreach ($mappableModels as $modelClassString) {
            $modelInstance = new $modelClassString();
            $modelName = $modelInstance::getHumanName();
            $dateField = $modelInstance::getDateField();

            $aggregateMetrics = $this->collectAggregateMetrics(
                $modelClassString,
                $dateField,
                $metricsComputedAt,
                $modelName
            );
            $currentTotalRecords = $aggregateMetrics['totalRecords'];
            $currentModelUpdateTime = $aggregateMetrics['maxDate'];
            $currentDbMaxDateString = $currentModelUpdateTime?->toIso8601String();
            
            // Metrics will always be recalculated now
            if ($this->output->isVerbose()) {
                $this->line("Calculating metrics for {$modelName}...");
            }
            $newlyCalculatedMetrics = ['modelName' => $modelName, 'tableName' => $modelInstance->getTable()];
            $newlyCalculatedMetrics['totalRecords'] = $currentTotalRecords;

            if ($newlyCalculatedMetrics['totalRecords'] > 0 && $dateField) {
                $newlyCalculatedMetrics['minDate'] = $aggregateMetrics['minDate']?->toDateString();
                $newlyCalculatedMetrics['maxDate'] = $currentModelUpdateTime?->toDateString();
                $newlyCalculatedMetrics['recordsLast30Days'] = $aggregateMetrics['recordsLast30Days'];
                $newlyCalculatedMetrics['recordsLast90Days'] = $aggregateMetrics['recordsLast90Days'];
                $newlyCalculatedMetrics['recordsLast1Year'] = $aggregateMetrics['recordsLast1Year'];
            } else {
                $newlyCalculatedMetrics['minDate'] = null;
                $newlyCalculatedMetrics['maxDate'] = null;
                $newlyCalculatedMetrics['recordsLast30Days'] = 0;
                $newlyCalculatedMetrics['recordsLast90Days'] = 0;
                $newlyCalculatedMetrics['recordsLast1Year'] = 0;
            }

            $this->appendSpecificMetrics($newlyCalculatedMetrics, $modelInstance, $currentTotalRecords);

            $modelMetricsData = $newlyCalculatedMetrics;

            $allMetrics[] = $modelMetricsData;
            OperationalSummaryLogger::emit($this, $this->getName(), 'model_complete', [
                'model' => $modelName,
                'total_records' => $currentTotalRecords,
                'max_date' => $currentDbMaxDateString,
            ]);

            if ($currentModelUpdateTime && (!isset($overallPageLastUpdated) || $currentModelUpdateTime->gt($overallPageLastUpdated))) {
                $overallPageLastUpdated = $currentModelUpdateTime;
            }
        }

        $pageLastUpdatedTimestamp = $overallPageLastUpdated ? $overallPageLastUpdated->toDateTimeString() : null;

        // Normalize any remaining collection/stdClass values before persisting the snapshot.
        $allMetricsClean = [];
        foreach ($allMetrics as $metricSet) {
            $cleanSet = [];
            foreach ($metricSet as $key => $value) {
                // Specifically handle 'violationLevelDistribution' if it's a Collection (of stdClass objects)
                if ($key === 'violationLevelDistribution' && $value instanceof \Illuminate\Support\Collection) {
                    $cleanSet[$key] = $value->map(function ($stdClassItem) {
                        return (array) $stdClassItem; // Convert each stdClass object to an associative array
                    })->toArray(); // Convert the mapped collection to an array of associative arrays
                } elseif ($value instanceof \Illuminate\Support\Collection) {
                    // For other collections, assume they are already arrays of arrays (e.g., from ->get()->toArray())
                    // or need to be converted. ->toArray() is a general approach.
                    $cleanSet[$key] = $value->toArray();
                } elseif ($value instanceof \stdClass) {
                    // If a top-level value is stdClass (not expected if collections are handled)
                    $cleanSet[$key] = (array) $value;
                } elseif (is_object($value) && method_exists($value, 'toArray')) {
                    // For Eloquent models or other objects with a toArray method
                    $cleanSet[$key] = $value->toArray();
                } else {
                    // Assumed to be array or scalar, which is fine for var_export
                    $cleanSet[$key] = $value;
                }
            }
            $allMetricsClean[] = $cleanSet;
        }

        try {
            $metricsSnapshotStore->replaceCurrent($allMetricsClean, $overallPageLastUpdated, $metricsComputedAt);
            Cache::forget(HomeController::HOME_PAGE_CACHE_KEY);

            $this->info('Successfully cached metrics data to metrics_snapshots.');
            OperationalSummaryLogger::emit($this, $this->getName(), 'complete', [
                'models_processed' => count($allMetricsClean),
                'output_table' => 'metrics_snapshots',
                'snapshot_key' => MetricsSnapshotStore::CURRENT_SNAPSHOT_KEY,
                'last_updated' => $pageLastUpdatedTimestamp,
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to write metrics data to metrics_snapshots: ' . $e->getMessage());
            Log::error('Failed to write metrics data to metrics_snapshots: ' . $e->getMessage());
            OperationalSummaryLogger::emit($this, $this->getName(), 'failed', [
                'output_table' => 'metrics_snapshots',
                'snapshot_key' => MetricsSnapshotStore::CURRENT_SNAPSHOT_KEY,
                'message' => $e->getMessage(),
            ], 'error');
            return 1;
        }
        
        return 0;
    }

    /**
     * Derive metrics coverage from the configured city model set instead of a
     * hard-coded legacy subset.
     *
     * @return array<class-string>
     */
    protected function metricModelClasses(): array
    {
        return collect(config('cities.cities', []))
            ->flatMap(fn (array $cityConfig) => $cityConfig['models'] ?? [])
            ->filter(fn ($modelClass) => is_string($modelClass) && $modelClass !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeMetricTimestamp(mixed $rawDate, Carbon $capturedAt, string $modelName): ?Carbon
    {
        if (!$rawDate) {
            return null;
        }

        try {
            $parsed = Carbon::parse($rawDate);
        } catch (\Throwable $throwable) {
            Log::warning("Unable to parse metrics timestamp for {$modelName}.", [
                'raw_date' => $rawDate,
                'message' => $throwable->getMessage(),
            ]);

            return null;
        }

        if ($parsed->gt($capturedAt)) {
            Log::warning("Future-dated metrics timestamp detected for {$modelName}; clamping to command time.", [
                'raw_date' => $parsed->toIso8601String(),
                'captured_at' => $capturedAt->toIso8601String(),
            ]);

            return $capturedAt->copy();
        }

        return $parsed;
    }

    protected function collectAggregateMetrics(
        string $modelClassString,
        ?string $dateField,
        Carbon $metricsComputedAt,
        string $modelName
    ): array {
        if (!$dateField) {
            return [
                'totalRecords' => $modelClassString::query()->count(),
                'minDate' => null,
                'maxDate' => null,
                'recordsLast30Days' => 0,
                'recordsLast90Days' => 0,
                'recordsLast1Year' => 0,
            ];
        }

        try {
            $baseQuery = $modelClassString::query();
            $wrappedDateField = $baseQuery->getQuery()->getGrammar()->wrap($dateField);
            $capturedAt = $metricsComputedAt->toDateTimeString();
            $days30Ago = $metricsComputedAt->copy()->subDays(30)->toDateTimeString();
            $days90Ago = $metricsComputedAt->copy()->subDays(90)->toDateTimeString();
            $yearAgo = $metricsComputedAt->copy()->subYear()->toDateTimeString();

            $aggregate = $modelClassString::query()
                ->selectRaw('COUNT(*) as total_records')
                ->selectRaw(
                    "MIN(CASE WHEN {$wrappedDateField} IS NOT NULL AND {$wrappedDateField} <= ? THEN {$wrappedDateField} END) as min_date",
                    [$capturedAt]
                )
                ->selectRaw(
                    "MAX(CASE WHEN {$wrappedDateField} IS NOT NULL AND {$wrappedDateField} <= ? THEN {$wrappedDateField} END) as max_date",
                    [$capturedAt]
                )
                ->selectRaw(
                    "SUM(CASE WHEN {$wrappedDateField} IS NOT NULL AND {$wrappedDateField} >= ? AND {$wrappedDateField} <= ? THEN 1 ELSE 0 END) as records_last_30_days",
                    [$days30Ago, $capturedAt]
                )
                ->selectRaw(
                    "SUM(CASE WHEN {$wrappedDateField} IS NOT NULL AND {$wrappedDateField} >= ? AND {$wrappedDateField} <= ? THEN 1 ELSE 0 END) as records_last_90_days",
                    [$days90Ago, $capturedAt]
                )
                ->selectRaw(
                    "SUM(CASE WHEN {$wrappedDateField} IS NOT NULL AND {$wrappedDateField} >= ? AND {$wrappedDateField} <= ? THEN 1 ELSE 0 END) as records_last_1_year",
                    [$yearAgo, $capturedAt]
                )
                ->first();

            return [
                'totalRecords' => (int) ($aggregate->total_records ?? 0),
                'minDate' => $this->normalizeMetricTimestamp($aggregate->min_date ?? null, $metricsComputedAt, $modelName),
                'maxDate' => $this->normalizeMetricTimestamp($aggregate->max_date ?? null, $metricsComputedAt, $modelName),
                'recordsLast30Days' => (int) ($aggregate->records_last_30_days ?? 0),
                'recordsLast90Days' => (int) ($aggregate->records_last_90_days ?? 0),
                'recordsLast1Year' => (int) ($aggregate->records_last_1_year ?? 0),
            ];
        } catch (\Throwable $throwable) {
            Log::error("Error calculating aggregate metrics for {$modelName}: " . $throwable->getMessage());

            return [
                'totalRecords' => 0,
                'minDate' => null,
                'maxDate' => null,
                'recordsLast30Days' => 0,
                'recordsLast90Days' => 0,
                'recordsLast1Year' => 0,
            ];
        }
    }

    protected function appendSpecificMetrics(array &$newlyCalculatedMetrics, Model $modelInstance, int $currentTotalRecords): void
    {
        if ($this->shouldDeferSpecificMetrics($modelInstance, $currentTotalRecords)) {
            $newlyCalculatedMetrics['specificMetricsDeferred'] = true;
            $newlyCalculatedMetrics['specificMetricsReason'] = 'Detailed breakdowns were skipped in the daily snapshot to keep the metrics job lightweight.';

            OperationalSummaryLogger::emit($this, $this->getName(), 'specific_metrics_deferred', [
                'model' => $modelInstance::getHumanName(),
                'total_records' => $currentTotalRecords,
                'threshold' => (int) config('backend_admin.metrics.specific_metrics_max_records', 500000),
            ]);

            return;
        }

        // Model-Specific Metrics - Ensure all ->get() calls are followed by ->toArray()
        if ($modelInstance instanceof CrimeData) {
            $newlyCalculatedMetrics['offenseGroupDistribution'] = $modelInstance::select('offense_description', DB::raw('count(*) as total'))
                ->whereNotNull('offense_description')->groupBy('offense_description')->orderBy('total', 'desc')->take(10)->get()->toArray();
            $newlyCalculatedMetrics['shootingIncidents'] = $modelInstance::where('shooting', '1')->count();
        } elseif ($modelInstance instanceof ThreeOneOneCase) {
            $newlyCalculatedMetrics['caseStatusDistribution'] = $modelInstance::select('case_status', DB::raw('count(*) as total'))
                ->whereNotNull('case_status')->groupBy('case_status')->orderBy('total', 'desc')->take(5)->get()->toArray();
            $avgClosure = $modelInstance::whereNotNull('closed_dt')->whereNotNull('open_dt')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, open_dt, closed_dt)) as avg_closure_hours'))->value('avg_closure_hours');
            $newlyCalculatedMetrics['averageClosureTimeHours'] = $avgClosure ? round($avgClosure, 2) : null;
        } elseif ($modelInstance instanceof FoodInspection) {
            $newlyCalculatedMetrics['resultDistribution'] = $modelInstance::select('result', DB::raw('count(*) as total'))
                ->whereNotNull('result')->groupBy('result')->orderBy('total', 'desc')->take(5)->get()->toArray();

            $distribution = $modelInstance::select(DB::raw("CASE WHEN TRIM(LOWER(viol_level)) = '*' THEN 'Low' WHEN TRIM(LOWER(viol_level)) = '**' THEN 'Medium' WHEN TRIM(LOWER(viol_level)) = '***' THEN 'High' WHEN TRIM(LOWER(viol_level)) = 'low' THEN 'Low' WHEN TRIM(LOWER(viol_level)) = 'medium' THEN 'Medium' WHEN TRIM(LOWER(viol_level)) = 'high' THEN 'High' ELSE 'Other' END as category_name"), DB::raw("COUNT(*) as total_count"))
                ->whereNotNull('viol_level')->whereRaw("TRIM(LOWER(viol_level)) != ''")->groupBy('category_name')->orderBy('total_count', 'desc')->take(4)->get();

            $newlyCalculatedMetrics['violationLevelDistribution'] = $distribution->map(function ($item) {
                $m = new \stdClass();
                $m->viol_level = $item->category_name;
                $m->total = $item->total_count;
                return $m;
            });
        } elseif ($modelInstance instanceof PropertyViolation) {
            $newlyCalculatedMetrics['statusDistribution'] = $modelInstance::select('status', DB::raw('count(*) as total'))
                ->whereNotNull('status')->groupBy('status')->orderBy('total', 'desc')->take(5)->get()->toArray();
            $newlyCalculatedMetrics['topViolationCodes'] = $modelInstance::select('code', 'description', DB::raw('count(*) as total'))
                ->whereNotNull('code')->groupBy('code', 'description')->orderBy('total', 'desc')->take(10)->get()->toArray();
        } elseif ($modelInstance instanceof BuildingPermit) {
            $newlyCalculatedMetrics['workTypeDistribution'] = $modelInstance::select('worktype', DB::raw('count(*) as total'))
                ->whereNotNull('worktype')->groupBy('worktype')->orderBy('total', 'desc')->take(10)->get()->toArray();
            $newlyCalculatedMetrics['permitStatusDistribution'] = $modelInstance::select('status', DB::raw('count(*) as total'))
                ->whereNotNull('status')->groupBy('status')->orderBy('total', 'desc')->take(5)->get()->toArray();
            $newlyCalculatedMetrics['totalDeclaredValuation'] = (float) $modelInstance::sum('declared_valuation');
        }
    }

    protected function shouldDeferSpecificMetrics(Model $modelInstance, int $currentTotalRecords): bool
    {
        if (
            !($modelInstance instanceof CrimeData)
            && !($modelInstance instanceof ThreeOneOneCase)
            && !($modelInstance instanceof FoodInspection)
            && !($modelInstance instanceof PropertyViolation)
            && !($modelInstance instanceof BuildingPermit)
        ) {
            return false;
        }

        $maxRecords = (int) config('backend_admin.metrics.specific_metrics_max_records', 500000);

        return $maxRecords > 0 && $currentTotalRecords > $maxRecords;
    }
}
