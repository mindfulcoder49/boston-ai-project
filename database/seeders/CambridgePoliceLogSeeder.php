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
    private const BATCH_SIZE = 500;
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

    public function run(): void
    {
        $this->command->info("Starting Cambridge Police Log Seeder...");
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

        foreach ($csvFiles as $file) {
            $this->command->info("Processing file: " . $file);
            $this->processFile(Storage::path($file));
        }

        $this->command->info("Cambridge Police Log Seeder finished.");
    }

    private function processFile(string $filePath): void
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setEscape(''); // Assuming default escape, adjust if needed
            
            // Filter out empty rows if any, based on a key column like 'file_number'
            $stmt = Statement::create()->where(fn (array $record) => !empty($record['file_number']));
            $records = $stmt->process($csv);

            $dataBatch = [];
            $progress = 0;
            $totalRecordsInFile = iterator_count($csv->getRecords()); // Count before statement processing for total
            $processedInFile = 0;
            $notFoundCount = 0;

            $this->command->info("Total records in file " . basename($filePath) . ": {$totalRecordsInFile}");

            foreach ($records as $record) {
                $progress++;
                $processedInFile++;

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
                    $crime_start_val = $occurred_on_date_main; // Assume start time is the given time
                    $crime_end_val = $occurred_on_date_main;   // Assume end time is the same
                } else {
                    $this->command->warn("Could not parse crime_date_time '{$crimeDateTimeStr}' for record with file_number '{$record['file_number']}'. Skipping date fields.");
                }
                
                $raw_location = trim($record['location'] ?? '');
                $coords = ['latitude' => null, 'longitude' => null];
                $street_for_db = null;

                if (!empty($raw_location)) {
                    // The 'location' from CSV is already somewhat processed by the download script
                    // It's either "BLOCK STREET" or "STREET1 & STREET2"
                    if (strpos($raw_location, '&') !== false || stripos($raw_location, ' AND ') !== false) {
                        $normalized_coords = $this->normalizeAndLookupIntersection($raw_location);
                        if ($normalized_coords) {
                            $coords = $normalized_coords;
                        }
                        $street_for_db = $this->normalizeStreetName($raw_location); // Normalize the intersection string itself
                    } else {
                        $parsedAddressInfo = $this->parseCrimeLocationAddress($raw_location);
                        if ($parsedAddressInfo) {
                            $crimeStreetNumber = $parsedAddressInfo['number'];
                            $crimeStreetName = $parsedAddressInfo['name']; // This is already normalized
                            $street_for_db = $crimeStreetName;

                            $dbAddresses = DB::table('cambridge_addresses')
                                ->where(DB::raw('LOWER(stname)'), strtolower($crimeStreetName))
                                ->select('street_number', 'latitude', 'longitude')
                                ->get();

                            if ($dbAddresses->isNotEmpty()) {
                                $closestMatch = null; $minDifference = PHP_INT_MAX;
                                foreach ($dbAddresses as $dbAddr) {
                                    $dbStreetNumberNumeric = intval($dbAddr->street_number);
                                    if ($dbStreetNumberNumeric > 0) { // Ensure valid numeric street number
                                        $difference = abs($crimeStreetNumber - $dbStreetNumberNumeric);
                                        if ($difference < $minDifference) {
                                            $minDifference = $difference; $closestMatch = $dbAddr;
                                        } elseif ($difference === $minDifference && $closestMatch && $dbStreetNumberNumeric < intval($closestMatch->street_number)) {
                                            // Prefer lower number in case of tie, or any other consistent rule
                                            $closestMatch = $dbAddr;
                                        }
                                    }
                                }
                                if ($closestMatch) {
                                     $coords = ['latitude' => $closestMatch->latitude, 'longitude' => $closestMatch->longitude];
                                }
                            }
                        }
                        // Fallback if address parsing failed or no match, try raw location if not already an intersection
                        if (empty($coords['latitude'])) {
                            $this->command->comment(" -> Location '{$raw_location}' not directly matched. Attempting broader search or fallback for file_number '{$record['file_number']}'.");
                            // Add more sophisticated fallbacks if needed, e.g., lowest address on street if only street name is good
                            if ($parsedAddressInfo && !empty($parsedAddressInfo['name'])) {
                                $addressStreet = DB::table('cambridge_addresses')
                                    ->where(DB::raw('LOWER(stname)'), strtolower($parsedAddressInfo['name']))
                                    ->whereNotNull('street_number')->where('street_number', 'REGEXP', '^[0-9]+$')
                                    ->orderByRaw('CAST(street_number AS UNSIGNED) ASC')
                                    ->select('latitude', 'longitude')->first();
                                if ($addressStreet) {
                                    $coords = ['latitude' => $addressStreet->latitude, 'longitude' => $addressStreet->longitude];
                                    $this->command->comment(" -> Fallback to lowest address on street '{$parsedAddressInfo['name']}'.");
                                }
                            }
                        }
                    }
                    if (empty($coords['latitude'])) {
                        $notFoundCount++;
                        $this->command->warn(" -> Location '{$raw_location}' NOT FOUND for file_number '{$record['file_number']}'.");
                    } else {
                         $this->command->info(" -> Location '{$raw_location}' FOUND for file_number '{$record['file_number']}' as {$coords['latitude']},{$coords['longitude']}. Street: {$street_for_db}");
                    }
                } else {
                    $notFoundCount++;
                    $this->command->warn(" -> Empty location field for file_number '{$record['file_number']}'.");
                }

                $incident_number = 'CPL-' . ($record['file_number'] ?? ('UNKNOWN-' . $progress));
                $offense_description_raw = $record['crime'] ?? null;
                $offense_description_decoded = $offense_description_raw ? html_entity_decode($offense_description_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;


                $dataBatch[] = [
                    'incident_number'     => $incident_number,
                    'offense_code'        => null, // Not in logs
                    'offense_code_group'  => null, // Not in logs
                    'offense_description' => $offense_description_decoded,
                    'district'            => null, // Not in logs
                    'reporting_area'      => null, // Not in logs
                    'shooting'            => false, // Assume false unless specified
                    'occurred_on_date'    => $occurred_on_date_main,
                    'year'                => $year,
                    'month'               => $month,
                    'day_of_week'         => $day_of_week,
                    'hour'                => $hour,
                    'ucr_part'            => null, // Not in logs
                    'street'              => $street_for_db,
                    'lat'                 => $coords['latitude'] ? round((float)$coords['latitude'], 7) : null,
                    'long'                => $coords['longitude'] ? round((float)$coords['longitude'], 7) : null,
                    'location'            => $raw_location, // Original location string from CSV
                    'crime_start_time'    => $crime_start_val,
                    'crime_end_time'      => $crime_end_val,
                    'crime_details'           => trim($record['crime_details'] ?? null), // New field for narrative
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} records from " . basename($filePath) . "... ({$notFoundCount} locations not found so far in this file)");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("Finished processing file: " . basename($filePath) . ". Total records processed in file: {$processedInFile}. Total locations not found in this file: {$notFoundCount}");

        } catch (\Exception $e) {
            $this->command->error("Error processing file: " . basename($filePath) . " - " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
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
        $primaryLookupString = $streetsForPrimaryLookup[0] . ' & ' . $streetsForPrimaryLookup[1];
        
        $lookup = DB::table('cambridge_intersections')
            ->where(DB::raw('LOWER(intersection)'), strtolower($primaryLookupString))
            ->first();

        if ($lookup) {
            $this->command->info("     SUCCESS (Primary Match): '{$intersectionQueryString}' found as '{$primaryLookupString}'.");
            return ['latitude' => $lookup->latitude, 'longitude' => $lookup->longitude];
        }
        $logBuffer[] = "     Primary lookup FAILED for '{$primaryLookupString}'.";
        
        // Fallback: Try swapping order if initial sort didn't match (unlikely if DB is also sorted, but a safeguard)
        $secondaryLookupString = $streetsForPrimaryLookup[1] . ' & ' . $streetsForPrimaryLookup[0];
        if ($primaryLookupString !== $secondaryLookupString) {
            $lookup = DB::table('cambridge_intersections')
                ->where(DB::raw('LOWER(intersection)'), strtolower($secondaryLookupString))
                ->first();
            if ($lookup) {
                $this->command->info("     SUCCESS (Secondary Match - swapped): '{$intersectionQueryString}' found as '{$secondaryLookupString}'.");
                return ['latitude' => $lookup->latitude, 'longitude' => $lookup->longitude];
            }
            $logBuffer[] = "     Secondary lookup FAILED for '{$secondaryLookupString}'.";
        }

        // Fallback to lowest address on street1
        if (!empty($street1_processed)) {
            $addressStreet1 = DB::table('cambridge_addresses')
                ->where(DB::raw('LOWER(stname)'), strtolower($street1_processed))
                ->whereNotNull('street_number')->where('street_number', 'REGEXP', '^[0-9]+$')
                ->orderByRaw('CAST(street_number AS UNSIGNED) ASC')
                ->select('latitude', 'longitude')->first();
            if ($addressStreet1) {
                 $this->command->info("     SUCCESS (Street 1 Fallback): Used lowest address on '{$street1_processed}'.");
                return ['latitude' => $addressStreet1->latitude, 'longitude' => $addressStreet1->longitude];
            }
             $logBuffer[] = "     Street 1 fallback FAILED for '{$street1_processed}'.";
        }
        // Fallback to lowest address on street2
        if (!empty($street2_processed)) {
            $addressStreet2 = DB::table('cambridge_addresses')
                ->where(DB::raw('LOWER(stname)'), strtolower($street2_processed))
                ->whereNotNull('street_number')->where('street_number', 'REGEXP', '^[0-9]+$')
                ->orderByRaw('CAST(street_number AS UNSIGNED) ASC')
                ->select('latitude', 'longitude')->first();
            if ($addressStreet2) {
                $this->command->info("     SUCCESS (Street 2 Fallback): Used lowest address on '{$street2_processed}'.");
                return ['latitude' => $addressStreet2->latitude, 'longitude' => $addressStreet2->longitude];
            }
            $logBuffer[] = "     Street 2 fallback FAILED for '{$street2_processed}'.";
        }

        $this->command->warn(implode("\n", $logBuffer));
        $this->command->warn("     All intersection lookup strategies FAILED for '{$intersectionQueryString}'.");
        return null;
    }

    private function parseCrimeLocationAddress(string $locationString): ?array
    {
        // Example: "1200 MASSACHUSETTS AVE"
        // Regex to capture number and street part
        if (preg_match('/^(\d+[A-Z]?(-\d+[A-Z]?)?)\s+(.*)$/i', $locationString, $matches)) {
            $numberPart = trim($matches[1]); // e.g., "1200", "10-12", "12A"
            $rawStreetNamePart = trim($matches[3]);
            
            // For simplicity in matching, we'll use the first number if it's a range like "10-12"
            $numericStreetNumberToMatch = intval($numberPart); // intval("10-12") is 10, intval("12A") is 12

            $normalizedStreetName = $this->normalizeStreetName($rawStreetNamePart);
            
            if ($numericStreetNumberToMatch >= 0 && !empty($normalizedStreetName)) { // Allow 0 block
                return [
                    'number' => $numericStreetNumberToMatch, // The numeric part for matching
                    'name' => $normalizedStreetName,         // Normalized street name
                    'original_number_part' => $numberPart   // Original number string "1200", "10-12"
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
