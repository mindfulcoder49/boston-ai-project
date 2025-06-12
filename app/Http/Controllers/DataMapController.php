<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\Mappable; // Assuming Mappable trait/interface exists
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Added
use Illuminate\Support\Facades\Auth; // Added
use Carbon\Carbon; // Added
use App\Models\User; // Ensure User model is imported if not already
use Illuminate\Support\Facades\Schema; // For schema checks

class DataMapController extends Controller
{
    // Consolidate all model configurations here
    protected array $modelConfigurations = [];

    public function __construct()
    {
        $this->modelConfigurations = [
            '311_cases' => [
                'modelClass' => \App\Models\ThreeOneOneCase::class,
                'humanName' => '311 Cases',
                'iconClass' => 'case-div-icon no-photo',
                'alcivartech_type_for_styling' => '311 Case', // Type used for styling in DataMapDisplay
                'latField' => 'latitude',
                'lngField' => 'longitude',
                // dateField, externalIdField, filterFieldsDescription, searchableColumns will be fetched from Mappable trait
            ],
            'property_violations' => [
                'modelClass' => \App\Models\PropertyViolation::class,
                'humanName' => 'Property Violations',
                'iconClass' => 'property-violation-div-icon',
                'alcivartech_type_for_styling' => 'Property Violation',
                'latField' => 'latitude', // Assuming parsed from 'location'
                'lngField' => 'longitude', // Assuming parsed from 'location'
            ],
            'food_inspections' => [
                'modelClass' => \App\Models\FoodInspection::class,
                'humanName' => 'Food Inspections',
                'iconClass' => 'food-inspection-div-icon',
                'alcivartech_type_for_styling' => 'Food Inspection',
                'latField' => 'latitude',
                'lngField' => 'longitude',
            ],
            'construction_off_hours' => [
                'modelClass' => \App\Models\ConstructionOffHour::class,
                'humanName' => 'Construction Off Hours',
                'iconClass' => 'construction-off-hour-div-icon',
                'alcivartech_type_for_styling' => 'Construction Off Hour',
                'latField' => 'latitude',
                'lngField' => 'longitude',
            ],
            'building_permits' => [
                'modelClass' => \App\Models\BuildingPermit::class,
                'humanName' => 'Building Permits',
                'iconClass' => 'permit-div-icon', // or building-permit-div-icon
                'alcivartech_type_for_styling' => 'Building Permit',
                'latField' => 'y_latitude',
                'lngField' => 'x_longitude',
            ],
            'crime' => [
                'modelClass' => \App\Models\CrimeData::class,
                'humanName' => 'Boston Crime',
                'iconClass' => 'crime-div-icon',
                'alcivartech_type_for_styling' => 'Crime',
                'latField' => 'lat',
                'lngField' => 'long',
            ],
            'everett_crime' => [
                'modelClass' => \App\Models\EverettCrimeData::class,
                'humanName' => 'Everett Crime',
                'iconClass' => 'crime-div-icon', // Shares icon style with Boston Crime in UI elements
                'alcivartech_type_for_styling' => 'Crime', // Shares styling type with Boston Crime on map
                'latField' => 'incident_latitude',
                'lngField' => 'incident_longitude',
            ],
        ];

        // Dynamically add Mappable trait based properties to each configuration
        foreach ($this->modelConfigurations as $key => &$config) {
            $modelInstance = app($config['modelClass']);
            if (in_array(Mappable::class, class_uses_recursive($modelInstance))) {
                $config['dateField'] = $modelInstance::getDateField();
                $config['externalIdField'] = $modelInstance::getExternalIdName();
                $config['filterFieldsDescription'] = $modelInstance::getFilterableFieldsDescription();
                $config['searchableColumns'] = $modelInstance::getSearchableColumns();
                // modelNameForHumans from Mappable can override humanName if desired, or be used as a fallback
                $config['modelNameForHumansMappable'] = $modelInstance::getModelNameForHumans();
            } else {
                // Log error or handle models not using Mappable if they are expected to
                Log::error("Model {$config['modelClass']} for dataType '{$key}' does not use the Mappable trait.");
                // Unset or mark as invalid to prevent issues later
                // For now, we assume all configured models use Mappable for these fields.
            }
        }
    }


    // Removed $modelMapping, will use $this->modelConfigurations['modelClass']
    // Removed MODELS constant, lat/lng fields are now in $modelConfigurations

    public function getModelMapping(): array
    {
        // Return a simplified mapping if needed elsewhere, or adjust consumers
        // For now, this returns model keys to model class strings, similar to old $modelMapping
        return array_map(fn($config) => $config['modelClass'], $this->modelConfigurations);
    }

    public function getModelClassForDataType(string $dataType): ?string
    {
        if (!isset($this->modelConfigurations[$dataType])) {
            abort(404, "Data type '{$dataType}' not found or not configured.");
        }
        $modelClass = $this->modelConfigurations[$dataType]['modelClass'];

        if (!in_array(Mappable::class, class_uses_recursive($modelClass))) {
            abort(500, "Model for {$dataType} does not use the Mappable trait/interface.");
        }
        return $modelClass;
    }

    // Refactored to accept a user context
    public function getMinDateForEffectiveUser(string $dataType, ?\App\Models\User $userContext = null)
    {
        $effectiveUser = $userContext ?: Auth::user(); // Default to current authenticated user if none provided
        $tierMinDate = null;

        if (!$effectiveUser) {
            // Guest user or no user context for a non-public scenario
            return Carbon::now()->subMonths(1); // Default guest access: 1 month
        }

        $effectiveTierDetails = $effectiveUser->getEffectiveTierDetails();
        $effectiveTier = $effectiveTierDetails['tier'];

        if ($effectiveTier === 'free') {
            $tierMinDate = Carbon::now()->subMonths(2);
        } elseif ($effectiveTier === 'basic') {
            $tierMinDate = Carbon::now()->subMonths(6);
        } elseif ($effectiveTier === 'pro') {
            $tierMinDate = null; // Pro users have no date restriction from tier
        } else {
            // Fallback for any unknown tier
            $tierMinDate = Carbon::now()->subMonths(1);
        }
        
        return $tierMinDate;
    }

    public function index(Request $request, string $dataType)
    {
        if (!isset($this->modelConfigurations[$dataType])) {
            abort(404, "Data type '{$dataType}' not configured.");
        }
        $config = $this->modelConfigurations[$dataType];
        $modelClass = $config['modelClass'];

        $tierMinDate = $this->getMinDateForEffectiveUser($dataType, Auth::user());

        $query = $modelClass::query();
        if ($tierMinDate) {
            $query->where($config['dateField'], '>=', $tierMinDate->toDateString());
        }

        $limit = $request->input('limit', 1000);
        $query->orderBy($config['dateField'], 'desc');
        $initialData = $query->limit(max(1, min($limit, 100000)))->get();
        $initialData = $this->enrichData($initialData, $dataType);

        // Pass the full configuration for this dataType
        $pageProps = [
            'initialData' => $initialData,
            'filters' => $request->all(),
            'dataType' => $dataType, // modelKey
            // Specific fields are now part of dataTypeConfig
            // 'dateField' => $config['dateField'],
            // 'externalIdField' => $config['externalIdField'],
            // 'filterFieldsDescription' => $config['filterFieldsDescription'],
            // 'searchableColumns' => $config['searchableColumns'],
            'dataTypeConfig' => $config, // Pass the whole config for this dataType
        ];
        // For MapToolbar, we need all model configs for the links
        $pageProps['allModelConfigurationsForToolbar'] = array_map(function($key, $conf) {
            return [
                'dataType' => $key, // modelKey
                'name' => $conf['humanName'],
                'iconClass' => $conf['iconClass'],
            ];
        }, array_keys($this->modelConfigurations), $this->modelConfigurations);


        return Inertia::render('DataMap', $pageProps);
    }

    public function combinedIndex(Request $request)
    {
        $allDataTypeDetails = [];
        $initialModelKey = null;
        $initialDataSets = [];
        $initialFilters = ['limit' => 100];

        if (!empty($this->modelConfigurations)) {
            $initialModelKey = array_key_first($this->modelConfigurations);
        }

        foreach ($this->modelConfigurations as $modelKey => $config) {
            $modelClass = $config['modelClass'];
            if (!in_array(Mappable::class, class_uses_recursive($modelClass))) {
                Log::warning("Skipping data type {$modelKey} in combinedIndex as its model class {$modelClass} is not Mappable.");
                continue;
            }

            // Use the full config, Mappable trait properties are already merged in constructor
            $allDataTypeDetails[$modelKey] = $config;
            // Ensure 'modelNameForHumans' is set, prefer 'humanName' from config, fallback to Mappable's
            $allDataTypeDetails[$modelKey]['modelNameForHumans'] = $config['humanName'] ?? $config['modelNameForHumansMappable'] ?? Str::title(str_replace('_', ' ', $modelKey));


            if ($modelKey === $initialModelKey) {
                $query = $modelClass::query();
                $tierMinDate = $this->getMinDateForEffectiveUser($modelKey, Auth::user());
                if ($tierMinDate) {
                    $query->where($config['dateField'], '>=', $tierMinDate->toDateString());
                }
                if (isset($initialFilters['limit'])) {
                    $query->limit(max(1, min((int)$initialFilters['limit'], 100000)));
                }
                $dataForInitialType = $this->enrichData($query->get(), $modelKey);
                if (!$dataForInitialType->isEmpty()) {
                    $initialDataSets[$initialModelKey] = $dataForInitialType;
                }
            }
        }

        if (!$initialModelKey && !empty($allDataTypeDetails)) {
            $initialModelKey = array_key_first($allDataTypeDetails);
            if (empty($initialDataSets[$initialModelKey]) && $initialModelKey) {
                $config = $allDataTypeDetails[$initialModelKey];
                $modelClass = $config['modelClass'];
                $query = $modelClass::query();
                $tierMinDateOnFallback = $this->getMinDateForEffectiveUser($initialModelKey, Auth::user());
                if ($tierMinDateOnFallback) {
                    $query->where($config['dateField'], '>=', $tierMinDateOnFallback->toDateString());
                }
                if (isset($initialFilters['limit'])) {
                    $query->limit(max(1, min((int)$initialFilters['limit'], 100000)));
                }
                $dataForFallbackInitialType = $this->enrichData($query->get(), $initialModelKey);
                if (!$dataForFallbackInitialType->isEmpty()) {
                    $initialDataSets[$initialModelKey] = $dataForFallbackInitialType;
                }
            }
        }
        
        // For MapToolbar in CombinedDataMap page
        $allModelConfigurationsForToolbar = array_map(function($key, $conf) {
            return [
                'dataType' => $key, // modelKey
                'name' => $conf['humanName'],
                'iconClass' => $conf['iconClass'],
            ];
        }, array_keys($this->modelConfigurations), $this->modelConfigurations);

        return Inertia::render('CombinedDataMap', [
            'modelMapping' => $this->getModelMapping(), // Keep this for CombinedDataMapComponent's availableModels computed prop
            'initialDataType' => $initialModelKey, // This is the initial model key
            'initialDataSets' => $initialDataSets,
            'initialFilters' => $initialFilters,
            'allDataTypeDetails' => $allDataTypeDetails, // This now contains the richer configuration for each model
            'allModelConfigurationsForToolbar' => $allModelConfigurationsForToolbar, // For MapToolbar
        ]);
    }

    public function applyQueryFilters(Builder $query, string $modelClass, array $filters, ?User $userContext)
    {
        $dateField = $modelClass::getDateField();
        $searchableColumns = $modelClass::getSearchableColumns();
        $tierMinDate = $this->getMinDateForEffectiveUser($modelClass, $userContext); // Pass modelClass for context if needed by getMinDate

        $processedKeys = [];

        // Apply tier-based date restriction first
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
                // If tierMinDate is set, query already has $dateField >= $tierMinDate
                // So, we just add the upper bound.
                $query->where($dateField, '<=', $value);
                $processedKeys[] = 'end_date';
                continue;
            }

            if (Str::endsWith($key, '_start') || Str::endsWith($key, '_min')) {
                $isDateRange = Str::endsWith($key, '_start');
                $suffix = $isDateRange ? '_start' : '_min';
                $correspondingSuffix = $isDateRange ? '_end' : '_max';

                $baseColumn = Str::beforeLast($key, $suffix);
                // Ensure baseColumn is a valid column for the model to prevent SQL injection if keys are user-generated
                // This check might be too simplistic if column names can be complex.
                // Consider checking against $modelClass::getFillable() or Schema::getColumnListing if necessary,
                // but filters should ideally come from predefined structures (like getFilterableFieldsDescription).
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
                 // Again, ensure $key is a valid column name before using it directly in a query.
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
            $query->where(function ($q) use ($searchableColumns, $searchTerm) {
                foreach ($searchableColumns as $col) {
                    // Ensure $col is a valid column before using in OR WHERE
                    // This check might be redundant if $searchableColumns is always sourced from valid schema columns.
                    // if (Schema::hasColumn(app($modelClass)->getTable(), $col)) {
                         $q->orWhere($col, 'LIKE', '%' . $searchTerm . '%');
                    // } else {
                    //    Log::warning("Searchable column '{$col}' not found in table for model {$modelClass}. Skipping in search_term.");
                    //}
                }
            });
        }
        // Note: The 'limit' filter is handled by the calling method (getData or SavedMapController@view) after this.
    }

    public function getData(Request $request, string $dataType)
    {
        $modelClass = $this->getModelClassForDataType($dataType);
        Log::info("Fetching data for {$dataType} with filters: " . json_encode($request->input('filters')));
        
        $query = $modelClass::query();
        $filters = $request->input('filters', []);
        $currentUser = Auth::user(); // User context for getData is always the authenticated user

        $this->applyQueryFilters($query, $modelClass, $filters, $currentUser);

        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int)$filters['limit'] : 1000;
        $query->limit(max(1, min($limit, 100000))); 

        $query->orderBy($modelClass::getDateField(), 'desc');
        
        Log::info("Query SQL: " . $query->toSql());
        Log::info("Query bindings: " . json_encode($query->getBindings()));
        $data = $query->get();

        $data = $this->enrichData($data, $dataType);

        return response()->json(['data' => $data, 'filtersApplied' => $filters]);
    }

    public function enrichData( $data, string $dataType)
    {
        if (!isset($this->modelConfigurations[$dataType])) {
            Log::error("enrichData: Configuration for dataType '{$dataType}' not found.");
            return $data; // Or handle error appropriately
        }
        $config = $this->modelConfigurations[$dataType];

        $data = $data->map(function ($point) use ($dataType, $config) {
            $point->alcivartech_model = $dataType;
            // Use alcivartech_type_for_styling from the centralized configuration
            $point->alcivartech_type = $config['alcivartech_type_for_styling'];

            // Normalize latitude and longitude field names using config
            $latField = $config['latField'];
            $lngField = $config['lngField'];

            if ($dataType === 'property_violations' && isset($point->location) && !empty($point->location)) {
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
            
            // Set alcivartech_date using dateField from config
            $dateFieldFromConfig = $config['dateField'];
            $point->alcivartech_date = $point->{$dateFieldFromConfig} ?? null;

            return $point;
        });

        // Aggregation logic might need to be aware of the new alcivartech_type if it was previously relying on a switch
        if ($config['alcivartech_type_for_styling'] === 'Food Inspection') {
             $data = $this->aggregateFoodViolations($data);
        }

        return $data;
    }

   
    // This function aggregates food inspection violations by license number
    // and returns a modified data set with aggregated records.
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

                // Process summary items: sort entries, then sort summary items by violdesc
                $processedSummaryItems = [];
                foreach ($violationSummaryMap as $descKey => $summaryDetails) {
                    $sortedEntries = collect($summaryDetails['entries'])->sortByDesc(function ($entry) {
                        $date = $entry['alcivartech_date'] ?? null;
                        return $date ? Carbon::parse($date)->timestamp : 0;
                    })->values()->all();
                    
                    $processedSummaryItems[] = [
                        'violdesc' => $summaryDetails['violdesc'], // Use from summaryDetails to ensure consistency
                        'entries' => $sortedEntries
                    ];
                }

                usort($processedSummaryItems, function ($a, $b) {
                    return strcmp($a['violdesc'], $b['violdesc']);
                });
                $violationSummary = $processedSummaryItems;
            }

            // Create the aggregated point, starting with mostRecentRecord's properties
            $aggregatedPointData = $mostRecentRecord;


            Log::info("Aggregated Food Inspection Data: " . json_encode($aggregatedPointData));
            Log::info("Most Recent Record: " . json_encode($mostRecentRecord));
            
            // Add/override specific fields for aggregation
            $aggregatedPointData['alcivartech_type'] = "Food Inspection";
            $aggregatedPointData['alcivartech_date'] = $mostRecentRecord->alcivartech_date; // Ensure it's the most recent date
            $aggregatedPointData['_is_aggregated_food_violation'] = true;

            if ($violationSummary !== null) {
                $aggregatedPointData['violation_summary'] = $violationSummary;
            }
            Log::info("Aggregated Food Inspection Data: " . json_encode($aggregatedPointData));
            
            return (object) $aggregatedPointData;
        })->filter()->values(); // filter() removes nulls, values() re-indexes collection

        // Combine all parts: non-food, food without licenseno (passed through), and aggregated food inspections
        return $nonFoodInspections->toBase()
            ->merge($foodInspectionsWithoutLicenseNo)
            ->merge($aggregatedFoodViolations)
            ->values();
    }
    // This method is for natural language processing queries
    // It takes a natural language query and converts it into a structured filter
    // It uses the OpenAI API to process the query and generate the filters
    // It also handles the authentication and tier-based restrictions

    public function naturalLanguageQuery(Request $request, string $dataType)
    {
        $user = Auth::user();
        if (!$user) {
            // Or handle guest access for NLP differently, e.g., deny or use sample data.
            // For now, assume 'auth' middleware protects this.
            return response()->json(['error' => 'Authentication required for natural language queries.'], 401);
        }
        // Tier-based restrictions are handled within getData, which this method calls.
        // getData itself uses getMinDateForEffectiveUser(dataType, Auth::user())

        $modelClass = $this->getModelClassForDataType($dataType);
        $queryText = $request->input('query');

        if (empty($queryText)) {
            return response()->json(['error' => 'Query text cannot be empty.'], 400);
        }

        try {
            $gptResponseJson = $this->queryGPT($queryText, $modelClass);
            $gptResponse = json_decode($gptResponseJson, true);

            if (isset($gptResponse['filters'])) {
                // Pass GPT's filters to the getData method
                $filterRequest = new Request(['filters' => $gptResponse['filters']]);
                // Merge original request's query parameters if needed, or just use GPT's
                // $filterRequest->setMethod('POST'); // getData expects POST or reads from input()
                
                // Call getData directly
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

        // These methods must exist on the model via Mappable trait or direct implementation
        // $fieldsDescription = $modelClass::getFilterableFieldsDescription(); // No longer directly used here for schema building
        $contextData = $modelClass::getContextData(); // Optional additional context, now part of schema description
        $dateField = $modelClass::getDateField(); // Still useful for system message

        $systemMessage = "You are an AI assistant that converts natural language queries into JSON filter objects for a dataset about {$modelClass::getModelNameForHumans()}. ".
                         "The primary date field for filtering is '{$dateField}'. Use 'start_date' and 'end_date' in 'YYYY-MM-DD' format for date ranges on this field. ".
                         "There is also a 'search_term' field which accepts a string for general free-text search across multiple fields. ".
                         "Today's date is " . now()->toDateString() . ". " .
                         "Refer to the available fields and their types in the function description. Only use filter keys that are explicitly mentioned or inferable from the description. Ensure all filter values are in the correct format (e.g. strings for text, numbers for numeric fields, booleans for true/false, arrays of strings/numbers for multi-select).";
        
        $userMessages = [
            ['role' => 'user', 'content' => "Convert this query into data filters: \"{$queryText}\""],
        ];
        // Context data is now part of the function schema description, but can also be added as a separate message if desired for emphasis.
        // if (!empty($contextData)) {
        //     $userMessages[] = ['role' => 'user', 'content' => "Additional context for the dataset: {$contextData}"];
        // }

        // Get the function schema directly from the model
        $functionTool = $modelClass::getGptFunctionSchema();

        // Validate that the schema was generated correctly (basic check)
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
                'model' => 'gpt-4o-mini', // Consider updating model if needed
                'messages' => array_merge([['role' => 'system', 'content' => $systemMessage]], $userMessages),
                'tools' => [$functionTool], // Use the schema from the model
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
}
