<?php

namespace App\Http\Controllers;

use App\Models\TrashScheduleByAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class TrashScheduleByAddressController extends Controller
{
    /**
     * Find closest matching addresses by address string from Boston and Cambridge datasets.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|min:1',
        ]);

        $address = $validated['address'];
        $searchTerm = '%' . $address . '%';

        // Boston: City is 'Boston'. Select 'zip_code' as 'zip'.
        $bostonQuery = TrashScheduleByAddress::where('full_address', 'LIKE', $searchTerm)
            ->select('full_address', 'x_coord', 'y_coord', DB::raw("'Boston' as city"), 'zip_code as zip', DB::raw("'boston_trash_schedules' as source_dataset"));

        // Cambridge Addresses: City is 'Cambridge'. Select 'zip_code' as 'zip'.
        $cambridgeAddressesQuery = DB::table('cambridge_addresses')
            ->where('full_addr', 'LIKE', $searchTerm)
            ->select('full_addr as full_address', 'longitude as x_coord', 'latitude as y_coord', DB::raw("'Cambridge' as city"), 'zip_code as zip', DB::raw("'cambridge_addresses' as source_dataset"));

        // Cambridge Intersections: City is 'Cambridge'. Select 'zip_code' as 'zip'.
        $cambridgeIntersectionsQuery = DB::table('cambridge_intersections')
            ->where('intersection', 'LIKE', $searchTerm)
            ->select('intersection as full_address', 'longitude as x_coord', 'latitude as y_coord', DB::raw("'Cambridge' as city"), 'zip_code as zip', DB::raw("'cambridge_intersections' as source_dataset"));
        
        $results = $bostonQuery
            ->union($cambridgeAddressesQuery)
            ->union($cambridgeIntersectionsQuery)
            ->orderBy('full_address') 
            ->limit(15) 
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }

    /**
     * Get address suggestions from Google Places Autocomplete API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleAutocomplete(Request $request)
    {
        $validated = $request->validate([
            'input' => 'required|string|min:1',
        ]);

        $apiKey = Config::get('services.google_places.api_key');
        if (!$apiKey) {
            Log::error('Google Places API key is not configured.');
            return response()->json(['error' => 'Service configuration error.'], 500);
        }

        $locationBias = [
            'circle' => [
                'center' => [
                    'latitude' => 42.365,
                    'longitude' => -71.085,
                ],
                'radius' => 15000.0 
            ]
        ];

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://places.googleapis.com/v1/places:autocomplete', [
                'input' => $validated['input'],
                // 'locationRestriction' => $locationBias,
                // 'includedPrimaryTypes' => ['address'], // Removed: 'address' is not a valid primary type for this parameter.
                                                        // The API returns addresses by default.
                'languageCode' => 'en', 
                'regionCode' => 'US',   
            ]);

            if (!$response->successful()) {
                Log::error('Google Places Autocomplete API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['error' => 'Failed to fetch suggestions.'], $response->status());
            }

            $suggestions = $response->json()['suggestions'] ?? [];
            $placePredictions = array_map(function ($suggestion) {
                return $suggestion['placePrediction']['text']['text'] ?? null;
            }, array_filter($suggestions, fn($s) => isset($s['placePrediction'])));
            
            return response()->json(['suggestions' => array_filter($placePredictions)]);

        } catch (\Exception $e) {
            Log::error('Exception calling Google Places Autocomplete API: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Geocode an address string using Google Geocoding API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function geocodeGooglePlace(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|min:1',
        ]);

        $apiKey = Config::get('services.google_places.api_key'); // Can be the same key if enabled for Geocoding API
        if (!$apiKey) {
            Log::error('Google Geocoding API key is not configured.');
            return response()->json(['error' => 'Service configuration error.'], 500);
        }

        // Define a bounding box for Boston/Cambridge to bias geocoding results
        // SW: (lat: 42.227, lng: -71.191), NE: (lat: 42.42, lng: -70.98)
        $bounds = '42.227,-71.191|42.42,-70.98';

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $validated['address'],
                'key' => $apiKey,
                // 'bounds' => $bounds, // Bias results to this bounding box
                // 'components' => 'country:US', // Further restrict if needed
            ]);

            if (!$response->successful()) {
                Log::error('Google Geocoding API request failed.', [
                    'address' => $validated['address'],
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['error' => 'Failed to geocode address.'], $response->status());
            }

            $data = $response->json();

            if (($data['status'] ?? 'ERROR') === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                $formattedAddress = $data['results'][0]['formatted_address'];
                return response()->json([
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'address' => $formattedAddress,
                ]);
            } else {
                Log::warning('Google Geocoding API Error for address.', [
                    'address' => $validated['address'],
                    'status' => $data['status'] ?? 'UNKNOWN_STATUS',
                    'error_message' => $data['error_message'] ?? 'No error message.'
                ]);
                return response()->json(['error' => 'Address not found or geocoding failed.'], 404);
            }
        } catch (\Exception $e) {
            Log::error('Exception during Google geocoding: ' . $e->getMessage(), ['address' => $validated['address']]);
            return response()->json(['error' => 'An unexpected error occurred during geocoding.'], 500);
        }
    }
}
