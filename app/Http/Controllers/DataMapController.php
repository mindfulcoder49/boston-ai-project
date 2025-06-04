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

class DataMapController extends Controller
{
    protected array $modelMapping = [
        '311_cases' => \App\Models\ThreeOneOneCase::class,
        'property_violations' => \App\Models\PropertyViolation::class,
        'food_inspections' => \App\Models\FoodInspection::class,
        'construction_off_hours' => \App\Models\ConstructionOffHour::class,
        'building_permits' => \App\Models\BuildingPermit::class,
        'crime' => \App\Models\CrimeData::class, // Ensure CrimeData is mappable
        // Add other data types and their models here
    ];

    private const MODELS = [
        'crime' => ['lat' => 'lat', 'lng' => 'long', 'id' => 'id', 'date_field' => 'occurred_on_date', 'foreign_key' => 'crime_data_id'],
        '311_cases' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'open_dt', 'foreign_key' => 'three_one_one_case_id'],
        'property_violations' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'status_dttm', 'foreign_key' => 'property_violation_id'],
        'construction_off_hours' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'start_datetime', 'foreign_key' => 'construction_off_hour_id'],
        'building_permits' => ['lat' => 'y_latitude', 'lng' => 'x_longitude', 'id' => 'id', 'date_field' => 'issued_date', 'foreign_key' => 'building_permit_id'],
        'food_inspections' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'external_id', 'date_field' => 'resultdttm', 'foreign_key' => 'food_inspection_id'],
    ];

    private function getModelClass(string $dataType): ?string
    {
        if (!isset($this->modelMapping[$dataType])) {
            abort(404, "Data type '{$dataType}' not found or not configured for mapping.");
        }
        $modelClass = $this->modelMapping[$dataType];

        // Ensure the model uses the Mappable trait or implements a similar interface
        if (!in_array(Mappable::class, class_uses_recursive($modelClass))) {
            abort(500, "Model for {$dataType} does not use the Mappable trait/interface.");
        }
        return $modelClass;
    }

    public function getMinDateForUser(string $dataType)
    {
        $modelClass = $this->getModelClass($dataType);
        $user = Auth::user();
        $tierMinDate = null;

        if ($user && !$user->subscribed('default')) {
            // Authenticated free user
            $tierMinDate = Carbon::now()->subMonths(2);
        } elseif ($user && $user->subscribed('default')) {
            $subscription = $user->subscription('default');
            if ($subscription && $subscription->stripe_price === config('stripe.prices.basic_plan')) {
                $tierMinDate = Carbon::now()->subMonths(6);
            } elseif ($subscription && $subscription->stripe_price === config('stripe.prices.pro_plan')) {
                // Pro users have no date restriction from tier
                $tierMinDate = null; 
            } else {
                 // Fallback for subscribed users without a recognized plan (treat as free)
                $tierMinDate = Carbon::now()->subMonths(2);
            }
        }

        return $tierMinDate;
    }

    public function index(Request $request, string $dataType)
    {
        $modelClass = $this->getModelClass($dataType);
        // Fetch initial data with a sensible limit. Filters can override this.
        //$initialData = $modelClass::limit(100000)->get(); 

        // Fetch data limited by user's subscription tier
        $user = Auth::user();
        $tierMinDate =$this->getMinDateForUser($dataType);

        $query = $modelClass::query();
        // Apply tier-based date restriction first
        if ($tierMinDate) {
            $query->where($modelClass::getDateField(), '>=', $tierMinDate->toDateString());
        }

        //get limit from request    
        $limit = $request->input('limit', 1000); // Default limit

        //order by date field
        $query->orderBy($modelClass::getDateField(), 'desc');
        $initialData = $query->limit(max(1, min($limit, 100000)))->get(); // Clamp limit for performance

        // enrich data with additional fields
        $initialData = $this->enrichData($initialData, $dataType);

        return Inertia::render('DataMap', [
            'initialData' => $initialData,
            'filters' => $request->all(), // Pass through any query params as initial filters
            'dataType' => $dataType,
            'dateField' => $modelClass::getDateField(), // From Mappable trait
            'externalIdField' => $modelClass::getExternalIdName(), // From Mappable trait
            // getFilterableFieldsDescription should return a JSON string or array
            'filterFieldsDescription' => $modelClass::getFilterableFieldsDescription(), // From Mappable trait
        ]);
    }

    public function combinedIndex(Request $request)
    {
        $allDataTypeDetails = [];
        $initialDataType = null;
        $initialData = collect(); // Use Laravel Collection
        $initialFilters = ['limit' => 100]; // Default initial filters

        // Determine the initial data type (e.g., the first one in the mapping)
        if (!empty($this->modelMapping)) {
            $initialDataType = array_key_first($this->modelMapping);
        }

        foreach ($this->modelMapping as $dataType => $modelClassString) {
            /** @var Mappable $modelClass */
            $modelClass = $this->getModelClass($dataType); // Ensures it uses Mappable
            if (!$modelClass) {
                Log::warning("Skipping data type {$dataType} in combinedIndex as its model class could not be resolved or is not Mappable.");
                continue;
            }

            $allDataTypeDetails[$dataType] = [
                'dateField' => $modelClass::getDateField(),
                'externalIdField' => $modelClass::getExternalIdName(),
                'filterFieldsDescription' => $modelClass::getFilterableFieldsDescription(), // Pass as is, component handles parsing
                'modelNameForHumans' => $modelClass::getModelNameForHumans(),
                'searchableColumns' => $modelClass::getSearchableColumns(), // Good to have for client-side reference if needed
            ];

            // Fetch initial data only for the designated initialDataType
            if ($dataType === $initialDataType) {
                $query = $modelClass::query();

                // Apply tier-based date restriction first
                $tierMinDate =$this->getMinDateForUser($dataType);
                if ($tierMinDate) {
                    $query->where($modelClass::getDateField(), '>=', $tierMinDate->toDateString());
                }

                // Apply initial filters if any (e.g., limit)
                if (isset($initialFilters['limit'])) {
                    $query->limit(max(1, min((int)$initialFilters['limit'], 100000)));
                }
                // Add other default filters for initial load if necessary

                
                $initialData = $this->enrichData($query->get(), $dataType);
            }
        }

        if (!$initialDataType && !empty($allDataTypeDetails)) {
            // Fallback if initialDataType wasn't set but we have details (e.g. first valid one)
            $initialDataType = array_key_first($allDataTypeDetails);
            // Potentially fetch data for this fallback initialDataType if not already fetched
            if ($initialData->isEmpty() && $initialDataType) {
                 /** @var Mappable $modelClass */
                $modelClass = $this->getModelClass($initialDataType);
                $query = $modelClass::query();
                if (isset($initialFilters['limit'])) {
                    $query->limit(max(1, min((int)$initialFilters['limit'], 100000)));
                }
                $initialData = $this->enrichData($query->get(), $initialDataType);
            }
        }


        return Inertia::render('CombinedDataMap', [
            'modelMapping' => $this->modelMapping, // Pass the raw mapping
            'initialDataType' => $initialDataType,
            'initialData' => $initialData,
            'initialFilters' => $initialFilters, // Pass the filters used for the initial data
            'allDataTypeDetails' => $allDataTypeDetails,
        ]);
    }

    public function getData(Request $request, string $dataType)
    {
        $modelClass = $this->getModelClass($dataType);
        Log::info("Fetching data for {$dataType} with filters: " . json_encode($request->input('filters')));
        /** @var Builder $query */
        $query = $modelClass::query();
        $filters = $request->input('filters', []);

        $user = Auth::user();
        $dateField = $modelClass::getDateField();
        $tierMinDate = null;

        if (!$user) {
            // Guest users - for now, let's assume they can't access this endpoint
            // Or, apply a very strict limit, e.g., 1 day.
            // This should ideally be handled by route middleware.
            // If allowing guests, set $tierMinDate = Carbon::now()->subDay();
            // For now, we assume 'auth' middleware protects this route.
            // If a guest somehow reaches here, they'll get no date restriction from this logic,
            // but also won't have a plan.
            // A more robust solution would be to explicitly deny or provide sample data.
            // For this iteration, we'll rely on middleware. If user is null, no tier-specific date limits apply here.
        } else if ($user && !$user->subscribed('default')) {
            // Authenticated free user
            $tierMinDate = Carbon::now()->subMonths(2);
        } elseif ($user && $user->subscribed('default')) {
            $subscription = $user->subscription('default');
            if ($subscription && $subscription->stripe_price === config('stripe.prices.basic_plan')) {
                $tierMinDate = Carbon::now()->subMonths(6);
            } elseif ($subscription && $subscription->stripe_price === config('stripe.prices.pro_plan')) {
                // Pro users have no date restriction from tier
                $tierMinDate = null; 
            } else {
                 // Fallback for subscribed users without a recognized plan (treat as free)
                $tierMinDate = Carbon::now()->subMonths(2);
            }
        }


        $searchableColumns = $modelClass::getSearchableColumns(); // From Mappable
        // $dateField is already defined above

        $processedKeys = []; // To keep track of filter keys that have been handled

        // Apply tier-based date restriction first
        if ($tierMinDate) {
            $query->where($dateField, '>=', $tierMinDate->toDateString());
        }

        foreach ($filters as $key => $value) {
            // Skip if already processed, or special keys handled elsewhere, or empty/null values that are not explicit false for booleans
            if (in_array($key, $processedKeys) || $key === 'search_term' || $key === 'limit' ||
                ($value === null && !is_bool($value)) || ($value === '' && !is_bool($value)) || (is_array($value) && empty(array_filter($value, fn($item) => ($item !== null && $item !== '') || is_bool($item) )))) {
                continue;
            }

            // 1. Primary date field handling (e.g., 'occurred_on_date' via 'start_date' & 'end_date' filters)
            if ($key === 'start_date' && !empty($value)) {
                $userStartDate = Carbon::parse($value);
                // Ensure user's start_date respects tier limitations
                if ($tierMinDate && $userStartDate->lt($tierMinDate)) {
                    $value = $tierMinDate->toDateString(); // Override with tier's minimum date
                }

                $endDateValue = $filters['end_date'] ?? null;
                if (!empty($endDateValue)) {
                    $query->whereBetween($dateField, [$value, $endDateValue]);
                    $processedKeys[] = 'end_date'; // Mark 'end_date' as handled
                } else {
                    $query->where($dateField, '>=', $value);
                }
                $processedKeys[] = 'start_date'; // Mark 'start_date' as handled
                continue;
            } elseif ($key === 'end_date' && !empty($value) && !isset($filters['start_date'])) {
                // Only 'end_date' is present for the primary date field
                // If tierMinDate is set, and no start_date is provided by user,
                // the query already has `where($dateField, '>=', $tierMinDate)`.
                // So, we just add the upper bound.
                $query->where($dateField, '<=', $value);
                $processedKeys[] = 'end_date';
                continue;
            }

            // 2. Secondary date fields (e.g., 'some_other_date_start' & 'some_other_date_end')
            // AND Numeric range fields (e.g., 'age_min' & 'age_max')
            if (Str::endsWith($key, '_start') || Str::endsWith($key, '_min')) {
                $isDateRange = Str::endsWith($key, '_start');
                $suffix = $isDateRange ? '_start' : '_min';
                $correspondingSuffix = $isDateRange ? '_end' : '_max';

                $baseColumn = Str::beforeLast($key, $suffix);
                $startValue = $value; // or minValue
                $endKey = $baseColumn . $correspondingSuffix;
                $endValue = $filters[$endKey] ?? null; // or maxValue

                // Ensure value is not an empty string before processing
                if ($startValue === '' && !is_numeric($startValue)) $startValue = null;
                if ($endValue === '' && !is_numeric($endValue)) $endValue = null;


                if ($startValue !== null && $endValue !== null) {
                    if (!$isDateRange) { // Numeric range
                        $numStartValue = filter_var($startValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        $numEndValue = filter_var($endValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        if ($numStartValue !== null && $numEndValue !== null) {
                            $query->whereBetween($baseColumn, [$numStartValue, $numEndValue]);
                        } else {
                            Log::warning("Invalid numeric range values for {$baseColumn}: min='{$startValue}', max='{$endValue}'");
                        }
                    } else { // Date range
                        $query->whereBetween($baseColumn, [$startValue, $endValue]);
                    }
                    $processedKeys[] = $endKey; // Mark corresponding _end or _max key as handled
                } elseif ($startValue !== null) {
                    if (!$isDateRange) { // Numeric min
                        $numStartValue = filter_var($startValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        if ($numStartValue !== null) {
                            $query->where($baseColumn, '>=', $numStartValue);
                        } else {
                             Log::warning("Invalid numeric min value for {$baseColumn}: '{$startValue}'");
                        }
                    } else { // Date start
                        $query->where($baseColumn, '>=', $startValue);
                    }
                }
                $processedKeys[] = $key; // Mark _start or _min key as handled
                continue;
            } elseif (Str::endsWith($key, '_end') || Str::endsWith($key, '_max')) {
                $isDateRange = Str::endsWith($key, '_end');
                $suffix = $isDateRange ? '_end' : '_max';
                $correspondingSuffix = $isDateRange ? '_start' : '_min';

                $baseColumn = Str::beforeLast($key, $suffix);
                $startKeyForThis = $baseColumn . $correspondingSuffix; // or _min

                $currentEndValue = $value;
                if ($currentEndValue === '' && !is_numeric($currentEndValue)) $currentEndValue = null;

                // Process _end or _max key only if its corresponding _start or _min key was not provided/handled
                if (!isset($filters[$startKeyForThis]) && $currentEndValue !== null) {
                    if (!$isDateRange) { // Numeric max
                        $numEndValue = filter_var($currentEndValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
                        if ($numEndValue !== null) {
                            $query->where($baseColumn, '<=', $numEndValue);
                        } else {
                            Log::warning("Invalid numeric max value for {$baseColumn}: '{$currentEndValue}'");
                        }
                    } else { // Date end
                         $query->where($baseColumn, '<=', $currentEndValue);
                    }
                }
                $processedKeys[] = $key; // Mark _end or _max key as handled
                continue;
            }
            
            // 3. General column filtering for other types (text, select, boolean, etc.)
            // These keys should directly correspond to database column names.
            if (!in_array($key, $processedKeys)) { // Ensure it wasn't handled by date logic
                 if (is_array($value)) {
                    // Ensure array values are not empty strings before applying whereIn
                    $filteredValues = array_filter($value, fn($item) => $item !== null && $item !== '');
                    if (!empty($filteredValues)) {
                        $query->whereIn($key, $filteredValues);
                    }
                } elseif (is_bool($value)) {
                    $query->where($key, $value);
                } else {
                    // For single string/numeric values, typically use LIKE for text.
                    // For numeric or exact matches, '=' would be better.
                    // This might need refinement based on column type from metadata if available.
                    // Defaulting to LIKE for broader matching.
                    $query->where($key, 'LIKE', "%{$value}%");
                }
                $processedKeys[] = $key;
            }
        }

        // Add this block after the foreach loop to handle search_term
        if (!empty($filters['search_term']) && !empty($searchableColumns)) {
            $searchTerm = $filters['search_term'];
            $query->where(function ($q) use ($searchableColumns, $searchTerm) {
                foreach ($searchableColumns as $col) {
                    $q->orWhere($col, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }

        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int)$filters['limit'] : 1000;
        // Tier-based limit adjustments could be added here if needed
        // Example:
        // if ($user && !$user->subscribed('default')) { $limit = min($limit, 200); } // Free user limit
        // elseif ($user && $user->subscribed('default') && $user->subscription('default')->stripe_price === config('stripe.prices.basic_plan')) { $limit = min($limit, 1000); } // Basic
        // Pro users could use the default clamp or a higher one.
        $query->limit(max(1, min($limit, 100000))); // Clamp limit for performance

        //order by date field
        $query->orderBy($modelClass::getDateField(), 'desc');
        
        Log::info("Query: " . $query->toSql());
        Log::info("Query values: " . json_encode($query->getBindings()));
        $data = $query->get();

        $data = $this->enrichData($data, $dataType);

        return response()->json(['data' => $data, 'filtersApplied' => $filters]);
    }

    public function enrichData( $data, string $dataType)
    { 
                // add alcivartech_type to the data
        // add alcivartech_type to the data based on the data.type and
        // also add alcivartech_date to the data based on the data.type
        // follow the exampled code above and ingnore the location stuff

        $data = $data->map(function ($point) use ($dataType) {
            $point->alcivartech_type = $dataType; // Set the type based on the data type
            //normalize latitude and longitude field name
            if ($dataType != "property_violations") {
                $point->latitude = $point->{self::MODELS[$dataType]['lat']};
                $point->longitude = $point->{self::MODELS[$dataType]['lng']};
            } else {
                //parse the location field into latitude and longitude if it exists
                if( isset($point->location) && !empty($point->location)) {
                    $location = json_decode($point->location, true);
                    if (isset($location['latitude']) && isset($location['longitude'])) {
                        $point->latitude = $location['latitude'];
                        $point->longitude = $location['longitude'];
                    }
                }
                unset($point->location);

            }

            switch ($dataType) {
                case 'crime':
                    $point->alcivartech_date = $point->occurred_on_date;
                    $point->alcivartech_type = 'Crime';
                    break;
                case '311_cases':
                    $point->alcivartech_date = $point->open_dt;

                    $point->alcivartech_type = '311 Case';

                    break;
                case 'building_permits':
                    $point->alcivartech_date = $point->issued_date;
                    $point->alcivartech_type = 'Building Permit';
                    break;
                case 'property_violations':
                    $point->alcivartech_date = $point->status_dttm;
                    $point->alcivartech_type = 'Property Violation';
                    break;
                case 'construction_off_hours':
                    $point->alcivartech_date = $point->start_datetime;
                    $point->alcivartech_type = 'Construction Off Hour';
                    break;
                case 'food_inspections':

                        $point->alcivartech_date = $point->resultdttm;
                        $point->alcivartech_type = 'Food Inspection';
                    break;
                default:
                    $point->alcivartech_date = null; // Default case
            }
            return $point;
        });

        $data = $this->aggregateFoodViolations($data);

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
        // No specific tier logic needed here beyond what getData enforces.

        $modelClass = $this->getModelClass($dataType);
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
