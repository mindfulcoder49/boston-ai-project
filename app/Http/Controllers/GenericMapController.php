<?php

namespace App\Http\Controllers;

use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\BuildingPermit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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

        $radius = $request->input('radius', 0.25);
        $days = $request->input('days', 14);

        $boundingBox = $this->getBoundingBox($centralLocation['latitude'], $centralLocation['longitude'], $radius);

        // Fetch data concurrently
        $crimeData = $this->fetchCrimeData($boundingBox, $days);
        $caseData = $this->fetch311CaseData($boundingBox, $days);
        $buildingPermits = $this->fetchBuildingPermits($boundingBox, $days);

        // Combine results
        $dataPoints = array_merge($crimeData, $caseData, $buildingPermits);

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

    private function fetchCrimeData($boundingBox, $days)
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();

        return CrimeData::select('lat', 'long', 'occurred_on_date', 'offense_description')
            ->whereBetween('lat', [$boundingBox['minLat'], $boundingBox['maxLat']])
            ->whereBetween('long', [$boundingBox['minLon'], $boundingBox['maxLon']])
            ->where('occurred_on_date', '>=', $startDate)
            ->get()
            ->map(function ($crime) {
                return [
                    'latitude' => $crime->lat,
                    'longitude' => $crime->long,
                    'date' => $crime->occurred_on_date,
                    'type' => 'Crime',
                    'info' => ['offense_description' => $crime->offense_description],
                ];
            })
            ->toArray();
    }

    private function fetch311CaseData($boundingBox, $days)
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();

        return ThreeOneOneCase::select('latitude', 'longitude', 'open_dt', 'case_title')
            ->whereBetween('latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
            ->whereBetween('longitude', [$boundingBox['minLon'], $boundingBox['maxLon']])
            ->where('open_dt', '>=', $startDate)
            ->get()
            ->map(function ($case) {
                return [
                    'latitude' => $case->latitude,
                    'longitude' => $case->longitude,
                    'date' => $case->open_dt,
                    'type' => '311 Case',
                    'info' => ['case_title' => $case->case_title],
                ];
            })
            ->toArray();
    }

    private function fetchBuildingPermits($boundingBox, $days)
    {
        $startDate = Carbon::now()->subDays($days)->toDateString();

        return BuildingPermit::select('y_latitude', 'x_longitude', 'issued_date', 'description')
            ->whereBetween('y_latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
            ->whereBetween('x_longitude', [$boundingBox['minLon'], $boundingBox['maxLon']])
            ->where('issued_date', '>=', $startDate)
            ->limit(150)
            ->get()
            ->map(function ($permit) {
                return [
                    'latitude' => $permit->y_latitude,
                    'longitude' => $permit->x_longitude,
                    'date' => $permit->issued_date,
                    'type' => 'Building Permit',
                    'info' => ['description' => $permit->description],
                ];
            })
            ->toArray();
    }
}

