<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use League\Csv\Writer; // Using league/csv for robust CSV writing

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

        $policeData = json_decode(File::get($inputPoliceDataPath), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($policeData)) {
            $this->error("Could not load or decode police data from {$inputPoliceDataPath}.");
            return 1;
        }

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

        $flattenedRecords = [];
        $allFieldnames = collect(); // Use Laravel Collection for easier unique/sorting

        foreach ($policeData as $record) {
            $flatRec = $this->flattenRecord($record, $geocodeData);
            $flattenedRecords[] = $flatRec;
            $allFieldnames = $allFieldnames->merge(array_keys($flatRec));
        }
        
        $allFieldnames = $allFieldnames->unique()->sort()->values()->all();

        $preferredFieldOrder = [
            'case_number',
            'incident_log_file_date', 'incident_entry_date', 'incident_time',
            'incident_type', 'incident_address', 'incident_latitude', 'incident_longitude',
            'incident_description',
            'arrest_name', 'arrest_address', 'arrest_age', 'arrest_date', 'arrest_charges'
        ];

        // Create the final fieldnames list
        $finalFieldnames = collect($preferredFieldOrder)
            ->filter(fn($field) => in_array($field, $allFieldnames)) // Keep preferred fields that exist
            ->merge(collect($allFieldnames)->diff($preferredFieldOrder)->sort()->values()) // Add remaining fields, sorted
            ->unique()
            ->values()
            ->all();
            
        if (empty($flattenedRecords)) {
            $this->info("No records to write to CSV.");
            return 0;
        }

        try {
            $csv = Writer::createFromPath($outputCsvPath, 'w+');
            $csv->insertOne($finalFieldnames); // Write header
            $csv->insertAll($flattenedRecords); // Write data

            $this->info("Successfully created combined CSV: {$outputCsvPath}");
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
        // $flatRec['incident_description'] = $incidentDetails['description'] ?? ''; // Moved from here

        $geoInfo = ($originalIncidentAddress && isset($geocodeData[$originalIncidentAddress])) ? $geocodeData[$originalIncidentAddress] : null;
        if (is_array($geoInfo)) {
            $flatRec['incident_latitude'] = $geoInfo['lat'] ?? '';
            $flatRec['incident_longitude'] = $geoInfo['lng'] ?? '';
        } else {
            $flatRec['incident_latitude'] = '';
            $flatRec['incident_longitude'] = '';
        }
        
        $flatRec['incident_description'] = $incidentDetails['description'] ?? ''; // Moved to here
        
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
