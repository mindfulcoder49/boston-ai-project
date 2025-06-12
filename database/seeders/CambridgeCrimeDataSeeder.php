<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
use App\Models\CambridgeCrimeReportData; // Added

class CambridgeCrimeDataSeeder extends Seeder
{
    private const BATCH_SIZE = 500;
    private const STREET_ABBREVIATIONS = [
        'MOUNT' => 'MT',
        'SAINT' => 'ST', // Note: This 'ST' for Saint might need care if 'ST' is also for Street.
                         // Consider 'ST.' or ensure DB consistency. For now, using 'ST'.
        'ACORN PK' => 'ACORN PARK DR',
        'GALILEO GALILEI' => 'GALILEI',
        'CAMBRIDGE CTR' => 'BROADWAY',
        'STREET NORTH'  => 'ST N',
        'HAWTHORNE' => 'HAWTHORN',
        /*
        'STREET' => 'ST',
        'AVENUE' => 'AVE',
        'ROAD' => 'RD',
        'DRIVE' => 'DR',
        'PLACE' => 'PL',
        'COURT' => 'CT',
        'LANE' => 'LN',
        'BOULEVARD' => 'BLVD',
        'PARKWAY' => 'PKWY',
        'SQUARE' => 'SQ',
        'TERRACE' => 'TER',
        'HIGHWAY' => 'HWY',
        'CIRCLE' => 'CIR',
        'ALLEY' => 'ALY',
        'EXPRESSWAY' => 'EXPY',
        'FREEWAY' => 'FWY',
        'JUNCTION' => 'JCT',
        'POINT' => 'PT',
        'TRAIL' => 'TRL',
        'TURNPIKE' => 'TPKE',
        'WAY' => 'WY',
        'CENTER' => 'CTR', */
        // Common directional words often abbreviated at start/end of street names
        // These are more complex to handle generally unless part of the abbreviation map for full names
        // e.g. if "NORTH MAIN STREET" is stored as "N MAIN ST"
        // For now, focusing on type abbreviations and explicit "Mount".
    ];

    public function run()
    {
        $datasetName = 'cambridge-crime-reports';
        $citySubdirectory = 'cambridge';
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");
        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (!empty($datasetFiles)) {
            sort($datasetFiles);
            $fileToProcess = end($datasetFiles);
            $this->command->info("Processing Cambridge crime data file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No file found for Cambridge crime data.");
        }
    }

    private function normalizeStreetName(string $streetName): string
    {
        $processedName = strtoupper(trim($streetName));
        $processedName = preg_replace('/\s+/', ' ', $processedName); // Normalize spaces

        foreach (self::STREET_ABBREVIATIONS as $search => $replace) {
            // Use word boundaries to ensure whole word replacement.
            // $search is already uppercase from the constant definition.
            $processedName = preg_replace('/\b' . preg_quote($search, '/') . '\b/', $replace, $processedName);
        }
        $processedName = rtrim($processedName, '.'); // Remove trailing periods
        return trim($processedName);
    }

    private function normalizeAndLookupIntersection(string $intersectionQueryString): ?array
    {
        $this->command->info("---> Attempting Intersection Lookup for: '{$intersectionQueryString}'");
        $logBuffer = []; // Buffer for detailed logs, only shown on complete failure

        // 1. Parse into two street parts
        $parts = explode(' & ', $intersectionQueryString);
        if (count($parts) !== 2) {
            $parts = preg_split('/\s+AND\s+/i', $intersectionQueryString, 2);
            if (count($parts) !== 2) {
                $logBuffer[] = "     Could not parse '{$intersectionQueryString}' into two distinct street names using ' & ' or ' AND '. Skipping detailed normalization.";
                $this->command->warn(implode("\n", $logBuffer));
                $this->command->warn("     Intersection lookup FAILED for '{$intersectionQueryString}'.");
                return null;
            }
        }

        $street1_original = trim($parts[0]);
        $street2_original = trim($parts[1]);
        $logBuffer[] = "     Parsed into: [1] '{$street1_original}' AND [2] '{$street2_original}'";

        // 2. Process each street name using the common normalizer
        $street1_processed = $this->normalizeStreetName($street1_original);
        $street2_processed = $this->normalizeStreetName($street2_original);
        $logBuffer[] = "     After normalization: [1] '{$street1_processed}' AND [2] '{$street2_processed}'";

        // 3. Alphabetize for primary lookup
        $streetsForPrimaryLookup = [$street1_processed, $street2_processed];
        sort($streetsForPrimaryLookup, SORT_STRING | SORT_FLAG_CASE);
        $primaryLookupString = $streetsForPrimaryLookup[0] . ' & ' . $streetsForPrimaryLookup[1];
        $logBuffer[] = "     Primary Lookup String (normalized, alphabetized): '{$primaryLookupString}'";

        // 4. Database Lookup (Primary: Normalized, Alphabetized)
        $lookup = DB::table('cambridge_intersections')
            ->where(DB::raw('LOWER(intersection)'), strtolower($primaryLookupString))
            ->first();

        if ($lookup) {
            $this->command->info("     SUCCESS (Primary Match): '{$intersectionQueryString}' found as '{$primaryLookupString}'. Lat: {$lookup->latitude}, Lon: {$lookup->longitude}");
            return ['latitude' => $lookup->latitude, 'longitude' => $lookup->longitude];
        }
        $logBuffer[] = "     Primary lookup FAILED for '{$primaryLookupString}'.";

        // 5. Fallback Lookup (Original parsed names, alphabetized, case-insensitive)
        $fallbackStreetsOriginal = [$street1_original, $street2_original];
        sort($fallbackStreetsOriginal, SORT_STRING | SORT_FLAG_CASE);
        $fallbackLookupStringOriginal = $fallbackStreetsOriginal[0] . ' & ' . $fallbackStreetsOriginal[1];
        $logBuffer[] = "     Fallback Lookup String (original, alphabetized): '{$fallbackLookupStringOriginal}'";
        
        $fallbackLookup = DB::table('cambridge_intersections')
            ->where(DB::raw('LOWER(intersection)'), strtolower($fallbackLookupStringOriginal))
            ->first();

        if ($fallbackLookup) {
            $this->command->info("     SUCCESS (Fallback Original Match): '{$intersectionQueryString}' found as '{$fallbackLookupStringOriginal}'. Lat: {$fallbackLookup->latitude}, Lon: {$fallbackLookup->longitude}");
            return ['latitude' => $fallbackLookup->latitude, 'longitude' => $fallbackLookup->longitude];
        }
        $logBuffer[] = "     Fallback lookup (original, alphabetized) FAILED for '{$fallbackLookupStringOriginal}'.";

        // 6. Second Fallback: Substring match for both processed street names (for multi-street intersections)
        // Ensure street names are not empty before attempting this
        if (!empty($street1_processed) && !empty($street2_processed)) {
            $street1_processed_lower = strtolower($street1_processed);
            $street2_processed_lower = strtolower($street2_processed);
            $logBuffer[] = "     Second Fallback Lookup: Checking for intersections containing BOTH '{$street1_processed_lower}' AND '{$street2_processed_lower}'.";

            $substringLookup = DB::table('cambridge_intersections')
                ->where(DB::raw('LOWER(intersection)'), 'LIKE', '%' . $street1_processed_lower . '%')
                ->where(DB::raw('LOWER(intersection)'), 'LIKE', '%' . $street2_processed_lower . '%')
                ->first(); // Take the first match if multiple exist

            if ($substringLookup) {
                $this->command->info("     SUCCESS (Substring Fallback Match): '{$intersectionQueryString}' (normalized as '{$street1_processed}' & '{$street2_processed}') found in '{$substringLookup->intersection}'. Lat: {$substringLookup->latitude}, Lon: {$substringLookup->longitude}");
                return ['latitude' => $substringLookup->latitude, 'longitude' => $substringLookup->longitude];
            }
            $logBuffer[] = "     Second Fallback (substring) FAILED for '{$street1_processed_lower}' AND '{$street2_processed_lower}'.";
        } else {
            $logBuffer[] = "     Skipping Second Fallback (substring) because one or both processed street names are empty.";
        }

        // 7. Third Fallback: Lowest address number on the first street
        if (!empty($street1_processed)) {
            $logBuffer[] = "     Third Fallback: Attempting to find lowest address on street '{$street1_processed}'.";
            $addressStreet1 = DB::table('cambridge_addresses')
                ->where(DB::raw('LOWER(stname)'), strtolower($street1_processed))
                ->whereNotNull('street_number')
                ->where('street_number', 'REGEXP', '^[0-9]+$') // Ensure it's purely numeric for reliable sorting
                ->orderByRaw('CAST(street_number AS UNSIGNED) ASC')
                ->select('latitude', 'longitude', 'full_addr')
                ->first();

            if ($addressStreet1) {
                $this->command->info("     SUCCESS (Street 1 Fallback Match): For '{$intersectionQueryString}', using lowest address on '{$street1_processed}': '{$addressStreet1->full_addr}'. Lat: {$addressStreet1->latitude}, Lon: {$addressStreet1->longitude}");
                return ['latitude' => $addressStreet1->latitude, 'longitude' => $addressStreet1->longitude];
            }
            $logBuffer[] = "     Third Fallback (lowest address on '{$street1_processed}') FAILED.";
        } else {
            $logBuffer[] = "     Skipping Third Fallback (lowest address on street 1) because processed street name 1 is empty.";
        }

        // 8. Fourth Fallback: Lowest address number on the second street
        if (!empty($street2_processed)) {
            $logBuffer[] = "     Fourth Fallback: Attempting to find lowest address on street '{$street2_processed}'.";
            $addressStreet2 = DB::table('cambridge_addresses')
                ->where(DB::raw('LOWER(stname)'), strtolower($street2_processed))
                ->whereNotNull('street_number')
                ->where('street_number', 'REGEXP', '^[0-9]+$')
                ->orderByRaw('CAST(street_number AS UNSIGNED) ASC')
                ->select('latitude', 'longitude', 'full_addr')
                ->first();

            if ($addressStreet2) {
                $this->command->info("     SUCCESS (Street 2 Fallback Match): For '{$intersectionQueryString}', using lowest address on '{$street2_processed}': '{$addressStreet2->full_addr}'. Lat: {$addressStreet2->latitude}, Lon: {$addressStreet2->longitude}");
                return ['latitude' => $addressStreet2->latitude, 'longitude' => $addressStreet2->longitude];
            }
            $logBuffer[] = "     Fourth Fallback (lowest address on '{$street2_processed}') FAILED.";
        } else {
            $logBuffer[] = "     Skipping Fourth Fallback (lowest address on street 2) because processed street name 2 is empty.";
        }
        
        // If all lookups failed, print the buffered logs and the final failure message
        $this->command->warn(implode("\n", $logBuffer));
        $this->command->warn("     All intersection lookup strategies FAILED for '{$intersectionQueryString}'.");
        return null;
    }

    private function processFile($filePath)
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setEscape('');
            $records = $csv->getRecords();
            $dataBatch = [];
            $progress = 0;
            $notFoundCount = 0;

            foreach ($records as $record) {
                $progress++;

                $crimeDateTimeRaw = trim($record['crime_date_time'] ?? '');
                $crimeStart = null;
                $crimeEnd = null;

                if (strpos($crimeDateTimeRaw, ' - ') !== false) {
                    [$startPart, $endPart] = array_map('trim', explode(' - ', $crimeDateTimeRaw, 2));
                    $crimeStart = $this->parseCrimeTimestampInternal($startPart);
                    if ($crimeStart && !empty($endPart)) {
                        if (preg_match('/^\d{1,2}:\d{2}$/', $endPart) && preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})\s/', $startPart, $dateMatches)) {
                            $fullEndPart = $dateMatches[1] . ' ' . $endPart;
                            $crimeEnd = $this->parseCrimeTimestampInternal($fullEndPart);
                        } else {
                            $crimeEnd = $this->parseCrimeTimestampInternal($endPart);
                        }
                    } elseif ($crimeStart && empty($endPart)) {
                        $crimeEnd = $crimeStart;
                    }
                } elseif (!empty($crimeDateTimeRaw)) {
                    $crimeStart = $this->parseCrimeTimestampInternal($crimeDateTimeRaw);
                    $crimeEnd = $crimeStart;
                }
                
                $fileNumberExternal = ($record['file_number'] === '') ? null : ($record['file_number'] ?? null);
                if (empty($fileNumberExternal)) {
                    $this->command->warn("Skipping record with empty file_number: " . json_encode($record));
                    continue;
                }

                $raw_location = trim($record['location'] ?? '');
                $coords = ['latitude' => null, 'longitude' => null];
                $location_found = false;
                $parsedAddressInfo = null; 

                if (!empty($raw_location)) {
                    $this->command->info("Processing raw location: '{$raw_location}'");
                    $location_for_db_lookup = preg_replace('/, Cambridge, MA$/i', '', $raw_location);
                    $location_for_db_lookup = trim($location_for_db_lookup);
                    $this->command->info("Cleaned location for DB lookup: '{$location_for_db_lookup}'");

                    if (strpos($location_for_db_lookup, '&') !== false || stripos($location_for_db_lookup, ' AND ') !== false) { // Intersection
                        $normalized_coords = $this->normalizeAndLookupIntersection($location_for_db_lookup);
                        if ($normalized_coords) {
                            $coords['latitude'] = $normalized_coords['latitude'];
                            $coords['longitude'] = $normalized_coords['longitude'];
                            $location_found = true;
                        }
                    } else { // Address
                        $this->command->info("Attempting Address Lookup for: '{$location_for_db_lookup}'");
                        // parseCrimeLocationAddress will now use normalizeStreetName internally
                        $parsedAddressInfo = $this->parseCrimeLocationAddress($location_for_db_lookup);
                        if ($parsedAddressInfo) {
                            $crimeStreetNumber = $parsedAddressInfo['number'];
                            $crimeStreetName = $parsedAddressInfo['name']; // This is now normalized
                            $this->command->info("  Parsed and Normalized address: Number='{$crimeStreetNumber}', Name='{$crimeStreetName}' (Original number part: '{$parsedAddressInfo['original_number_part']}')");

                            // Query against stname using the normalized $crimeStreetName
                            $dbAddresses = DB::table('cambridge_addresses')
                                ->where(DB::raw('LOWER(stname)'), strtolower($crimeStreetName)) // stname in DB should be normalized too
                                ->select('street_number', 'latitude', 'longitude', 'full_addr')
                                ->get();

                            if ($dbAddresses->isNotEmpty()) {
                                $this->command->info("  Found " . $dbAddresses->count() . " addresses on street '{$crimeStreetName}'. Searching for closest to number '{$crimeStreetNumber}'.");
                                $closestMatch = null;
                                $minDifference = PHP_INT_MAX;

                                foreach ($dbAddresses as $dbAddr) {
                                    $dbStreetNumberNumeric = intval($dbAddr->street_number);
                                    if ($dbStreetNumberNumeric > 0) { // Ensure it's a valid number for comparison
                                        $difference = abs($crimeStreetNumber - $dbStreetNumberNumeric);
                                        if ($difference < $minDifference) {
                                            $minDifference = $difference;
                                            $closestMatch = $dbAddr;
                                        }
                                    }
                                }

                                if ($closestMatch) {
                                    $coords['latitude'] = $closestMatch->latitude;
                                    $coords['longitude'] = $closestMatch->longitude;
                                    $location_found = true;
                                    $this->command->info("  Closest address match: '{$closestMatch->full_addr}' (Num diff: {$minDifference}). Lat: {$coords['latitude']}, Lon: {$coords['longitude']}");
                                } else {
                                    $this->command->warn("  No valid numeric street numbers found for '{$crimeStreetName}' to compare with '{$crimeStreetNumber}'.");
                                }
                            } else {
                                 $this->command->warn("  No addresses found for normalized street name '{$crimeStreetName}'.");
                            }
                        } else {
                             $this->command->warn("  Could not parse '{$location_for_db_lookup}' into number/name for detailed address lookup.");
                        }

                        if (!$location_found) {
                            $this->command->info("  Attempting fallback LIKE lookup for address '{$location_for_db_lookup}%'");
                            $lookup = DB::table('cambridge_addresses')
                                ->where(DB::raw('LOWER(full_addr)'), 'LIKE', strtolower($location_for_db_lookup) . '%')
                                ->first();
                            if ($lookup) {
                                $coords['latitude'] = $lookup->latitude;
                                $coords['longitude'] = $lookup->longitude;
                                $location_found = true;
                                $this->command->info("  Fallback LIKE '{$location_for_db_lookup}%' MATCH FOUND: '{$lookup->full_addr}'. Lat: {$coords['latitude']}, Lon: {$coords['longitude']}");
                            } else {
                                $this->command->info("  Attempting second fallback LIKE lookup for address '%{$raw_location}%'");
                                $lookup_raw = DB::table('cambridge_addresses')
                                    ->where(DB::raw('LOWER(full_addr)'), 'LIKE', '%' . strtolower($raw_location) . '%')
                                    ->first();
                                if ($lookup_raw) {
                                    $coords['latitude'] = $lookup_raw->latitude;
                                    $coords['longitude'] = $lookup_raw->longitude;
                                    $location_found = true;
                                    $this->command->info("  Second fallback LIKE '%{$raw_location}%' MATCH FOUND: '{$lookup_raw->full_addr}'. Lat: {$coords['latitude']}, Lon: {$coords['longitude']}");
                                } else {
                                    $this->command->info("  Second fallback LIKE '%{$raw_location}%' FAILED.");
                                }
                            }
                        }

                        // New Fallback: If address not found, and street name was parsed, look for street name in intersections
                        if (!$location_found && $parsedAddressInfo && !empty($parsedAddressInfo['name'])) {
                            $streetNameToSearchInIntersections = $parsedAddressInfo['name'];
                            $this->command->info("  Address not found. Attempting final fallback: Searching for street '{$streetNameToSearchInIntersections}' in intersections table.");
                            
                            $intersectionFallback = DB::table('cambridge_intersections')
                                ->where(DB::raw('LOWER(intersection)'), 'LIKE', '%' . strtolower($streetNameToSearchInIntersections) . '%')
                                ->select('latitude', 'longitude', 'intersection')
                                ->first();

                            if ($intersectionFallback) {
                                $coords['latitude'] = $intersectionFallback->latitude;
                                $coords['longitude'] = $intersectionFallback->longitude;
                                $location_found = true;
                                $this->command->info("  SUCCESS (Address Fallback to Intersection): Found street '{$streetNameToSearchInIntersections}' in intersection '{$intersectionFallback->intersection}'. Lat: {$coords['latitude']}, Lon: {$coords['longitude']}");
                            } else {
                                $this->command->warn("  Final fallback (street '{$streetNameToSearchInIntersections}' in intersections) FAILED.");
                            }
                        }
                    }

                    if (!$location_found) {
                        $this->command->warn("LOCATION NOT FOUND for '{$raw_location}' (Cleaned: '{$location_for_db_lookup}') using all strategies.");
                        $notFoundCount++;
                    }
                } else { // Empty raw_location
                     $this->command->warn("Empty location string for record: " . ($fileNumberExternal ?? 'N/A'));
                     $notFoundCount++;
                }

                $dataBatch[] = [
                    'file_number_external'  => $fileNumberExternal,
                    'date_of_report'        => $this->formatDate($record['date_of_report'] ?? null),
                    'crime_datetime_raw'    => ($crimeDateTimeRaw === '') ? null : $crimeDateTimeRaw,
                    'crime_start_time'      => $crimeStart,
                    'crime_end_time'        => $crimeEnd,
                    'crime'                 => ($record['crime'] === '') ? null : ($record['crime'] ?? null),
                    'reporting_area'        => ($record['reporting_area'] === '') ? null : ($record['reporting_area'] ?? null),
                    'neighborhood'          => ($record['neighborhood'] === '') ? null : ($record['neighborhood'] ?? null),
                    'location_address'      => ($raw_location === '') ? null : $raw_location,
                    'latitude'              => $coords['latitude'], // Already handles null if not found
                    'longitude'             => $coords['longitude'], // Already handles null if not found
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} records... ({$notFoundCount} locations not found so far)");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("File processed: " . basename($filePath) . ". Total locations not found: {$notFoundCount}");
        } catch (\Exception $e) {
            $this->command->error("Error processing crime data file: " . basename($filePath) . " - " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        }
    }

    private function parseCrimeLocationAddress(string $locationString): ?array
    {
        // Regex to capture a numeric part and the street name
        if (preg_match('/^(\d+)\s+(.*)$/', $locationString, $matches)) {
            $numberPart = $matches[1]; // The numeric part
            $rawStreetNamePart = $matches[2]; // The rest of the string is the street name
            
            $numericStreetNumber = intval($numberPart); 

            // Normalize the extracted street name
            $normalizedStreetName = $this->normalizeStreetName($rawStreetNamePart);
            
            $this->command->info("Parsed address: Number='{$numericStreetNumber}', Raw Name='{$rawStreetNamePart}', Normalized Name='{$normalizedStreetName}'");
            if ($numericStreetNumber > -1 && !empty($normalizedStreetName)) { // Allow 0 as a street number if it occurs
                return [
                    'number' => $numericStreetNumber,
                    'name' => $normalizedStreetName, // Return the normalized name
                    'original_number_part' => $numberPart 
                ];
            }
        }
        $this->command->warn("Could not parse address string '{$locationString}' into number and street name components.");
        return null; 
    }

    private function parseCrimeTimestampInternal($timeString): ?string // Renamed and updated
    {
        $timeString = trim($timeString);
        if (!$timeString) return null;
        try {
            // Expected format "m/d/Y H:i", e.g., "01/18/2009 22:00"
            return Carbon::createFromFormat('m/d/Y H:i', $timeString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Fallback for "m/d/Y g:i A" e.g. "01/18/2009 10:00 PM"
             try {
                return Carbon::createFromFormat('m/d/Y g:i A', $timeString)->format('Y-m-d H:i:s');
            } catch (\Exception $e2) {
                $this->command->warn("Could not parse crime timestamp: '{$timeString}'. Error: " . $e->getMessage());
                return null;
            }
        }
    }
    
    private function formatDate($dateString): ?string // Renamed and updated
    {
        if (empty($dateString) || strtolower($dateString) === 'nan') {
            return null;
        }
        try {
            // Handles ISO 8601 format like "2009-01-21T16:32:00.000"
            // Also handles "m/d/Y" if that's the format for date_of_report
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateString)) { // Matches m/d/Y
                 return Carbon::createFromFormat('m/d/Y', $dateString)->startOfDay()->format('Y-m-d H:i:s');
            }
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date: {$dateString}");
            return null;
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void // Renamed and updated
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['file_number_external']));
        if (empty($validBatch)) {
            return;
        }
        
        $model = new CambridgeCrimeReportData();
        $fillable = $model->getFillable();
        $updateColumns = array_filter($fillable, function ($col) {
            return !in_array($col, ['file_number_external']);
        });

        DB::table($model->getTable())->upsert(
            $validBatch,
            ['file_number_external'],
            array_values($updateColumns)
        );
    }
}
