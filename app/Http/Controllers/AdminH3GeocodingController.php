<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\H3LocationName;

class AdminH3GeocodingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $adminEmail = config('admin.email');
            if (empty($adminEmail)) abort(403, 'Admin email not configured.');
            if (!Auth::check() || Auth::user()->email !== $adminEmail) abort(403, 'Unauthorized.');
            return $next($request);
        });
    }

    public function index()
    {
        // All unique (h3_index, h3_resolution, model_class) combos across materialized findings
        $rows = DB::table('h3_hotspot_findings')
            ->select('h3_index', 'h3_resolution', 'model_class')
            ->distinct()
            ->get();

        $existingNames = H3LocationName::pluck('location_name', 'h3_index');

        $seen     = [];
        $hexagons = [];

        foreach ($rows as $row) {
            if (isset($seen[$row->h3_index])) continue;
            $seen[$row->h3_index] = true;

            $hexagons[] = [
                'h3_index'      => $row->h3_index,
                'h3_resolution' => $row->h3_resolution,
                'city'          => $this->inferCity($row->model_class),
                'location_name' => $existingNames[$row->h3_index] ?? null,
            ];
        }

        // Sort by city, then resolution
        usort($hexagons, fn($a, $b) =>
            $a['city'] !== $b['city']
                ? strcmp($a['city'], $b['city'])
                : $a['h3_resolution'] - $b['h3_resolution']
        );

        return Inertia::render('Admin/H3Geocoding', [
            'hexagons' => $hexagons,
        ]);
    }

    public function geocode(Request $request)
    {
        $request->validate([
            'hexagons'                => 'required|array|max:50',
            'hexagons.*.h3_index'     => 'required|string|max:20',
            'hexagons.*.lat'          => 'required|numeric',
            'hexagons.*.lng'          => 'required|numeric',
            'hexagons.*.resolution'   => 'required|integer|min:1|max:15',
        ]);

        $apiKey = config('services.google_places.api_key');
        if (!$apiKey) {
            return response()->json(['error' => 'Google API key not configured.'], 500);
        }

        $results = [];

        foreach ($request->input('hexagons') as $hex) {
            try {
                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => $hex['lat'] . ',' . $hex['lng'],
                    'key'    => $apiKey,
                ]);

                $data = $response->json();

                if (($data['status'] ?? '') === 'OK' && !empty($data['results'])) {
                    $name = $this->extractLocationName($data, (int) $hex['resolution']);

                    H3LocationName::updateOrCreate(
                        ['h3_index' => $hex['h3_index']],
                        [
                            'h3_resolution'        => $hex['resolution'],
                            'location_name'        => $name,
                            'geocoded_at'          => now(),
                            'raw_geocode_response' => $data,
                        ]
                    );

                    $results[] = ['h3_index' => $hex['h3_index'], 'location_name' => $name, 'status' => 'ok', 'raw_response' => $data];
                } else {
                    Log::info("H3 geocoding: no result for {$hex['h3_index']} ({$hex['lat']},{$hex['lng']}). Status: " . ($data['status'] ?? 'unknown'));
                    $results[] = ['h3_index' => $hex['h3_index'], 'location_name' => null, 'status' => 'no_result', 'raw_response' => $data];
                }
            } catch (\Exception $e) {
                Log::warning("H3 geocoding failed for {$hex['h3_index']}: " . $e->getMessage());
                $results[] = ['h3_index' => $hex['h3_index'], 'location_name' => null, 'status' => 'error', 'raw_response' => null];
            }
        }

        Cache::forget('h3_location_names_map');

        return response()->json(['results' => $results]);
    }

    // ---------- helpers ----------

    private function inferCity(string $modelClass): string
    {
        foreach (config('cities.cities', []) as $cityConfig) {
            if (in_array($modelClass, $cityConfig['models'] ?? [])) {
                return $cityConfig['name'];
            }
        }
        $default = config('cities.default', 'boston');
        return config("cities.cities.{$default}.name", 'Boston');
    }

    /**
     * Build a human-readable area label from the full geocoding API response.
     *
     * Format: "~0.3 mi around 26 Robeson St in Jamaica Plain Neighborhood, Boston, MA"
     */
    private function extractLocationName(array $apiData, int $resolution): string
    {
        $radiusLabels = [
            4  => '~10 mi',
            5  => '~5 mi',
            6  => '~2 mi',
            7  => '~0.75 mi',
            8  => '~0.3 mi',
            9  => '~0.1 mi',
            10 => '~0.05 mi',
        ];

        $radius = $radiusLabels[$resolution]
            ?? ($resolution < 4 ? '~20 mi' : '~0.02 mi');

        $firstResult = $apiData['results'][0] ?? null;
        if (!$firstResult) return 'Unknown location';

        $byLong  = [];
        $byShort = [];
        foreach ($firstResult['address_components'] ?? [] as $c) {
            foreach ($c['types'] as $type) {
                $byLong[$type]  ??= $c['long_name'];
                $byShort[$type] ??= $c['short_name'];
            }
        }

        $streetNum    = $byLong['street_number'] ?? null;
        $streetName   = $byShort['route']        ?? null;
        $neighborhood = $byLong['neighborhood']
            ?? $byLong['sublocality_level_1']
            ?? $byLong['sublocality']
            ?? null;
        $locality = $byLong['locality'] ?? $byLong['postal_town'] ?? null;
        $state    = $byShort['administrative_area_level_1'] ?? null;
        $county   = $byLong['administrative_area_level_2']  ?? null;

        $street = ($streetNum && $streetName) ? "{$streetNum} {$streetName}" : null;

        if ($neighborhood && $locality && $state) {
            $neighborhoodLabel = "{$neighborhood} Neighborhood, {$locality}, {$state}";
            $location = $street ? "{$street} in {$neighborhoodLabel}" : $neighborhoodLabel;
            return "{$radius} around {$location}";
        }

        if ($locality && $state) {
            $location = $street ? "{$street}, {$locality}, {$state}" : "{$locality}, {$state}";
            return "{$radius} around {$location}";
        }

        if ($county && $state) return "{$radius} around {$county}, {$state}";

        return "{$radius} around " . ($byLong['administrative_area_level_1'] ?? 'Unknown location');
    }
}
