<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache; // Added
use Illuminate\Support\Facades\Log; // Added

// Import Models
use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\FoodInspection;
use App\Models\PropertyViolation;
use App\Models\BuildingPermit;

class MetricsController extends Controller
{
    protected $mappableModels = [
        CrimeData::class,
        ThreeOneOneCase::class,
        FoodInspection::class,
        PropertyViolation::class,
        BuildingPermit::class,
    ];

    public function index()
    {
        $allMetrics = [];
        $overallPageLastUpdated = null;
        $cacheTtlHours = 24; // Define a TTL for cache entries, e.g., 24 hours

        foreach ($this->mappableModels as $modelClassString) {
            $modelInstance = new $modelClassString();
            $modelName = $modelInstance::getModelNameForHumans();
            $dateField = $modelInstance::getDateField(); // Can be null if not applicable

            $metricsDataCacheKey = "metrics:data:{$modelName}";
            $metricsDbMaxDateCacheKey = "metrics:db_max_date:{$modelName}"; // Stores the MAX(date_field) at the time of caching
            $metricsCacheWriteTimeKey = "metrics:cache_write_time:{$modelName}"; // Stores when this cache entry was written

            $cachedMetrics = Cache::get($metricsDataCacheKey);
            $cachedDbMaxDateString = Cache::get($metricsDbMaxDateCacheKey);
            $cachedWriteTimeString = Cache::get($metricsCacheWriteTimeKey);

            $currentTotalRecords = $modelInstance::count();
            $currentDbMaxDateString = null;
            if ($dateField && $currentTotalRecords > 0) {
                try {
                    $currentDbMaxDate = $modelInstance::max($dateField);
                    $currentDbMaxDateString = $currentDbMaxDate ? Carbon::parse($currentDbMaxDate)->toIso8601String() : null;
                } catch (\Exception $e) {
                    Log::error("Error fetching max date for {$modelName}: " . $e->getMessage());
                    // Proceed without currentDbMaxDateString, likely forcing a recalc or relying on older cache
                }
            }

            $recalculateMetrics = true;
            $modelMetricsData = []; // Holds the final metrics for this model
            $currentModelUpdateTime = null;

            if ($cachedMetrics !== null) {
                if ($dateField && $currentTotalRecords > 0) {
                    // Cache is fresh if the DB's max date matches the one stored with the cache
                    // OR if current DB max date is somehow older/null (e.g. data cleared) and we have a cached version based on a previous max date.
                    if ($cachedDbMaxDateString === $currentDbMaxDateString) {
                        $recalculateMetrics = false;
                    }
                    // If $cachedDbMaxDateString is not null, but $currentDbMaxDateString is null (data deleted),
                    // we should recalculate to reflect that there's no data. So $recalculateMetrics remains true.
                } else {
                    // No date field or no current records. Invalidation based on max date is not applicable.
                    // Rely on standard cache TTL. If cache exists, use it.
                    $recalculateMetrics = false;
                }
            }

            if ($recalculateMetrics) {
                $newlyCalculatedMetrics = ['modelName' => $modelName, 'tableName' => $modelInstance->getTable()];
                $newlyCalculatedMetrics['totalRecords'] = $currentTotalRecords;

                if ($newlyCalculatedMetrics['totalRecords'] > 0 && $dateField) {
                    try {
                        $minDateVal = $modelInstance::min($dateField);
                        $newlyCalculatedMetrics['minDate'] = $minDateVal ? Carbon::parse($minDateVal)->toDateString() : null;
                        $newlyCalculatedMetrics['maxDate'] = $currentDbMaxDateString ? Carbon::parse($currentDbMaxDateString)->toDateString() : null;

                        $newlyCalculatedMetrics['recordsLast30Days'] = $modelInstance::where($dateField, '>=', Carbon::now()->subDays(30))->count();
                        $newlyCalculatedMetrics['recordsLast90Days'] = $modelInstance::where($dateField, '>=', Carbon::now()->subDays(90))->count();
                        $newlyCalculatedMetrics['recordsLast1Year'] = $modelInstance::where($dateField, '>=', Carbon::now()->subYear())->count();
                    } catch (\Exception $e) {
                        Log::error("Error calculating general date metrics for {$modelName}: " . $e->getMessage());
                        $newlyCalculatedMetrics['minDate'] = 'Error';
                        $newlyCalculatedMetrics['maxDate'] = 'Error';
                        $newlyCalculatedMetrics['recordsLast30Days'] = 0;
                        $newlyCalculatedMetrics['recordsLast90Days'] = 0;
                        $newlyCalculatedMetrics['recordsLast1Year'] = 0;
                    }
                } else {
                    $newlyCalculatedMetrics['minDate'] = null;
                    $newlyCalculatedMetrics['maxDate'] = null;
                    $newlyCalculatedMetrics['recordsLast30Days'] = 0;
                    $newlyCalculatedMetrics['recordsLast90Days'] = 0;
                    $newlyCalculatedMetrics['recordsLast1Year'] = 0;
                }

                // Model-Specific Metrics
                if ($modelInstance instanceof CrimeData) {
                    $newlyCalculatedMetrics['offenseGroupDistribution'] = $modelInstance::select('offense_description', DB::raw('count(*) as total'))
                        ->whereNotNull('offense_description')->groupBy('offense_description')->orderBy('total', 'desc')->take(10)->get();
                    $newlyCalculatedMetrics['shootingIncidents'] = $modelInstance::where(function($query) { $query->where('shooting', '1'); })->count();
                } elseif ($modelInstance instanceof ThreeOneOneCase) {
                    $newlyCalculatedMetrics['caseStatusDistribution'] = $modelInstance::select('case_status', DB::raw('count(*) as total'))
                        ->whereNotNull('case_status')->groupBy('case_status')->orderBy('total', 'desc')->take(5)->get();
                    $avgClosure = $modelInstance::whereNotNull('closed_dt')->whereNotNull('open_dt')
                        ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, open_dt, closed_dt)) as avg_closure_hours'))->value('avg_closure_hours');
                    $newlyCalculatedMetrics['averageClosureTimeHours'] = $avgClosure ? round($avgClosure, 2) : null;
                } elseif ($modelInstance instanceof FoodInspection) {
                    $newlyCalculatedMetrics['resultDistribution'] = $modelInstance::select('result', DB::raw('count(*) as total'))
                        ->whereNotNull('result')->groupBy('result')->orderBy('total', 'desc')->take(5)->get();
                    $distribution = $modelInstance::select(DB::raw("CASE WHEN TRIM(LOWER(viol_level)) = '*' THEN 'Low' WHEN TRIM(LOWER(viol_level)) = '**' THEN 'Medium' WHEN TRIM(LOWER(viol_level)) = '***' THEN 'High' WHEN TRIM(LOWER(viol_level)) = 'low' THEN 'Low' WHEN TRIM(LOWER(viol_level)) = 'medium' THEN 'Medium' WHEN TRIM(LOWER(viol_level)) = 'high' THEN 'High' ELSE 'Other' END as category_name"), DB::raw("COUNT(*) as total_count"))
                        ->whereNotNull('viol_level')->whereRaw("TRIM(LOWER(viol_level)) != ''")->groupBy('category_name')->orderBy('total_count', 'desc')->take(4)->get();
                    $newlyCalculatedMetrics['violationLevelDistribution'] = $distribution->map(function ($item) { $m = new \stdClass(); $m->viol_level = $item->category_name; $m->total = $item->total_count; return $m; });
                } elseif ($modelInstance instanceof PropertyViolation) {
                    $newlyCalculatedMetrics['statusDistribution'] = $modelInstance::select('status', DB::raw('count(*) as total'))
                        ->whereNotNull('status')->groupBy('status')->orderBy('total', 'desc')->take(5)->get();
                    $newlyCalculatedMetrics['topViolationCodes'] = $modelInstance::select('code', 'description', DB::raw('count(*) as total'))
                        ->whereNotNull('code')->groupBy('code', 'description')->orderBy('total', 'desc')->take(10)->get();
                } elseif ($modelInstance instanceof BuildingPermit) {
                    $newlyCalculatedMetrics['workTypeDistribution'] = $modelInstance::select('worktype', DB::raw('count(*) as total'))
                        ->whereNotNull('worktype')->groupBy('worktype')->orderBy('total', 'desc')->take(10)->get();
                    $newlyCalculatedMetrics['permitStatusDistribution'] = $modelInstance::select('status', DB::raw('count(*) as total'))
                        ->whereNotNull('status')->groupBy('status')->orderBy('total', 'desc')->take(5)->get();
                    $newlyCalculatedMetrics['totalDeclaredValuation'] = $modelInstance::sum('declared_valuation');
                }
                // --- End of metrics calculation block ---

                Cache::put($metricsDataCacheKey, $newlyCalculatedMetrics, now()->addHours($cacheTtlHours));
                Cache::put($metricsCacheWriteTimeKey, Carbon::now()->toIso8601String(), now()->addHours($cacheTtlHours));
                if ($dateField && $currentTotalRecords > 0) {
                    Cache::put($metricsDbMaxDateCacheKey, $currentDbMaxDateString, now()->addHours($cacheTtlHours));
                } else {
                    Cache::forget($metricsDbMaxDateCacheKey);
                }
                
                $modelMetricsData = $newlyCalculatedMetrics;
                $currentModelUpdateTime = Carbon::now();
            } else {
                $modelMetricsData = $cachedMetrics;
                if ($dateField && $currentTotalRecords > 0 && $cachedDbMaxDateString) {
                    $currentModelUpdateTime = Carbon::parse($cachedDbMaxDateString);
                } elseif ($cachedWriteTimeString) {
                    $currentModelUpdateTime = Carbon::parse($cachedWriteTimeString);
                } else {
                    // Fallback if cache is inconsistent
                    $currentModelUpdateTime = Carbon::createFromTimestamp(0); // Treat as very old
                }
            }

            $allMetrics[] = $modelMetricsData;

            if ($currentModelUpdateTime && (!isset($overallPageLastUpdated) || $currentModelUpdateTime->gt($overallPageLastUpdated))) {
                $overallPageLastUpdated = $currentModelUpdateTime;
            }
        }

        $pageLastUpdatedTimestamp = $overallPageLastUpdated ? $overallPageLastUpdated->toDateTimeString() : Carbon::now()->toDateTimeString();

        return Inertia::render('DataMetrics', [
            'metricsData' => $allMetrics,
            'lastUpdated' => $pageLastUpdatedTimestamp,
        ]);
    }
}
