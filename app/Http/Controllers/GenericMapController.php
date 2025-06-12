<?php

namespace App\Http\Controllers;

use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\BuildingPermit;
use App\Models\PropertyViolation;
use App\Models\ConstructionOffHour;
use App\Models\DataPoint;
use App\Models\FoodInspection; // Corrected model name from FoodEstablishmentViolation
use App\Models\EverettCrimeData; // Added EverettCrimeData model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GenericMapController extends Controller
{

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

        // FoodInspection uses violdttm and resultdttm. Both should be in fillable.
        // If not, add them explicitly. Current FoodInspection model has them in fillable.
        // Example:
        // if ($modelClass === \App\Models\FoodInspection::class) {
        //     if(!in_array('violdttm', $fields)) $fields[] = 'violdttm';
        // }

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
        $daysToFilter = 7; // Default for unauthenticated users
        $targetTable = 'data_points'; // Default table

        if ($user) {
            $effectiveTierDetails = $user->getEffectiveTierDetails();
            $currentPlanTier = $effectiveTierDetails['tier'];

            if ($currentPlanTier === 'free') {
                $daysToFilter = 14; // Authenticated free user
                $targetTable = 'data_points';
            } elseif ($currentPlanTier === 'basic') {
                $daysToFilter = 21; // Basic plan
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

        $defaultLatitude = 42.3601;
        $defaultLongitude = -71.0589;
        $defaultAddress = 'Boston, MA';

        // Set defaults if user is not logged in
        if (Auth::check()) {
            $user = Auth::user();
            $location = $user->locations->first();
            if ($location) {
                $defaultLatitude = $location->latitude;
                $defaultLongitude = $location->longitude;
                $defaultAddress = $location->address;
            }
        }

        $centralLocation = $request->input('centralLocation', [
            'latitude' => $defaultLatitude,
            'longitude' => $defaultLongitude,
            'address' => $defaultAddress,
        ]);



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
                $targetTable.'.type as alcivartech_type_raw', // Keep raw type for switch, rename to avoid clash if alcivartech_type is also a field in JSON
                // $targetTable.'.alcivartech_date as data_point_alcivartech_date', // This can be used if alcivartech_date in data_points is the source of truth

                DB::raw($this->getJsonSelectForModel(\App\Models\CrimeData::class, 'crime_data_json')),
                DB::raw($this->getJsonSelectForModel(\App\Models\ThreeOneOneCase::class, 'three_one_one_case_json')),
                DB::raw($this->getJsonSelectForModel(\App\Models\PropertyViolation::class, 'property_violation_json')),
                DB::raw($this->getJsonSelectForModel(\App\Models\ConstructionOffHour::class, 'construction_off_hour_json')),
                DB::raw($this->getJsonSelectForModel(\App\Models\BuildingPermit::class, 'building_permit_json')),
                DB::raw($this->getJsonSelectForModel(\App\Models\FoodInspection::class, 'food_inspection_json')),
                DB::raw($this->getJsonSelectForModel(\App\Models\EverettCrimeData::class, 'everett_crime_data_json')) // Added EverettCrimeData
            )
            ->leftJoin('crime_data', $targetTable.'.crime_data_id', '=', 'crime_data.id')
            ->leftJoin('three_one_one_cases', $targetTable.'.three_one_one_case_id', '=', 'three_one_one_cases.id')
            ->leftJoin('property_violations', $targetTable.'.property_violation_id', '=', 'property_violations.id')
            ->leftJoin('construction_off_hours', $targetTable.'.construction_off_hour_id', '=', 'construction_off_hours.id')
            ->leftJoin('building_permits', $targetTable.'.building_permit_id', '=', 'building_permits.id')
            ->leftJoin('food_inspections', $targetTable.'.food_inspection_id', '=', 'food_inspections.id')
            ->leftJoin('everett_crime_data', $targetTable.'.everett_crime_data_id', '=', 'everett_crime_data.id') // Added join for EverettCrimeData
            ->whereRaw("ST_Distance_Sphere({$targetTable}.location, ST_GeomFromText(?)) <= ?", [$wktPoint, $radiusInMeters]);

        if ($cutoffDateTime && $targetTable === 'data_points') { 
            // Apply date filtering based on the main date of the data_point itself
            $query->where($targetTable.'.alcivartech_date', '>=', $cutoffDateTime);
        }
        
        $dataPoints = $query->get();    

        $dataPoints = $dataPoints->map(function ($point) {
            // Decode JSON strings into objects
            $point->crime_data = $point->crime_data_json ? json_decode($point->crime_data_json) : null;
            unset($point->crime_data_json);
            $point->three_one_one_case_data = $point->three_one_one_case_json ? json_decode($point->three_one_one_case_json) : null;
            unset($point->three_one_one_case_json);
            $point->property_violation_data = $point->property_violation_json ? json_decode($point->property_violation_json) : null;
            unset($point->property_violation_json);
            $point->construction_off_hour_data = $point->construction_off_hour_json ? json_decode($point->construction_off_hour_json) : null;
            unset($point->construction_off_hour_json);
            $point->building_permit_data = $point->building_permit_json ? json_decode($point->building_permit_json) : null;
            unset($point->building_permit_json);
            $point->food_inspection_data = $point->food_inspection_json ? json_decode($point->food_inspection_json) : null;
            unset($point->food_inspection_json);
            $point->everett_crime_data = $point->everett_crime_data_json ? json_decode($point->everett_crime_data_json) : null; // Added EverettCrimeData
            unset($point->everett_crime_data_json);

            preg_match('/POINT\((-?\d+\.\d+) (-?\d+\.\d+)\)/', $point->location_wkt, $matches);
            $point->longitude = isset($matches[1]) ? floatval($matches[1]) : null;
            $point->latitude = isset($matches[2]) ? floatval($matches[2]) : null;
            unset($point->location_wkt); 

            $humanReadableType = 'Unknown';
            $point->alcivartech_model = $point->alcivartech_type_raw; // Set the model from the raw type

            switch ($point->alcivartech_type_raw) {
                case 'crime_data':
                    $humanReadableType = 'Crime';
                    break;
                case 'everett_crime_data': // Added case for Everett crime
                    $humanReadableType = 'Crime'; // Keep human-readable type as 'Crime'
                    break;
                case 'three_one_one_cases':
                    $humanReadableType = '311 Case';
                    break;
                case 'building_permits':
                    $humanReadableType = 'Building Permit';
                    break;
                case 'property_violations':
                    $humanReadableType = 'Property Violation';
                    break;
                case 'construction_off_hours':
                    $humanReadableType = 'Construction Off Hour';
                    break;
                case 'food_inspections': 
                    $humanReadableType = 'Food Inspection';
                    break;
            }
            $point->alcivartech_type = $humanReadableType;
            unset($point->alcivartech_type_raw);

            switch ($humanReadableType) { // This switch should now use the humanReadableType
                case 'Crime':
                    // Differentiate based on the model (raw type)
                    if ($point->alcivartech_model === 'everett_crime_data' && $point->everett_crime_data) {
                        $point->alcivartech_date = $point->everett_crime_data->occurred_on_datetime ?? null;
                    } elseif ($point->alcivartech_model === 'crime_data' && $point->crime_data) {
                        $point->alcivartech_date = $point->crime_data->occurred_on_date ?? null;
                    } else {
                        $point->alcivartech_date = null;
                    }
                    break;
                case '311 Case':
                    // Assuming 'open_dt' is the field in ThreeOneOneCase model
                    $point->alcivartech_date = $point->three_one_one_case_data->open_dt ?? null; 
                    break;
                case 'Building Permit':
                    $point->alcivartech_date = $point->building_permit_data->issued_date ?? null;
                    break;
                case 'Property Violation':
                    $point->alcivartech_date = $point->property_violation_data->status_dttm ?? null;
                    break;
                case 'Construction Off Hour':
                    $point->alcivartech_date = $point->construction_off_hour_data->start_datetime ?? null;
                    break;
                case 'Food Inspection': 
                    if ($point->food_inspection_data && !empty($point->food_inspection_data->violdttm)) {
                        $point->alcivartech_date = $point->food_inspection_data->violdttm;
                    } elseif ($point->food_inspection_data && !empty($point->food_inspection_data->resultdttm)) {
                        $point->alcivartech_date = $point->food_inspection_data->resultdttm;
                    } else {
                        $point->alcivartech_date = null;
                    }
                    break;
                default:
                    $point->alcivartech_date = null;
            }

            if (!Auth::check() && $humanReadableType === 'Food Inspection') {
                $restrictedMessage = "Log In to See Food Inspection Information";
                
                if ($point->food_inspection_data) {
                    $foodData = (array)$point->food_inspection_data;
                    $newFoodData = new \stdClass();
                    foreach ($foodData as $key => $value) {
                        // Remove specific location fields from food_inspection_data itself
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
                    $point->food_inspection_data = $newFoodData;
                }
                // Unset top-level lat/lng for this data point if it's a food inspection and user is not authed
                unset($point->latitude);
                unset($point->longitude);
            }
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

        $dataPointModelConfig = [
            'crime_data' => [
              'dataObjectKey' => 'crime_data',
              'mainIdentifierLabel' => 'Incident Number',
              'mainIdentifierField' => 'incident_number',
              'descriptionLabel' => 'Description',
              'descriptionField' => 'offense_description',
              'additionalFields' => [
                ['label' => 'District', 'key' => 'district'],
                ['label' => 'Shooting', 'key' => 'shooting', 'condition' => 'boolean_true_only'],
              ]
            ],
            'everett_crime_data' => [
              'dataObjectKey' => 'everett_crime_data',
              'mainIdentifierLabel' => 'Case Number',
              'mainIdentifierField' => 'case_number',
              'descriptionLabel' => 'Description',
              'descriptionField' => 'incident_description',
              'additionalFields' => [
                ['label' => 'Incident Type', 'key' => 'incident_type'],
                ['label' => 'Arrest Name', 'key' => 'arrest_name', 'condition' => 'not_empty_string'],
                ['label' => 'Arrest Charges', 'key' => 'arrest_charges', 'condition' => 'not_empty_string'],
              ]
            ],
            'three_one_one_cases' => [
              'dataObjectKey' => 'three_one_one_case_data',
              'mainIdentifierLabel' => 'Case ID',
              'mainIdentifierField' => 'case_enquiry_id',
              'descriptionLabel' => 'Title',
              'descriptionField' => 'case_title',
              'additionalFields' => [
                  ['label' => 'Status', 'key' => 'case_status'],
                  ['label' => 'Address', 'key' => 'location_street_name'],
              ]
            ],
            'building_permits' => [
              'dataObjectKey' => 'building_permit_data',
              'mainIdentifierLabel' => 'Permit Number',
              'mainIdentifierField' => 'permitnumber',
              'descriptionLabel' => 'Description',
              'descriptionField' => 'description',
              'additionalFields' => [
                ['label' => 'Permit Type', 'key' => 'permit_type'],
                ['label' => 'Status', 'key' => 'status'],
                ['label' => 'Address', 'key' => 'address'],
              ]
            ],
            'property_violations' => [
              'dataObjectKey' => 'property_violation_data',
              'mainIdentifierLabel' => 'Ticket Number',
              'mainIdentifierField' => 'ticket_number',
              'descriptionLabel' => 'Description',
              'descriptionField' => 'description',
              'additionalFields' => [
                ['label' => 'Violation Type', 'key' => 'violation_type'],
                ['label' => 'Status', 'key' => 'status'],
                ['label' => 'Address', 'key' => 'address'],
              ]
            ],
            'construction_off_hours' => [
              'dataObjectKey' => 'construction_off_hour_data',
              'mainIdentifierLabel' => 'Application Number',
              'mainIdentifierField' => 'app_no',
              'descriptionLabel' => 'Address',
              'descriptionField' => 'address',
              'additionalFields' => [
                  ['label' => 'Permit Type', 'key' => 'permit_type'],
                  ['label' => 'Hours Requested', 'key' => 'hours_req'],
              ]
            ],
            'food_inspections' => [
              'dataObjectKey' => 'food_inspection_data',
              'customPopupHandler' => true, 
              'mainIdentifierLabel' => 'License No',
              'mainIdentifierField' => 'licenseno',
              'descriptionLabel' => 'Business Name',
              'descriptionField' => 'businessname',
               'additionalFields' => [
                ['label' => 'Violation', 'key' => 'violdesc'],
                ['label' => 'Result', 'key' => 'result'],
                ['label' => 'Address', 'key' => 'address'],
              ]
            ]
        ];

        $mapConfiguration = [
            'dataPointModelConfig' => $dataPointModelConfig,
            // Add other map-wide configurations here if needed in the future
        ];

        return response()->json([
            'dataPoints' => $dataPoints,
            'centralLocation' => $centralLocation,
            'mapConfiguration' => $mapConfiguration, // Add mapConfiguration to the response
        ]);
    }
}
