<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
//add config
use Illuminate\Support\Facades\Config; // For accessing config values

class ProcessEverettDataCommand extends Command
{
    protected $signature = 'everett:process-data {api : The Google API to use for geocoding (places|geolocation)}';
    protected $description = 'Process Everett trash data and geocode addresses using a specified Google API.';

    private const API_CALL_DELAY_MICROSECONDS = 100000; // 0.1 seconds

    public function handle()
    {
        $api = $this->argument('api');

        if (!in_array($api, ['places', 'geolocation'])) {
            $this->error("Invalid API specified. Please use 'places' or 'geolocation'.");
            return Command::FAILURE;
        }

        $this->info("Starting data processing for Everett using the '{$api}' API.");

        $everettConfig = config('everett_datasets');
        if (!$everettConfig || !isset($everettConfig['markdown_output_directory'])) {
            $this->error("Everett datasets configuration for 'markdown_output_directory' is missing.");
            return 1;
        }

        $markdownDirName = $everettConfig['markdown_output_directory'];
        $baseStoragePath = storage_path('app/datasets/everett');
        $markdownPath = $baseStoragePath . '/' . trim($markdownDirName, '/');
        
        $outputJsonFilename = 'everett_police_calls_and_arrest_data.json';
        $outputJsonPath = $baseStoragePath . '/' . $outputJsonFilename;
        
        $geocodeCacheFilename = 'geocoded_addresses.json';
        $geocodeCachePath = $baseStoragePath . '/' . $geocodeCacheFilename;

        if (!File::isDirectory($markdownPath)) {
            $this->error("Markdown directory not found: {$markdownPath}");
            return 1;
        }

        // Ensure output directories exist
        if (!File::isDirectory($baseStoragePath)) {
            File::makeDirectory($baseStoragePath, 0775, true, true);
        }

        // Part 1: Parse Markdown files and combine data
        $this->info("Processing Markdown logs from: {$markdownPath}");
        $allData = $this->processMarkdownLogs($markdownPath);

        if (empty($allData)) {
            $this->warn("No data parsed from Markdown logs.");
        } else {
            $this->info("Successfully parsed " . count($allData) . " unique case entries.");
        }
        
        // Save combined data
        File::put($outputJsonPath, json_encode(array_values($allData), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Combined data saved to: {$outputJsonPath}");

        // Part 2: Geocode addresses
        $this->info("Starting geocoding process...");
        $apiKey = Config::get('services.google_geocoding.api_key');
        if (!$apiKey || $apiKey === 'YOUR_GOOGLE_GEOCODING_API_KEY_HERE') {
            $this->error("GOOGLE_GEOCODING_API_KEY is not set in .env file or is set to placeholder.");
            return 1;
        }

        $policeData = json_decode(File::get($outputJsonPath), true);
        if (empty($policeData)) {
            $this->warn("No data loaded from {$outputJsonPath} for geocoding. Exiting geocoding step.");
            $this->info("Everett data processing complete (parsing only).");
            return 0;
        }

        $geocodedCache = File::exists($geocodeCachePath) ? json_decode(File::get($geocodeCachePath), true) : [];
        if (json_last_error() !== JSON_ERROR_NONE && File::exists($geocodeCachePath)) {
            $this->warn("Could not decode existing geocode cache. Starting with an empty cache.");
            $geocodedCache = [];
        }


        $addressesToProcessMap = $this->mapOriginalToGeocodableAddresses($policeData);
        $this->info("Found " . count($addressesToProcessMap) . " unique original incident addresses to process for geocoding.");

        $newlyGeocodedCount = 0;
        $failedGeocodingCount = 0;
        $processedAddressesCount = 0;
        $totalAddressesToQuery = 0;

        // Pre-calculate how many addresses actually need API calls
        foreach ($addressesToProcessMap as $originalAddrKey => $addrToGeocode) {
            if ($addrToGeocode !== null && !array_key_exists($originalAddrKey, $geocodedCache)) {
                $totalAddressesToQuery++;
            }
        }
        $this->info("Need to query API for {$totalAddressesToQuery} new addresses.");
        $currentQueryCount = 0;

        foreach ($addressesToProcessMap as $originalAddrKey => $addrToGeocode) {
            $processedAddressesCount++;
            $this->output->write("\rProcessing address " . $processedAddressesCount . "/" . count($addressesToProcessMap) . ": \"" . Str::limit($originalAddrKey, 50) . "\"");

            if ($addrToGeocode === null) {
                if (!array_key_exists($originalAddrKey, $geocodedCache)) {
                    $geocodedCache[$originalAddrKey] = null; // Cache that it's unprocessable
                    // No need to save cache here, will be saved if an API call is made or at the end.
                }
                continue;
            }

            if (array_key_exists($originalAddrKey, $geocodedCache)) {
                continue;
            }
            
            $currentQueryCount++;
            $this->output->write("\rProcessing address " . $processedAddressesCount . "/" . count($addressesToProcessMap) . " (API Call " . $currentQueryCount . "/" . $totalAddressesToQuery . "): Querying for \"" . Str::limit($addrToGeocode, 40) . "\"");


            $coordinates = $api === 'places' ? $this->geocodeWithPlaces($addrToGeocode) : $this->geocodeWithGeolocation($addrToGeocode);
            $geocodedCache[$originalAddrKey] = $coordinates;
            $newlyGeocodedCount++;

            if ($coordinates === null) {
                $failedGeocodingCount++;
            }

            File::put($geocodeCachePath, json_encode($geocodedCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            if ($currentQueryCount < $totalAddressesToQuery) { // Avoid sleep after the last item
                 usleep(self::API_CALL_DELAY_MICROSECONDS);
            }
        }
        $this->output->write("\n"); // New line after progress bar

        $this->info("Geocoding Complete.");
        $this->line("Total unique original incident addresses considered: " . count($addressesToProcessMap));
        $this->line("Addresses newly geocoded (or attempted): {$newlyGeocodedCount}");
        $this->line("Addresses that failed geocoding in this run: {$failedGeocodingCount}");
        $this->line("Total addresses in cache: " . count($geocodedCache));
        $this->info("Geocoded data cache saved to: {$geocodeCachePath}");
        $this->info("Everett data processing complete.");
        return 0;
    }

    private function processMarkdownLogs(string $markdownDir): array
    {
        $allData = [];

        // Process arrest logs
        $arrestLogFiles = File::glob($markdownDir . "/arr_log_*.md");
        foreach ($arrestLogFiles as $filepath) {
            $this->line("Processing arrest log: {$filepath}");
            $arrests = $this->parseArrestLogFile($filepath);
            foreach ($arrests as $arrest) {
                $caseNum = $arrest['case_number'];
                if (!isset($allData[$caseNum])) {
                    $allData[$caseNum] = ["case_number" => $caseNum, "arrest_details" => null, "incident_details" => null];
                }
                $allData[$caseNum]['arrest_details'] = [
                    'name' => $arrest['name'],
                    'address' => $arrest['address'],
                    'age' => $arrest['age'],
                    'date' => $arrest['date'],
                    'charges' => $arrest['charges']
                ];
            }
        }

        // Process call logs
        $callLogFiles = File::glob($markdownDir . "/call_log_*.md");
        foreach ($callLogFiles as $filepath) {
            $this->line("Processing call log: {$filepath}");
            $calls = $this->parseCallLogFile($filepath);
            foreach ($calls as $call) {
                $caseNum = $call['case_number'] ?? null;
                if (!$caseNum) continue;
                if (!isset($allData[$caseNum])) {
                    $allData[$caseNum] = ["case_number" => $caseNum, "arrest_details" => null, "incident_details" => null];
                }
                $allData[$caseNum]['incident_details'] = [
                    'log_file_date' => $call['log_file_date'],
                    'entry_date' => $call['entry_date'],
                    'time' => $call['time'],
                    'type' => $call['type'],
                    'address' => $call['address'],
                    'description' => $call['description']
                ];
            }
        }
        return $allData;
    }

    private function parseArrestLogFile(string $filepath): array
    {
        $arrests = [];
        $lines = array_map('trim', File::lines($filepath)->toArray());

        $namePattern = "/^([A-Z][A-Z\s,'.-]+,\s*[A-Z][A-Z\s,'.-]+(?:\s+[A-Z\.\s-]+)?)$/";
        $ageCasePattern = "/^\s*age:\s*(\d+)\s*arrest date:\s*(\d{2}\/\d{2}\/\d{4})\s*case:\s*(\d{6})\s*$/";
        $skipPatterns = "/ARREST LOG|From:.*To:|^\s*\f\s*$|^$/";

        $i = 0;
        $numLines = count($lines);
        while ($i < $numLines) {
            $line = $lines[$i];
            if (preg_match($skipPatterns, $line) || empty($line)) {
                $i++;
                continue;
            }

            if (preg_match($namePattern, $line, $nameMatch)) {
                if ($i + 2 < $numLines) {
                    $potentialName = trim($nameMatch[1]);
                    $potentialAddress = trim($lines[$i + 1]);
                    $ageCaseLine = trim($lines[$i + 2]);

                    if (preg_match($ageCasePattern, $ageCaseLine, $ageCaseMatch)) {
                        $currentArrest = [
                            'name' => $potentialName,
                            'address' => $potentialAddress,
                            'age' => $ageCaseMatch[1],
                            'date' => $ageCaseMatch[2],
                            'case_number' => $ageCaseMatch[3],
                            'charges' => []
                        ];
                        $i += 3;
                        while ($i < $numLines) {
                            $chargeLine = trim($lines[$i]);
                            if (empty($chargeLine) || preg_match($skipPatterns, $chargeLine) || preg_match($namePattern, $chargeLine)) {
                                break;
                            }
                            $currentArrest['charges'][] = $chargeLine;
                            $i++;
                        }
                        $arrests[] = $currentArrest;
                        continue;
                    }
                }
            }
            $i++;
        }
        return $arrests;
    }

    private function parseCallLogFile(string $filepath): array
    {
        $calls = [];
        $lines = File::lines($filepath)->toArray(); // Keep original lines with trailing spaces for some patterns

        $fileLogDate = null;
        $currentCallData = [];
        $state = 'EXPECT_FILE_DATE';

        $fileDatePattern = "/^\s*DAILY LOG\s+(\d{2}\/\d{2}\/\d{4})\s*$/";
        $entryHeaderPattern = "/^\s*\*\*\*\s+[A-Z]{3}\s+(\d{2}\/\d{2}\/\d{4})\s+(.+?)\s+\*{10}\s*$/";
        $timeLocPattern = "/^\s*(\d{2}:\d{2})\s*\*\s*(.+?)(?:\s+EVE)?\s*$/"; // Match on line with internal spaces
        $caseDescPattern = "/^\s*(\d{6})\s*\*\s*(.+)$/"; // Match on line with internal spaces
        $pageFeedPattern = "/^\f\s*$/";

        foreach ($lines as $lineContent) {
            $line = rtrim($lineContent, "\n\r"); // Remove newline, keep trailing spaces for pattern matching
            $strippedLine = trim($line);

            if (empty($strippedLine) || preg_match($pageFeedPattern, $line)) {
                continue;
            }

            switch ($state) {
                case 'EXPECT_FILE_DATE':
                    if (preg_match($fileDatePattern, $strippedLine, $match)) {
                        $fileLogDate = $match[1];
                        $state = 'EXPECT_ENTRY_HEADER';
                    }
                    break;
                case 'EXPECT_ENTRY_HEADER':
                    if (preg_match($entryHeaderPattern, $strippedLine, $match)) {
                        $currentCallData = [
                            'log_file_date' => $fileLogDate,
                            'entry_date' => $match[1],
                            'type' => trim($match[2])
                        ];
                        $state = 'EXPECT_TIME_LOC';
                    }
                    break;
                case 'EXPECT_TIME_LOC':
                    if (preg_match($timeLocPattern, $line, $match)) { // Use $line (not $strippedLine)
                        $currentCallData['time'] = $match[1];
                        $currentCallData['address'] = trim($match[2]);
                        $state = 'EXPECT_CASE_DESC';
                    } else {
                        $currentCallData = [];
                        $state = 'EXPECT_ENTRY_HEADER'; // Reset
                    }
                    break;
                case 'EXPECT_CASE_DESC':
                    if (preg_match($caseDescPattern, $line, $match)) { // Use $line
                        $currentCallData['case_number'] = $match[1];
                        $currentCallData['description'] = trim($match[2]);
                        $calls[] = $currentCallData;
                        $currentCallData = [];
                        $state = 'EXPECT_ENTRY_HEADER';
                    } else {
                        $currentCallData = [];
                        $state = 'EXPECT_ENTRY_HEADER'; // Reset
                    }
                    break;
            }
        }
        return $calls;
    }
    
    private function mapOriginalToGeocodableAddresses(array $dataRecords): array
    {
        $addressMap = [];
        foreach ($dataRecords as $record) {
            $originalAddress = $record['incident_details']['address'] ?? null;

            if (!$originalAddress || !is_string($originalAddress)) {
                continue;
            }
            $cleanedOriginalAddress = trim($originalAddress);
            if (empty($cleanedOriginalAddress)) {
                continue;
            }

            // Split by "Apt:" (case-insensitive) and take the first part
            $addressParts = preg_split('/Apt:/i', $cleanedOriginalAddress);
            $streetAddressPart = trim($addressParts[0]);

            if (empty($streetAddressPart)) {
                 if (!array_key_exists($cleanedOriginalAddress, $addressMap)) {
                    $addressMap[$cleanedOriginalAddress] = null; // Mark as not geocodable
                }
                continue;
            }
            $geocodableAddress = "{$streetAddressPart}, Everett, MA 02149";
            if (!array_key_exists($cleanedOriginalAddress, $addressMap)) {
                $addressMap[$cleanedOriginalAddress] = $geocodableAddress;
            }
        }
        return $addressMap;
    }

    private function cleanAddressForGeocoding(string $address): string
    {
        // Rule 1: Remove leading zeroes from address numbers.
        // e.g., "0 BROADWAY ST" -> "BROADWAY ST", "007 BOND ST" -> "7 BOND ST"
        $cleanedAddress = preg_replace('/^\s*0+\s+/', '', $address);

        // Rule 2: Turn all instances of "BROADWAY ST" into "BROADWAY".
        // This is case-insensitive.
        $cleanedAddress = preg_replace('/BROADWAY ST/i', 'BROADWAY', $cleanedAddress);

        return $cleanedAddress;
    }

    private function geocodeWithPlaces(string $address): ?array
    {
        $address = $this->cleanAddressForGeocoding($address);

        $apiKey = Config::get('services.google_places.api_key');
        if (!$apiKey) {
            Log::error('Google Places API key is not configured.');
            $this->error('Service configuration error: Google Places API key missing.');
            return null;
        }

        $locationBias = [
            'circle' => [
                'center' => ['latitude' => 42.408978, 'longitude' => -71.053868],
                'radius' => 5000.0 // 5km radius around Everett center
            ]
        ];

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://places.googleapis.com/v1/places:autocomplete', [
                'input' => $address,
                'locationRestriction' => $locationBias,
                'languageCode' => 'en',
                'regionCode' => 'US',
            ]);

            if (!$response->successful()) {
                Log::error('Google Places Autocomplete API request failed.', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $suggestions = $response->json()['suggestions'] ?? [];
            if (empty($suggestions)) {
                return null;
            }

            // Take the top suggestion and geocode it to get lat/lng
            $topSuggestionAddress = $suggestions[0]['placePrediction']['text']['text'] ?? null;
            if (!$topSuggestionAddress) {
                return null;
            }
            
            $this->line("  > Top suggestion from Places API: '{$topSuggestionAddress}'");
            return $this->geocodeWithGeolocation($topSuggestionAddress);

        } catch (\Exception $e) {
            Log::error('Exception calling Google Places Autocomplete API: ' . $e->getMessage());
            return null;
        }
    }

    private function geocodeWithGeolocation(string $address): ?array
    {
        $address = $this->cleanAddressForGeocoding($address);
        
        $apiKey = Config::get('services.google_places.api_key');
        if (!$apiKey) {
            Log::error('Google Geocoding API key is not configured.');
            $this->error('Service configuration error: Google Geocoding API key missing.');
            return null;
        }

        // Bounding box for Everett, MA. SW: (42.39, -71.07), NE: (42.42, -71.03)
        $bounds = '42.39,-71.07|42.42,-71.03';

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey,
                'bounds' => $bounds,
            ]);

            if (!$response->successful()) {
                Log::error('Google Geocoding API request failed.', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? 'ERROR') === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return ['lat' => $location['lat'], 'lng' => $location['lng']];
            } else {
                Log::warning('Google Geocoding API Error for address.', ['address' => $address, 'status' => $data['status'] ?? 'UNKNOWN']);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception during Google geocoding: ' . $e->getMessage(), ['address' => $address]);
            return null;
        }
    }
}
