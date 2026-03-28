<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenericMapController extends Controller
{
    /**
     * Convert a model class basename to the data-point naming convention.
     *
     * Laravel's Str::snake() does not split trailing numeric suffixes, so
     * NewYork311 becomes "new_york311" instead of the schema convention
     * "new_york_311" used by generated data-point foreign keys.
     */
    protected function getModelDataPointStem(string $modelClass): string
    {
        $baseName = class_basename($modelClass);
        $normalized = preg_replace('/([a-zA-Z])(\d+)/', '$1_$2', $baseName);

        return Str::snake($normalized);
    }

    /**
     * Get the model class from a table name within the city's models.
     */
    protected function getModelClassFromTableName(string $tableName, array $cityModels): ?string
    {
        foreach ($cityModels as $modelClass) {
            if (class_exists($modelClass) && app($modelClass)->getTable() === $tableName) {
                return $modelClass;
            }
        }
        return null;
    }

    /**
     * Calculate Haversine distance between two points in kilometers.
     */
    protected function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    /**
     * Get the city context (database, models, etc.) based on coordinates.
     *
     * Uses the cities config to find the nearest city to the given coordinates.
     */
    protected function getCityContext(float $latitude, float $longitude): array
    {
        $cities = Config::get('cities.cities', []);
        $defaultCity = Config::get('cities.default', 'boston');

        if (empty($cities)) {
            Log::error('GenericMapController: No cities configured in config/cities.php');
            // Return a minimal fallback
            return [
                'city' => 'unknown',
                'data_points_table' => 'data_points',
                'linkable_models' => [],
                'db_connection' => 'mysql',
            ];
        }

        $nearestCity = null;
        $nearestDistance = PHP_FLOAT_MAX;

        foreach ($cities as $cityKey => $cityConfig) {
            $distance = $this->haversineDistance(
                $latitude,
                $longitude,
                $cityConfig['latitude'],
                $cityConfig['longitude']
            );

            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestCity = $cityKey;
            }
        }

        // Use default city if no nearest found (shouldn't happen with valid config)
        if ($nearestCity === null) {
            $nearestCity = $defaultCity;
        }

        $cityConfig = $cities[$nearestCity];

        return [
            'city' => $nearestCity,
            'data_points_table' => $cityConfig['data_points_table'],
            'linkable_models' => $cityConfig['models'] ?? [],
            'db_connection' => $cityConfig['db_connection'],
        ];
    }

    protected function getCityContextByKey(string $cityKey): ?array
    {
        $cityConfig = Config::get("cities.cities.{$cityKey}");

        if (!$cityConfig) {
            return null;
        }

        return [
            'city' => $cityKey,
            'data_points_table' => $cityConfig['data_points_table'],
            'linkable_models' => $cityConfig['models'] ?? [],
            'db_connection' => $cityConfig['db_connection'],
        ];
    }

    protected function getJsonSelectForModel($modelClass, $jsonAlias, ?string $tableAlias = null)
    {
        $model = new $modelClass;
        $tableIdentifier = $tableAlias ?: $model->getTable();

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
            $jsonObjectParts[] = "`{$tableIdentifier}`.`{$field}`";
        }

        return "JSON_OBJECT(" . implode(', ', $jsonObjectParts) . ") as $jsonAlias";
    }

    protected function shouldDeferSourceLookup(string $modelClass, string $queryConnectionName): bool
    {
        $model = new $modelClass();
        $sourceConnectionName = $model->getConnectionName() ?: $queryConnectionName;

        return $sourceConnectionName !== $queryConnectionName;
    }

    protected function loadDeferredSourceData($dataPoints, array $deferredLookups): array
    {
        $loadedSourceData = [];

        foreach ($deferredLookups as $sourceTableName => $lookup) {
            $modelClass = $lookup['model_class'];
            $model = new $modelClass();
            $keyName = $model->getKeyName();
            $foreignIds = $dataPoints
                ->pluck($lookup['foreign_key_column'])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (string) $value)
                ->unique()
                ->values()
                ->all();

            if (empty($foreignIds)) {
                $loadedSourceData[$sourceTableName] = [];
                continue;
            }

            $fields = $this->getDeferredPreviewSelectFields($modelClass, $model);

            $loadedSourceData[$sourceTableName] = $modelClass::query()
                ->select($fields)
                ->whereIn($keyName, $foreignIds)
                ->get()
                ->mapWithKeys(fn ($record) => [
                    (string) $record->{$keyName} => $this->sanitizeSourceRecord($record->getAttributes()),
                ])
                ->all();
        }

        return $loadedSourceData;
    }

    protected function getDeferredPreviewSelectFields(string $modelClass, $model): array
    {
        $keyName = $model->getKeyName();
        $dateField = method_exists($modelClass, 'getDateField') ? $modelClass::getDateField() : null;
        $previewFields = [
            'crimename1',
            'crimename2',
            'crimename3',
            'incident_type_group',
            'incident_type',
            'incident_description',
            'incident_address',
            'offense_code_group',
            'offense_description',
            'primary_type',
            'offense_category',
            'incident_category',
            'crime',
            'offense_parent_group',
            'nibrs_crime_against_category',
            'nibrs_offense_code_description',
            'description',
            'incident_subcategory',
            'crime_details',
            'crime_details_concatenated',
            'offense',
            'location_description',
            'location',
            'block_address',
            'block',
            'street_name',
            'street',
            'address',
            'address_number',
            'address_street',
            'street_type',
        ];

        $fields = [$keyName];

        if ($dateField) {
            $fields[] = $dateField;
        }

        return array_values(array_unique(array_merge(
            $fields,
            array_intersect($model->getFillable(), $previewFields),
        )));
    }

    protected function sanitizeSourceRecord(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $attributes[$key] = null;
            }
        }

        return $attributes;
    }

    public function getRadialMapData(Request $request)
    {
        $user = Auth::user();
        $currentPlanTier = 'free';
        $daysToFilter = 14;

        $centralLocation = $request->input('centralLocation');

        // Get default city from config for fallback location
        $defaultCity = Config::get('cities.default', 'boston');
        $defaultCityConfig = Config::get("cities.cities.{$defaultCity}", [
            'latitude' => 42.3601,
            'longitude' => -71.0589,
            'name' => 'Boston',
        ]);

        if (!$centralLocation) {
            $centralLocation = [
                'latitude' => $defaultCityConfig['latitude'],
                'longitude' => $defaultCityConfig['longitude'],
                'address' => $defaultCityConfig['name'] . ', USA',
            ];
        }

        $latitude = $centralLocation['latitude'];
        $longitude = $centralLocation['longitude'];

        $forcedCity = $request->input('city');
        $cityContext = null;

        if (is_string($forcedCity) && $forcedCity !== '') {
            $cityContext = $this->getCityContextByKey($forcedCity);

            if (!$cityContext) {
                return response()->json([
                    'message' => "Unknown city '{$forcedCity}'.",
                ], 422);
            }
        }

        if (!$cityContext) {
            $cityContext = $this->getCityContext($latitude, $longitude);
        }

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

        $deferredLookups = [];

        foreach ($linkableModels as $modelClass) {
            if (!class_exists($modelClass) || !in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                Log::warning("GenericMapController: Model {$modelClass} is not Mappable or does not exist. Skipping for JOIN/SELECT.");
                continue;
            }
            $modelInstance = new $modelClass();
            $sourceTableName = $modelInstance->getTable();
            $modelStem = $this->getModelDataPointStem($modelClass);
            $jsonAlias = $modelStem . '_json';
            $fkColumnInDataPoints = $modelStem . '_id';

            if ($this->shouldDeferSourceLookup($modelClass, $dbConnection)) {
                $query->addSelect($targetTable.'.'.$fkColumnInDataPoints);
                $deferredLookups[$sourceTableName] = [
                    'model_class' => $modelClass,
                    'foreign_key_column' => $fkColumnInDataPoints,
                ];
                continue;
            }

            $query->addSelect(DB::raw($this->getJsonSelectForModel($modelClass, $jsonAlias)));
            $query->leftJoin($sourceTableName, $targetTable.'.'.$fkColumnInDataPoints, '=', $sourceTableName.'.'.$modelInstance->getKeyName());
        }

        $query->whereRaw("ST_Distance_Sphere({$targetTable}.location, ST_GeomFromText(?)) <= ?", [$wktPoint, $radiusInMeters]);

        if ($cutoffDateTime && $targetTable === 'data_points') {
            $query->where($targetTable.'.alcivartech_date', '>=', $cutoffDateTime);
        }

        $dataPoints = $query->get();
        $deferredSourceData = $this->loadDeferredSourceData($dataPoints, $deferredLookups);

        $dataPoints = $dataPoints->map(function ($point) use ($linkableModels, $deferredLookups, $deferredSourceData) {
            $sourceTableName = $point->alcivartech_type_raw;
            $modelClass = $this->getModelClassFromTableName($sourceTableName, $linkableModels);

            if ($modelClass) {
                $modelStem = $this->getModelDataPointStem($modelClass);
                $jsonAlias = $modelStem . '_json';
                $dataObjectKey = $modelStem . '_data';

                if (isset($deferredLookups[$sourceTableName])) {
                    $foreignKeyColumn = $deferredLookups[$sourceTableName]['foreign_key_column'];
                    $foreignId = property_exists($point, $foreignKeyColumn) ? (string) $point->{$foreignKeyColumn} : null;
                    $sourceRecord = $foreignId ? ($deferredSourceData[$sourceTableName][$foreignId] ?? null) : null;
                    $point->{$dataObjectKey} = $sourceRecord ? (object) $sourceRecord : null;
                    unset($point->{$foreignKeyColumn});
                } elseif (property_exists($point, $jsonAlias) && $point->{$jsonAlias}) {
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
                    $jsonAliasFallback = $this->getModelDataPointStem($mc) . '_json';
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
            $dataObjectKey = $this->getModelDataPointStem($modelClass) . '_data';

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
            'city' => $cityContext['city'],
        ]);
    }
}
