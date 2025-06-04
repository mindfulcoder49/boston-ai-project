<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
use App\Services\CambridgeAddressLookupService; // Added

class CambridgeCrimeDataSeederMerge extends Seeder
{
    private const BATCH_SIZE = 500; // For DB upserts
    private const MAX_RECORDS_IN_MEMORY_CHUNK = 10000; // For accumulating raw CSV records
    // STREET_ABBREVIATIONS removed

    // addressCache and intersectionCache removed
    private ?CambridgeAddressLookupService $addressLookupService = null; // Added


    public function run()
    {
        $this->command->info("Starting Cambridge Crime Data Merge Seeder...");

        $this->addressLookupService = new CambridgeAddressLookupService(
            $this->command,
            env('GOOGLE_GEOCODING_API_KEY')
        );
        $this->addressLookupService->loadDbCaches();

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
            $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk, $grandTotalRecordsProcessed); // Pass grandTotal for unique ID
            $grandTotalRecordsProcessed += $chunkStats['processed'];
            $grandTotalNotFoundCount += $chunkStats['notFound'];
            $this->command->info("Final chunk processed.");
        }
        
        $this->addressLookupService->finalSaveGeocodeCache(); // Save geocode cache at the end

        $this->command->info("Cambridge Crime Data Merge Seeder finished. Total records processed: {$grandTotalRecordsProcessed}. Total locations not found: {$grandTotalNotFoundCount}.");
    }

    // loadAddressData method removed
    // loadIntersectionData method removed
    // normalizeStreetName method removed
    // normalizeAndLookupIntersection method removed
    // parseCrimeLocationAddress method removed
    
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

    private function processAndInsertRecordsChunk(array $rawCsvRecords, int $baseIncidentCountForUnknown = 0): array
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
            
            $raw_location_from_csv = trim($record['location'] ?? '');
            $coords = ['latitude' => null, 'longitude' => null];
            $street_for_db = null;
            $locationSource = 'not_provided';
            
            if (!empty($raw_location_from_csv)) {
                // The service's getCoordinatesForLocation handles cleaning like removing ", Cambridge, MA"
                $locationInfo = $this->addressLookupService->getCoordinatesForLocation($raw_location_from_csv);
                
                $coords['latitude'] = $locationInfo['latitude'];
                $coords['longitude'] = $locationInfo['longitude'];
                $street_for_db = $locationInfo['street_for_db'];
                $locationSource = $locationInfo['source'];

                if (empty($coords['latitude']) || empty($coords['longitude'])) {
                    $notFoundInChunk++;
                    // Log if needed
                }
            } else {
                $notFoundInChunk++;
                $locationSource = 'empty_input';
            }

            $incident_number = 'CAM-' . ($record['file_number'] ?? ('UNKNOWN-' . ($baseIncidentCountForUnknown + $recordsProcessedInChunk)));

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
                'location'            => $raw_location_from_csv, // Store original location from CSV
                'crime_start_time'    => $crime_start_val,
                'crime_end_time'      => $crime_end_val,
                'crime_details'       => null, // This seeder does not have 'crime_details' from source
                'created_at'          => now(),
                'updated_at'          => now(),
                'source_city'         => 'Cambridge'
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
            'crime_start_time', 'crime_end_time',
            'updated_at',
            'source_city'
        ];

        DB::table('crime_data')->upsert(
            $dataBatch,
            ['incident_number'],
            $updateColumns
        );
    }
}
