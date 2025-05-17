<?php

namespace App\Http\Controllers;

use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\BuildingPermit;
use App\Models\PropertyViolation;
use App\Models\ConstructionOffHour;
use App\Models\DataPoint;
use App\Models\FoodEstablishmentViolation; // Add this line
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GenericMapController extends Controller
{

    public function generateJsonObjectFromModel($modelClass)
    {
        $fillable = (new $modelClass)->getFillable();
    
        // Escape reserved keywords with backticks for MySQL
        $jsonObject = implode(', ', array_map(function ($field) {
            $escapedField = "`$field`"; // Add backticks around the field name
            return "'$field', $escapedField";
        }, $fillable));
    
        return "JSON_OBJECT($jsonObject)";
    }

    public function getRadialMapData(Request $request)
    {
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
        $days = 14;
        $crimeDays = 14;
        $caseDays = 14;
        $permitDays = 14;
        $violationDays = 14;
        $offHourDays = 14;

    
        $latitude = $centralLocation['latitude'];
        $longitude = $centralLocation['longitude'];
        $radiusInMeters = $radius * 1609.34; // Convert miles to meters

        $wktPoint = "POINT($longitude $latitude)";

        $dataPoints = DB::table('data_points')
            ->select(
                'data_points.id as data_point_id',
                DB::raw('ST_AsText(data_points.location) as location_wkt'), // Convert spatial data to readable format
                'data_points.type as alcivartech_type', // Renaming type to prevent conflicts

                // Crime Data
                'crime_data.id as crime_id',
                'crime_data.*',


                // 311 Cases
                'three_one_one_cases.id as case_id',
                'three_one_one_cases.*',

                // Property Violations
                'property_violations.id as violation_id',
                'property_violations.*',

                // Construction Off Hours
                'construction_off_hours.id as construction_id',
                'construction_off_hours.*',

                // Building Permits
                'building_permits.id as permit_id',
                'building_permits.*',

                // Food Establishment Violations
                'food_establishment_violations.id as food_violation_id',
                'food_establishment_violations.*'
            )
            ->leftJoin('crime_data', 'data_points.crime_data_id', '=', 'crime_data.id')
            ->leftJoin('three_one_one_cases', 'data_points.three_one_one_case_id', '=', 'three_one_one_cases.id')
            ->leftJoin('property_violations', 'data_points.property_violation_id', '=', 'property_violations.id')
            ->leftJoin('construction_off_hours', 'data_points.construction_off_hour_id', '=', 'construction_off_hours.id')
            ->leftJoin('building_permits', 'data_points.building_permit_id', '=', 'building_permits.id')
            ->leftJoin('food_establishment_violations', 'data_points.food_establishment_violation_id', '=', 'food_establishment_violations.id') // Add this line
            ->whereRaw("ST_Distance_Sphere(data_points.location, ST_GeomFromText(?)) <= ?", [$wktPoint, $radiusInMeters])
            ->get();    

        // Convert location_wkt (e.g., "POINT(-71.0589 42.3601)") into separate lat/lng
        $dataPoints = $dataPoints->map(function ($point) {
            preg_match('/POINT\((-?\d+\.\d+) (-?\d+\.\d+)\)/', $point->location_wkt, $matches);
            $point->longitude = $matches[1] ?? null;
            $point->latitude = $matches[2] ?? null;

            //make sure latitute and longitude are numbers not strings
            $point->latitude = floatval($point->latitude);
            $point->longitude = floatval($point->longitude);
            
            unset($point->location_wkt); // Remove WKT field



            //also convert the alcivartech type to a human readable format specifically:
            /*    'Crime': 
                '311 Case': 
                'Building Permit': 
                'Property Violation': 
                'Construction Off Hour':  */
            
            switch ($point->alcivartech_type) {
                case 'crime_data':
                    $point->alcivartech_type = 'Crime';
                    break;
                case 'three_one_one_cases':
                    $point->alcivartech_type = '311 Case';
                    break;
                case 'building_permits':
                    $point->alcivartech_type = 'Building Permit';
                    break;
                case 'property_violations':
                    $point->alcivartech_type = 'Property Violation';
                    break;
                case 'construction_off_hours':
                    $point->alcivartech_type = 'Construction Off Hour';
                    break;
                case 'food_establishment_violations': // Add this case
                    $point->alcivartech_type = 'Food Establishment Violation';
                    break;
                default:
                    $point->alcivartech_type = 'Unknown';
            }

                        //set alcivartech_date field to the right date field based on the type
                        switch ($point->alcivartech_type) {
                            case 'Crime':
                                $point->alcivartech_date = $point->occurred_on_date;
                                break;
                            case '311 Case':
                                $point->alcivartech_date = $point->open_dt;
                                break;
                            case 'Building Permit':
                                $point->alcivartech_date = $point->issued_date;
                                break;
                            case 'Property Violation':
                                $point->alcivartech_date = $point->status_dttm;
                                break;
                            case 'Construction Off Hour':
                                $point->alcivartech_date = $point->start_datetime;
                                break;
                            case 'Food Establishment Violation': // Add this case
                                $point->alcivartech_date = $point->violdttm;
                                break;
                            default:
                                $point->alcivartech_date = null;
                        }


            return $point;
        });
        
        Log::info('Data points fetched.', ['totalDataPointsCount' => $dataPoints->count()]);

        //log some of the data points
        $dataPoints->take(5)->each(function ($dataPoint) {
            Log::info('Data point', [$dataPoint]);
        });

        return response()->json([
            'dataPoints' => $dataPoints,
            'centralLocation' => $centralLocation,
        ]);
    }
}
