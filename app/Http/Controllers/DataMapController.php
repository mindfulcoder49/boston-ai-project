<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\Mappable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DataMapController extends Controller
{
    // Registry of model keys to their class strings
    protected array $modelRegistry = [];

    public function __construct()
    {
        $this->modelRegistry = [
            '311_cases' => \App\Models\ThreeOneOneCase::class,
            'cambridge_311_cases' => \App\Models\CambridgeThreeOneOneCase::class,

            'property_violations' => \App\Models\PropertyViolation::class,
            'cambridge_housing_violations' => \App\Models\CambridgeHousingViolationData::class,

            'food_inspections' => \App\Models\FoodInspection::class,
            'cambridge_sanitary_inspections' => \App\Models\CambridgeSanitaryInspectionData::class,

            'construction_off_hours' => \App\Models\ConstructionOffHour::class,

            'building_permits' => \App\Models\BuildingPermit::class,
            'cambridge_building_permits' => \App\Models\CambridgeBuildingPermitData::class,

            'crime' => \App\Models\CrimeData::class,
            'everett_crime' => \App\Models\EverettCrimeData::class,
            'cambridge_crime_reports' => \App\Models\CambridgeCrimeReportData::class,
            // Cambridge Models
            'person_crash_data' => \App\Models\PersonCrashData::class,

            'chicago_crime' => \App\Models\ChicagoCrime::class,
        ];

        // Validate that all registered models use the Mappable trait
        foreach ($this->modelRegistry as $key => $class) {
            if (!class_exists($class) || !in_array(Mappable::class, class_uses_recursive($class))) {
                Log::error("Model class {$class} for key '{$key}' either does not exist or does not use the Mappable trait. Please ensure it's correctly configured.");
                // Optionally, unset $this->modelRegistry[$key] or throw an exception
                // For now, we'll log an error. This should be addressed during development.
            }
        }
    }

    protected function getCityContextForDataType(string $dataType): array
    {
        // Chicago models
        if (Str::startsWith($dataType, 'chicago_')) {
            return [
                'city' => 'chicago',
                'center' => [41.8781, -87.6298], // lat, lon
                'zoom' => 11,
            ];
        }

        // Default to Boston for all others (Boston, Cambridge, Everett, etc.)
        return [
            'city' => 'boston',
            'center' => [42.3601, -71.0589], // lat, lon
            'zoom' => 12,
        ];
    }

    public function getModelMapping(): array
    {
        return $this->modelRegistry;
    }

    public function getModelClassForDataType(string $dataType): ?string
    {
        if (!isset($this->modelRegistry[$dataType])) {
            abort(404, "Data type '{$dataType}' not found or not configured.");
        }
        $modelClass = $this->modelRegistry[$dataType];

        // This check is now also in constructor, but good for safety here too.
        if (!class_exists($modelClass) || !in_array(Mappable::class, class_uses_recursive($modelClass))) {
            abort(500, "Model for {$dataType} ('{$modelClass}') does not exist or use the Mappable trait.");
        }
        return $modelClass;
    }

    public function getMinDateForEffectiveUser(string $dataTypeOrModelClass, ?\App\Models\User $userContext = null)
    {
        // $dataTypeOrModelClass is not strictly needed here if tier logic is global.
        // Kept for signature consistency, might be used if tier rules become model-specific.
        $effectiveUser = $userContext ?: Auth::user();
        $tierMinDate = null;

        if (!$effectiveUser) {
            return null; // Default guest access: 1 month
        }

        $effectiveTierDetails = $effectiveUser->getEffectiveTierDetails();
        $effectiveTier = $effectiveTierDetails['tier'];

        if ($effectiveTier === 'free') {
            $tierMinDate = Carbon::now()->subMonths(2);
        } elseif ($effectiveTier === 'basic') {
            $tierMinDate = Carbon::now()->subMonths(6);
        } elseif ($effectiveTier === 'pro') {
            $tierMinDate = null; 
        } else {
            $tierMinDate = Carbon::now()->subMonths(1);
        }
        
        //return $tierMinDate;
        return null;
    }

    private function getDataTypeConfig(string $modelClass): array
    {
        if (!class_exists($modelClass) || !in_array(Mappable::class, class_uses_recursive($modelClass))) {
            Log::error("Cannot get data type config for non-mappable model: {$modelClass}");
            return []; // Or throw an exception
        }
        $filterDesc = $modelClass::getFilterableFieldsDescription();
        if (is_string($filterDesc)) {
            try {
                $filterDesc = json_decode($filterDesc, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                Log::error("Failed to decode filterFieldsDescription for {$modelClass}: " . $e->getMessage());
                $filterDesc = [];
            }
        }

        return [
            'humanName' => $modelClass::getHumanName(),
            'iconClass' => $modelClass::getIconClass(),
            'alcivartech_type_for_styling' => $modelClass::getAlcivartechTypeForStyling(),
            'latitudeField' => $modelClass::getLatitudeField(), // Note: This is the original field name
            'longitudeField' => $modelClass::getLongitudeField(), // Note: This is the original field name
            'dateField' => $modelClass::getDateField(),
            'externalIdField' => $modelClass::getExternalIdName(),
            'filterFieldsDescription' => $filterDesc,
            'searchableColumns' => $modelClass::getSearchableColumns(),
            // 'modelNameForHumansMappable' is no longer needed as getHumanName is primary
        ];
    }

    public function index(Request $request, string $dataType)
    {
        $modelClass = $this->getModelClassForDataType($dataType);
        $config = $this->getDataTypeConfig($modelClass);
        $cityContext = $this->getCityContextForDataType($dataType);

        $tierMinDate = $this->getMinDateForEffectiveUser($modelClass, Auth::user());

        // Create an instance of the model to get the connection name
        $modelInstance = new $modelClass();
        $query = $modelClass::on($modelInstance->getConnectionName())->getQuery();
        //log the query
        Log::info("Query for {$dataType} on connection {$modelInstance->getConnectionName()}: " . $query->toSql());
        Log::info("Query bindings: " . json_encode($query->getBindings()));

        if ($tierMinDate) {
            $query->where($config['dateField'], '>=', $tierMinDate->toDateString());
        }

        $limit = $request->input('limit', 1000);
        $query->orderBy($config['dateField'], 'desc');
        $initialData = $query->limit(max(0, min($limit, 100000)))->get();
        $initialData = $this->enrichData($initialData, $dataType, $modelClass);


        $allModelConfigurationsForToolbar = [];
        foreach ($this->modelRegistry as $key => $class) {
            if (class_exists($class) && in_array(Mappable::class, class_uses_recursive($class))) {
                 $allModelConfigurationsForToolbar[] = [
                    'dataType' => $key,
                    'name' => $class::getHumanName(),
                    'iconClass' => $class::getIconClass(),
                ];
            }
        }
        
        $mapConfiguration = $this->generateMapConfiguration();

        $pageProps = [
            'initialData' => $initialData,
            'filters' => $request->all(),
            'dataType' => $dataType,
            'dataTypeConfig' => $config,
            'allModelConfigurationsForToolbar' => $allModelConfigurationsForToolbar,
            'mapConfiguration' => $mapConfiguration, // Add map configuration
            'initialClusterRadius' => $request->input('clusterRadius', 80), // Add this line
            'initialMapSettings' => [
                'center' => $cityContext['center'],
                'zoom' => $cityContext['zoom'],
            ],
        ];

        return Inertia::render('DataMap', $pageProps);
    }

    public function combinedIndex(Request $request)
    {
        $allDataTypeDetails = [];
        $initialModelKey = null;
        $initialDataSets = [];
        $initialFilters = ['limit' => 100]; // Default initial filter

        if (!empty($this->modelRegistry)) {
            $initialModelKey = array_key_first($this->modelRegistry);
        }

        foreach ($this->modelRegistry as $modelKey => $modelClassString) {
            if (!class_exists($modelClassString) || !in_array(Mappable::class, class_uses_recursive($modelClassString))) {
                Log::warning("Skipping data type {$modelKey} in combinedIndex as its model class {$modelClassString} is not Mappable or does not exist.");
                continue;
            }
            $allDataTypeDetails[$modelKey] = $this->getDataTypeConfig($modelClassString);

            if ($modelKey === $initialModelKey) {
                $config = $allDataTypeDetails[$initialModelKey];
                $query = $modelClassString::query();
                $tierMinDate = $this->getMinDateForEffectiveUser($modelClassString, Auth::user());
                if ($tierMinDate) {
                    $query->where($config['dateField'], '>=', $tierMinDate->toDateString());
                }
                if (isset($initialFilters['limit'])) {
                     $query->limit(max(0, min((int)$initialFilters['limit'], 100000)));
                }
                $query->orderBy($config['dateField'], 'desc');
                $dataForInitialType = $this->enrichData($query->get(), $initialModelKey, $modelClassString);
                if (!$dataForInitialType->isEmpty()) {
                    $initialDataSets[$initialModelKey] = $dataForInitialType;
                }
            }
        }
        
        // Fallback if initialModelKey had no data
        if ($initialModelKey && empty($initialDataSets[$initialModelKey]) && !empty($allDataTypeDetails)) {
             // Try to find the first model key from $allDataTypeDetails that might have data or is configured
            $firstAvailableModelKey = array_key_first($allDataTypeDetails);
            if ($firstAvailableModelKey && $firstAvailableModelKey !== $initialModelKey) { // if different from original initialModelKey
                $initialModelKey = $firstAvailableModelKey; // Update initialModelKey
                 if (empty($initialDataSets[$initialModelKey])) { // Check if data needs to be fetched
                    $config = $allDataTypeDetails[$initialModelKey];
                    $modelClassString = $this->modelRegistry[$initialModelKey];
                    $query = $modelClassString::query();
                    $tierMinDateOnFallback = $this->getMinDateForEffectiveUser($modelClassString, Auth::user());
                    if ($tierMinDateOnFallback) {
                        $query->where($config['dateField'], '>=', $tierMinDateOnFallback->toDateString());
                    }
                    if (isset($initialFilters['limit'])) {
                        $query->limit(max(0, min((int)$initialFilters['limit'], 100000)));
                    }
                    $query->orderBy($config['dateField'], 'desc');
                    $dataForFallbackInitialType = $this->enrichData($query->get(), $initialModelKey, $modelClassString);
                    if (!$dataForFallbackInitialType->isEmpty()) {
                        $initialDataSets[$initialModelKey] = $dataForFallbackInitialType;
                    }
                }
            } elseif (!$firstAvailableModelKey) { // If $allDataTypeDetails was empty or became empty
                 $initialModelKey = null;
            }
        }


        $allModelConfigurationsForToolbar = [];
        foreach ($this->modelRegistry as $key => $class) {
             if (class_exists($class) && in_array(Mappable::class, class_uses_recursive($class))) {
                $allModelConfigurationsForToolbar[] = [
                    'dataType' => $key,
                    'name' => $class::getHumanName(),
                    'iconClass' => $class::getIconClass(),
                ];
            }
        }

        $mapConfiguration = $this->generateMapConfiguration();

        return Inertia::render('CombinedDataMap', [
            'modelMapping' => $this->getModelMapping(),
            'initialDataType' => $initialModelKey,
            'initialDataSets' => $initialDataSets,
            'initialFilters' => $initialFilters,
            'allDataTypeDetails' => $allDataTypeDetails,
            'allModelConfigurationsForToolbar' => $allModelConfigurationsForToolbar,
            'mapConfiguration' => $mapConfiguration, // Add map configuration
        ]);
    }

// ... applyQueryFilters remains largely the same, it already uses $modelClass::getDateField() etc. ...
    public function applyQueryFilters(Builder $query, string $modelClass, array $filters, ?User $userContext)
    {
        $dateField = $modelClass::getDateField();
        $searchableColumns = $modelClass::getSearchableColumns();
        $tierMinDate = $this->getMinDateForEffectiveUser($modelClass, $userContext); 

        $processedKeys = [];

        if ($tierMinDate) {
            $query->where($dateField, '>=', $tierMinDate->toDateString());
        }

        foreach ($filters as $key => $value) {
            if (in_array($key, $processedKeys) || $key === 'search_term' || $key === 'limit' ||
                ($value === null && !is_bool($value)) || ($value === '' && !is_bool($value)) || (is_array($value) && empty(array_filter($value, fn($item) => ($item !== null && $item !== '') || is_bool($item) )))) {
                continue;
            }

            if ($key === 'start_date' && !empty($value)) {
                $userStartDate = Carbon::parse($value);
                $effectiveStartDate = $value;
                if ($tierMinDate && $userStartDate->lt($tierMinDate)) {
                    $effectiveStartDate = $tierMinDate->toDateString();
                }

                $endDateValue = $filters['end_date'] ?? null;
                if (!empty($endDateValue)) {
                    $query->whereBetween($dateField, [$effectiveStartDate, $endDateValue]);
                    $processedKeys[] = 'end_date';
                } else {
                    $query->where($dateField, '>=', $effectiveStartDate);
                }
                $processedKeys[] = 'start_date';
                continue;
            } elseif ($key === 'end_date' && !empty($value) && !isset($filters['start_date'])) {
                $query->where($dateField, '<=', $value);
                $processedKeys[] = 'end_date';
                continue;
            }

            if (Str::endsWith($key, '_start') || Str::endsWith($key, '_min')) {
                $isDateRange = Str::endsWith($key, '_start');
                $suffix = $isDateRange ? '_start' : '_min';
                $correspondingSuffix = $isDateRange ? '_end' : '_max';

                $baseColumn = Str::beforeLast($key, $suffix);
                if (!Schema::hasColumn($modelClass::make()->getTable(), $baseColumn)) {
                    Log::warning("Invalid base column '{$baseColumn}' derived from filter key '{$key}' for model {$modelClass}. Skipping.");
                    continue;
                }

                $startValue = $value;
                $endKey = $baseColumn . $correspondingSuffix;
                $endValue = $filters[$endKey] ?? null;

                if ($startValue === '' && !is_numeric($startValue)) $startValue = null;
                if ($endValue === '' && !is_numeric($endValue)) $endValue = null;

                if ($startValue !== null && $endValue !== null) {
                    if (!$isDateRange) {
                        $numStartValue = filter_var($startValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        $numEndValue = filter_var($endValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        if ($numStartValue !== null && $numEndValue !== null) {
                            $query->whereBetween($baseColumn, [$numStartValue, $numEndValue]);
                        } else {
                            Log::warning("Invalid numeric range values for {$baseColumn}: min='{$startValue}', max='{$endValue}'");
                        }
                    } else {
                        $query->whereBetween($baseColumn, [$startValue, $endValue]);
                    }
                    $processedKeys[] = $endKey;
                } elseif ($startValue !== null) {
                    if (!$isDateRange) {
                        $numStartValue = filter_var($startValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        if ($numStartValue !== null) {
                            $query->where($baseColumn, '>=', $numStartValue);
                        } else {
                             Log::warning("Invalid numeric min value for {$baseColumn}: '{$startValue}'");
                        }
                    } else {
                        $query->where($baseColumn, '>=', $startValue);
                    }
                }
                $processedKeys[] = $key;
                continue;
            } elseif (Str::endsWith($key, '_end') || Str::endsWith($key, '_max')) {
                $isDateRange = Str::endsWith($key, '_end');
                $suffix = $isDateRange ? '_end' : '_max';
                $correspondingSuffix = $isDateRange ? '_start' : '_min';

                $baseColumn = Str::beforeLast($key, $suffix);
                if (!Schema::hasColumn($modelClass::make()->getTable(), $baseColumn)) {
                    Log::warning("Invalid base column '{$baseColumn}' derived from filter key '{$key}' for model {$modelClass}. Skipping.");
                    continue;
                }
                $startKeyForThis = $baseColumn . $correspondingSuffix;

                $currentEndValue = $value;
                if ($currentEndValue === '' && !is_numeric($currentEndValue)) $currentEndValue = null;

                if (!isset($filters[$startKeyForThis]) && $currentEndValue !== null) {
                    if (!$isDateRange) {
                        $numEndValue = filter_var($currentEndValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        if ($numEndValue !== null) {
                            $query->where($baseColumn, '<=', $numEndValue);
                        } else {
                            Log::warning("Invalid numeric max value for {$baseColumn}: '{$currentEndValue}'");
                        }
                    } else {
                         $query->where($baseColumn, '<=', $currentEndValue);
                    }
                }
                $processedKeys[] = $key;
                continue;
            }
            
            if (!in_array($key, $processedKeys)) {
                if (!Schema::hasColumn($modelClass::make()->getTable(), $key)) {
                    Log::warning("Invalid filter key '{$key}' (not a column) for model {$modelClass}. Skipping.");
                    continue;
                }
                if (is_array($value)) {
                    $filteredValues = array_filter($value, fn($item) => $item !== null && $item !== '');
                    if (!empty($filteredValues)) {
                        $query->whereIn($key, $filteredValues);
                    }
                } elseif (is_bool($value)) {
                    $query->where($key, $value);
                } else {
                    $query->where($key, 'LIKE', "%{$value}%");
                }
                $processedKeys[] = $key;
            }
        }

        if (!empty($filters['search_term']) && !empty($searchableColumns)) {
            $searchTerm = $filters['search_term'];
            $query->where(function ($q) use ($searchableColumns, $searchTerm, $modelClass) { // Added $modelClass for logging
                $modelInstance = app($modelClass);
                $connection = $modelInstance->getConnectionName();
                $table = $modelInstance->getTable();
                foreach ($searchableColumns as $col) {
                     if (Schema::connection($connection)->hasColumn($table, $col)) { // Check column existence on correct connection
                         $q->orWhere($col, 'LIKE', '%' . $searchTerm . '%');
                     } else {
                        Log::warning("Searchable column '{$col}' not found in table '{$table}' on connection '{$connection}' for model {$modelClass}. Skipping in search_term.");
                    }
                }
            });
        }
    }


    public function getData(Request $request, string $dataType)
    {
        $modelClass = $this->getModelClassForDataType($dataType);
        Log::info("Fetching data for {$dataType} with filters: " . json_encode($request->input('filters')));
        
        $query = $modelClass::query();
        $filters = $request->input('filters', []);
        $currentUser = Auth::user();

        $this->applyQueryFilters($query, $modelClass, $filters, $currentUser);

        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int)$filters['limit'] : 1000;
        $query->limit(max(0, min($limit, 100000))); 

        $query->orderBy($modelClass::getDateField(), 'desc');
        
        Log::info("Query SQL: " . $query->toSql());
        Log::info("Query bindings: " . json_encode($query->getBindings()));
        $data = $query->get();

        $data = $this->enrichData($data, $dataType, $modelClass);

        $mapConfiguration = $this->generateMapConfiguration();

        return response()->json([
            'data' => $data,
            'filtersApplied' => $filters,
            'mapConfiguration' => $mapConfiguration, // Add map configuration
        ]);
    }

    public function enrichData( $data, string $dataType, string $modelClass)
    {
        // $modelClass is now passed, no need to call getModelClassForDataType again
        // $config is replaced by direct calls to $modelClass static methods

        $alcivartechTypeForStyling = $modelClass::getAlcivartechTypeForStyling();
        $latField = $modelClass::getLatitudeField();
        $lngField = $modelClass::getLongitudeField();
        $dateFieldFromConfig = $modelClass::getDateField();


        $data = $data->map(function ($point) use ($dataType, $modelClass, $alcivartechTypeForStyling, $latField, $lngField, $dateFieldFromConfig) {
            $point->alcivartech_model = $dataType;
            $point->alcivartech_type = $alcivartechTypeForStyling;

            if ($dataType === 'property_violations' && isset($point->location) && !empty($point->location)) {
                // This specific logic for property_violations might need to be part of PropertyViolation model
                // or handled via a specific method if it's complex. For now, keeping it here.
                // Ideally, the model itself should present latitude/longitude consistently.
                $location = json_decode($point->location, true);
                if (isset($location['latitude']) && isset($location['longitude'])) {
                    $point->latitude = $location['latitude'];
                    $point->longitude = $location['longitude'];
                }
                unset($point->location);
            } else {
                $point->latitude = $point->{$latField} ?? null;
                $point->longitude = $point->{$lngField} ?? null;
            }
            
            // Unset the raw location field to prevent JSON encoding errors with binary data.
            if (isset($point->location)) {
                unset($point->location);
            }
            
            $point->alcivartech_date = $point->{$dateFieldFromConfig} ?? null;

            return $point;
        });

        if ($alcivartechTypeForStyling === 'Food Inspection') {
             $data = $this->aggregateFoodViolations($data);
        }

        return $data;
    }

// ... aggregateFoodViolations remains the same ...
    public function aggregateFoodViolations($dataPoints)
    {
        $nonFoodInspections = collect();
        $foodInspectionsToAggregate = collect();
        $foodInspectionsWithoutLicenseNo = collect();

        // Partition dataPoints
        foreach ($dataPoints as $dp) {
            if (isset($dp->alcivartech_type) && $dp->alcivartech_type === 'Food Inspection') {
                if (empty($dp->licenseno)) {
                    $foodInspectionsWithoutLicenseNo->push($dp);
                } else {
                    $foodInspectionsToAggregate->push($dp);
                }
            } else {
                $nonFoodInspections->push($dp);
            }
        }

        if ($foodInspectionsToAggregate->isEmpty()) {
            return $nonFoodInspections->merge($foodInspectionsWithoutLicenseNo)->values();
        }

        $groupedByLicense = $foodInspectionsToAggregate->groupBy('licenseno');

        $aggregatedFoodViolations = $groupedByLicense->map(function ($licenseGroup) {
            // Sort by date to find the most recent record for representative data
            $licenseGroup = $licenseGroup->sortByDesc(function ($item) {
                $date = $item->alcivartech_date ?? null;
                return $date ? Carbon::parse($date)->timestamp : 0;
            });
            $mostRecentRecord = $licenseGroup->first();

            // Filter for actual violations within the group
            $actualViolationEntries = $licenseGroup->filter(fn($viol) => !empty($viol->violdttm));

            $violationSummary = null;

            if ($actualViolationEntries->isNotEmpty()) {
                $violationSummaryMap = [];
                foreach ($actualViolationEntries as $viol) {
                    $descKey = !empty($viol->violdesc) ? $viol->violdesc : 'Unknown Violation Description';
                    if (!isset($violationSummaryMap[$descKey])) {
                        $violationSummaryMap[$descKey] = [
                            'violdesc' => $descKey,
                            'entries' => []
                        ];
                    }
                    $violationSummaryMap[$descKey]['entries'][] = [
                        'alcivartech_date' => $viol->alcivartech_date,
                        'viol_status' => $viol->viol_status,
                        'comments' => $viol->comments,
                        'result' => $viol->result,
                        'viol_level' => $viol->viol_level,
                    ];
                }

                $processedSummaryItems = [];
                foreach ($violationSummaryMap as $descKey => $summaryDetails) {
                    $sortedEntries = collect($summaryDetails['entries'])->sortByDesc(function ($entry) {
                        $date = $entry['alcivartech_date'] ?? null;
                        return $date ? Carbon::parse($date)->timestamp : 0;
                    })->values()->all();
                    
                    $processedSummaryItems[] = [
                        'violdesc' => $summaryDetails['violdesc'],
                        'entries' => $sortedEntries
                    ];
                }

                usort($processedSummaryItems, function ($a, $b) {
                    return strcmp($a['violdesc'], $b['violdesc']);
                });
                $violationSummary = $processedSummaryItems;
            }
            
            // Create a new object for the aggregated point to avoid modifying original objects if they are referenced elsewhere.
            // Start by cloning the most recent record or creating a new stdClass.
            $aggregatedPointData = clone $mostRecentRecord; // Shallow clone, careful with nested objects if any.
                                                            // Or $aggregatedPointData = new \stdClass(); and copy properties.

            // Add/override specific fields for aggregation
            $aggregatedPointData->alcivartech_type = "Food Inspection"; // Ensure this is set
            $aggregatedPointData->alcivartech_date = $mostRecentRecord->alcivartech_date; 
            $aggregatedPointData->_is_aggregated_food_violation = true;

            if ($violationSummary !== null) {
                $aggregatedPointData->violation_summary = $violationSummary;
            }
            
            return $aggregatedPointData; // Return the new/cloned object
        })->filter()->values();

        return $nonFoodInspections->toBase()
            ->merge($foodInspectionsWithoutLicenseNo)
            ->merge($aggregatedFoodViolations)
            ->values();
    }
// ... naturalLanguageQuery and queryGPT remain largely the same ...
    public function naturalLanguageQuery(Request $request, string $dataType)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required for natural language queries.'], 401);
        }

        $modelClass = $this->getModelClassForDataType($dataType);
        $queryText = $request->input('query');

        if (empty($queryText)) {
            return response()->json(['error' => 'Query text cannot be empty.'], 400);
        }

        try {
            $gptResponseJson = $this->queryGPT($queryText, $modelClass);
            $gptResponse = json_decode($gptResponseJson, true);

            if (isset($gptResponse['filters'])) {
                $filterRequest = new Request(['filters' => $gptResponse['filters']]);
                return $this->getData($filterRequest, $dataType);
            }
            return response()->json(['error' => 'Could not parse query filters from AI response.', 'query' => $queryText, 'raw_ai_response' => $gptResponseJson], 400);

        } catch (\Exception $e) {
            \Log::error("NLP Query Error for {$dataType}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to process natural language query: ' . $e->getMessage()], 500);
        }
    }

    private function queryGPT(string $queryText, string $modelClass): string
    {
        $client = new Client();
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        $dateField = $modelClass::getDateField();
        $humanName = $modelClass::getHumanName(); // Use new method

        $systemMessage = "You are an AI assistant that converts natural language queries into JSON filter objects for a dataset about {$humanName}. ".
                         "The primary date field for filtering is '{$dateField}'. Use 'start_date' and 'end_date' in 'YYYY-MM-DD' format for date ranges on this field. ".
                         "There is also a 'search_term' field which accepts a string for general free-text search across multiple fields. ".
                         "Today's date is " . now()->toDateString() . ". " .
                         "Refer to the available fields and their types in the function description. Only use filter keys that are explicitly mentioned or inferable from the description. Ensure all filter values are in the correct format (e.g. strings for text, numbers for numeric fields, booleans for true/false, arrays of strings/numbers for multi-select).";
        
        $userMessages = [
            ['role' => 'user', 'content' => "Convert this query into data filters: \"{$queryText}\""],
        ];
        
        $functionTool = $modelClass::getGptFunctionSchema();

        if (!isset($functionTool['type']) || $functionTool['type'] !== 'function' || !isset($functionTool['function']['parameters'])) {
            \Log::error("DataMapController: model {$modelClass}::getGptFunctionSchema() returned an invalid schema.");
            throw new \Exception("Failed to generate a valid function schema for model {$modelClass}.");
        }
        
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => array_merge([['role' => 'system', 'content' => $systemMessage]], $userMessages),
                'tools' => [$functionTool],
                'tool_choice' => ["type" => "function", "function" => ["name" => $functionTool['function']['name']]], 
            ]
        ]);

        $responseBody = json_decode($response->getBody()->getContents(), true);

        if (isset($responseBody['choices'][0]['message']['tool_calls'][0]['function']['arguments'])) {
            return $responseBody['choices'][0]['message']['tool_calls'][0]['function']['arguments'];
        }
        
        \Log::error('OpenAI GPT Error: Failed to get valid filter arguments. Response: ' . json_encode($responseBody));
        throw new \Exception('Failed to get valid filter arguments from OpenAI. Check logs for details. OpenAI Response: ' . json_encode($responseBody));
    }

    public function generateMapConfiguration(): array
    {
        $dataPointModelConfig = [];
        $modelToSubObjectKeyMap = [];

        foreach ($this->modelRegistry as $modelKey => $modelClass) {
            if (!class_exists($modelClass) || !in_array(Mappable::class, class_uses_recursive($modelClass))) {
                continue;
            }

            $dataObjectKey = Str::snake(class_basename($modelClass)) . '_data';
            $modelToSubObjectKeyMap[$modelKey] = $dataObjectKey;

            $dataPointModelConfig[$modelKey] = [
                'dataObjectKey' => $dataObjectKey,
                'displayTitle' => $modelClass::getAlcivartechTypeForStyling(),
                'popupConfig' => $modelClass::getPopupConfig(),
            ];
        }

        return [
            'dataPointModelConfig' => $dataPointModelConfig,
            'modelToSubObjectKeyMap' => $modelToSubObjectKeyMap,
        ];
    }
}
