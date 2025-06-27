<?php

namespace App\Http\Controllers;

// Import all necessary models.
// It's better to have these explicitly listed or managed via a config/registry.
use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\BuildingPermit;
use App\Models\PropertyViolation;
use App\Models\ConstructionOffHour;
use App\Models\FoodInspection;
use App\Models\EverettCrimeData;
use App\Models\CambridgeThreeOneOneCase;
use App\Models\CambridgeBuildingPermitData;
use App\Models\CambridgeCrimeReportData;
use App\Models\CambridgeHousingViolationData;
use App\Models\CambridgeSanitaryInspectionData;
// DataPoint model is not directly joined but is the base table.
// use App\Models\DataPoint; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Added for string manipulation

class GenericMapController extends Controller
{
    /**
     * Define the Mappable models that data_points can link to.
     * This should ideally be consistent with DataPointSeeder::MODELS_TO_PROCESS.
     * Key: Fully qualified model class name. Value: simple key or alias if needed, otherwise class name itself.
     */
    private const LINKABLE_MODELS = [
        // Boston Models
        \App\Models\CrimeData::class,
        \App\Models\ThreeOneOneCase::class,
        \App\Models\PropertyViolation::class,
        \App\Models\ConstructionOffHour::class,
        \App\Models\BuildingPermit::class,
        \App\Models\FoodInspection::class,

        // Everett Models
        \App\Models\EverettCrimeData::class,

        // Cambridge Models
        \App\Models\CambridgeThreeOneOneCase::class,
        \App\Models\CambridgeBuildingPermitData::class,
        \App\Models\CambridgeCrimeReportData::class,
        \App\Models\CambridgeHousingViolationData::class,
        \App\Models\CambridgeSanitaryInspectionData::class,

        \App\Models\PersonCrashData::class, // Assuming this is a Mappable model as well
    ];

    // Helper to get model class from table name (data_points.type)
    protected function getModelClassFromTableName(string $tableName): ?string
    {
        foreach (self::LINKABLE_MODELS as $modelClass) {
            if (app($modelClass)->getTable() === $tableName) {
                return $modelClass;
            }
        }
        return null;
    }


    protected function getJsonSelectForModel($modelClass, $jsonAlias)
    {
        $model = new $modelClass;
        $table = $model->getTable();
        
        $fields = $model->getFillable();
        $fields[] = $model->getKeyName(); // Add primary key
        
        // Add date field(s) used in logic if not already in fillable
        if (method_exists($modelClass, 'getDateField')) {
            $dateField = $modelClass::getDateField();
            if (!in_array($dateField, $fields)) {
                $fields[] = $dateField;
            }
        }

        // Ensure specific fields needed for display logic are included
        if ($modelClass === \App\Models\FoodInspection::class) {
            if(!in_array('violdttm', $fields)) $fields[] = 'violdttm';
            if(!in_array('resultdttm', $fields)) $fields[] = 'resultdttm';
            if(!in_array('licenseno', $fields)) $fields[] = 'licenseno';
            if(!in_array('businessname', $fields)) $fields[] = 'businessname';
            if(!in_array('violdesc', $fields)) $fields[] = 'violdesc';
            if(!in_array('result', $fields)) $fields[] = 'result';
        }
        // Add similar checks for other models if they have specific fields
        // used in the frontend popup logic that might not be in $fillable.

        $fields = array_unique(array_filter($fields)); // Remove duplicates and empty values

        if (empty($fields)) {
            return "JSON_OBJECT() as $jsonAlias"; // Return empty JSON object if no fields
        }

        $jsonObjectParts = [];
        foreach ($fields as $field) {
            $jsonObjectParts[] = "'$field'";
            $jsonObjectParts[] = "`$table`.`$field`";
        }

        return "JSON_OBJECT(" . implode(', ', $jsonObjectParts) . ") as $jsonAlias";
    }

    public function getRadialMapData(Request $request)
    {
        $user = Auth::user();
        $currentPlanTier = 'free'; // Default for unauthenticated or free users
        $daysToFilter = 14; // Default for unauthenticated users
        $targetTable = 'data_points'; // Default table

        if ($user) {
            $effectiveTierDetails = $user->getEffectiveTierDetails();
            $currentPlanTier = $effectiveTierDetails['tier'];

            if ($currentPlanTier === 'free') {
                $daysToFilter = 14; // Authenticated free user
                $targetTable = 'data_points';
            } elseif ($currentPlanTier === 'basic') {
                $daysToFilter = 14; // Basic plan
                $targetTable = 'data_points';
            } elseif ($currentPlanTier === 'pro') {
                $targetTable = 'data_points'; // Pro plan uses data_points (or could be all_time_data_points if that exists)
                // For Pro, effectively all time from data_points, or a very long period.
                // If using data_points, $daysToFilter could be set very large or logic adapted.
                // For simplicity, let's set a larger number of days for pro on data_points.
                $daysToFilter = 31; // Pro plan gets more days from data_points
                                     // Or, if 'all_time_data_points' table is used for Pro, set $daysToFilter = null
                                     // and adjust the query logic. Assuming data_points for now.
            }
        }

        $cutoffDateTime = $daysToFilter ? Carbon::now()->subDays($daysToFilter)->startOfDay() : null;

        Log::info('User authentication status for data filtering.', [
            'authenticated' => (bool)$user,
            'currentPlanTier' => $currentPlanTier,
            'targetTable' => $targetTable,
            'daysToFilter' => $daysToFilter,
            'cutoffDateTime' => $cutoffDateTime ? $cutoffDateTime->toDateTimeString() : 'N/A (all time)',
        ]);

        $centralLocation = $request->input('centralLocation');

        // Fallback if frontend doesn't provide location, which it should.
        if (!$centralLocation) {
            $centralLocation = [
                'latitude' => 42.3601,
                'longitude' => -71.0589,
                'address' => 'Boston, MA',
            ];
        }

        $language_codes = $request->input('language_codes', ['es-MX', 'zh-CN', 'ht-HT', 'vi-VN', 'pt-BR', 'en-US']);
        //remove any invalid language codes
        $language_codes = array_intersect($language_codes, ['es-MX', 'zh-CN', 'ht-HT', 'vi-VN', 'pt-BR', 'en-US']);


        Log::info('Language codes to include.', ['language_codes' => $language_codes]);

        $radius = $request->input('radius', .25);
        //clamp radius to a maximum of 1
        $radius = min(max($radius, 0.01), .50); // Clamp between 0.01 and 1 mile
        Log::info('Radius for radial search.', ['radius' => $radius]);
        // The individual $crimeDays, $caseDays etc. are no longer primary drivers for date filtering,
        // $cutoffDateTime based on subscription will be used.
        // These can be removed or kept if there's a different specific use case for them later.
        // For now, we'll rely on the global $cutoffDateTime.

    
        $latitude = $centralLocation['latitude'];
        $longitude = $centralLocation['longitude'];
        $radiusInMeters = $radius * 1609.34; // Convert miles to meters

        $wktPoint = "POINT($longitude $latitude)";

        $query = DB::table($targetTable) // Use dynamic targetTable
            ->select(
                $targetTable.'.id as data_point_id', 
                DB::raw("ST_AsText({$targetTable}.location) as location_wkt"),
                $targetTable.'.type as alcivartech_type_raw', // This is the source table name
                $targetTable.'.alcivartech_date as data_point_alcivartech_date_from_dp_table' // Date from data_points table itself
            );

        // Dynamically add JSON selects and LEFT JOINs
        foreach (self::LINKABLE_MODELS as $modelClass) {
            if (!class_exists($modelClass) || !in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                Log::warning("GenericMapController: Model {$modelClass} is not Mappable or does not exist. Skipping for JOIN/SELECT.");
                continue;
            }
            $modelInstance = new $modelClass();
            $sourceTableName = $modelInstance->getTable();
            $jsonAlias = Str::snake(class_basename($modelClass)) . '_json';
            $fkColumnInDataPoints = Str::snake(class_basename($modelClass)) . '_id';

            $query->addSelect(DB::raw($this->getJsonSelectForModel($modelClass, $jsonAlias)));
            $query->leftJoin($sourceTableName, $targetTable.'.'.$fkColumnInDataPoints, '=', $sourceTableName.'.'.$modelInstance->getKeyName());
        }
            
        $query->whereRaw("ST_Distance_Sphere({$targetTable}.location, ST_GeomFromText(?)) <= ?", [$wktPoint, $radiusInMeters]);

        if ($cutoffDateTime && $targetTable === 'data_points') { 
            $query->where($targetTable.'.alcivartech_date', '>=', $cutoffDateTime);
        }
        
        $dataPoints = $query->get();    

        $dataPoints = $dataPoints->map(function ($point) {
            // Dynamically decode JSON and set properties
            $sourceTableName = $point->alcivartech_type_raw; // e.g., 'crime_data', 'cambridge_311_service_requests'
            $modelClass = $this->getModelClassFromTableName($sourceTableName);

            if ($modelClass) {
                $jsonAlias = Str::snake(class_basename($modelClass)) . '_json';
                $dataObjectKey = Str::snake(class_basename($modelClass)) . '_data'; // e.g., crime_data_data

                if (property_exists($point, $jsonAlias) && $point->{$jsonAlias}) {
                    $point->{$dataObjectKey} = json_decode($point->{$jsonAlias});
                } else {
                    $point->{$dataObjectKey} = null;
                }
                unset($point->{$jsonAlias});

                $point->alcivartech_type = $modelClass::getAlcivartechTypeForStyling(); // Human-readable type for styling/display
                
                // Set alcivartech_date from the source model's data object
                $dateFieldInSource = $modelClass::getDateField();
                if ($point->{$dataObjectKey} && property_exists($point->{$dataObjectKey}, $dateFieldInSource)) {
                    $point->alcivartech_date = $point->{$dataObjectKey}->{$dateFieldInSource};
                } else {
                    // Fallback to the date stored in data_points table if source model's date is not available
                    $point->alcivartech_date = $point->data_point_alcivartech_date_from_dp_table;
                }
            } else {
                // Fallback if modelClass couldn't be determined
                $point->alcivartech_type = 'Unknown';
                $point->alcivartech_date = $point->data_point_alcivartech_date_from_dp_table;
                // Try to clean up any potential _json fields if model is unknown
                foreach (self::LINKABLE_MODELS as $mc) {
                    $jsonAliasFallback = Str::snake(class_basename($mc)) . '_json';
                    if (property_exists($point, $jsonAliasFallback)) {
                        unset($point->{$jsonAliasFallback});
                    }
                }
            }
            unset($point->data_point_alcivartech_date_from_dp_table);


            preg_match('/POINT\((-?\d+\.\d+) (-?\d+\.\d+)\)/', $point->location_wkt, $matches);
            $point->longitude = isset($matches[1]) ? floatval($matches[1]) : null;
            $point->latitude = isset($matches[2]) ? floatval($matches[2]) : null;
            unset($point->location_wkt); 

            $point->alcivartech_model = $point->alcivartech_type_raw; // Keep original table name as model identifier
            unset($point->alcivartech_type_raw);


            // Specific handling for FoodInspection if user is not authenticated
            /*
            if (!Auth::check() && $modelClass === \App\Models\FoodInspection::class) {
                $restrictedMessage = "Log In to See Food Inspection Information";
                $foodDataKey = Str::snake(class_basename(\App\Models\FoodInspection::class)) . '_data';

                if ($point->{$foodDataKey}) {
                    $foodData = (array)$point->{$foodDataKey};
                    $newFoodData = new \stdClass();
                    foreach ($foodData as $key => $value) {
                        if (in_array($key, ['latitude', 'longitude', 'location', 'lat', 'lng', 'gpsy', 'gpsx', 'y_latitude', 'x_longitude'])) {
                            continue; 
                        }
                        if ($key === 'licenseno') {
                            $newFoodData->$key = md5((string)$value);
                        } elseif (!empty($value) && !in_array($key, ['violdesc', 'viol_level', 'comments'])) {
                            $newFoodData->$key = $restrictedMessage;
                        } else {
                            $newFoodData->$key = $value;
                        }
                    }
                    $point->{$foodDataKey} = $newFoodData;
                }
                unset($point->latitude);
                unset($point->longitude);
            }
                */
            return $point;
        })->filter(function ($point) use ($cutoffDateTime) {
            // If there's no cutoff date (e.g., for an "all time" scenario, though not fully implemented for pro plan yet),
            // or if the point has no date, include it.
            // However, if a cutoffDateTime is set, points without a date should likely be excluded.
            if (!$cutoffDateTime) {
                return true; 
            }
            if (empty($point->alcivartech_date)) {
                // If a date filter is active, points without a date are excluded.
                return false; 
            }
            // Ensure the point's final alcivartech_date is on or after the cutoff.
            // Carbon::parse can handle various date string formats.
            return Carbon::parse($point->alcivartech_date)->startOfDay()->gte($cutoffDateTime);
        })->values(); // Reset keys after filtering
        
        
        Log::info('Data points fetched and filtered.', ['totalDataPointsCount' => $dataPoints->count()]);

        //log some of the data points
        $dataPoints->take(5)->each(function ($dataPoint) {
            Log::info('Data point', [$dataPoint]);
        });

        // The dataPointModelConfig needs to be generated dynamically or its keys must match data_points.type
        $dataPointModelConfig = [];
        $modelToSubObjectKeyMap = []; // Initialize the new map

        foreach (self::LINKABLE_MODELS as $modelClass) {
             if (!class_exists($modelClass) || !in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                continue;
            }
            $modelInstance = new $modelClass();
            $tableName = $modelInstance->getTable(); // This will be the key, e.g., 'crime_data'
            $dataObjectKey = Str::snake(class_basename($modelClass)) . '_data';

            // Populate the modelToSubObjectKeyMap
            $modelToSubObjectKeyMap[$tableName] = $dataObjectKey;

            $filterDesc = $modelClass::getFilterableFieldsDescription();
            if (is_string($filterDesc)) {
                try {
                    $filterDesc = json_decode($filterDesc, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    Log::error("Failed to decode filterFieldsDescription for {$modelClass}: " . $e->getMessage());
                    $filterDesc = [];
                }
            }

            $baseConfigEntry = [
                'dataObjectKey' => $dataObjectKey,
                'displayTitle' => $modelClass::getAlcivartechTypeForStyling(),
                'filterFieldsDescription' => $filterDesc,
            ];

            // Get specific popup config from the model
            // This method is now expected to be implemented due to the Mappable trait contract.
            $popupConfig = $modelClass::getPopupConfig();
            $configEntry = array_merge($baseConfigEntry, $popupConfig);

            $dataPointModelConfig[$tableName] = $configEntry;
        }


        $mapConfiguration = [
            'dataPointModelConfig' => $dataPointModelConfig,
            'modelToSubObjectKeyMap' => $modelToSubObjectKeyMap, // Add the new map here
        ];

        return response()->json([
            'dataPoints' => $dataPoints,
            'centralLocation' => $centralLocation,
            'mapConfiguration' => $mapConfiguration, // Add mapConfiguration to the response
        ]);
    }
}
