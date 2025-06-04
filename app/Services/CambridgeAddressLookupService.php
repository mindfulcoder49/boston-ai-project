<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CambridgeAddressLookupService
{
    private const STREET_ABBREVIATIONS = [
        'MOUNT' => 'MT',
        'SAINT' => 'ST',
        'ACORN PK' => 'ACORN PARK DR',
        'GALILEO GALILEI' => 'GALILEI',
        'CAMBRIDGE CTR' => 'BROADWAY',
        'STREET NORTH'  => 'ST N',
        'HAWTHORNE' => 'HAWTHORN',
    ];

    private const API_CALL_DELAY_MICROSECONDS = 200000; // 0.2 seconds to be safe
    private const GEOCODE_CACHE_FILENAME = 'datasets/cambridge/cambridge_geocoded_addresses.json';

    private Command $command;
    private ?string $googleApiKey;
    private array $addressCache = [];
    private array $intersectionCache = [];
    private array $geocodeCache = [];
    private string $geocodeCachePath;
    private bool $apiKeyValid = false;
    private int $apiCallCount = 0;

    public function __construct(Command $command, ?string $googleApiKey)
    {
        $this->command = $command;
        $this->googleApiKey = $googleApiKey;
        $this->geocodeCachePath = Storage::disk('local')->path(self::GEOCODE_CACHE_FILENAME);

        if ($this->googleApiKey && $this->googleApiKey !== 'YOUR_GOOGLE_GEOCODING_API_KEY_HERE' && !empty($this->googleApiKey)) {
            $this->apiKeyValid = true;
        } else {
            $this->command->warn("Google Geocoding API key is missing or invalid. Geocoding fallback will be disabled.");
        }
        $this->_loadGeocodeCache();
    }

    public function loadDbCaches(): void
    {
        $this->_loadAddressData();
        $this->_loadIntersectionData();
    }

    private function _cleanLocationString(string $rawLocationInput): string
    {
        $location = trim($rawLocationInput);
        // Remove city, state, zip if present (specifically Cambridge, MA)
        $location = preg_replace('/,\s*(Cambridge|CAMBRIDGE)\s*,\s*(MA|Ma|ma)\s*\d{0,5}\s*$/i', '', $location);
        $location = preg_replace('/,\s*(Cambridge|CAMBRIDGE)\s*(MA|Ma|ma)?\s*$/i', '', $location);
        $location = preg_replace('/,\s*Cambridge\s*$/i', '', $location); // Handle case with only city
        return trim($location);
    }

    public function getCoordinatesForLocation(string $rawLocationInput, string $cityContext = 'Cambridge', string $stateContext = 'MA'): array
    {
        $cleanedLocation = $this->_cleanLocationString($rawLocationInput);
        $result = ['latitude' => null, 'longitude' => null, 'street_for_db' => null, 'source' => 'not_found'];

        if (empty($cleanedLocation)) {
            $result['source'] = 'empty_input';
            return $result;
        }

        // Try parsing as intersection first
        if (strpos($cleanedLocation, '&') !== false || stripos($cleanedLocation, ' AND ') !== false) {
            $parsedIntersection = $this->_parseAndNormalizeIntersectionString($cleanedLocation);
            if ($parsedIntersection && $parsedIntersection['combined_norm_sorted']) {
                $result['street_for_db'] = $parsedIntersection['combined_norm_sorted'];
                $coords = $this->_findIntersectionCoordinatesUsingCache($parsedIntersection['combined_norm_sorted']);
                if ($coords) {
                    $result['latitude'] = $coords['latitude'];
                    $result['longitude'] = $coords['longitude'];
                    $result['source'] = 'db_cache_intersection';
                    return $result;
                } elseif ($this->apiKeyValid) {
                    $apiCoords = $this->_geocodeViaApi($result['street_for_db'], $cityContext, $stateContext);
                    if ($apiCoords) {
                        $result['latitude'] = $apiCoords['lat'];
                        $result['longitude'] = $apiCoords['lng'];
                        $result['source'] = 'api_intersection';
                        return $result;
                    }
                }
                $result['source'] = 'not_found_intersection_after_api_attempt_or_disabled';
                return $result; // street_for_db is set, but no coords
            }
        }

        // Try parsing as address
        $parsedAddress = $this->_parseLocationAddress($cleanedLocation);
        if ($parsedAddress && !empty($parsedAddress['name'])) {
            $result['street_for_db'] = $parsedAddress['name'];
            // Use original number part for more accurate reconstruction if needed for API
            $addressForCacheLookup = ['number' => $parsedAddress['number'], 'name' => $parsedAddress['name']];
            $coords = $this->_findAddressCoordinatesUsingCache($addressForCacheLookup['number'], $addressForCacheLookup['name']);

            if ($coords) {
                $result['latitude'] = $coords['latitude'];
                $result['longitude'] = $coords['longitude'];
                $result['source'] = 'db_cache_address';
                return $result;
            } elseif ($this->apiKeyValid) {
                // Use a well-formed address string for API lookup
                $apiAddressString = $parsedAddress['original_number_part'] ? trim("{$parsedAddress['original_number_part']} {$parsedAddress['name']}") : $parsedAddress['name'];
                 if (empty(trim($apiAddressString))) $apiAddressString = $cleanedLocation; // fallback to cleaned location if parsed is empty

                $apiCoords = $this->_geocodeViaApi($apiAddressString, $cityContext, $stateContext);
                if ($apiCoords) {
                    $result['latitude'] = $apiCoords['lat'];
                    $result['longitude'] = $apiCoords['lng'];
                    $result['source'] = 'api_address';
                    return $result;
                }
            }
            $result['source'] = 'not_found_address_after_api_attempt_or_disabled';
            return $result; // street_for_db is set, but no coords
        }

        // Fallback: Could not parse as specific address or intersection, try geocoding the cleaned string directly
        // Normalize the cleanedLocation as a general street name for street_for_db
        $result['street_for_db'] = $this->_normalizeStreetName($cleanedLocation);
        if ($this->apiKeyValid) {
            $apiCoords = $this->_geocodeViaApi($cleanedLocation, $cityContext, $stateContext);
            if ($apiCoords) {
                $result['latitude'] = $apiCoords['lat'];
                $result['longitude'] = $apiCoords['lng'];
                $result['source'] = 'api_fallback_raw';
                return $result;
            }
        }
        $result['source'] = 'not_found_unparsed_after_api_attempt_or_disabled';
        return $result;
    }


    private function _loadAddressData(): void
    {
        $this->command->info("Loading Cambridge address data into service cache...");
        $addresses = DB::table('cambridge_addresses')
            ->select('street_number', 'stname', 'latitude', 'longitude')
            ->whereNotNull('stname')->whereNotNull('latitude')->whereNotNull('longitude')
            ->get();

        foreach ($addresses as $address) {
            $normalizedStName = strtolower($this->_normalizeStreetName($address->stname));
            if (empty($normalizedStName)) continue;
            $streetNumberNumeric = intval(preg_replace('/[^\d].*/', '', $address->street_number));

            $this->addressCache[$normalizedStName][] = [
                'number' => $streetNumberNumeric,
                'original_number' => $address->street_number,
                'latitude' => (float)$address->latitude,
                'longitude' => (float)$address->longitude,
            ];
        }
        foreach ($this->addressCache as $streetName => $addressList) {
            usort($this->addressCache[$streetName], fn ($a, $b) => $a['number'] <=> $b['number']);
        }
        $this->command->info("Service: Loaded " . count($addresses) . " addresses, " . count($this->addressCache) . " unique street names.");
    }

    private function _loadIntersectionData(): void
    {
        $this->command->info("Loading Cambridge intersection data into service cache...");
        $intersections = DB::table('cambridge_intersections')
            ->select('intersection', 'latitude', 'longitude')
            ->whereNotNull('intersection')->whereNotNull('latitude')->whereNotNull('longitude')
            ->get();
        foreach ($intersections as $intersection) {
            $this->intersectionCache[strtolower($intersection->intersection)] = [
                'latitude' => (float)$intersection->latitude,
                'longitude' => (float)$intersection->longitude,
            ];
        }
        $this->command->info("Service: Loaded " . count($intersections) . " intersections.");
    }

    public function _normalizeStreetName(string $streetName): string
    {
        $processedName = strtoupper(trim($streetName));
        $processedName = preg_replace('/\s+/', ' ', $processedName);
        $processedName = preg_replace('/^THE\s+/', '', $processedName);
        foreach (self::STREET_ABBREVIATIONS as $search => $replace) {
            $processedName = preg_replace('/\b' . preg_quote($search, '/') . '\b/i', $replace, $processedName);
        }
        return rtrim(trim($processedName), '.');
    }

    private function _parseLocationAddress(string $locationString): ?array
    {
        if (preg_match('/^(\d+[A-Z]?(-\d+[A-Z]?)?(\s+BLOCK)?)\s+(.*)$/i', $locationString, $matches) ||
            preg_match('/^(BLOCK\s+\d+[A-Z]?(-\d+[A-Z]?)?)\s+(.*)$/i', $locationString, $matches) ||
            preg_match('/^(\d+[A-Z]?(-\d+[A-Z]?)?)\s+(.*)$/i', $locationString, $matches)) {
            $numberPart = trim($matches[1]);
            $rawStreetNamePart = trim(end($matches));
            $numericStreetNumberToMatch = intval(preg_replace('/(\s*BLOCK\s*)|[^\d].*/i', '', $numberPart));
            $normalizedStreetName = $this->_normalizeStreetName($rawStreetNamePart);
            if (!empty($normalizedStreetName)) {
                return ['number' => $numericStreetNumberToMatch, 'name' => $normalizedStreetName, 'original_number_part' => $numberPart];
            }
        }
        return null;
    }

    private function _parseAndNormalizeIntersectionString(string $intersectionQueryString): ?array
    {
        $parts = preg_split('/\s+&\s+|\s+AND\s+/i', $intersectionQueryString, 2, PREG_SPLIT_NO_EMPTY);
        if (count($parts) !== 2) return null;

        $street1Norm = $this->_normalizeStreetName(trim($parts[0]));
        $street2Norm = $this->_normalizeStreetName(trim($parts[1]));

        if (empty($street1Norm) || empty($street2Norm)) return null;

        $sortedStreets = [$street1Norm, $street2Norm];
        sort($sortedStreets, SORT_STRING | SORT_FLAG_CASE);
        return [
            'street1_norm' => $street1Norm,
            'street2_norm' => $street2Norm,
            'combined_norm_sorted' => $sortedStreets[0] . ' & ' . $sortedStreets[1]
        ];
    }

    private function _findIntersectionCoordinatesUsingCache(string $normalizedIntersectionKey): ?array
    {
        $lookupKey = strtolower($normalizedIntersectionKey);
        if (isset($this->intersectionCache[$lookupKey])) {
            //$this->command->comment("Service Cache Hit (Intersection): '{$normalizedIntersectionKey}'");
            return $this->intersectionCache[$lookupKey];
        }

        // Fallback to individual streets in intersection from address cache (lowest address)
        $parts = explode(' & ', $normalizedIntersectionKey);
        if (count($parts) === 2) {
            foreach ($parts as $streetName) {
                $streetCacheKey = strtolower(trim($streetName));
                if (!empty($this->addressCache[$streetCacheKey])) {
                    //$this->command->comment("Service Fallback (Intersection to Address Cache): Used lowest address on '{$streetName}' for '{$normalizedIntersectionKey}'.");
                    return $this->addressCache[$streetCacheKey][0]; // First address (lowest number)
                }
            }
        }
        return null;
    }

    private function _findAddressCoordinatesUsingCache(int $targetNumber, string $normalizedStreetName): ?array
    {
        $streetCacheKey = strtolower($normalizedStreetName);
        $cachedAddressesOnStreet = $this->addressCache[$streetCacheKey] ?? [];

        if (empty($cachedAddressesOnStreet)) return null;

        $closestMatch = null;
        $minDifference = PHP_INT_MAX;

        foreach ($cachedAddressesOnStreet as $cachedAddr) {
            if ($cachedAddr['number'] >= 0) { // Allow 0 for block addresses
                $difference = abs($targetNumber - $cachedAddr['number']);
                if ($difference < $minDifference) {
                    $minDifference = $difference;
                    $closestMatch = $cachedAddr;
                } elseif ($difference === $minDifference && $closestMatch && $cachedAddr['number'] < $closestMatch['number']) {
                    $closestMatch = $cachedAddr; // Prefer lower number in case of tie
                }
            }
        }
        
        if ($closestMatch) {
            // $this->command->comment("Service Cache Hit (Address): Closest match for '{$targetNumber} {$normalizedStreetName}' is '{$closestMatch['original_number']} {$normalizedStreetName}'. Diff: {$minDifference}");
            return ['latitude' => $closestMatch['latitude'], 'longitude' => $closestMatch['longitude']];
        } elseif (!empty($cachedAddressesOnStreet)) { // Fallback to lowest address on street if no good number match
            // $this->command->comment("Service Fallback (Address Cache): No direct number match for '{$targetNumber} {$normalizedStreetName}', using lowest address on street.");
            return $this->addressCache[$streetCacheKey][0];
        }
        return null;
    }

    private function _geocodeViaApi(string $addressToGeocode, string $city, string $state): ?array
    {
        if (!$this->apiKeyValid || empty(trim($addressToGeocode))) {
            return null;
        }

        $fullAddressQuery = Str::squish("{$addressToGeocode}, {$city}, {$state}");
        $cacheKey = strtolower($fullAddressQuery);

        if (array_key_exists($cacheKey, $this->geocodeCache)) {
            //$this->command->comment("Service Geocode API Cache Hit: '{$fullAddressQuery}'");
            return $this->geocodeCache[$cacheKey];
        }
        
        // Ensure directory exists for geocode cache
        $cacheDir = dirname($this->geocodeCachePath);
        if (!File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0775, true, true);
        }

        if ($this->apiCallCount > 0) { // Don't sleep before the very first API call of the service's lifetime
            usleep(self::API_CALL_DELAY_MICROSECONDS);
        }
        $this->apiCallCount++;
        $this->command->comment("Service Geocode API Call #{$this->apiCallCount}: Querying for '{$fullAddressQuery}'");

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $fullAddressQuery,
                'key' => $this->googleApiKey
            ]);

            if (!$response->successful()) {
                Log::error("Geocoding API request failed for '{$fullAddressQuery}'. Status: " . $response->status() . " Body: " . $response->body());
                $this->geocodeCache[$cacheKey] = null; // Cache failure
                $this->_saveGeocodeCache();
                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? 'ERROR') === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                $coordinates = ['lat' => $location['lat'], 'lng' => $location['lng']];
                $this->geocodeCache[$cacheKey] = $coordinates;
                $this->_saveGeocodeCache();
                return $coordinates;
            } else {
                Log::warning("Geocoding API Error for '{$fullAddressQuery}': " . ($data['status'] ?? 'UNKNOWN_STATUS') . " - " . ($data['error_message'] ?? 'No error message.'));
                $this->geocodeCache[$cacheKey] = null; // Cache error response
                $this->_saveGeocodeCache();
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Exception during geocoding for '{$fullAddressQuery}': " . $e->getMessage());
            $this->geocodeCache[$cacheKey] = null; // Cache exception
            $this->_saveGeocodeCache();
            return null;
        }
    }

    private function _loadGeocodeCache(): void
    {
        if (File::exists($this->geocodeCachePath)) {
            $jsonContents = File::get($this->geocodeCachePath);
            $decodedData = json_decode($jsonContents, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->geocodeCache = $decodedData;
                $this->command->info("Service: Loaded " . count($this->geocodeCache) . " items from geocode cache: {$this->geocodeCachePath}");
            } else {
                $this->command->warn("Service: Could not decode geocode cache file. Starting with an empty cache. Error: " . json_last_error_msg());
                $this->geocodeCache = [];
            }
        } else {
            $this->command->info("Service: Geocode cache file not found. Starting with an empty cache. Path: {$this->geocodeCachePath}");
            $this->geocodeCache = [];
        }
    }

    private function _saveGeocodeCache(): void
    {
        try {
            File::put($this->geocodeCachePath, json_encode($this->geocodeCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            $this->command->error("Service: Failed to save geocode cache to {$this->geocodeCachePath}. Error: " . $e->getMessage());
            Log::error("Service: Failed to save geocode cache. Error: " . $e->getMessage());
        }
    }

    // Call this at the end of a seeder run if you want to ensure the cache is written,
    // though it's saved after each new API result.
    public function finalSaveGeocodeCache(): void
    {
        $this->_saveGeocodeCache();
        $this->command->info("Service: Final geocode cache save attempted. Total API calls during this run: {$this->apiCallCount}");
    }
}
