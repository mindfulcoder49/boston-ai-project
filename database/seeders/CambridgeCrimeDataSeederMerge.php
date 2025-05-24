<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;

class CambridgeCrimeDataSeederMerge extends Seeder
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
        /* Add other abbreviations from CambridgeCrimeDataSeeder if needed
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
            $this->command->info("Processing Cambridge crime data file for merge: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No file found for Cambridge crime data merge.");
        }
    }

    private function normalizeStreetName(string $streetName): string
    {
        $processedName = strtoupper(trim($streetName));
        $processedName = preg_replace('/\s+/', ' ', $processedName); 

        foreach (self::STREET_ABBREVIATIONS as $search => $replace) {
            $processedName = preg_replace('/\b' . preg_quote($search, '/') . '\b/', $replace, $processedName);
        }
        $processedName = rtrim($processedName, '.'); 
        return trim($processedName);
    }

    private function normalizeAndLookupIntersection(string $intersectionQueryString): ?array
    {
        // This method is copied from CambridgeCrimeDataSeeder
        // It attempts to find coordinates for an intersection.
        // For brevity, its implementation is not repeated here but should be identical
        // to the one in CambridgeCrimeDataSeeder.php
        // ... (Implementation from CambridgeCrimeDataSeeder::normalizeAndLookupIntersection)
        // For the purpose of this example, we'll use a simplified version.
        // In a real scenario, copy the full method from CambridgeCrimeDataSeeder.php

        $this->command->info("---> [Merge] Attempting Intersection Lookup for: '{$intersectionQueryString}'");
        $logBuffer = [];

        $parts = explode(' & ', $intersectionQueryString);
        if (count($parts) !== 2) {
            $parts = preg_split('/\s+AND\s+/i', $intersectionQueryString, 2);
            if (count($parts) !== 2) {
                $logBuffer[] = "     [Merge] Could not parse '{$intersectionQueryString}' into two distinct street names.";
                $this->command->warn(implode("\n", $logBuffer));
                return null;
            }
        }
        $street1_original = trim($parts[0]);
        $street2_original = trim($parts[1]);
        $street1_processed = $this->normalizeStreetName($street1_original);
        $street2_processed = $this->normalizeStreetName($street2_original);

        $streetsForPrimaryLookup = [$street1_processed, $street2_processed];
        sort($streetsForPrimaryLookup, SORT_STRING | SORT_FLAG_CASE);
        $primaryLookupString = $streetsForPrimaryLookup[0] . ' & ' . $streetsForPrimaryLookup[1];
        
        $lookup = DB::table('cambridge_intersections')
            ->where(DB::raw('LOWER(intersection)'), strtolower($primaryLookupString))
            ->first();

        if ($lookup) {
            $this->command->info("     [Merge] SUCCESS (Primary Match): '{$intersectionQueryString}' found as '{$primaryLookupString}'.");
            return ['latitude' => $lookup->latitude, 'longitude' => $lookup->longitude];
        }
        // Add other fallbacks from CambridgeCrimeDataSeeder if needed...
        $logBuffer[] = "     [Merge] Primary lookup FAILED for '{$primaryLookupString}'.";
        
        // Fallback to lowest address on street1
        if (!empty($street1_processed)) {
            $addressStreet1 = DB::table('cambridge_addresses')
                ->where(DB::raw('LOWER(stname)'), strtolower($street1_processed))
                ->whereNotNull('street_number')->where('street_number', 'REGEXP', '^[0-9]+$')
                ->orderByRaw('CAST(street_number AS UNSIGNED) ASC')
                ->select('latitude', 'longitude')->first();
            if ($addressStreet1) {
                 $this->command->info("     [Merge] SUCCESS (Street 1 Fallback): Used lowest address on '{$street1_processed}'.");
                return ['latitude' => $addressStreet1->latitude, 'longitude' => $addressStreet1->longitude];
            }
        }
        // Fallback to lowest address on street2
        if (!empty($street2_processed)) {
            $addressStreet2 = DB::table('cambridge_addresses')
                ->where(DB::raw('LOWER(stname)'), strtolower($street2_processed))
                ->whereNotNull('street_number')->where('street_number', 'REGEXP', '^[0-9]+$')
                ->orderByRaw('CAST(street_number AS UNSIGNED) ASC')
                ->select('latitude', 'longitude')->first();
            if ($addressStreet2) {
                $this->command->info("     [Merge] SUCCESS (Street 2 Fallback): Used lowest address on '{$street2_processed}'.");
                return ['latitude' => $addressStreet2->latitude, 'longitude' => $addressStreet2->longitude];
            }
        }

        $this->command->warn(implode("\n", $logBuffer));
        $this->command->warn("     [Merge] All intersection lookup strategies FAILED for '{$intersectionQueryString}'.");
        return null;
    }

    private function parseCrimeLocationAddress(string $locationString): ?array
    {
        // This method is copied from CambridgeCrimeDataSeeder
        // It parses an address string into number and street name.
        // For brevity, its implementation is not repeated here but should be identical
        // to the one in CambridgeCrimeDataSeeder.php
        // ... (Implementation from CambridgeCrimeDataSeeder::parseCrimeLocationAddress)
        if (preg_match('/^(\d+)\s+(.*)$/', $locationString, $matches)) {
            $numberPart = $matches[1];
            $rawStreetNamePart = $matches[2];
            $numericStreetNumber = intval($numberPart);
            $normalizedStreetName = $this->normalizeStreetName($rawStreetNamePart);
            if ($numericStreetNumber > -1 && !empty($normalizedStreetName)) {
                return [
                    'number' => $numericStreetNumber,
                    'name' => $normalizedStreetName,
                    'original_number_part' => $numberPart
                ];
            }
        }
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

                // Primary date fields from date_of_report
                $reportDateCarbon = $this->parseReportDate($record['date_of_report'] ?? null);
                $occurred_on_date_main = $reportDateCarbon ? $reportDateCarbon->format('Y-m-d H:i:s') : null;

                // Crime start and end times from crime_date_time
                $timeField = trim($record['crime_date_time'] ?? '');
                $crime_start_val = null;
                $crime_end_val = null; 

                if (strpos($timeField, ' - ') !== false) {
                    [$startPart, $endPart] = array_map('trim', explode(' - ', $timeField, 2));
                    
                    $crime_start_val = $this->parseCrimeTimestamp($startPart);

                    // If start time was parsed successfully and end part exists
                    if ($crime_start_val && !empty($endPart)) {
                        // Check if endPart is just a time (e.g., "HH:MM" or "H:MM")
                        if (strpos($endPart, '/') === false && preg_match('/^\d{1,2}:\d{2}$/', $endPart)) {
                            // Extract date from startPart
                            $startDateComponent = '';
                            if (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})\s/', $startPart, $dateMatches)) {
                                $startDateComponent = $dateMatches[1];
                            }

                            if (!empty($startDateComponent)) {
                                $fullEndPart = $startDateComponent . ' ' . $endPart;
                                $crime_end_val = $this->parseCrimeTimestamp($fullEndPart);
                            } else {
                                $this->command->warn("[Merge] Could not extract date from start part '{$startPart}' to construct full end time for '{$endPart}'.");
                                $crime_end_val = null; // Or attempt to parse $endPart as is if it might be a full date itself
                            }
                        } else {
                            // endPart seems to be a full date-time or an unparseable format
                            $crime_end_val = $this->parseCrimeTimestamp($endPart);
                        }
                    } else if (empty($endPart) && $crime_start_val) {
                        // If there's a " - " but end part is empty, treat end as start
                        $crime_end_val = $crime_start_val;
                    }

                } else if (!empty($timeField)) {
                    $crime_start_val = $this->parseCrimeTimestamp($timeField);
                    $crime_end_val = $crime_start_val; 
                }
                
                $raw_location = trim($record['location'] ?? '');
                $coords = ['latitude' => null, 'longitude' => null];
                $street_for_db = null;
                $parsedAddressInfo = null;

                if (!empty($raw_location)) {
                    $location_for_db_lookup = preg_replace('/, Cambridge, MA$/i', '', $raw_location);
                    $location_for_db_lookup = trim($location_for_db_lookup);

                    if (strpos($location_for_db_lookup, '&') !== false || stripos($location_for_db_lookup, ' AND ') !== false) {
                        $normalized_coords = $this->normalizeAndLookupIntersection($location_for_db_lookup);
                        if ($normalized_coords) {
                            $coords = $normalized_coords;
                        }
                        $street_for_db = $location_for_db_lookup; // Use cleaned intersection string
                    } else {
                        $parsedAddressInfo = $this->parseCrimeLocationAddress($location_for_db_lookup);
                        if ($parsedAddressInfo) {
                            $crimeStreetNumber = $parsedAddressInfo['number'];
                            $crimeStreetName = $parsedAddressInfo['name'];
                            $street_for_db = $crimeStreetName; // Use parsed street name

                            $dbAddresses = DB::table('cambridge_addresses')
                                ->where(DB::raw('LOWER(stname)'), strtolower($crimeStreetName))
                                ->select('street_number', 'latitude', 'longitude')
                                ->get();

                            if ($dbAddresses->isNotEmpty()) {
                                $closestMatch = null; $minDifference = PHP_INT_MAX;
                                foreach ($dbAddresses as $dbAddr) {
                                    $dbStreetNumberNumeric = intval($dbAddr->street_number);
                                    if ($dbStreetNumberNumeric > 0) {
                                        $difference = abs($crimeStreetNumber - $dbStreetNumberNumeric);
                                        if ($difference < $minDifference) {
                                            $minDifference = $difference; $closestMatch = $dbAddr;
                                        }
                                    }
                                }
                                if ($closestMatch) $coords = ['latitude' => $closestMatch->latitude, 'longitude' => $closestMatch->longitude];
                            }
                        }
                        // Fallback for address if coords not found yet (simplified from CambridgeCrimeDataSeeder)
                        if (empty($coords['latitude']) && !empty($location_for_db_lookup)) {
                             $lookup = DB::table('cambridge_addresses')
                                ->where(DB::raw('LOWER(full_addr)'), 'LIKE', strtolower($location_for_db_lookup) . '%')
                                ->first();
                            if ($lookup) $coords = ['latitude' => $lookup->latitude, 'longitude' => $lookup->longitude];
                        }
                         if (empty($coords['latitude']) && $parsedAddressInfo && !empty($parsedAddressInfo['name'])) {
                            $intersectionFallback = DB::table('cambridge_intersections')
                                ->where(DB::raw('LOWER(intersection)'), 'LIKE', '%' . strtolower($parsedAddressInfo['name']) . '%')
                                ->select('latitude', 'longitude')->first();
                            if ($intersectionFallback) $coords = ['latitude' => $intersectionFallback->latitude, 'longitude' => $intersectionFallback->longitude];
                        }
                    }
                    if (empty($coords['latitude'])) $notFoundCount++;
                } else {
                    $notFoundCount++;
                }

                $incident_number = 'CAM-' . ($record['file_number'] ?? ('UNKNOWN-' . $progress));

                $dataBatch[] = [
                    'incident_number'     => $incident_number,
                    'offense_code'        => null,
                    'offense_code_group'  => null,
                    'offense_description' => $record['crime'] ?? null,
                    'district'            => $record['reporting_area'] ?? null, 
                    'reporting_area'      => $record['reporting_area'] ?? null,
                    'shooting'            => false,
                    'occurred_on_date'    => $occurred_on_date_main, // Based on date_of_report
                    'year'                => $reportDateCarbon ? $reportDateCarbon->year : null,
                    'month'               => $reportDateCarbon ? $reportDateCarbon->month : null,
                    'day_of_week'         => $reportDateCarbon ? $reportDateCarbon->format('l') : null,
                    'hour'                => $reportDateCarbon ? $reportDateCarbon->hour : null, // Hour of report date
                    'ucr_part'            => null,
                    'street'              => $street_for_db,
                    'lat'                 => $coords['latitude'] ? round((float)$coords['latitude'], 7) : null,
                    'long'                => $coords['longitude'] ? round((float)$coords['longitude'], 7) : null,
                    'location'            => $raw_location,
                    'crime_start_time'    => $crime_start_val, // New field
                    'crime_end_time'      => $crime_end_val,   // New field
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("[Merge] Processed {$progress} records... ({$notFoundCount} locations not found so far)");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("[Merge] File processed: " . basename($filePath) . ". Total locations not found: {$notFoundCount}");
        } catch (\Exception $e) {
            $this->command->error("[Merge] Error processing crime data file: " . basename($filePath) . " - " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
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
