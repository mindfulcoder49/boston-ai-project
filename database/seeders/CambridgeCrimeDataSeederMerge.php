<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
// Note: Statement class might be useful if complex filtering is needed before chunking,
// but for now, we read all records from the selected file.
// use League\Csv\Statement; 

class CambridgeCrimeDataSeederMerge extends Seeder
{
    private const BATCH_SIZE = 500; // For DB upserts
    private const MAX_RECORDS_IN_MEMORY_CHUNK = 10000; // For accumulating raw CSV records
    private const STREET_ABBREVIATIONS = [
        'MOUNT' => 'MT',
        'SAINT' => 'ST',
        'ACORN PK' => 'ACORN PARK DR',
        'GALILEO GALILEI' => 'GALILEI',
        'CAMBRIDGE CTR' => 'BROADWAY',
        'STREET NORTH'  => 'ST N',
        'HAWTHORNE' => 'HAWTHORN',
        // Add other abbreviations from CambridgePoliceLogSeeder if needed
    ];

    private array $addressCache = [];
    private array $intersectionCache = [];

    public function run()
    {
        $this->command->info("Starting Cambridge Crime Data Merge Seeder...");

        $this->loadAddressData();
        $this->loadIntersectionData();

        $datasetName = 'cambridge-crime-reports';
        $citySubdirectory = 'cambridge';
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");
        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (empty($datasetFiles)) {
            $this->command->warn("No file found for Cambridge crime data merge.");
            return;
        }
        
        sort($datasetFiles);
        $fileToProcessPath = end($datasetFiles);
        $this->command->info("Selected Cambridge crime data file for merge: " . $fileToProcessPath);

        $recordsChunk = [];
        $grandTotalRecordsProcessed = 0;
        $grandTotalNotFoundCount = 0;
            
        try {
            $csv = Reader::createFromPath(Storage::path($fileToProcessPath), 'r');
            $csv->setHeaderOffset(0);
            $csv->setEscape('');
            
            // Assuming 'file_number' or a similar consistently present field can be used to filter empty rows if necessary.
            // For now, processing all records.
            $fileRecordsIterator = $csv->getRecords();

            $recordsReadFromFile = 0;
            foreach ($fileRecordsIterator as $record) {
                // Basic check for empty record, adjust if a specific key is more reliable
                if (empty(array_filter($record))) continue;

                $recordsChunk[] = $record;
                $recordsReadFromFile++;

                if (count($recordsChunk) >= self::MAX_RECORDS_IN_MEMORY_CHUNK) {
                    $this->command->info("Processing a chunk of " . count($recordsChunk) . " records from " . basename($fileToProcessPath) . "...");
                    $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk);
                    $grandTotalRecordsProcessed += $chunkStats['processed'];
                    $grandTotalNotFoundCount += $chunkStats['notFound'];
                    $recordsChunk = []; // Clear the chunk
                    $this->command->info("Chunk processed. Total records processed so far: {$grandTotalRecordsProcessed}. Locations not found so far: {$grandTotalNotFoundCount}.");
                }
            }
            $this->command->info("Finished reading {$recordsReadFromFile} records from " . basename($fileToProcessPath) . ".");

        } catch (\Exception $e) {
            $this->command->error("Error reading or preparing records from file: " . basename($fileToProcessPath) . " - " . $e->getMessage());
        }
        

        // Process any remaining records in the last chunk
        if (!empty($recordsChunk)) {
            $this->command->info("Processing the final chunk of " . count($recordsChunk) . " records...");
            $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk);
            $grandTotalRecordsProcessed += $chunkStats['processed'];
            $grandTotalNotFoundCount += $chunkStats['notFound'];
            $this->command->info("Final chunk processed.");
        }

        $this->command->info("Cambridge Crime Data Merge Seeder finished. Total records processed: {$grandTotalRecordsProcessed}. Total locations not found: {$grandTotalNotFoundCount}.");
    }

    private function loadAddressData(): void
    {
        $this->command->info("[Merge] Loading Cambridge address data into cache...");
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
            $streetNumberNumeric = intval($address->street_number);

            $this->addressCache[$normalizedStName][] = [
                'number' => $streetNumberNumeric,
                'original_number' => $address->street_number,
                'latitude' => (float)$address->latitude,
                'longitude' => (float)$address->longitude,
            ];
        }

        foreach ($this->addressCache as $streetName => $addressList) {
            usort($this->addressCache[$streetName], function ($a, $b) {
                return $a['number'] <=> $b['number'];
            });
        }
        $this->command->info("[Merge] Finished loading " . count($addresses) . " addresses into cache, grouped by " . count($this->addressCache) . " unique street names.");
    }

    private function loadIntersectionData(): void
    {
        $this->command->info("[Merge] Loading Cambridge intersection data into cache...");
        $intersections = DB::table('cambridge_intersections')
            ->select('intersection', 'latitude', 'longitude')
            ->whereNotNull('intersection')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($intersections as $intersection) {
            $this->intersectionCache[strtolower($intersection->intersection)] = [ // Assuming intersection name is already normalized and sorted in DB
                'latitude' => (float)$intersection->latitude,
                'longitude' => (float)$intersection->longitude,
            ];
        }
        $this->command->info("[Merge] Finished loading " . count($intersections) . " intersections into cache.");
    }


    private function normalizeStreetName(string $streetName): string
    {
        $processedName = strtoupper(trim($streetName));
        $processedName = preg_replace('/\s+/', ' ', $processedName); 
        // Remove "THE " from the beginning
        $processedName = preg_replace('/^THE\s+/', '', $processedName);

        foreach (self::STREET_ABBREVIATIONS as $search => $replace) {
            $processedName = preg_replace('/\b' . preg_quote($search, '/') . '\b/i', $replace, $processedName);
        }
        $processedName = rtrim($processedName, '.'); 
        return trim($processedName);
    }

    private function normalizeAndLookupIntersection(string $intersectionQueryString): ?array
    {
        $this->command->comment("---> [Merge] Attempting Intersection Lookup for: '{$intersectionQueryString}'");
        $logBuffer = [];

        $parts = preg_split('/\s+&\s+|\s+AND\s+/i', $intersectionQueryString, 2, PREG_SPLIT_NO_EMPTY);

        if (count($parts) !== 2) {
            $logBuffer[] = "     [Merge] Could not parse '{$intersectionQueryString}' into two distinct street names.";
            // $this->command->warn(implode("\n", $logBuffer)); // Can be too verbose
            return null;
        }
        $street1_original = trim($parts[0]);
        $street2_original = trim($parts[1]);
        $street1_processed = $this->normalizeStreetName($street1_original);
        $street2_processed = $this->normalizeStreetName($street2_original);

        $streetsForPrimaryLookup = [$street1_processed, $street2_processed];
        sort($streetsForPrimaryLookup, SORT_STRING | SORT_FLAG_CASE); 
        $primaryLookupString = strtolower($streetsForPrimaryLookup[0] . ' & ' . $streetsForPrimaryLookup[1]);
        
        if (isset($this->intersectionCache[$primaryLookupString])) {
            // $this->command->info("     [Merge] SUCCESS (Primary Cache Match): '{$intersectionQueryString}' found as '{$primaryLookupString}'.");
            return $this->intersectionCache[$primaryLookupString];
        }
        $logBuffer[] = "     [Merge] Primary cache lookup FAILED for '{$primaryLookupString}'.";
        
        $secondaryLookupString = strtolower($streetsForPrimaryLookup[1] . ' & ' . $streetsForPrimaryLookup[0]);
        if ($primaryLookupString !== $secondaryLookupString && isset($this->intersectionCache[$secondaryLookupString])) {
            // $this->command->info("     [Merge] SUCCESS (Secondary Cache Match - swapped): '{$intersectionQueryString}' found as '{$secondaryLookupString}'.");
            return $this->intersectionCache[$secondaryLookupString];
        }
        if ($primaryLookupString !== $secondaryLookupString) {
            $logBuffer[] = "     [Merge] Secondary cache lookup FAILED for '{$secondaryLookupString}'.";
        }

        if (!empty($street1_processed)) {
            $cachedAddressesStreet1 = $this->addressCache[strtolower($street1_processed)] ?? [];
            if (!empty($cachedAddressesStreet1)) {
                $addressStreet1 = $cachedAddressesStreet1[0]; // Lowest address
                // $this->command->info("     [Merge] SUCCESS (Street 1 Fallback - Cache): Used lowest address on '{$street1_processed}'.");
                return ['latitude' => $addressStreet1['latitude'], 'longitude' => $addressStreet1['longitude']];
            }
            $logBuffer[] = "     [Merge] Street 1 fallback (Cache) FAILED for '{$street1_processed}'.";
        }

        if (!empty($street2_processed)) {
            $cachedAddressesStreet2 = $this->addressCache[strtolower($street2_processed)] ?? [];
            if (!empty($cachedAddressesStreet2)) {
                $addressStreet2 = $cachedAddressesStreet2[0]; // Lowest address
                // $this->command->info("     [Merge] SUCCESS (Street 2 Fallback - Cache): Used lowest address on '{$street2_processed}'.");
                return ['latitude' => $addressStreet2['latitude'], 'longitude' => $addressStreet2['longitude']];
            }
            $logBuffer[] = "     [Merge] Street 2 fallback (Cache) FAILED for '{$street2_processed}'.";
        }
        
        // $this->command->warn(implode("\n", $logBuffer)); // Can be too verbose
        // $this->command->warn("     [Merge] All intersection lookup strategies FAILED for '{$intersectionQueryString}'.");
        return null;
    }

    private function parseCrimeLocationAddress(string $locationString): ?array
    {
        // Regex to capture number and street part, handles "BLOCK" prefix/suffix.
        if (preg_match('/^(\d+[A-Z]?(-\d+[A-Z]?)?(\s+BLOCK)?)\s+(.*)$/i', $locationString, $matches) ||
            preg_match('/^(BLOCK\s+\d+[A-Z]?(-\d+[A-Z]?)?)\s+(.*)$/i', $locationString, $matches) || 
            preg_match('/^(\d+[A-Z]?(-\d+[A-Z]?)?)\s+(.*)$/i', $locationString, $matches)
           ) {
            $numberPart = trim($matches[1]); 
            $rawStreetNamePart = trim(end($matches)); 

            $numericStreetNumberToMatch = intval(preg_replace('/(\s*BLOCK\s*)/i', '', $numberPart));
            $normalizedStreetName = $this->normalizeStreetName($rawStreetNamePart);
            
            if (!empty($normalizedStreetName)) { 
                return [
                    'number' => $numericStreetNumberToMatch, 
                    'name' => $normalizedStreetName,         
                    'original_number_part' => $numberPart   
                ];
            }
        }
        // $this->command->comment(" -> [Merge] Could not parse address: '{$locationString}' into number and street."); // Can be too verbose
        return null;
    }
    
    private function parseReportDate($dateString) // New method for 'date_of_report'
    {
        $dateString = trim($dateString);
        if (!$dateString) return null;
        try {
            // Assuming date_of_report is also in 'm/d/Y H:i' or a format Carbon can parse
            // If it's just a date, Carbon::parse should handle it.
            // If it has a specific format like "YYYY-MM-DDTHH:MM:SS.mmm", adjust accordingly.
            // For "01/20/2009" style dates from CSV, Carbon::parse might be okay,
            // but createFromFormat might be safer if format is fixed.
            // Let's assume it's a date that Carbon can parse directly or 'm/d/Y'
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateString)) { // Matches m/d/Y
                return Carbon::createFromFormat('m/d/Y', $dateString)->startOfDay();
            }
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            $this->command->warn("[Merge] Could not parse report date: {$dateString}");
            return null;
        }
    }

    private function parseCrimeTimestamp($timeString) // Renamed from parseCrimeTime for clarity
    {
        $timeString = trim($timeString);
        if (!$timeString) return null;
        try {
            // Expected format "m/d/Y H:i", e.g., "01/18/2009 22:00"
            return Carbon::createFromFormat('m/d/Y H:i', $timeString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Log the problematic string along with the warning
            $this->command->warn("[Merge] Could not parse crime timestamp: '{$timeString}'. Error: " . $e->getMessage());
            return null;
        }
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

            $reportDateCarbon = $this->parseReportDate($record['date_of_report'] ?? null);
            $occurred_on_date_main = $reportDateCarbon ? $reportDateCarbon->format('Y-m-d H:i:s') : null;

            $timeField = trim($record['crime_date_time'] ?? '');
            $crime_start_val = null;
            $crime_end_val = null; 

            if (strpos($timeField, ' - ') !== false) {
                [$startPart, $endPart] = array_map('trim', explode(' - ', $timeField, 2));
                $crime_start_val = $this->parseCrimeTimestamp($startPart);
                if ($crime_start_val && !empty($endPart)) {
                    if (strpos($endPart, '/') === false && preg_match('/^\d{1,2}:\d{2}$/', $endPart)) {
                        $startDateComponent = '';
                        if (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})\s/', $startPart, $dateMatches)) {
                            $startDateComponent = $dateMatches[1];
                        }
                        if (!empty($startDateComponent)) {
                            $fullEndPart = $startDateComponent . ' ' . $endPart;
                            $crime_end_val = $this->parseCrimeTimestamp($fullEndPart);
                        } else {
                            $crime_end_val = null;
                        }
                    } else {
                        $crime_end_val = $this->parseCrimeTimestamp($endPart);
                    }
                } else if (empty($endPart) && $crime_start_val) {
                    $crime_end_val = $crime_start_val;
                }
            } else if (!empty($timeField)) {
                $crime_start_val = $this->parseCrimeTimestamp($timeField);
                $crime_end_val = $crime_start_val; 
            }
            
            $raw_location = trim($record['location'] ?? '');
            $coords = ['latitude' => null, 'longitude' => null];
            $street_for_db = null;
            
            if (!empty($raw_location)) {
                $location_for_db_lookup = preg_replace('/, Cambridge, MA$/i', '', $raw_location);
                $location_for_db_lookup = trim($location_for_db_lookup);

                if (strpos($location_for_db_lookup, '&') !== false || stripos($location_for_db_lookup, ' AND ') !== false) {
                    $normalized_coords = $this->normalizeAndLookupIntersection($location_for_db_lookup);
                    if ($normalized_coords) {
                        $coords = $normalized_coords;
                    }
                    $street_for_db = $this->normalizeStreetName($location_for_db_lookup); // Normalize the intersection string itself
                } else {
                    $parsedAddressInfo = $this->parseCrimeLocationAddress($location_for_db_lookup);
                    if ($parsedAddressInfo) {
                        $crimeStreetNumber = $parsedAddressInfo['number'];
                        $crimeStreetName = $parsedAddressInfo['name'];
                        $street_for_db = $crimeStreetName;

                        $cachedAddressesOnStreet = $this->addressCache[strtolower($crimeStreetName)] ?? [];
                        if (!empty($cachedAddressesOnStreet)) {
                            $closestMatch = null; $minDifference = PHP_INT_MAX;
                            foreach ($cachedAddressesOnStreet as $cachedAddr) {
                                if ($cachedAddr['number'] >= 0) { // Allow 0 for block addresses
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
                    // Fallback if address parsing failed or no match, try lowest address on street
                    if (empty($coords['latitude']) && $parsedAddressInfo && !empty($parsedAddressInfo['name'])) {
                        $cachedStreetAddresses = $this->addressCache[strtolower($parsedAddressInfo['name'])] ?? [];
                        if (!empty($cachedStreetAddresses)) {
                            $lowestAddress = $cachedStreetAddresses[0]; // Cache is sorted
                            $coords = ['latitude' => $lowestAddress['latitude'], 'longitude' => $lowestAddress['longitude']];
                            // $this->command->comment(" -> [Merge] Fallback to lowest address on street '{$parsedAddressInfo['name']}'.");
                        }
                    }
                }
                if (empty($coords['latitude'])) {
                    $notFoundInChunk++;
                }
            } else {
                $notFoundInChunk++;
            }

            $incident_number = 'CAM-' . ($record['file_number'] ?? ('UNKNOWN-' . ($grandTotalRecordsProcessed + $recordsProcessedInChunk))); // Ensure more unique unknown ID

            $dataBatch[] = [
                'incident_number'     => $incident_number,
                'offense_code'        => null,
                'offense_code_group'  => null,
                'offense_description' => $record['crime'] ?? null,
                'district'            => $record['reporting_area'] ?? null, 
                'reporting_area'      => $record['reporting_area'] ?? null,
                'shooting'            => false,
                'occurred_on_date'    => $occurred_on_date_main,
                'year'                => $reportDateCarbon ? $reportDateCarbon->year : null,
                'month'               => $reportDateCarbon ? $reportDateCarbon->month : null,
                'day_of_week'         => $reportDateCarbon ? $reportDateCarbon->format('l') : null,
                'hour'                => $reportDateCarbon ? $reportDateCarbon->hour : null,
                'ucr_part'            => null,
                'street'              => $street_for_db,
                'lat'                 => $coords['latitude'] ? round((float)$coords['latitude'], 7) : null,
                'long'                => $coords['longitude'] ? round((float)$coords['longitude'], 7) : null,
                'location'            => $raw_location,
                'crime_start_time'    => $crime_start_val,
                'crime_end_time'      => $crime_end_val,
                'crime_details'       => null, // This seeder does not have 'crime_details' from source
                'created_at'          => now(),
                'updated_at'          => now(),
            ];

            if (count($dataBatch) >= self::BATCH_SIZE) {
                $this->insertOrUpdateBatch($dataBatch);
                $this->command->info("... [Merge] upserted " . count($dataBatch) . " records to DB (processed {$currentRecordIndexInChunk}/" . count($rawCsvRecords) . " in current chunk) ...");
                $dataBatch = [];
            }
        }

        if (!empty($dataBatch)) {
            $this->insertOrUpdateBatch($dataBatch);
            $this->command->info("... [Merge] upserted final " . count($dataBatch) . " records to DB for this chunk ...");
        }
        
        return ['processed' => $recordsProcessedInChunk, 'notFound' => $notFoundInChunk];
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        if (empty($dataBatch)) return;

        $updateColumns = [
            'offense_code', 'offense_code_group', 'offense_description', 'district', 
            'reporting_area', 'shooting', 'occurred_on_date', 'year', 'month', 
            'day_of_week', 'hour', 'ucr_part', 'street', 'lat', 'long', 'location', 
            'crime_start_time', 'crime_end_time', // Add new columns here
            'updated_at'
        ];

        DB::table('crime_data')->upsert(
            $dataBatch,
            ['incident_number'],
            $updateColumns
        );
    }
}
