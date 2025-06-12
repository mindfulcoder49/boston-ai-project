<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CambridgeBuildingPermitData;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NativeCambridgeBuildingPermitsSeeder extends Seeder
{
    private const BATCH_SIZE = 200; // Smaller batch for potentially large raw_data JSON

    // Explicitly define mapped CSV headers to DB fields
    private const DIRECT_MAP = [
        'id' => 'permit_id_external',
        'address' => 'address',
        'address_geocoded' => 'address_geocoded',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'status' => 'status',
        'applicant_submit_date' => 'applicant_submit_date',
        'issue_date' => 'issue_date',
        'number_of_residential_units' => 'number_of_residential_units',
        'current_property_use' => 'current_property_use',
        'proposed_property_use' => 'proposed_property_use',
        'total_cost_of_construction' => 'total_cost_of_construction',
        'detailed_description_of_work' => 'detailed_description_of_work',
        'gross_square_footage' => 'gross_square_footage',
        'building_use' => 'building_use', // CSV 'building_use'
        'maplot_number' => 'maplot_number',
    ];


    public function run()
    {
        $datasetName = 'cambridge-building-permits';
        $citySubdirectory = 'cambridge';
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");

        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (!empty($datasetFiles)) {
            sort($datasetFiles);
            $fileToProcess = end($datasetFiles);
            $this->command->info("Processing Native Cambridge Building Permits file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No files found for Native Cambridge Building Permits.");
        }
    }

    private function processFile($filePath)
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setEscape('');
            $csvHeaders = $csv->getHeader();
            $records = $csv->getRecords();
            $dataBatch = [];
            $progress = 0;

            foreach ($records as $record) {
                $progress++;
                $permitData = [];
                $rawData = [];

                // Map known fields
                foreach (self::DIRECT_MAP as $csvKey => $dbKey) {
                    $value = $record[$csvKey] ?? null;
                    $permitData[$dbKey] = ($value === '') ? null : $value;
                }

                // Handle type conversions for known fields
                $permitData['applicant_submit_date'] = $this->formatDate($permitData['applicant_submit_date'] ?? null);
                $permitData['issue_date'] = $this->formatDate($permitData['issue_date'] ?? null);
                $permitData['latitude'] = (is_numeric($permitData['latitude'] ?? null)) ? (float)$permitData['latitude'] : null;
                $permitData['longitude'] = (is_numeric($permitData['longitude'] ?? null)) ? (float)$permitData['longitude'] : null;
                $permitData['number_of_residential_units'] = (is_numeric($permitData['number_of_residential_units'] ?? null)) ? (int)$permitData['number_of_residential_units'] : null;
                $permitData['total_cost_of_construction'] = (is_numeric($permitData['total_cost_of_construction'] ?? null)) ? (float)$permitData['total_cost_of_construction'] : null;
                $permitData['gross_square_footage'] = (is_numeric($permitData['gross_square_footage'] ?? null)) ? (int)$permitData['gross_square_footage'] : null;


                // Collect remaining fields into raw_data
                foreach ($csvHeaders as $header) {
                    if (!array_key_exists($header, self::DIRECT_MAP)) {
                        $value = $record[$header] ?? null;
                        if ($value !== '' && $value !== null) {
                            $rawData[$header] = $value;
                        }
                    }
                }
                $permitData['raw_data'] = !empty($rawData) ? json_encode($rawData) : null;

                $permitData['created_at'] = now();
                $permitData['updated_at'] = now();
                
                if (empty($permitData['permit_id_external'])) {
                     $this->command->warn("Skipping record with empty permit_id_external (CSV 'id'): " . json_encode($record));
                     continue;
                }

                $dataBatch[] = $permitData;

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} Native Cambridge Building Permit records...");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("Native Cambridge Building Permits file processed successfully: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing Native Cambridge Building Permits file: " . basename($filePath) . " - " . $e->getMessage() . "\n" . $e->getTraceAsString());
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
            $this->command->warn("Could not parse date for Native Cambridge Building Permit: {$dateString}");
            return null;
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['permit_id_external']));
        if (empty($validBatch)) {
            return;
        }
        
        $model = new CambridgeBuildingPermitData();
        $fillable = $model->getFillable();
        // Ensure 'raw_data' is included if it's fillable
        $updateColumns = array_filter($fillable, function ($col) {
            return !in_array($col, ['permit_id_external']);
        });


        DB::table($model->getTable())->upsert(
            $validBatch,
            ['permit_id_external'],
            array_values($updateColumns)
        );
    }
}
