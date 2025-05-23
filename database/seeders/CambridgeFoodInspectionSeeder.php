<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FoodInspection;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CambridgeFoodInspectionSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
        $datasetName = 'cambridge-sanitary-inspections';
        $citySubdirectory = 'cambridge';
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");

        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (!empty($datasetFiles)) {
            sort($datasetFiles);
            $fileToProcess = end($datasetFiles);
            $this->command->info("Processing Cambridge sanitary inspections file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No files found for Cambridge sanitary inspections.");
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
                $point = $record['geocoded_column'] ?? '';
                list($latitude, $longitude) = $this->parsePoint($point);

                //calculate status based on if there's a date_corrected
                $violationStatus = null;
                if (!empty($record['date_corrected'])) {
                    $violationStatus = 'Pass';
                } else {
                    $violationStatus = 'Fail';
                }

                $comments = $record['inspector_comments'] ?? null;
                //if the date corrected is set add a " corrected on" to the comments
                if (!empty($record['date_corrected'])) {
                    $comments .= " Corrected on: " . $this->formatDate($record['date_corrected']);
                }
                
                $dataBatch[] = [
                    'external_id'    => $record['case_number'] ?? null,
                    'businessname'   => $record['establishment_name'] ?? null,
                    'licenseno'      => $record['code_number'] ?? null,
                    'descript'       => $record['code_description'] ?? null,
                    'comments'       => $comments,
                    'issdttm'        => $this->formatDate($record['case_open_date']),
                    'expdttm'        => $this->formatDate($record['case_closed_date']),
                    'resultdttm'     => $this->formatDate($record['case_open_date']), // updated: opendate goes to resultdttm
                    'status_date'    => $this->formatDate($record['date_corrected']),  // updated: date_corrected goes to status_date
                    'licstatus'      => $record['code_case_status'] ?? null,
                    'address'        => $record['address'] ?? null,
                    'property_id'    => $record['parcel'] ?? null,
                    'latitude'       => $latitude,
                    'longitude'      => $longitude,
                    'violation'      => $record['code_description'] ?? null, // repeated field for viol mapping
                    'viol_level'     => $record['code_number'] ?? null,        // repeated field for viol mapping
                    'violdesc'       => $record['code_description'] ?? null,   // repeated field for viol mapping
                    'violdttm'       => $this->formatDate($record['date_cited']),  // updated: date_cited goes to violdttm
                    'viol_status'    => $violationStatus,
                    'city'           => 'Cambridge',
                    'state'          => 'MA',
                    'zip'            => null,
                    'language_code'  => 'en-US',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    // ...other columns remain null...
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} records...");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("File processed successfully: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing file: " . basename($filePath) . " - " . $e->getMessage());
        }
    }

    private function formatDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        try {
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date: {$dateString}");
            return null;
        }
    }

    private function parsePoint(string $point)
    {
        // Expected format: "POINT (longitude latitude)"
        if (preg_match('/POINT\s*\(\s*([-\d\.]+)\s+([-\d\.]+)\s*\)/', $point, $matches)) {
            // Note: CSV uses POINT(lon lat); assign accordingly.
            return [(float)$matches[2], (float)$matches[1]]; // [latitude, longitude]
        }
        return [null, null];
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['external_id']));
        if (empty($validBatch)) {
            return;
        }
        $updateColumns = array_keys($validBatch[0]);
        // Remove 'external_id' and 'created_at' from update columns.
        $updateColumns = array_filter($updateColumns, function ($col) {
            return !in_array($col, ['external_id', 'created_at']);
        });

        DB::table((new FoodInspection)->getTable())->upsert(
            $validBatch,
            ['external_id'],
            array_values($updateColumns)
        );
    }
}
