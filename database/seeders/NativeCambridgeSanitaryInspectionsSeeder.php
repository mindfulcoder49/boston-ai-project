<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CambridgeSanitaryInspectionData;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NativeCambridgeSanitaryInspectionsSeeder extends Seeder
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
            $this->command->info("Processing Native Cambridge Sanitary Inspections file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No files found for Native Cambridge Sanitary Inspections.");
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

                $latitude = null;
                $longitude = null;
                $geocodedColumnText = ($record['geocoded_column'] === '') ? null : ($record['geocoded_column'] ?? null);
                if ($geocodedColumnText) {
                    list($lat, $lon) = $this->parsePoint($geocodedColumnText);
                    $latitude = $lat;
                    $longitude = $lon;
                }
                
                $caseNumberGroup = ($record['case_number'] === '') ? null : ($record['case_number'] ?? null);
                 if (empty($caseNumberGroup) && empty($record['establishment_name'])) { // Skip if no case number and no establishment name
                    $this->command->warn("Skipping record with empty case_number and establishment_name: " . json_encode($record));
                    continue;
                }


                $dataBatch[] = [
                    'case_number_group'     => $caseNumberGroup,
                    'address'               => ($record['address'] === '') ? null : ($record['address'] ?? null),
                    'parcel'                => ($record['parcel'] === '') ? null : ($record['parcel'] ?? null),
                    'establishment_name'    => ($record['establishment_name'] === '') ? null : ($record['establishment_name'] ?? null),
                    'code_number'           => ($record['code_number'] === '') ? null : ($record['code_number'] ?? null),
                    'code_description'      => ($record['code_description'] === '') ? null : ($record['code_description'] ?? null),
                    'inspector_comments'    => ($record['inspector_comments'] === '') ? null : ($record['inspector_comments'] ?? null),
                    'case_open_date'        => $this->formatDate($record['case_open_date'] ?? null),
                    'case_closed_date'      => $this->formatDate($record['case_closed_date'] ?? null),
                    'date_cited'            => $this->formatDate($record['date_cited'] ?? null),
                    'date_corrected'        => $this->formatDate($record['date_corrected'] ?? null),
                    'code_case_status'      => ($record['code_case_status'] === '') ? null : ($record['code_case_status'] ?? null),
                    'latitude'              => $latitude,
                    'longitude'             => $longitude,
                    'geocoded_column_text'  => $geocodedColumnText,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];

                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertBatch($dataBatch); 
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} Native Cambridge Sanitary Inspection records...");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertBatch($dataBatch); 
            }
            $this->command->info("Native Cambridge Sanitary Inspections file processed successfully: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing Native Cambridge Sanitary Inspections file: " . basename($filePath) . " - " . $e->getMessage());
        }
    }

    private function parsePoint(string $point)
    {
        if (preg_match('/POINT\s*\(\s*([-\d\.]+)\s+([-\d\.]+)\s*\)/', $point, $matches)) {
            return [(float)$matches[2], (float)$matches[1]]; // [latitude, longitude]
        }
        return [null, null];
    }

    private function formatDate($dateString)
    {
        if (empty($dateString) || strtolower($dateString) === 'nan') {
            return null;
        }
        try {
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date for Native Cambridge Sanitary Inspection: {$dateString}");
            return null;
        }
    }

    private function insertBatch(array $dataBatch): void
    {
        if (empty($dataBatch)) {
            return;
        }
        // Filter out items where case_number_group is essential and missing, or other critical fields
        $validBatch = array_filter($dataBatch, fn($item) => !(empty($item['case_number_group']) && empty($item['establishment_name'])));
        if (empty($validBatch)) {
            return;
        }
        DB::table((new CambridgeSanitaryInspectionData)->getTable())->insert($validBatch);
    }
}
