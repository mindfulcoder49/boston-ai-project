<?php

namespace App\Http\Controllers;

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
use App\Models\PersonCrashData;
use App\Models\ChicagoCrime;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenericMapController extends Controller
{
    private const BOSTON_LINKABLE_MODELS = [
        \App\Models\CrimeData::class,
        \App\Models\ThreeOneOneCase::class,
        \App\Models\PropertyViolation::class,
        \App\Models\ConstructionOffHour::class,
        \App\Models\BuildingPermit::class,
        \App\Models\FoodInspection::class,
        \App\Models\EverettCrimeData::class,
        \App\Models\CambridgeThreeOneOneCase::class,
        \App\Models\CambridgeBuildingPermitData::class,
        \App\Models\CambridgeCrimeReportData::class,
        \App\Models\CambridgeHousingViolationData::class,
        \App\Models\CambridgeSanitaryInspectionData::class,
        \App\Models\PersonCrashData::class,
    ];

    private const CHICAGO_LINKABLE_MODELS = [
        \App\Models\ChicagoCrime::class,
    ];

    protected function getModelClassFromTableName(string $tableName, array $cityModels): ?string
    {
        foreach ($cityModels as $modelClass) {
            if (app($modelClass)->getTable() === $tableName) {
                return $modelClass;
            }
        }
        return null;
    }

    protected function getCityContext(float $latitude, float $longitude): array
    {
        $bostonLat = 42.3601;
        $bostonLon = -71.0589;
        $chicagoLat = 41.8781;
        $chicagoLon = -87.6298;

        $earthRadius = 6371;
        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);

        $latToBoston = deg2rad($bostonLat);
        $lonToBoston = deg2rad($bostonLon);
        $latToChicago = deg2rad($chicagoLat);
        $lonToChicago = deg2rad($chicagoLon);

        $latDeltaBoston = $latToBoston - $latFrom;
        $lonDeltaBoston = $lonToBoston - $lonFrom;
        $latDeltaChicago = $latToChicago - $latFrom;
        $lonDeltaChicago = $lonToChicago - $lonFrom;

        $angleBoston = 2 * asin(sqrt(pow(sin($latDeltaBoston / 2), 2) + cos($latFrom) * cos($latToBoston) * pow(sin($lonDeltaBoston / 2), 2)));
        $distBoston = $angleBoston * $earthRadius;

        $angleChicago = 2 * asin(sqrt(pow(sin($latDeltaChicago / 2), 2) + cos($latFrom) * cos($latToChicago) * pow(sin($lonDeltaChicago / 2), 2)));
        $distChicago = $angleChicago * $earthRadius;

        if ($distChicago < $distBoston) {
            return [
                'city' => 'chicago',
                'data_points_table' => 'chicago_data_points',
                'linkable_models' => self::CHICAGO_LINKABLE_MODELS,
                'db_connection' => 'chicago_db',
            ];
        }

        return [
            'city' => 'boston',
            'data_points_table' => 'data_points',
            'linkable_models' => self::BOSTON_LINKABLE_MODELS,
            'db_connection' => 'mysql',
        ];
    }

    protected function getJsonSelectForModel($modelClass, $jsonAlias)
    {
        $model = new $modelClass;
        $table = $model->getTable();
        
        $fields = $model->getFillable();
        $fields[] = $model->getKeyName();
        
        if (method_exists($modelClass, 'getDateField')) {
            $dateField = $modelClass::getDateField();
            if (!in_array($dateField, $fields)) {
                $fields[] = $dateField;
            }
        }

        if ($modelClass === \App\Models\FoodInspection::class) {
            if(!in_array('violdttm', $fields)) $fields[] = 'violdttm';
            if(!in_array('resultdttm', $fields)) $fields[] = 'resultdttm';
            if(!in_array('licenseno', $fields)) $fields[] = 'licenseno';
            if(!in_array('businessname', $fields)) $fields[] = 'businessname';
            if(!in_array('violdesc', $fields)) $fields[] = 'violdesc';
            if(!in_array('result', $fields)) $fields[] = 'result';
        }

        $fields = array_unique(array_filter($fields));

        if (empty($fields)) {
            return "JSON_OBJECT() as $jsonAlias";
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
        $currentPlanTier = 'free';
        $daysToFilter = 14;

        $centralLocation = $request->input('centralLocation');

        if (!$centralLocation) {
            $centralLocation = [
                'latitude' => 42.3601,
                'longitude' => -71.0589,
                'address' => 'Boston, MA',
            ];
        }
        
        $latitude = $centralLocation['latitude'];
        $longitude = $centralLocation['longitude'];

        $cityContext = $this->getCityContext($latitude, $longitude);
        $targetTable = $cityContext['data_points_table'];
        $linkableModels = $cityContext['linkable_models'];
        $dbConnection = $cityContext['db_connection'];

        if ($user) {
            $effectiveTierDetails = $user->getEffectiveTierDetails();
            $currentPlanTier = $effectiveTierDetails['tier'];

            if ($currentPlanTier === 'free') {
                $daysToFilter = 14;
            } elseif ($currentPlanTier === 'basic') {
                $daysToFilter = 14;
            } elseif ($currentPlanTier === 'pro') {
                $daysToFilter = 31;
            }
        }

        $cutoffDateTime = $daysToFilter ? Carbon::now()->subDays($daysToFilter)->startOfDay() : null;

        Log::info('User authentication status for data filtering.', [
            'authenticated' => (bool)$user,
            'currentPlanTier' => $currentPlanTier,
            'targetTable' => $targetTable,
            'daysToFilter' => $daysToFilter,
            'cutoffDateTime' => $cutoffDateTime ? $cutoffDateTime->toDateTimeString() : 'N/A (all time)',
            'cityContext' => $cityContext['city'],
        ]);

        $language_codes = $request->input('language_codes', ['es-MX', 'zh-CN', 'ht-HT', 'vi-VN', 'pt-BR', 'en-US']);
        $language_codes = array_intersect($language_codes, ['es-MX', 'zh-CN', 'ht-HT', 'vi-VN', 'pt-BR', 'en-US']);

        Log::info('Language codes to include.', ['language_codes' => $language_codes]);

        $radius = $request->input('radius', .25);
        $radius = min(max($radius, 0.01), .50);
        Log::info('Radius for radial search.', ['radius' => $radius]);

        $radiusInMeters = $radius * 1609.34;

        $wktPoint = "POINT($longitude $latitude)";

        $query = DB::connection($dbConnection)->table($targetTable)
            ->select(
                $targetTable.'.id as data_point_id', 
                DB::raw("ST_AsText({$targetTable}.location) as location_wkt"),
                $targetTable.'.type as alcivartech_type_raw',
                $targetTable.'.alcivartech_date as data_point_alcivartech_date_from_dp_table'
            );

        foreach ($linkableModels as $modelClass) {
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

        $dataPoints = $dataPoints->map(function ($point) use ($linkableModels) {
            $sourceTableName = $point->alcivartech_type_raw;
            $modelClass = $this->getModelClassFromTableName($sourceTableName, $linkableModels);

            if ($modelClass) {
                $jsonAlias = Str::snake(class_basename($modelClass)) . '_json';
                $dataObjectKey = Str::snake(class_basename($modelClass)) . '_data';

                if (property_exists($point, $jsonAlias) && $point->{$jsonAlias}) {
                    $point->{$dataObjectKey} = json_decode($point->{$jsonAlias});
                } else {
                    $point->{$dataObjectKey} = null;
                }
                unset($point->{$jsonAlias});

                $point->alcivartech_type = $modelClass::getAlcivartechTypeForStyling();
                
                $dateFieldInSource = $modelClass::getDateField();
                if ($point->{$dataObjectKey} && property_exists($point->{$dataObjectKey}, $dateFieldInSource)) {
                    $point->alcivartech_date = $point->{$dataObjectKey}->{$dateFieldInSource};
                } else {
                    $point->alcivartech_date = $point->data_point_alcivartech_date_from_dp_table;
                }
            } else {
                $point->alcivartech_type = 'Unknown';
                $point->alcivartech_date = $point->data_point_alcivartech_date_from_dp_table;
                foreach ($linkableModels as $mc) {
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

            $point->alcivartech_model = $point->alcivartech_type_raw;
            unset($point->alcivartech_type_raw);

            return $point;
        })->filter(function ($point) use ($cutoffDateTime) {
            if (!$cutoffDateTime) {
                return true; 
            }
            if (empty($point->alcivartech_date)) {
                return false; 
            }
            return Carbon::parse($point->alcivartech_date)->startOfDay()->gte($cutoffDateTime);
        })->values();
        
        Log::info('Data points fetched and filtered.', ['totalDataPointsCount' => $dataPoints->count()]);

        $dataPointModelConfig = [];
        $modelToSubObjectKeyMap = [];

        foreach ($linkableModels as $modelClass) {
             if (!class_exists($modelClass) || !in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                continue;
            }
            $modelInstance = new $modelClass();
            $tableName = $modelInstance->getTable();
            $dataObjectKey = Str::snake(class_basename($modelClass)) . '_data';

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

            $popupConfig = $modelClass::getPopupConfig();
            $configEntry = array_merge($baseConfigEntry, $popupConfig);

            $dataPointModelConfig[$tableName] = $configEntry;
        }

        $mapConfiguration = [
            'dataPointModelConfig' => $dataPointModelConfig,
            'modelToSubObjectKeyMap' => $modelToSubObjectKeyMap,
        ];

        return response()->json([
            'dataPoints' => $dataPoints,
            'centralLocation' => $centralLocation,
            'mapConfiguration' => $mapConfiguration,
        ]);
    }
}
