<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
use League\Csv\Statement;
use App\Services\CambridgeAddressLookupService;
use App\Models\CambridgeCrimeReportData; // Added for model reference

class CambridgePoliceLogSeeder extends Seeder
{
    private const BATCH_SIZE = 500; 
    private const MAX_RECORDS_IN_MEMORY_CHUNK = 10000; 
    private ?CambridgeAddressLookupService $addressLookupService = null;

    public function run(): void
    {
        $this->command->info("Starting Cambridge Police Log Seeder (targeting cambridge_crime_reports_data)...");
        
        $this->addressLookupService = new CambridgeAddressLookupService(
            $this->command,
            env('GOOGLE_GEOCODING_API_KEY')
        );
        $this->addressLookupService->loadDbCaches(); 

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
                        $recordsChunk = []; 
                        $this->command->info("Chunk processed. Grand total records processed so far: {$grandTotalRecordsProcessed}. Locations not found so far: {$grandTotalNotFoundCount}.");
                    }
                }
                $this->command->info("Finished reading {$recordsReadFromFile} records from " . basename($filePath) . ".");

            } catch (\Exception $e) {
                $this->command->error("Error reading or preparing records from file: " . basename($filePath) . " - " . $e->getMessage());
            }
        }

        if (!empty($recordsChunk)) {
            $this->command->info("Processing the final chunk of " . count($recordsChunk) . " records...");
            $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk);
            $grandTotalRecordsProcessed += $chunkStats['processed'];
            $grandTotalNotFoundCount += $chunkStats['notFound'];
            $this->command->info("Final chunk processed.");
        }
        
        $this->addressLookupService->finalSaveGeocodeCache(); 

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
            
            $fileNumberExternal = trim($record['file_number'] ?? '');
            if (empty($fileNumberExternal)) {
                $this->command->warn("[Police Log] Skipping record due to empty file_number: " . json_encode(array_slice($record, 0, 3)));
                continue;
            }
            $recordsProcessedInChunk++;


            $crimeDateTimeStr = trim($record['crime_date_time'] ?? '');
            $crimeDateTimeCarbon = $this->parseCrimeTimestamp($crimeDateTimeStr);

            $date_of_report_val = null;
            $crime_start_val = null;
            $crime_end_val = null;

            if ($crimeDateTimeCarbon) {
                $date_of_report_val = $crimeDateTimeCarbon->format('Y-m-d H:i:s'); // Or ->startOfDay() if only date is needed
                $crime_start_val = $crimeDateTimeCarbon->format('Y-m-d H:i:s');
                $crime_end_val = $crimeDateTimeCarbon->format('Y-m-d H:i:s'); // Assuming log entry is a point in time
            }
            
            $raw_location_from_csv = trim($record['location'] ?? '');
            $coords = ['latitude' => null, 'longitude' => null];
            
            if (!empty($raw_location_from_csv)) {
                $locationInfo = $this->addressLookupService->getCoordinatesForLocation($raw_location_from_csv);
                
                $coords['latitude'] = $locationInfo['latitude'];
                $coords['longitude'] = $locationInfo['longitude'];

                if (empty($coords['latitude']) || empty($coords['longitude'])) {
                    $notFoundInChunk++;
                }
            } else {
                $notFoundInChunk++;
            }

            $crime_description_raw = $record['crime'] ?? null;
            $crime_description_decoded = $crime_description_raw ? html_entity_decode($crime_description_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
            $crime_details_val = trim($record['crime_details'] ?? null); // Added

            $dataBatch[] = [
                'file_number_external'  => $fileNumberExternal,
                'date_of_report'        => $date_of_report_val,
                'crime_datetime_raw'    => ($crimeDateTimeStr === '') ? null : $crimeDateTimeStr,
                'crime_start_time'      => $crime_start_val,
                'crime_end_time'        => $crime_end_val,
                'crime'                 => $crime_description_decoded,
                'reporting_area'        => null, // Not available in typical police log CSV
                'neighborhood'          => null, // Not available in typical police log CSV
                'location_address'      => ($raw_location_from_csv === '') ? null : $raw_location_from_csv,
                'latitude'              => $coords['latitude'] ? round((float)$coords['latitude'], 7) : null,
                'longitude'             => $coords['longitude'] ? round((float)$coords['longitude'], 7) : null,
                'crime_details'         => ($crime_details_val === '') ? null : $crime_details_val, // Added
                'created_at'            => now(),
                'updated_at'            => now(),
            ];

            if (count($dataBatch) >= self::BATCH_SIZE) {
                $this->insertOrUpdateBatch($dataBatch);
                $this->command->info("... [Police Log] upserted " . count($dataBatch) . " records to cambridge_crime_reports_data (processed {$currentRecordIndexInChunk}/" . count($rawCsvRecords) . " in current chunk) ...");
                $dataBatch = [];
            }
        }

        if (!empty($dataBatch)) {
            $this->insertOrUpdateBatch($dataBatch);
            $this->command->info("... [Police Log] upserted final " . count($dataBatch) . " records to cambridge_crime_reports_data for this chunk ...");
        }
        
        return ['processed' => $recordsProcessedInChunk, 'notFound' => $notFoundInChunk];
    }

    private function parseCrimeTimestamp(string $timeString): ?Carbon
    {
        $timeString = trim($timeString);
        if (!$timeString) return null;
        try {
            return Carbon::createFromFormat('m/d/Y g:i A', $timeString);
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('m/d/Y H:i', $timeString);
            } catch (\Exception $e2) {
                $this->command->warn("[Police Log] Could not parse crime timestamp: '{$timeString}'. Error: " . $e->getMessage());
                return null;
            }
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        if (empty($dataBatch)) return;

        $model = new CambridgeCrimeReportData(); // Used to get table name and fillable fields
        $fillable = $model->getFillable();
        
        // Columns to update on duplicate, excluding the unique key and created_at
        $updateColumns = array_filter($fillable, function ($col) {
            return !in_array($col, ['file_number_external', 'created_at']);
        });
        // Ensure 'updated_at' is explicitly in updateColumns if not already by fillable logic
        if (!in_array('updated_at', $updateColumns)) {
            $updateColumns[] = 'updated_at';
        }
        // crime_details will be included here if it's in $fillable


        DB::table($model->getTable())->upsert(
            $dataBatch,
            ['file_number_external'], 
            array_values($updateColumns) 
        );
        // $this->command->info("[Police Log] Upserted batch of " . count($dataBatch) . " records to " . $model->getTable());
    }
}
