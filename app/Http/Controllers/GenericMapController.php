<?php

namespace App\Http\Controllers;

use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\BuildingPermit;
use App\Models\PropertyViolation;
use App\Models\ConstructionOffHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class GenericMapController extends Controller
{
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

        $radius = $request->input('radius', .25);
        $crimeDays = 14;
        $caseDays = 14;
        $permitDays = 14;
        $violationDays = 14;
        $offHourDays = 14;

        $boundingBox = $this->getBoundingBox($centralLocation['latitude'], $centralLocation['longitude'], $radius);

        $crimeData = collect($this->getCrimeDataForBoundingBox($boundingBox, $crimeDays));
        Log::info('Crime data fetched.', ['crimeDataCount' => $crimeData->count()]);

        $caseData = collect($this->getThreeOneOneCaseDataForBoundingBox($boundingBox, $caseDays));
        Log::info('311 case data fetched.', ['caseDataCount' => $caseData->count()]);

        $buildingPermits = collect($this->getBuildingPermitsForBoundingBox($boundingBox, $permitDays));
        Log::info('Building permits data fetched.', ['buildingPermitsCount' => $buildingPermits->count()]);

        $propertyViolations = collect($this->getPropertyViolationsForBoundingBox($boundingBox, $violationDays));
        Log::info('Property violations data fetched.', ['propertyViolationsCount' => $propertyViolations->count()]);

        $offHours = collect($this->getConstructionOffHoursForBoundingBox($boundingBox, $offHourDays));
        Log::info('Construction off hours data fetched.', ['offHoursCount' => $offHours->count()]);

        $dataPoints = $crimeData->merge($caseData)->merge($buildingPermits)->merge($propertyViolations)->merge($offHours);
        Log::info('Data points merged.', ['totalDataPointsCount' => $dataPoints->count()]);




        return response()->json([
            'dataPoints' => $dataPoints,
            'centralLocation' => $centralLocation,
        ]);
    }

    private function getBoundingBox($lat, $lon, $radius)
    {
        $earthRadius = 3959;

        $latDelta = rad2deg($radius / $earthRadius);
        $lonDelta = rad2deg($radius / ($earthRadius * cos(deg2rad($lat))));

        return [
            'minLat' => $lat - $latDelta,
            'maxLat' => $lat + $latDelta,
            'minLon' => $lon - $lonDelta,
            'maxLon' => $lon + $lonDelta,
        ];
    }

    public function getCrimeDataForBoundingBox($boundingBox, $days)
    {
        Log::info('Fetching crime data within bounding box.', ['boundingBox' => $boundingBox, 'days' => $days]);

        $startDate = Carbon::now()->subDays($days)->toDateString();

        $query = CrimeData::whereBetween('lat', [$boundingBox['minLat'], $boundingBox['maxLat']])
                          ->whereBetween('long', [$boundingBox['minLon'], $boundingBox['maxLon']])
                          ->where('occurred_on_date', '>=', $startDate);

        $crimeData = $query->get();

        Log::info('Crime data query executed.', ['rowsFetched' => $crimeData->count()]);

        // Transform data for the map
        return $crimeData->map(function ($crime) {
            // Convert crime object to an array and exclude the latitude, longitude, and date fields
            $info = Arr::except($crime->toArray(), ['lat', 'long', 'occurred_on_date', 'created_at', 'updated_at', 'location', 'offense_code_group']);
        
            return [
                'latitude' => $crime->lat,
                'longitude' => $crime->long,
                'date' => $crime->occurred_on_date,
                'type' => 'Crime',
                'info' => $info,
            ];
        });
    }

    public function getThreeOneOneCaseDataForBoundingBox($boundingBox, $days)
    {
        Log::info('Fetching 311 case data within bounding box.', ['boundingBox' => $boundingBox, 'days' => $days]);

        $startDate = Carbon::now()->subDays($days)->toDateString();

        $query = ThreeOneOneCase::whereBetween('latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
                                ->whereBetween('longitude', [$boundingBox['minLon'], $boundingBox['maxLon']])
                                ->where('open_dt', '>=', $startDate);

        $cases = $query->get();

        Log::info('311 case data query executed.', ['rowsFetched' => $cases->count()]);

        // Transform data for the map
        return $cases->map(function ($case) {
            // Convert case object to an array and exclude the latitude, longitude, and date fields
            $info = Arr::except($case->toArray(), ['latitude', 'longitude', 'open_dt','checksum']);
        
            return [
                'latitude' => $case->latitude,
                'longitude' => $case->longitude,
                'date' => $case->open_dt,
                'type' => '311 Case',
                'info' => $info,
            ];
        });
    }

    public function getBuildingPermitsForBoundingBox($boundingBox, $days)
    {
        Log::info('Fetching building permits within bounding box.', ['boundingBox' => $boundingBox, 'days' => $days]);

        $startDate = Carbon::now()->subDays($days)->toDateString();

        $buildingPermits = BuildingPermit::whereBetween('y_latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
                                         ->whereBetween('x_longitude', [$boundingBox['minLon'], $boundingBox['maxLon']])
                                         ->where('issued_date', '>=', $startDate)
                                         ->get();

        Log::info('Building permits data query executed.', ['rowsFetched' => $buildingPermits->count()]);

        // Transform data for the map
        return $buildingPermits->map(function ($permit) {
            // Convert permit object to an array and exclude the latitude, longitude, and date fields
            $info = Arr::except($permit->toArray(), ['y_latitude', 'x_longitude', 'issued_date', 'applicant']);

            return [
                'latitude' => $permit->y_latitude,
                'longitude' => $permit->x_longitude,
                'date' => $permit->issued_date,
                'type' => 'Building Permit',
                'info' => $info,
            ];
        });

    }

    public function getPropertyViolationsForBoundingBox($boundingBox, $days)
    {
        Log::info('Fetching property violations within bounding box.', ['boundingBox' => $boundingBox, 'days' => $days]);

        $startDate = Carbon::now()->subDays($days)->toDateString();

        $violations = PropertyViolation::whereBetween('latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
                                         ->whereBetween('longitude', [$boundingBox['minLon'], $boundingBox['maxLon']])
                                         ->where('status_dttm', '>=', $startDate)
                                          ->get();

        Log::info('Property violations data query executed.', ['rowsFetched' => $violations->count()]);

        // Transform data for the map
        return $violations->map(function ($violation) {
            // Convert violation object to an array and exclude the latitude, longitude, and date fields
            $info = Arr::except($violation->toArray(), ['latitude', 'longitude', 'status_dttm', 'created_at', 'updated_at']);

            return [
                'latitude' => $violation->latitude,
                'longitude' => $violation->longitude,
                'date' => $violation->status_dttm,
                'type' => 'Property Violation',
                'info' => $info,
            ];
        });

    }


    
    public function getConstructionOffHoursForBoundingBox($boundingBox, $days)
    {
        Log::info('Fetching construction off hours within bounding box.', ['boundingBox' => $boundingBox, 'days' => $days]);

        $startDate = Carbon::now()->subDays($days)->toDateString();

        $offHours = ConstructionOffHour::whereBetween('latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
                                            ->whereBetween('longitude', [$boundingBox['minLon'], $boundingBox['maxLon']])
                                            ->where('start_datetime', '>=', $startDate)->where('start_datetime', '<', Carbon::now())
                                            ->get();

        Log::info('Construction off hours data query executed.', ['rowsFetched' => $offHours->count()]);

        // Transform data for the map
        return $offHours->map(function ($offHour) {
            // Convert offHour object to an array and exclude the latitude, longitude, and date fields
            $info = Arr::except($offHour->toArray(), ['latitude', 'longitude', 'created_at', 'updated_at']);
            //convert start datetime to date
            $start_date = Carbon::parse($offHour->start_datetime)->toDateString();

            return [
                'latitude' => $offHour->latitude,
                'longitude' => $offHour->longitude,
                'date' => $start_date,
                'type' => 'Construction Off Hour',
                'info' => $info,
            ];
        });

    }
}
