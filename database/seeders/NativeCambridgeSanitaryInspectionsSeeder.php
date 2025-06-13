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

    private function trimToNull($value, $chars = " \t\n\r\0\x0B\xC2\xA0")
    {
        if ($value === null) {
            return null;
        }
        // Ensure value is a string before trimming
        $trimmed = trim((string) $value, $chars);
        return ($trimmed === '') ? null : $trimmed;
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
                $geocodedColumnText = $this->trimToNull($record['geocoded_column'] ?? null);
                if ($geocodedColumnText) {
                    list($lat, $lon) = $this->parsePoint($geocodedColumnText);
                    $latitude = $lat;
                    $longitude = $lon;
                }
                
                $dbCaseNumberGroup = $this->trimToNull($record['case_number'] ?? null);
                $dbEstablishmentName = $this->trimToNull($record['establishment_name'] ?? null);
                $dbCodeNumber = $this->trimToNull($record['code_number'] ?? null);
                $dbDateCited = $this->formatDate($record['date_cited'] ?? null);

                if ($dbCaseNumberGroup === null && $dbEstablishmentName === null) { 
                    $this->command->warn("Skipping record with empty case_number and establishment_name: " . json_encode($record));
                    continue;
                }

                // Construct unique_violation_identifier
                $idPart1 = '';
                if ($dbCaseNumberGroup !== null) {
                    $idPart1 = $dbCaseNumberGroup;
                } elseif ($dbEstablishmentName !== null) {
                    $idPart1 = 'ESTAB_' . md5($dbEstablishmentName);
                } else {
                    // This should not be reached due to the skip logic above
                    $this->command->error("Critical error: Both case_number_group and establishment_name are null for unique ID construction. Record: " . json_encode($record));
                    // Potentially skip or assign a fallback that's unlikely to collide but indicates an issue
                    $idPart1 = 'ERROR_MISSING_PRIMARY_ID_' . uniqid(); 
                }

                $idPart2 = $dbCodeNumber ?? '__NO_CODE__';
                $idPart3 = $dbDateCited ? Carbon::parse($dbDateCited)->toDateString() : '__NO_DATE_CITED__';
                
                $uniqueViolationIdentifier = $idPart1 . '|' . $idPart2 . '|' . $idPart3;
                // Ensure the identifier does not exceed typical varchar limits if necessary, though 255 should be fine with md5 for establishment name.


                $dataBatch[] = [
                    'case_number_group'     => $dbCaseNumberGroup,
                    'address'               => $this->trimToNull($record['address'] ?? null),
                    'parcel'                => $this->trimToNull($record['parcel'] ?? null),
                    'establishment_name'    => $dbEstablishmentName,
                    'code_number'           => $dbCodeNumber,
                    'code_description'      => $this->trimToNull($record['code_description'] ?? null),
                    'inspector_comments'    => $this->trimToNull($record['inspector_comments'] ?? null),
                    'case_open_date'        => $this->formatDate($record['case_open_date'] ?? null),
                    'case_closed_date'      => $this->formatDate($record['case_closed_date'] ?? null),
                    'date_cited'            => $dbDateCited,
                    'date_corrected'        => $this->formatDate($record['date_corrected'] ?? null),
                    'code_case_status'      => $this->trimToNull($record['code_case_status'] ?? null),
                    'latitude'              => $latitude,
                    'longitude'             => $longitude,
                    'geocoded_column_text'  => $geocodedColumnText,
                    'unique_violation_identifier' => $uniqueViolationIdentifier,
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
        
        // The skip logic is now primarily in processFile. 
        // This filter can be a secondary check or removed if confident in processFile's logic.
        $validBatch = array_filter($dataBatch, function($item) {
            return !($item['case_number_group'] === null && $item['establishment_name'] === null);
        });

        if (empty($validBatch)) {
            return;
        }

        $updateColumns = [
            'case_number_group', 'address', 'parcel', 'establishment_name', 
            'code_number', 'code_description', 'inspector_comments', 
            'case_open_date', 'case_closed_date', 'date_cited', 'date_corrected', 
            'code_case_status', 'latitude', 'longitude', 'geocoded_column_text', 
            'updated_at'
        ];

        DB::table((new CambridgeSanitaryInspectionData)->getTable())->upsert(
            $validBatch,
            ['unique_violation_identifier'],
            $updateColumns
        );
    }
}
