<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader; // Import League CSV Reader

class ThreeOneOneSeeder extends Seeder
{
    private const BATCH_SIZE = 500; // Define batch size for DB operations

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
            sort($files); // Ensure consistent processing order, e.g., oldest to newest or vice-versa
            $file = end($files); // Process the most recent file
            $this->command->info("Processing file: " . $file);
            $this->processFile(Storage::path($file));
        } else {
            $this->command->warn("No files found to process for name: " . $name);
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
            $this->command->error("File not found: $filePath");
            return;
        }

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // The header is on the first row
            $records = $csv->getRecords(); // Get an iterator for the records

            $dataBatch = [];
            $rowCount = 0;
            $processedCount = 0;

            // It's difficult to get an accurate total row count from the iterator without iterating twice
            // or loading everything into memory. We'll report progress based on processed batches.

            foreach ($records as $index => $row) {
                $rowCount++;
                try {
                    // $rowData is already an associative array from League\Csv\Reader
                    $cleanedData = $this->validateAndCleanData($row);
                    $dataBatch[] = $cleanedData;

                    if (count($dataBatch) >= self::BATCH_SIZE) {
                        $this->insertOrUpdateBatch($dataBatch);
                        $processedCount += count($dataBatch);
                        $dataBatch = []; // Reset the batch
                        $this->command->info("Processed {$processedCount} records...");
                    }
                } catch (\Exception $e) {
                    // Log errors without interrupting the seeding process
                    Log::error("Error processing row " . ($index + 1) . " in file $filePath: " . $e->getMessage() . " Data: " . json_encode($row));
                    $this->command->warn("Skipped row " . ($index + 1) . " due to error: " . $e->getMessage());
                }
            }

            // Process any remaining data in the last batch
            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
                $processedCount += count($dataBatch);
            }
            $this->command->info("Finished processing file: " . basename($filePath) . ". Total records processed: {$processedCount}. Total rows read: {$rowCount}.");

        } catch (\Exception $e) {
            $this->command->error("Failed to read or process CSV file {$filePath}: " . $e->getMessage());
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        if (empty($dataBatch)) {
            return;
        }
        // Ensure all items in batch have the case_enquiry_id for upsert
        $validBatch = array_filter($dataBatch, fn($item) => isset($item['case_enquiry_id']));

        if (empty($validBatch)) {
            $this->command->warn("A batch of records was skipped as no valid 'case_enquiry_id' was found.");
            return;
        }
        
        // Define columns to update in case of conflict
        $updateColumns = array_keys($validBatch[0]);
        // Remove 'case_enquiry_id' from updateColumns as it's the unique key
        $updateColumns = array_filter($updateColumns, fn($col) => $col !== 'case_enquiry_id');
        // Ensure 'created_at' is not updated on conflict, 'updated_at' should be.
        if (!in_array('updated_at', $updateColumns) && array_key_exists('updated_at', $validBatch[0])) {
             // This logic might be too simplistic if 'updated_at' isn't always present or needs special handling.
             // For upsert, typically all non-key fields are listed for update.
        }


        DB::table('three_one_one_cases')->upsert(
            $validBatch,
            ['case_enquiry_id'], // Unique key(s)
            $updateColumns // Columns to update on duplicate
        );
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
            // 'checksum' => $row['checksum'] ?? null, // Checksum might not be needed in DB
            'ward_number' => $row['ward_number'] ?? null, // Often redundant if 'ward' is present
            'language_code' => 'en-US', // Default
            'threeoneonedescription' => $row['description'] ?? null, 
            'source_city' => 'Boston', 
            'created_at' => now(), // Add created_at
            'updated_at' => now(), // Add updated_at
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
        if (empty($value)) {
            return null; // Allow null or empty values
        }
        if (!is_numeric($value)) {
            throw new \Exception("Invalid double value: \"$value\"");
        }
        return floatval($value);
    }

    private function validateDateTime($value): ?string
    {
        if (is_null($value) || empty(trim($value))) { // Also check for empty string
            return null;
        }
        try {
            // Attempt to parse with Carbon for more robust date handling
            $date = \Carbon\Carbon::parse($value);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Log or handle specific invalid date formats if necessary
            // For now, throw exception to be caught by the row processing loop
            throw new \Exception("Invalid datetime value: $value. Error: " . $e->getMessage());
        }
    }
}
