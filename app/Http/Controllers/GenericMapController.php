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
        $user = Auth::user();
        $currentPlan = null;
        $daysToFilter = 7; // Default for unauthenticated users
        $targetTable = 'data_points'; // Default table

        if ($user) {
            $daysToFilter = 14; // Authenticated free user
            if ($user->subscribed('default')) {
                $subscription = $user->subscription('default');
                if ($subscription) {
                    if ($subscription->stripe_price === config('stripe.prices.basic_plan')) {
                        $currentPlan = 'basic';
                        $daysToFilter = 21; // Basic plan gets ~6 months from data_points
                        $targetTable = 'data_points';
                    } elseif ($subscription->stripe_price === config('stripe.prices.pro_plan')) {
                        $currentPlan = 'pro';
                        $targetTable = 'data_points'; // Pro plan uses all_time_data_points
                        // No date filtering for pro plan on all_time_data_points, or a very long period if needed for performance.
                        // For true "all time", $daysToFilter is not strictly applied to the query on this table.
                        // We can set it to a very large number or null to signify no filtering.
                        $daysToFilter = 31; // Or a very large number like 365 * 20 (20 years)
                    }
                }
            }
        }

        $cutoffDateTime = $daysToFilter ? Carbon::now()->subDays($daysToFilter)->startOfDay() : null;

        Log::info('User authentication status for data filtering.', [
            'authenticated' => (bool)$user,
            'currentPlan' => $currentPlan,
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
                $targetTable.'.id as data_point_id', // Qualify column names with table name
                DB::raw("ST_AsText({$targetTable}.location) as location_wkt"),
                $targetTable.'.type as alcivartech_type',

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

                // Food Inspections
                'food_inspections.id as food_inspection_id',
                'food_inspections.*'
            )
            ->leftJoin('crime_data', $targetTable.'.crime_data_id', '=', 'crime_data.id')
            ->leftJoin('three_one_one_cases', $targetTable.'.three_one_one_case_id', '=', 'three_one_one_cases.id')
            ->leftJoin('property_violations', $targetTable.'.property_violation_id', '=', 'property_violations.id')
            ->leftJoin('construction_off_hours', $targetTable.'.construction_off_hour_id', '=', 'construction_off_hours.id')
            ->leftJoin('building_permits', $targetTable.'.building_permit_id', '=', 'building_permits.id')
            ->leftJoin('food_inspections', $targetTable.'.food_inspection_id', '=', 'food_inspections.id')
            ->whereRaw("ST_Distance_Sphere({$targetTable}.location, ST_GeomFromText(?)) <= ?", [$wktPoint, $radiusInMeters]);

        if ($cutoffDateTime && $targetTable === 'data_points') { // Apply date filtering only if cutoffDateTime is set and it's the regular data_points table
            $query->where($targetTable.'.alcivartech_date', '>=', $cutoffDateTime);
        }
        // For all_time_data_points, we don't apply the $cutoffDateTime unless a specific very long-term cutoff is desired for performance.

        $dataPoints = $query->get();    

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
                case 'food_inspections': // Add this case
                    $point->alcivartech_type = 'Food Inspection';
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
                            case 'Food Inspection': // Add this case
                                //use violation date as the date if it exists
                                if ($point->violdttm) {
                                    $point->alcivartech_date = $point->violdttm;
                                } else {
                                    // Otherwise, use the result date
                                    $point->alcivartech_date = $point->resultdttm;
                                }
                                break;
                            default:
                                $point->alcivartech_date = null;
                        }

            // If user is not logged in, replace food inspection data fields
            if (!Auth::check() && $point->alcivartech_type === 'Food Inspection') {
                $restrictedMessage = "Log In to See Food Inspection Information";
                // Iterate over a copy of keys to avoid issues if properties are unset or changed during iteration
                $pointProperties = (array)$point;
                foreach ($pointProperties as $key => $value) {
                    // hash the licenseno to prevent scraping
                    // remove latitude and longitude
                    if ($key === 'lat' || $key === 'lng' || $key === 'latitude' || $key === 'longitude' || $key === 'location') {
                        unset($point->$key);
                    }
                    if ($key === 'licenseno') {
                        $point->licenseno = md5($point->licenseno);
                    }
                    if (!empty($point->$key) && $key !== 'alcivartech_type' && $key !== 'alcivartech_date'
                        && $key !== 'licenseno' && $key !== 'violdesc' && $key !== 'viol_level' && $key !== 'comments') {
                        $point->$key = $restrictedMessage;
                    }
                }
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
