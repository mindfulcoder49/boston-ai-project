<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ThreeOneOneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $name = '311-service-requests'; // Specify the file naming convention to look for
        $files = Storage::disk('local')->files('datasets'); // Fetch all files from 'datasets' directory

        // Filter files to match the specified naming convention
        $files = array_filter($files, function ($file) use ($name) {
            return strpos($file, $name) !== false;
        });

        // Only proceed if there are any files to process
        if (!empty($files)) {
            $file = end($files); // Process the most recent file
            echo "Processing file: " . $file . "\n";
            $this->processFile(Storage::path($file));
        } else {
            echo "No files found to process for name: " . $name . "\n";
        }
    }

    /**
     * Process the file and insert data into the database.
     *
     * @param string $filePath
     * @return void
     */
    private function processFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return;
        }

        // Read and decode the file content (assuming CSV)
        $rows = array_map('str_getcsv', file($filePath));
        $header = array_shift($rows); // Extract header row

        foreach ($rows as $index => $row) {
            try {
                // Combine header and row data into an associative array
                $rowData = array_combine($header, $row);

                // Validate and clean the data
                $cleanedData = $this->validateAndCleanData($rowData);

                // Insert cleaned data into the database
                DB::table('three_one_one_cases')->insert($cleanedData);
            } catch (\Exception $e) {
                // Log errors without interrupting the seeding process
                Log::error("Error processing row $index in file $filePath: " . $e->getMessage());
            }
        }
    }

    /**
     * Validate and clean data for insertion.
     *
     * @param array $row
     * @return array
     * @throws \Exception
     */
    private function validateAndCleanData(array $row): array
    {
        return [
            'case_enquiry_id' => $this->validateInteger($row['case_enquiry_id'] ?? null, 'case_enquiry_id'),
            'open_dt' => $this->validateDateTime($row['open_dt'] ?? null),
            'sla_target_dt' => $row['sla_target_dt'] ?? null,
            'closed_dt' => $this->validateDateTime($row['closed_dt'] ?? null),
            'on_time' => $row['on_time'] ?? null,
            'case_status' => $row['case_status'] ?? null,
            'closure_reason' => $row['closure_reason'] ?? null,
            'case_title' => $row['case_title'] ?? null,
            'subject' => $row['subject'] ?? null,
            'reason' => $row['reason'] ?? null,
            'type' => $row['type'] ?? null,
            'queue' => $row['queue'] ?? null,
            'department' => $row['department'] ?? null,
            'submitted_photo' => $row['submitted_photo'] ?? null,
            'closed_photo' => $row['closed_photo'] ?? null,
            'location' => $row['location'] ?? null,
            'fire_district' => $row['fire_district'] ?? null,
            'pwd_district' => $row['pwd_district'] ?? null,
            'city_council_district' => $row['city_council_district'] ?? null,
            'police_district' => $row['police_district'] ?? null,
            'neighborhood' => $row['neighborhood'] ?? null,
            'neighborhood_services_district' => $row['neighborhood_services_district'] ?? null,
            'ward' => $row['ward'] ?? null,
            'precinct' => $row['precinct'] ?? null,
            'location_street_name' => $row['location_street_name'] ?? null,
            'location_zipcode' => $this->validateDouble($row['location_zipcode'] ?? null),
            'latitude' => $this->validateDouble($row['latitude'] ?? null),
            'longitude' => $this->validateDouble($row['longitude'] ?? null),
            'source' => $row['source'] ?? null,
            'checksum' => $row['checksum'] ?? null,
            'ward_number' => $row['ward_number'] ?? null,
            'language_code' => 'en-US',
        ];
    }

    /**
     * Validation helpers for integer, double, and datetime remain the same.
     */
    private function validateInteger($value, string $field): ?int
    {
        if (is_null($value)) {
            return null;
        }
        if (!is_numeric($value) || intval($value) != $value) {
            throw new \Exception("Invalid integer for $field: $value");
        }
        return intval($value);
    }

    private function validateDouble($value): ?float
    {
        if (is_null($value)) {
            return null;
        }
        if (!is_numeric($value)) {
            throw new \Exception("Invalid double value: $value");
        }
        return floatval($value);
    }

    private function validateDateTime($value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        $date = date_create($value);
        if (!$date) {
            throw new \Exception("Invalid datetime value: $value");
        }
        return $date->format('Y-m-d H:i:s');
    }
}
