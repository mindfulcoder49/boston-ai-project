<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CambridgeCrimeReportData;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NativeCambridgeCrimeReportsSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

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
            $this->command->info("Processing Native Cambridge Crime Reports file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No files found for Native Cambridge Crime Reports.");
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

            foreach ($records as $record) {
                $progress++;

                $crimeDateTimeRaw = trim($record['crime_date_time'] ?? '');
                $crimeStart = null;
                $crimeEnd = null;

                if (strpos($crimeDateTimeRaw, ' - ') !== false) {
                    [$startPart, $endPart] = array_map('trim', explode(' - ', $crimeDateTimeRaw, 2));
                    $crimeStart = $this->parseCrimeTimestampInternal($startPart);
                    if ($crimeStart && !empty($endPart)) {
                         // Check if endPart is just time (e.g. "07:00") and needs date from startPart
                        if (preg_match('/^\d{1,2}:\d{2}$/', $endPart) && preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})\s/', $startPart, $dateMatches)) {
                            $fullEndPart = $dateMatches[1] . ' ' . $endPart;
                            $crimeEnd = $this->parseCrimeTimestampInternal($fullEndPart);
                        } else {
                            $crimeEnd = $this->parseCrimeTimestampInternal($endPart);
                        }
                    } elseif ($crimeStart && empty($endPart)) { // If only start is present after ' - ', use it for end too
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

                $dataBatch[] = [
                    'file_number_external'  => $fileNumberExternal,
                    'date_of_report'        => $this->formatDate($record['date_of_report'] ?? null),
                    'crime_datetime_raw'    => ($crimeDateTimeRaw === '') ? null : $crimeDateTimeRaw,
                    'crime_start_time'      => $crimeStart,
                    'crime_end_time'        => $crimeEnd,
                    'crime'                 => ($record['crime'] === '') ? null : ($record['crime'] ?? null),
                    'reporting_area'        => ($record['reporting_area'] === '') ? null : ($record['reporting_area'] ?? null),
                    'neighborhood'          => ($record['neighborhood'] === '') ? null : ($record['neighborhood'] ?? null),
                    'location_address'      => ($record['location'] === '') ? null : ($record['location'] ?? null),
                    'latitude'              => null, // Not present in sample CSV for crime-reports
                    'longitude'             => null, // Not present in sample CSV for crime-reports
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} Native Cambridge Crime Report records...");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("Native Cambridge Crime Reports file processed successfully: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing Native Cambridge Crime Reports file: " . basename($filePath) . " - " . $e->getMessage());
        }
    }
    
    private function parseCrimeTimestampInternal($timeString): ?string
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
                $this->command->warn("Could not parse crime timestamp for Native Cambridge Crime Report: '{$timeString}'. Error: " . $e->getMessage());
                return null;
            }
        }
    }

    private function formatDate($dateString)
    {
        if (empty($dateString) || strtolower($dateString) === 'nan') {
            return null;
        }
        try {
            // Handles ISO 8601 format like "2009-01-21T16:32:00.000"
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date for Native Cambridge Crime Report: {$dateString}");
            return null;
        }
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
            return !in_array($col, ['file_number_external']);
        });

        DB::table($model->getTable())->upsert(
            $validBatch,
            ['file_number_external'],
            array_values($updateColumns)
        );
    }
}
