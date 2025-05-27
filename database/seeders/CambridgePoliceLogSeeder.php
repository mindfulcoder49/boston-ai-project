<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Statement;

class CambridgePoliceLogSeeder extends Seeder
{
    private const BATCH_SIZE = 500; // For DB upserts
    private const MAX_RECORDS_IN_MEMORY_CHUNK = 10000; // For accumulating raw CSV records before processing
    private const STREET_ABBREVIATIONS = [
        'MOUNT' => 'MT',
        'SAINT' => 'ST',
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
    ];

    private array $addressCache = [];
    private array $intersectionCache = [];

    public function run(): void
    {
        $this->command->info("Starting Cambridge Police Log Seeder...");
        
        $this->loadAddressData();
        $this->loadIntersectionData();

        $logDirectory = 'datasets/cambridge/logs';
        $files = Storage::disk('local')->files($logDirectory);

        $csvFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (empty($csvFiles)) {
            $this->command->warn("No CSV files found in {$logDirectory}.");
            return;
        }

        $this->command->info("Found " . count($csvFiles) . " CSV files to process in {$logDirectory}.");

        $recordsChunk = [];
        $grandTotalRecordsProcessed = 0;
        $grandTotalNotFoundCount = 0;
        $fileCount = 0;

        foreach ($csvFiles as $filePath) {
            $fileCount++;
            $this->command->info("Reading file #{$fileCount}/" . count($csvFiles) . ": " . basename($filePath));
            
            try {
                $csv = Reader::createFromPath(Storage::path($filePath), 'r');
                $csv->setHeaderOffset(0);
                $csv->setEscape('');
                
                $stmt = Statement::create()->where(fn (array $record) => !empty($record['file_number']));
                $fileRecordsIterator = $stmt->process($csv);

                $recordsReadFromFile = 0;
                foreach ($fileRecordsIterator as $record) {
                    $recordsChunk[] = $record;
                    $recordsReadFromFile++;

                    if (count($recordsChunk) >= self::MAX_RECORDS_IN_MEMORY_CHUNK) {
                        $this->command->info("Processing a chunk of " . count($recordsChunk) . " records...");
                        $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk);
                        $grandTotalRecordsProcessed += $chunkStats['processed'];
                        $grandTotalNotFoundCount += $chunkStats['notFound'];
                        $recordsChunk = []; // Clear the chunk
                        $this->command->info("Chunk processed. Grand total records processed so far: {$grandTotalRecordsProcessed}. Locations not found so far: {$grandTotalNotFoundCount}.");
                    }
                }
                $this->command->info("Finished reading {$recordsReadFromFile} records from " . basename($filePath) . ".");

            } catch (\Exception $e) {
                $this->command->error("Error reading or preparing records from file: " . basename($filePath) . " - " . $e->getMessage());
                // Optionally skip this file or halt; here, we continue with the next file.
            }
        }

        // Process any remaining records in the last chunk
        if (!empty($recordsChunk)) {
            $this->command->info("Processing the final chunk of " . count($recordsChunk) . " records...");
            $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk);
            $grandTotalRecordsProcessed += $chunkStats['processed'];
            $grandTotalNotFoundCount += $chunkStats['notFound'];
            $this->command->info("Final chunk processed.");
        }

        $this->command->info("Cambridge Police Log Seeder finished. Total records processed: {$grandTotalRecordsProcessed}. Total locations not found: {$grandTotalNotFoundCount}.");
    }

    private function processAndInsertRecordsChunk(array $rawCsvRecords): array
    {
        $dataBatch = [];
        $recordsProcessedInChunk = 0;
        $notFoundInChunk = 0;
        $currentRecordIndexInChunk = 0;

        foreach ($rawCsvRecords as $record) {
            $currentRecordIndexInChunk++;
            $recordsProcessedInChunk++;

            $crimeDateTimeStr = trim($record['crime_date_time'] ?? '');
            $crimeDateTimeCarbon = $this->parseCrimeTimestamp($crimeDateTimeStr);

            $occurred_on_date_main = null;
            $year = null;
            $month = null;
            $day_of_week = null;
            $hour = null;
            $crime_start_val = null;
            $crime_end_val = null;

            if ($crimeDateTimeCarbon) {
                $occurred_on_date_main = $crimeDateTimeCarbon->format('Y-m-d H:i:s');
                $year = $crimeDateTimeCarbon->year;
                $month = $crimeDateTimeCarbon->month;
                $day_of_week = $crimeDateTimeCarbon->format('l');
                $hour = $crimeDateTimeCarbon->hour;
                $crime_start_val = $occurred_on_date_main;
                $crime_end_val = $occurred_on_date_main;
            } else {
                 // Warning already logged by parseCrimeTimestamp if needed
            }
            
            $raw_location = trim($record['location'] ?? '');
            $coords = ['latitude' => null, 'longitude' => null];
            $street_for_db = null;

            if (!empty($raw_location)) {
                if (strpos($raw_location, '&') !== false || stripos($raw_location, ' AND ') !== false) {
                    $normalized_coords = $this->normalizeAndLookupIntersection($raw_location);
                    if ($normalized_coords) {
                        $coords = $normalized_coords;
                    }
                    $street_for_db = $this->normalizeStreetName($raw_location);
                } else {
                    $parsedAddressInfo = $this->parseCrimeLocationAddress($raw_location);
                    if ($parsedAddressInfo) {
                        $crimeStreetNumber = $parsedAddressInfo['number'];
                        $crimeStreetName = $parsedAddressInfo['name'];
                        $street_for_db = $crimeStreetName;
                        $cachedAddressesOnStreet = $this->addressCache[strtolower($crimeStreetName)] ?? [];
                        if (!empty($cachedAddressesOnStreet)) {
                            $closestMatch = null; $minDifference = PHP_INT_MAX;
                            foreach ($cachedAddressesOnStreet as $cachedAddr) {
                                if ($cachedAddr['number'] > 0) {
                                    $difference = abs($crimeStreetNumber - $cachedAddr['number']);
                                    if ($difference < $minDifference) {
                                        $minDifference = $difference; $closestMatch = $cachedAddr;
                                    } elseif ($difference === $minDifference && $closestMatch && $cachedAddr['number'] < $closestMatch['number']) {
                                        $closestMatch = $cachedAddr;
                                    }
                                }
                            }
                            if ($closestMatch) {
                                 $coords = ['latitude' => $closestMatch['latitude'], 'longitude' => $closestMatch['longitude']];
                            }
                        }
                    }
                    if (empty($coords['latitude'])) {
                        if ($parsedAddressInfo && !empty($parsedAddressInfo['name'])) {
                            $cachedStreetAddresses = $this->addressCache[strtolower($parsedAddressInfo['name'])] ?? [];
                            if (!empty($cachedStreetAddresses)) {
                                $lowestAddress = $cachedStreetAddresses[0];
                                $coords = ['latitude' => $lowestAddress['latitude'], 'longitude' => $lowestAddress['longitude']];
                            }
                        }
                    }
                }
                if (empty($coords['latitude'])) {
                    $notFoundInChunk++;
                    // $this->command->warn(" -> Location '{$raw_location}' NOT FOUND for file_number '{$record['file_number']}'."); // Too verbose here
                }
            } else {
                $notFoundInChunk++;
                // $this->command->warn(" -> Empty location field for file_number '{$record['file_number']}'."); // Too verbose here
            }

            $incident_number = 'CPL-' . ($record['file_number'] ?? ('UNKNOWN-' . $recordsProcessedInChunk));
            $offense_description_raw = $record['crime'] ?? null;
            $offense_description_decoded = $offense_description_raw ? html_entity_decode($offense_description_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;

            $dataBatch[] = [
                'incident_number'     => $incident_number,
                'offense_code'        => null,
                'offense_code_group'  => null,
                'offense_description' => $offense_description_decoded,
                'district'            => null,
                'reporting_area'      => null,
                'shooting'            => false,
                'occurred_on_date'    => $occurred_on_date_main,
                'year'                => $year,
                'month'               => $month,
                'day_of_week'         => $day_of_week,
                'hour'                => $hour,
                'ucr_part'            => null,
                'street'              => $street_for_db,
                'lat'                 => $coords['latitude'] ? round((float)$coords['latitude'], 7) : null,
                'long'                => $coords['longitude'] ? round((float)$coords['longitude'], 7) : null,
                'location'            => $raw_location,
                'crime_start_time'    => $crime_start_val,
                'crime_end_time'      => $crime_end_val,
                'crime_details'       => trim($record['crime_details'] ?? null),
                'created_at'          => now(),
                'updated_at'          => now(),
            ];

            if (count($dataBatch) >= self::BATCH_SIZE) {
                $this->insertOrUpdateBatch($dataBatch);
                $this->command->info("... upserted " . count($dataBatch) . " records to DB (processed {$currentRecordIndexInChunk}/" . count($rawCsvRecords) . " in current chunk) ...");
                $dataBatch = [];
            }
        }

        if (!empty($dataBatch)) {
            $this->insertOrUpdateBatch($dataBatch);
            $this->command->info("... upserted final " . count($dataBatch) . " records to DB for this chunk ...");
        }
        
        return ['processed' => $recordsProcessedInChunk, 'notFound' => $notFoundInChunk];
    }

    private function loadAddressData(): void
    {
        $this->command->info("Loading Cambridge address data into cache...");
        $addresses = DB::table('cambridge_addresses')
            ->select('street_number', 'stname', 'latitude', 'longitude')
            ->whereNotNull('stname')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($addresses as $address) {
            $normalizedStName = strtolower($this->normalizeStreetName($address->stname));
            if (empty($normalizedStName)) {
                continue;
            }
            $streetNumberNumeric = intval($address->street_number); // Handles numeric and alphanumeric like "1A"

            $this->addressCache[$normalizedStName][] = [
                'number' => $streetNumberNumeric, // Store the numeric part for comparison
                'original_number' => $address->street_number, // Keep original for reference if needed
                'latitude' => (float)$address->latitude,
                'longitude' => (float)$address->longitude,
            ];
        }

        // Sort addresses by street number for efficient searching
        foreach ($this->addressCache as $streetName => $addressList) {
            usort($this->addressCache[$streetName], function ($a, $b) {
                return $a['number'] <=> $b['number'];
            });
        }
        $this->command->info("Finished loading " . count($addresses) . " addresses into cache, grouped by " . count($this->addressCache) . " unique street names.");
    }

    private function loadIntersectionData(): void
    {
        $this->command->info("Loading Cambridge intersection data into cache...");
        $intersections = DB::table('cambridge_intersections')
            ->select('intersection', 'latitude', 'longitude')
            ->whereNotNull('intersection')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($intersections as $intersection) {
            // The intersection name in the DB should already be normalized (e.g., StreetA & StreetB, sorted alphabetically)
            // If not, normalization (splitting, sorting, rejoining) would be needed here.
            // Assuming 'intersection' column is already in 'StreetA & StreetB' format, sorted alphabetically.
            $this->intersectionCache[strtolower($intersection->intersection)] = [
                'latitude' => (float)$intersection->latitude,
                'longitude' => (float)$intersection->longitude,
            ];
        }
        $this->command->info("Finished loading " . count($intersections) . " intersections into cache.");
    }

    private function normalizeStreetName(string $streetName): string
    {
        $processedName = strtoupper(trim($streetName));
        // Replace multiple spaces with a single space
        $processedName = preg_replace('/\s+/', ' ', $processedName);
        // Remove "THE " from the beginning of street names if it exists
        $processedName = preg_replace('/^THE\s+/', '', $processedName);

        foreach (self::STREET_ABBREVIATIONS as $search => $replace) {
            // Use word boundaries to avoid partial replacements (e.g., 'AVENUE' in 'RAVENSWOOD AVENUE')
            $processedName = preg_replace('/\b' . preg_quote($search, '/') . '\b/i', $replace, $processedName);
        }
        // Remove trailing periods
        $processedName = rtrim($processedName, '.');
        return trim($processedName);
    }
    
    private function normalizeAndLookupIntersection(string $intersectionQueryString): ?array
    {
        $this->command->comment("---> Attempting Intersection Lookup for: '{$intersectionQueryString}'");
        $logBuffer = [];

        $parts = preg_split('/\s+&\s+|\s+AND\s+/i', $intersectionQueryString, 2, PREG_SPLIT_NO_EMPTY);

        if (count($parts) !== 2) {
            $logBuffer[] = "     Could not parse '{$intersectionQueryString}' into two distinct street names.";
            $this->command->warn(implode("\n", $logBuffer));
            return null;
        }
        $street1_original = trim($parts[0]);
        $street2_original = trim($parts[1]);
        $street1_processed = $this->normalizeStreetName($street1_original);
        $street2_processed = $this->normalizeStreetName($street2_original);

        $streetsForPrimaryLookup = [$street1_processed, $street2_processed];
        sort($streetsForPrimaryLookup, SORT_STRING | SORT_FLAG_CASE); // Case-insensitive sort
        $primaryLookupString = strtolower($streetsForPrimaryLookup[0] . ' & ' . $streetsForPrimaryLookup[1]);
        
        if (isset($this->intersectionCache[$primaryLookupString])) {
            $this->command->info("     SUCCESS (Primary Cache Match): '{$intersectionQueryString}' found as '{$primaryLookupString}'.");
            return $this->intersectionCache[$primaryLookupString];
        }
        $logBuffer[] = "     Primary cache lookup FAILED for '{$primaryLookupString}'.";
        
        // Fallback: Try swapping order (should be covered by sort, but as a safeguard if DB isn't perfectly normalized or cache keying differs)
        $secondaryLookupString = strtolower($streetsForPrimaryLookup[1] . ' & ' . $streetsForPrimaryLookup[0]);
        if ($primaryLookupString !== $secondaryLookupString && isset($this->intersectionCache[$secondaryLookupString])) {
            $this->command->info("     SUCCESS (Secondary Cache Match - swapped): '{$intersectionQueryString}' found as '{$secondaryLookupString}'.");
            return $this->intersectionCache[$secondaryLookupString];
        }
        if ($primaryLookupString !== $secondaryLookupString) {
            $logBuffer[] = "     Secondary cache lookup FAILED for '{$secondaryLookupString}'.";
        }

        // Fallback to lowest address on street1 using addressCache
        if (!empty($street1_processed)) {
            $cachedAddressesStreet1 = $this->addressCache[strtolower($street1_processed)] ?? [];
            if (!empty($cachedAddressesStreet1)) {
                // Cache is sorted by number, first element is the lowest.
                $addressStreet1 = $cachedAddressesStreet1[0];
                $this->command->info("     SUCCESS (Street 1 Fallback - Cache): Used lowest address on '{$street1_processed}'.");
                return ['latitude' => $addressStreet1['latitude'], 'longitude' => $addressStreet1['longitude']];
            }
            $logBuffer[] = "     Street 1 fallback (Cache) FAILED for '{$street1_processed}'.";
        }

        // Fallback to lowest address on street2 using addressCache
        if (!empty($street2_processed)) {
            $cachedAddressesStreet2 = $this->addressCache[strtolower($street2_processed)] ?? [];
            if (!empty($cachedAddressesStreet2)) {
                $addressStreet2 = $cachedAddressesStreet2[0];
                $this->command->info("     SUCCESS (Street 2 Fallback - Cache): Used lowest address on '{$street2_processed}'.");
                return ['latitude' => $addressStreet2['latitude'], 'longitude' => $addressStreet2['longitude']];
            }
            $logBuffer[] = "     Street 2 fallback (Cache) FAILED for '{$street2_processed}'.";
        }

        $this->command->warn(implode("\n", $logBuffer));
        $this->command->warn("     All intersection lookup strategies FAILED for '{$intersectionQueryString}'.");
        return null;
    }

    private function parseCrimeLocationAddress(string $locationString): ?array
    {
        // Example: "1200 MASSACHUSETTS AVE"
        // Regex to capture number and street part
        // Updated regex to better handle cases like "0 BLOCK", "1-10 BLOCK"
        if (preg_match('/^(\d+[A-Z]?(-\d+[A-Z]?)?(\s+BLOCK)?)\s+(.*)$/i', $locationString, $matches) ||
            preg_match('/^(BLOCK\s+\d+[A-Z]?(-\d+[A-Z]?)?)\s+(.*)$/i', $locationString, $matches) || // Handles "BLOCK 123 MAIN ST"
            preg_match('/^(\d+[A-Z]?(-\d+[A-Z]?)?)\s+(.*)$/i', $locationString, $matches) // Original
           ) {
            $numberPart = trim($matches[1]); 
            $rawStreetNamePart = trim(end($matches)); // Use end() to get the last capture group for street name

            // If "BLOCK" is part of numberPart, remove it for numeric conversion but keep for original
            $numericStreetNumberToMatch = intval(preg_replace('/(\s*BLOCK\s*)/i', '', $numberPart));

            $normalizedStreetName = $this->normalizeStreetName($rawStreetNamePart);
            
            if (!empty($normalizedStreetName)) { // Allow 0 block, street number can be 0
                return [
                    'number' => $numericStreetNumberToMatch, 
                    'name' => $normalizedStreetName,         
                    'original_number_part' => $numberPart   
                ];
            }
        }
        $this->command->comment(" -> Could not parse address: '{$locationString}' into number and street.");
        return null;
    }
    
    private function parseCrimeTimestamp(string $timeString): ?Carbon
    {
        $timeString = trim($timeString);
        if (!$timeString) return null;
        try {
            // Expected format from logs "m/d/Y H:i AM/PM", e.g., "5/21/2025 4:12 AM"
            return Carbon::createFromFormat('m/d/Y g:i A', $timeString);
        } catch (\Exception $e) {
            try {
                // Fallback for "m/d/Y H:i" (24-hour format if AM/PM is missing but time is like 13:00)
                return Carbon::createFromFormat('m/d/Y H:i', $timeString);
            } catch (\Exception $e2) {
                $this->command->warn("Could not parse crime timestamp: '{$timeString}'. Error: " . $e->getMessage());
                return null;
            }
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        if (empty($dataBatch)) return;

        $updateColumns = [
            'offense_code', 'offense_code_group', 'offense_description', 'district', 
            'reporting_area', 'shooting', 'occurred_on_date', 'year', 'month', 
            'day_of_week', 'hour', 'ucr_part', 'street', 'lat', 'long', 'location', 
            'crime_start_time', 'crime_end_time', 'crime_details', // Added narrative
            'updated_at'
        ];

        DB::table('crime_data')->upsert(
            $dataBatch,
            ['incident_number'], // Unique key(s)
            $updateColumns      // Columns to update on duplicate
        );
        $this->command->info("Upserted batch of " . count($dataBatch) . " records.");
    }
}
