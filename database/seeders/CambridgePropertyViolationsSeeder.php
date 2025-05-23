<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyViolation;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CambridgePropertyViolationsSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
        $datasetName = 'cambridge-housing-code-violations';
        $citySubdirectory = 'cambridge';
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");

        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (!empty($datasetFiles)) {
            sort($datasetFiles);
            $fileToProcess = end($datasetFiles);
            $this->command->info("Processing Cambridge housing code violations file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No files found for Cambridge housing code violations.");
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

                $description = $record['description'] ?? null;
                if (!empty($record['corrective_action'])) {
                    $description .= " Corrective Action: " . $record['corrective_action'];
                }
                if (!empty($record['correction_required_by'])) {
                    $description .= " Correction Required By: " . $record['correction_required_by'];
                }
                //skip if code is empty
                if (empty($record['code'])) {
                    $this->command->warn("Skipping record with empty code: " . json_encode($record));
                    continue;
                }
                
                $dataBatch[] = [
                    'case_no'            => $record['recordid'] ?? null,
                    'ap_case_defn_key'   => null, // no corresponding field in CSV
                    'status_dttm'        => $this->formatDate($record['application_submit_date'] ?? null),
                    'status'             => $record['status_x'] ?? null,
                    'code'               => $record['code'] ?? null,
                    'value'              => null,
                    'description'        => $description,
                    'violation_stno'     => null,
                    'violation_sthigh'   => null,
                    'violation_street'   => $record['fulladdress'] ?? null,
                    'violation_suffix'   => null,
                    'violation_city'     => null,
                    'violation_state'    => 'MA',
                    'violation_zip'      => null,
                    'ward'               => null,
                    'contact_addr1'      => null,
                    'contact_addr2'      => null,
                    'contact_city'       => null,
                    'contact_state'      => null,
                    'contact_zip'        => null,
                    'sam_id'             => null,
                    'latitude'           => is_numeric($record['latitude'] ?? null) ? (float)$record['latitude'] : null,
                    'longitude'          => is_numeric($record['longitude'] ?? null) ? (float)$record['longitude'] : null,
                    'location'           => $record['point'] ?? null,
                    'language_code'      => 'en-US',
                    'created_at'         => now(),
                    'updated_at'         => now(),
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

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['case_no']));
        if (empty($validBatch)) {
            return;
        }
        $updateColumns = array_keys($validBatch[0]);
        // Remove 'case_no' and 'created_at' from update columns.
        $updateColumns = array_filter($updateColumns, function ($col) {
            return !in_array($col, ['case_no', 'created_at']);
        });

        DB::table((new PropertyViolation)->getTable())->upsert(
            $validBatch,
            ['case_no'],
            array_values($updateColumns)
        );
    }
}
