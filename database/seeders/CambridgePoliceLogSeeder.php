<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Services\CambridgeAddressLookupService; // Added

class CambridgePoliceLogSeeder extends Seeder
{
    private const BATCH_SIZE = 500; // For DB upserts
    private const MAX_RECORDS_IN_MEMORY_CHUNK = 10000; // For accumulating raw CSV records before processing
    // STREET_ABBREVIATIONS removed

    // addressCache and intersectionCache removed
    private ?CambridgeAddressLookupService $addressLookupService = null; // Added

    public function run(): void
    {
        $this->command->info("Starting Cambridge Police Log Seeder...");
        
        $this->addressLookupService = new CambridgeAddressLookupService(
            $this->command,
            env('GOOGLE_GEOCODING_API_KEY')
        );
        $this->addressLookupService->loadDbCaches(); // Loads addresses and intersections into the service

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
        
        $this->addressLookupService->finalSaveGeocodeCache(); // Save geocode cache at the end

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
            }
            
            $raw_location_from_csv = trim($record['location'] ?? '');
            $coords = ['latitude' => null, 'longitude' => null];
            $street_for_db = null;
            $locationSource = 'not_provided';

            if (!empty($raw_location_from_csv)) {
                $locationInfo = $this->addressLookupService->getCoordinatesForLocation($raw_location_from_csv);
                
                $coords['latitude'] = $locationInfo['latitude'];
                $coords['longitude'] = $locationInfo['longitude'];
                $street_for_db = $locationInfo['street_for_db'];
                $locationSource = $locationInfo['source'];

                if (empty($coords['latitude']) || empty($coords['longitude'])) {
                    $notFoundInChunk++;
                    // Log if needed, e.g., $this->command->comment("Location '{$raw_location_from_csv}' not geocoded. Source: {$locationSource}. Street for DB: {$street_for_db}");
                }
            } else {
                $notFoundInChunk++;
                $locationSource = 'empty_input';
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
                'location'            => $raw_location_from_csv,
                'crime_start_time'    => $crime_start_val,
                'crime_end_time'      => $crime_end_val,
                'crime_details'       => trim($record['crime_details'] ?? null),
                'created_at'          => now(),
                'updated_at'          => now(),
                'source_city'         => 'Cambridge'
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
            'crime_start_time', 'crime_end_time', 'crime_details',
            'updated_at',
            'source_city'
        ];

        DB::table('crime_data')->upsert(
            $dataBatch,
            ['incident_number'], // Unique key(s)
            $updateColumns      // Columns to update on duplicate
        );
        $this->command->info("Upserted batch of " . count($dataBatch) . " records.");
    }
}
