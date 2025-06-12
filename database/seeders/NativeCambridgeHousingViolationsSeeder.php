<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CambridgeHousingViolationData;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NativeCambridgeHousingViolationsSeeder extends Seeder
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
            $this->command->info("Processing Native Cambridge Housing Violations file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No files found for Native Cambridge Housing Violations.");
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
                
                $recordIdExternal = ($record['recordid'] === '') ? null : ($record['recordid'] ?? null);
                if (empty($recordIdExternal)) {
                    $this->command->warn("Skipping record with empty recordid: " . json_encode($record));
                    continue;
                }

                $dataBatch[] = [
                    'record_id_external'        => $recordIdExternal,
                    'full_address'              => ($record['fulladdress'] === '') ? null : ($record['fulladdress'] ?? null),
                    'parcel_number'             => ($record['parcel_number'] === '') ? null : ($record['parcel_number'] ?? null),
                    'code'                      => ($record['code'] === '') ? null : ($record['code'] ?? null),
                    'description'               => ($record['description'] === '') ? null : ($record['description'] ?? null),
                    'corrective_action'         => ($record['corrective_action'] === '') ? null : ($record['corrective_action'] ?? null),
                    'correction_required_by'    => ($record['correction_required_by'] === '') ? null : ($record['correction_required_by'] ?? null),
                    'status'                    => ($record['status_x'] === '') ? null : ($record['status_x'] ?? null),
                    'application_submit_date'   => $this->formatDate($record['application_submit_date'] ?? null, 'm/d/Y'),
                    'issue_date'                => $this->formatDate($record['issue_date'] ?? null, 'm/d/Y'),
                    'longitude'                 => (is_numeric($record['longitude'] ?? null)) ? (float)$record['longitude'] : null,
                    'latitude'                  => (is_numeric($record['latitude'] ?? null)) ? (float)$record['latitude'] : null,
                    'point_text'                => ($record['point'] === '') ? null : ($record['point'] ?? null),
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} Native Cambridge Housing Violation records...");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("Native Cambridge Housing Violations file processed successfully: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing Native Cambridge Housing Violations file: " . basename($filePath) . " - " . $e->getMessage());
        }
    }

    private function formatDate($dateString, $format = null)
    {
        if (empty($dateString) || strtolower($dateString) === 'nan') {
            return null;
        }
        try {
            if ($format) {
                return Carbon::createFromFormat($format, $dateString)->format('Y-m-d');
            }
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date for Native Cambridge Housing Violation: {$dateString}");
            return null;
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['record_id_external']));
        if (empty($validBatch)) {
            return;
        }
        
        $model = new CambridgeHousingViolationData();
        $fillable = $model->getFillable();
        $updateColumns = array_filter($fillable, function ($col) {
            return !in_array($col, ['record_id_external']);
        });

        DB::table($model->getTable())->upsert(
            $validBatch,
            ['record_id_external'],
            array_values($updateColumns)
        );
    }
}
