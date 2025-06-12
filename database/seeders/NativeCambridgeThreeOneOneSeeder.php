<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CambridgeThreeOneOneCase;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NativeCambridgeThreeOneOneSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
        $datasetName = 'cambridge-311-service-requests';
        $citySubdirectory = 'cambridge';
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");

        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (!empty($datasetFiles)) {
            sort($datasetFiles); // Sort to get the latest by filename convention
            $fileToProcess = end($datasetFiles);
            $this->command->info("Processing Native Cambridge 311 file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No files found for Native Cambridge 311.");
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
                $dataBatch[] = [
                    'ticket_id_external'            => $record['ticket_id'] ?? null,
                    'city'                          => ($record['city'] === '') ? null : ($record['city'] ?? null),
                    'issue_type'                    => ($record['issue_type'] === '') ? null : ($record['issue_type'] ?? null),
                    'issue_category'                => ($record['issue_category'] === '') ? null : ($record['issue_category'] ?? null),
                    'ticket_status'                 => ($record['ticket_status'] === '') ? null : ($record['ticket_status'] ?? null),
                    'issue_description'             => ($record['issue_description'] === '') ? null : ($record['issue_description'] ?? null),
                    'ticket_closed_date_time'       => $this->formatDate($record['ticket_closed_date_time'] ?? null),
                    'ticket_created_date_time'      => $this->formatDate($record['ticket_created_date_time'] ?? null),
                    'ticket_last_updated_date_time' => $this->formatDate($record['ticket_last_updated_date_time'] ?? null),
                    'address'                       => ($record['address'] === '') ? null : ($record['address'] ?? null),
                    'latitude'                      => (is_numeric($record['lat'] ?? null)) ? (float)$record['lat'] : null,
                    'longitude'                     => (is_numeric($record['lng'] ?? null)) ? (float)$record['lng'] : null,
                    'location_text'                 => ($record['location'] === '') ? null : ($record['location'] ?? null),
                    'image_url'                     => ($record['image'] === '') ? null : ($record['image'] ?? null),
                    'acknowledged_at'               => $this->formatDate($record['acknowledged_at'] ?? null),
                    'html_url'                      => ($record['html_url'] === '') ? null : ($record['html_url'] ?? null),
                    'created_at'                    => now(),
                    'updated_at'                    => now(),
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} Native Cambridge 311 records...");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("Native Cambridge 311 file processed successfully: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing Native Cambridge 311 file: " . basename($filePath) . " - " . $e->getMessage());
        }
    }

    private function formatDate($dateString)
    {
        if (empty($dateString) || strtolower($dateString) === 'nan') {
            return null;
        }
        try {
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date for Native Cambridge 311: {$dateString}");
            return null;
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['ticket_id_external']));
        if (empty($validBatch)) {
            return;
        }
        
        $model = new CambridgeThreeOneOneCase();
        $fillable = $model->getFillable();
        $updateColumns = array_filter($fillable, function ($col) {
            return !in_array($col, ['ticket_id_external']);
        });

        DB::table($model->getTable())->upsert(
            $validBatch,
            ['ticket_id_external'],
            array_values($updateColumns)
        );
    }
}
