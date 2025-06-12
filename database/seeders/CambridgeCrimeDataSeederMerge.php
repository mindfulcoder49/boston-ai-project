<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
use App\Services\CambridgeAddressLookupService;
use App\Models\CambridgeCrimeReportData; 

class CambridgeCrimeDataSeederMerge extends Seeder
{
    private const BATCH_SIZE = 500; 
    private const MAX_RECORDS_IN_MEMORY_CHUNK = 10000; 
    private ?CambridgeAddressLookupService $addressLookupService = null; 


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
            
            $fileRecordsIterator = $csv->getRecords();

            $recordsReadFromFile = 0;
            foreach ($fileRecordsIterator as $record) {
                if (empty(array_filter($record))) continue;

                $recordsChunk[] = $record;
                $recordsReadFromFile++;

                if (count($recordsChunk) >= self::MAX_RECORDS_IN_MEMORY_CHUNK) {
                    $this->command->info("Processing a chunk of " . count($recordsChunk) . " records from " . basename($fileToProcessPath) . "...");
                    $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk);
                    $grandTotalRecordsProcessed += $chunkStats['processed'];
                    $grandTotalNotFoundCount += $chunkStats['notFound'];
                    $recordsChunk = [];
                    $this->command->info("Chunk processed. Total records processed so far: {$grandTotalRecordsProcessed}. Locations not found so far: {$grandTotalNotFoundCount}.");
                }
            }
            $this->command->info("Finished reading {$recordsReadFromFile} records from " . basename($fileToProcessPath) . ".");

        } catch (\Exception $e) {
            $this->command->error("Error reading or preparing records from file: " . basename($fileToProcessPath) . " - " . $e->getMessage());
        }
        

        if (!empty($recordsChunk)) {
            $this->command->info("Processing the final chunk of " . count($recordsChunk) . " records...");
            $chunkStats = $this->processAndInsertRecordsChunk($recordsChunk, $grandTotalRecordsProcessed);
            $grandTotalRecordsProcessed += $chunkStats['processed'];
            $grandTotalNotFoundCount += $chunkStats['notFound'];
            $this->command->info("Final chunk processed.");
        }
        
        $this->addressLookupService->finalSaveGeocodeCache();

        $this->command->info("Cambridge Crime Data Merge Seeder finished. Total records processed: {$grandTotalRecordsProcessed}. Total locations not found: {$grandTotalNotFoundCount}.");
    }
    
    private function formatDate($dateString): ?string 
    {
        $dateString = trim($dateString);
        if (empty($dateString) || strtolower($dateString) === 'nan') {
            return null;
        }
        try {
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $dateString)) { 
                 return Carbon::createFromFormat('m/d/Y', $dateString)->startOfDay()->format('Y-m-d H:i:s');
            }
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->command->warn("[Merge] Could not parse report date: {$dateString}");
            return null;
        }
    }

    private function parseCrimeTimestampInternal($timeString): ?string 
    {
        $timeString = trim($timeString);
        if (!$timeString) return null;
        try {
            return Carbon::createFromFormat('m/d/Y H:i', $timeString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('m/d/Y g:i A', $timeString)->format('Y-m-d H:i:s');
            } catch (\Exception $e2) {
                $this->command->warn("[Merge] Could not parse crime timestamp: '{$timeString}'. Error: " . $e->getMessage());
                return null;
            }
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

            $fileNumberExternal = ($record['file_number'] === '') ? null : ($record['file_number'] ?? null);
            if (empty($fileNumberExternal)) {
                $this->command->warn("[Merge] Skipping record with empty file_number: " . json_encode($record));
                continue; 
            }

            $crimeDateTimeRaw = trim($record['crime_date_time'] ?? '');
            $crime_start_val = null;
            $crime_end_val = null; 

            if (strpos($crimeDateTimeRaw, ' - ') !== false) {
                [$startPart, $endPart] = array_map('trim', explode(' - ', $crimeDateTimeRaw, 2));
                $crime_start_val = $this->parseCrimeTimestampInternal($startPart);
                if ($crime_start_val && !empty($endPart)) {
                    if (preg_match('/^\d{1,2}:\d{2}$/', $endPart) && preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})\s/', $startPart, $dateMatches)) {
                        $fullEndPart = $dateMatches[1] . ' ' . $endPart;
                        $crime_end_val = $this->parseCrimeTimestampInternal($fullEndPart);
                    } else {
                        $crime_end_val = $this->parseCrimeTimestampInternal($endPart);
                    }
                } elseif ($crime_start_val && empty($endPart)) {
                    $crime_end_val = $crime_start_val;
                }
            } elseif (!empty($crimeDateTimeRaw)) {
                $crime_start_val = $this->parseCrimeTimestampInternal($crimeDateTimeRaw);
                $crime_end_val = $crime_start_val; 
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
            
            $recordsProcessedInChunk++;

            $dataBatch[] = [
                'file_number_external'  => $fileNumberExternal,
                'date_of_report'        => $this->formatDate($record['date_of_report'] ?? null),
                'crime_datetime_raw'    => ($crimeDateTimeRaw === '') ? null : $crimeDateTimeRaw,
                'crime_start_time'      => $crime_start_val,
                'crime_end_time'        => $crime_end_val,
                'crime'                 => ($record['crime'] === '') ? null : ($record['crime'] ?? null),
                'reporting_area'        => ($record['reporting_area'] === '') ? null : ($record['reporting_area'] ?? null),
                'neighborhood'          => ($record['neighborhood'] === '') ? null : ($record['neighborhood'] ?? null),
                'location_address'      => ($raw_location_from_csv === '') ? null : $raw_location_from_csv,
                'latitude'              => $coords['latitude'] ? round((float)$coords['latitude'], 7) : null,
                'longitude'             => $coords['longitude'] ? round((float)$coords['longitude'], 7) : null,
                'created_at'            => now(),
                'updated_at'            => now(),
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
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['file_number_external']));
        if (empty($validBatch)) {
            return;
        }
        
        $model = new CambridgeCrimeReportData();
        $fillable = $model->getFillable();
        $updateColumns = array_filter($fillable, function ($col) {
            return !in_array($col, ['file_number_external', 'created_at']);
        });

        DB::table($model->getTable())->upsert(
            $validBatch,
            ['file_number_external'],
            array_values($updateColumns)
        );
    }
}
