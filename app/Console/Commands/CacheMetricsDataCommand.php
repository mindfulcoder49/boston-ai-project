<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

// Import Models
use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\FoodInspection;
use App\Models\PropertyViolation;
use App\Models\BuildingPermit;

class CacheMetricsDataCommand extends Command
{
    protected $signature = 'app:cache-metrics-data';
    protected $description = 'Calculates and caches all data metrics to a configuration file.';

    protected $mappableModels = [
        CrimeData::class,
        ThreeOneOneCase::class,
        FoodInspection::class,
        PropertyViolation::class,
        BuildingPermit::class,
    ];

    public function handle()
    {
        $this->info('Starting to calculate and cache metrics data...');

        $allMetrics = [];
        $overallPageLastUpdated = null;

        foreach ($this->mappableModels as $modelClassString) {
            $modelInstance = new $modelClassString();
            $modelName = $modelInstance::getModelNameForHumans();
            $dateField = $modelInstance::getDateField();

            $currentTotalRecords = $modelInstance::count();
            $currentDbMaxDateString = null;
            $currentModelUpdateTime = Carbon::now(); // Default to now, will be updated if max date is found

            if ($dateField && $currentTotalRecords > 0) {
                try {
                    $currentDbMaxDate = $modelInstance::max($dateField);
                    if ($currentDbMaxDate) {
                        $parsedDate = Carbon::parse($currentDbMaxDate);
                        $currentDbMaxDateString = $parsedDate->toIso8601String();
                        $currentModelUpdateTime = $parsedDate;
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching max date for {$modelName}: " . $e->getMessage());
                }
            }
            
            // Metrics will always be recalculated now
            $this->line("Calculating metrics for {$modelName}...");
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
                
                // Your working logic for violationLevelDistribution data retrieval
                $distribution = $modelInstance::select(DB::raw("CASE WHEN TRIM(LOWER(viol_level)) = '*' THEN 'Low' WHEN TRIM(LOWER(viol_level)) = '**' THEN 'Medium' WHEN TRIM(LOWER(viol_level)) = '***' THEN 'High' WHEN TRIM(LOWER(viol_level)) = 'low' THEN 'Low' WHEN TRIM(LOWER(viol_level)) = 'medium' THEN 'Medium' WHEN TRIM(LOWER(viol_level)) = 'high' THEN 'High' ELSE 'Other' END as category_name"), DB::raw("COUNT(*) as total_count"))
                    ->whereNotNull('viol_level')->whereRaw("TRIM(LOWER(viol_level)) != ''")->groupBy('category_name')->orderBy('total_count', 'desc')->take(4)->get();
                
                // This creates a Collection of stdClass objects, as per your original working snippet
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

            $modelMetricsData = $newlyCalculatedMetrics;

            $allMetrics[] = $modelMetricsData;

            if ($currentModelUpdateTime && (!isset($overallPageLastUpdated) || $currentModelUpdateTime->gt($overallPageLastUpdated))) {
                $overallPageLastUpdated = $currentModelUpdateTime;
            }
        }

        $pageLastUpdatedTimestamp = $overallPageLastUpdated ? $overallPageLastUpdated->toDateTimeString() : Carbon::now()->toDateTimeString();

        // Ensure $allMetrics contains only arrays before var_export
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

        $configContent = "<?php\n\nreturn " . var_export([
            'last_updated' => $pageLastUpdatedTimestamp,
            'data' => $allMetricsClean,
        ], true) . ";\n";
        
        // More robust preg_replace for stdClass::__set_state, just in case
        // This aims to convert stdClass::__set_state(array(...)) to array(...)
        $configContent = preg_replace('/stdClass::__set_state\s*\((array\s*\(.*?\))\s*\)/s', '$1', $configContent);
        
        // General cleanup for any remaining Eloquent model __set_state calls if they somehow slip through
        // This aims to convert Model::__set_state(array(...)) to array(...)
        $configContent = preg_replace('/\\\App\\\Models\\\[a-zA-Z]+::__set_state\s*\((array\s*\(.*?\))\s*\)/s', '$1', $configContent);

        try {
            File::put(config_path('metrics.php'), $configContent);
            $this->info('Successfully cached metrics data to config/metrics.php.');
        } catch (\Exception $e) {
            $this->error('Failed to write metrics data to config/metrics.php: ' . $e->getMessage());
            Log::error('Failed to write metrics data to config/metrics.php: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
