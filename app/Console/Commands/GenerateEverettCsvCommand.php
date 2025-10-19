<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use League\Csv\Writer; // Using league/csv for robust CSV writing
use League\Csv\Reader;

class GenerateEverettCsvCommand extends Command
{
    protected $signature = 'app:generate-everett-csv';
    protected $description = 'Generates a combined CSV file from Everett police data and geocoded information.';

    public function handle()
    {
        $this->info("Starting Everett CSV generation...");

        $baseStoragePath = storage_path('app/datasets/everett');
        
        $inputPoliceDataFilename = 'everett_police_calls_and_arrest_data.json';
        $inputPoliceDataPath = $baseStoragePath . '/' . $inputPoliceDataFilename;
        
        $inputGeocodeDataFilename = 'geocoded_addresses.json';
        $inputGeocodeDataPath = $baseStoragePath . '/' . $inputGeocodeDataFilename;

        $outputCsvFilename = 'everett_police_data_combined.csv';
        $outputCsvPath = $baseStoragePath . '/' . $outputCsvFilename;

        if (!File::exists($inputPoliceDataPath)) {
            $this->error("Police data JSON file not found: {$inputPoliceDataPath}");
            return 1;
        }

        $this->info("Loading police data from {$inputPoliceDataPath}...");
        $policeData = json_decode(File::get($inputPoliceDataPath), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($policeData)) {
            $this->error("Could not load or decode police data from {$inputPoliceDataPath}.");
            return 1;
        }
        $this->info("Loaded " . count($policeData) . " records from JSON.");

        $geocodeData = [];
        if (File::exists($inputGeocodeDataPath)) {
            $geocodeData = json_decode(File::get($inputGeocodeDataPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->warn("Could not decode geocode data from {$inputGeocodeDataPath}. Proceeding without geocodes for some records if this file is corrupt.");
                $geocodeData = []; // Treat as empty if corrupt
            }
        } else {
            $this->warn("Geocode data file not found: {$inputGeocodeDataPath}. Proceeding without geocodes.");
        }

        // The field names are predictable from flattenRecord(). We can define them statically
        // to avoid iterating through all records, which is very slow.
        $this->info("Using predefined CSV header structure.");
        $finalFieldnames = [
            'case_number',
            'incident_log_file_date',
            'incident_entry_date',
            'incident_time',
            'incident_type',
            'incident_address',
            'incident_latitude',
            'incident_longitude',
            'incident_description',
            'arrest_name',
            'arrest_address',
            'arrest_age',
            'arrest_date',
            'arrest_charges',
        ];

        $existingCaseNumbers = collect();
        $isAppending = false;

        if (File::exists($outputCsvPath) && File::size($outputCsvPath) > 0) {
            $this->info("Checking existing CSV file at {$outputCsvPath}...");
            try {
                $csvReader = Reader::createFromPath($outputCsvPath, 'r');
                $csvReader->setHeaderOffset(0);
                $existingHeader = $csvReader->getHeader();

                if ($existingHeader === $finalFieldnames) {
                    $this->info("Existing CSV has a matching header. Reading existing case numbers to prevent duplicates...");
                    $isAppending = true;
                    foreach ($csvReader->fetchColumn('case_number') as $caseNumber) {
                        $existingCaseNumbers->add($caseNumber);
                    }
                    $this->info("Found {$existingCaseNumbers->count()} existing records in the CSV.");
                } else {
                    $this->warn("Existing CSV header does not match the expected header. Regenerating the entire file.");
                }
            } catch (\Exception $e) {
                $this->error("Could not read existing CSV file: " . $e->getMessage() . ". Regenerating the entire file.");
            }
        }

        $recordsToProcess = collect($policeData);
        if ($isAppending) {
            $this->info("Filtering for new records to append...");

            // Optimize lookup by flipping the collection to use keys for O(1) checks
            $existingCaseNumbersMap = $existingCaseNumbers->flip();
            $newRecords = [];
            $totalRecords = count($policeData);
            $processedCount = 0;
            $reportIncrement = 5000;

            foreach ($policeData as $record) {
                $processedCount++;
                if ($processedCount % $reportIncrement === 0) {
                    $this->output->write("\rFiltering progress: {$processedCount}/{$totalRecords} records checked...");
                }

                $caseNumber = $record['case_number'] ?? null;
                if ($caseNumber === null || !isset($existingCaseNumbersMap[$caseNumber])) {
                    $newRecords[] = $record;
                }
            }
            $this->output->write("\n"); // New line after progress
            $this->info("Filtering complete.");

            $recordsToProcess = collect($newRecords);
        }

        if ($recordsToProcess->isEmpty()) {
            $this->info("No new records to add to the CSV.");
            return 0;
        }
        
        $this->info("Preparing to process {$recordsToProcess->count()} records for the CSV...");

        $flattenedRecords = [];
        foreach ($recordsToProcess as $record) {
            $flatRec = $this->flattenRecord($record, $geocodeData);
            // Ensure all fields are present and in the correct order
            $orderedRec = [];
            foreach ($finalFieldnames as $field) {
                $orderedRec[$field] = $flatRec[$field] ?? '';
            }
            $flattenedRecords[] = $orderedRec;
        }
        $this->info("Record processing complete.");

        try {
            $mode = $isAppending ? 'a+' : 'w+';
            $csv = Writer::createFromPath($outputCsvPath, $mode);
            
            if (!$isAppending) {
                $this->info("Writing new CSV header...");
                $csv->insertOne($finalFieldnames); // Write header only for new files
            }
            
            $this->info("Writing " . count($flattenedRecords) . " records to the CSV file...");
            $csv->insertAll($flattenedRecords); // Write data

            if ($isAppending) {
                $this->info("Successfully appended " . count($flattenedRecords) . " new records to {$outputCsvPath}");
            } else {
                $this->info("Successfully created combined CSV: {$outputCsvPath}");
            }
        } catch (\Exception $e) {
            $this->error("Error writing CSV file: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function flattenRecord(array $record, array $geocodeData): array
    {
        $flatRec = [];

        $flatRec['case_number'] = $record['case_number'] ?? '';

        // Incident details
        $incidentDetails = $record['incident_details'] ?? null;
        $flatRec['incident_log_file_date'] = $incidentDetails['log_file_date'] ?? '';
        $flatRec['incident_entry_date'] = $incidentDetails['entry_date'] ?? '';
        $flatRec['incident_time'] = $incidentDetails['time'] ?? '';
        $flatRec['incident_type'] = $incidentDetails['type'] ?? '';
        $originalIncidentAddress = $incidentDetails['address'] ?? '';
        $flatRec['incident_address'] = $originalIncidentAddress;

        $geoInfo = ($originalIncidentAddress && isset($geocodeData[$originalIncidentAddress])) ? $geocodeData[$originalIncidentAddress] : null;
        if (is_array($geoInfo)) {
            $flatRec['incident_latitude'] = $geoInfo['lat'] ?? '';
            $flatRec['incident_longitude'] = $geoInfo['lng'] ?? '';
        } else {
            $flatRec['incident_latitude'] = '';
            $flatRec['incident_longitude'] = '';
        }
        
        $flatRec['incident_description'] = $incidentDetails['description'] ?? '';
        
        // Arrest details
        $arrestDetails = $record['arrest_details'] ?? null;
        $flatRec['arrest_name'] = $arrestDetails['name'] ?? '';
        $flatRec['arrest_address'] = $arrestDetails['address'] ?? '';
        $flatRec['arrest_age'] = $arrestDetails['age'] ?? '';
        $flatRec['arrest_date'] = $arrestDetails['date'] ?? '';
        $charges = $arrestDetails['charges'] ?? [];
        $flatRec['arrest_charges'] = is_array($charges) ? implode(" | ", $charges) : '';
        
        return $flatRec;
    }
}
