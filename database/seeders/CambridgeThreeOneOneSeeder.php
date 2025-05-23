<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CambridgeThreeOneOneSeeder extends Seeder
{
    private const BATCH_SIZE = 200; // Define batch size for upserting

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('CambridgeThreeOneOneSeeder: Seeding process started.');
        $datasetNamePart = 'cambridge-311-service-requests'; // Common name part for the dataset
        $citySubdirectory = 'cambridge'; // City-specific subdirectory

        $filesPath = "datasets/{$citySubdirectory}";
        $allFiles = Storage::disk('local')->files($filesPath);

        // Filter files to match the specified naming convention
        $files = array_filter($allFiles, function ($file) use ($datasetNamePart) {
            return strpos(basename($file), $datasetNamePart) !== false && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        // Sort files by name to process the most recent one (assuming timestamp in filename makes recent ones last)
        if (!empty($files)) {
            sort($files); // Ensure correct order for `end()`
            $file = end($files); // Process the most recent file
            Log::info("CambridgeThreeOneOneSeeder: Processing Cambridge 311 file: {$file}");
            $this->processFile(Storage::path($file));
        } else {
            Log::warning("CambridgeThreeOneOneSeeder: No files found to process in {$filesPath} for name part: {$datasetNamePart}");
            echo "No files found to process in {$filesPath} for name part: {$datasetNamePart}\n";
        }
        Log::info('CambridgeThreeOneOneSeeder: Seeding process finished.');
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
            Log::error("CambridgeThreeOneOneSeeder: File not found: {$filePath}");
            echo "File not found: $filePath\n";
            return;
        }

        $fileHandle = fopen($filePath, 'r');
        if ($fileHandle === false) {
            Log::error("CambridgeThreeOneOneSeeder: Could not open file: {$filePath}");
            echo "Could not open file: $filePath\n";
            return;
        }

        $header = fgetcsv($fileHandle); // Extract header row
        if ($header === false) {
            Log::error("CambridgeThreeOneOneSeeder: Could not read header from file: {$filePath}");
            fclose($fileHandle);
            return;
        }
        Log::info("CambridgeThreeOneOneSeeder: Successfully read header from {$filePath}.");

        $rowCount = 0;
        $processedForBatchCount = 0;
        $skippedYearCount = 0;
        $errorCount = 0;
        $batchData = [];

        DB::beginTransaction();
        Log::info("CambridgeThreeOneOneSeeder: Started database transaction.");
        try {
            while (($row = fgetcsv($fileHandle)) !== false) {
                $rowCount++;
                if ($rowCount === 1 && $row === $header) { // Skip header if it's read again by any chance
                    continue;
                }

                try {
                    if (count($header) !== count($row)) {
                        Log::warning("CambridgeThreeOneOneSeeder: Row {$rowCount}: Mismatch between header and row column count in file {$filePath}. Columns in header: " . count($header) . ", columns in row: " . count($row) . ". Skipping row. Data: " . implode(',', array_slice($row, 0, 5)));
                        $errorCount++;
                        continue;
                    }
                    $rowData = array_combine($header, $row);

                    // Validate and clean the data
                    $cleanedData = $this->validateAndCleanData($rowData);

                    // Filter by year 2025
                    if (isset($cleanedData['open_dt'])) {
                        try {
                            $openDate = Carbon::parse($cleanedData['open_dt']);
                            if ($openDate->year !== 2025) {
                                $skippedYearCount++;
                                // Log::debug("CambridgeThreeOneOneSeeder: Row {$rowCount} (ID: {$cleanedData['case_enquiry_id']}): Skipped due to open_dt year not being 2025 (Year: {$openDate->year}).");
                                continue;
                            }
                        } catch (\Exception $e) {
                            Log::warning("CambridgeThreeOneOneSeeder: Row {$rowCount} (ID: {$cleanedData['case_enquiry_id']}): Could not parse open_dt '{$cleanedData['open_dt']}' for year check. Error: " . $e->getMessage());
                            $errorCount++;
                            continue;
                        }
                    } else {
                        // If open_dt is null, decide if it should be skipped or processed.
                        // For now, let's skip if we can't determine the year.
                        Log::warning("CambridgeThreeOneOneSeeder: Row {$rowCount} (ID: {$cleanedData['case_enquiry_id']}): Skipped due to null open_dt, cannot verify year.");
                        $skippedYearCount++;
                        continue;
                    }
                    
                    $batchData[] = $cleanedData;
                    $processedForBatchCount++;

                    if (count($batchData) >= self::BATCH_SIZE) {
                        $this->upsertBatch($batchData);
                        $batchData = []; // Reset batch
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("CambridgeThreeOneOneSeeder: Error processing row {$rowCount} in file {$filePath}: " . $e->getMessage() . " | Row data: " . json_encode($rowData ?? $row));
                }
            }

            // Upsert any remaining data in the last batch
            if (!empty($batchData)) {
                $this->upsertBatch($batchData);
            }

            DB::commit();
            Log::info("CambridgeThreeOneOneSeeder: Database transaction committed.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical("CambridgeThreeOneOneSeeder: Critical error during Cambridge 311 seeding, transaction rolled back: " . $e->getMessage());
            echo "Critical error during seeding, see log file.\n";
        } finally {
            fclose($fileHandle);
            Log::info("CambridgeThreeOneOneSeeder: Closed file handle for {$filePath}.");
        }
        
        $summary = "CambridgeThreeOneOneSeeder: Finished processing {$filePath}. " .
                   "Total rows read: {$rowCount}. " .
                   "Rows skipped (not 2025): {$skippedYearCount}. " .
                   "Rows processed for DB: {$processedForBatchCount}. " .
                   "Rows with errors: {$errorCount}.";
        Log::info($summary);
        echo $summary . "\n";
    }

    /**
     * Upserts a batch of data.
     *
     * @param array $batchData
     * @return void
     */
    private function upsertBatch(array $batchData): void
    {
        if (empty($batchData)) {
            return;
        }
        try {
            DB::table('three_one_one_cases')->upsert($batchData, ['case_enquiry_id'], [
                // Specify columns to update if the record exists
                'open_dt', 'sla_target_dt', 'closed_dt', 'on_time', 'case_status', 
                'closure_reason', 'case_title', 'subject', 'reason', 'type', 'queue', 
                'department', 'threeoneonedescription', 'submitted_photo', 'closed_photo', 'location', 
                'fire_district', 'pwd_district', 'city_council_district', 'police_district', 
                'neighborhood', 'neighborhood_services_district', 'ward', 'precinct', 
                'location_street_name', 'location_zipcode', 'latitude', 'longitude', 
                'source', 'checksum', 'ward_number', 'language_code', 
                'source_city', 'updated_at'
            ]);
            Log::info("CambridgeThreeOneOneSeeder: Successfully upserted batch of " . count($batchData) . " records.");
        } catch (\Exception $e) {
            Log::error("CambridgeThreeOneOneSeeder: Error upserting batch: " . $e->getMessage() . " | Sample Case ID from batch: " . ($batchData[0]['case_enquiry_id'] ?? 'N/A'));
            // Optionally, re-throw or handle more gracefully (e.g. try individual upserts)
            throw $e; // Re-throw to be caught by the transaction handler
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
        // Cambridge CSV Headers:
        // "ticket_id","city","issue_type","issue_category","ticket_status","issue_description",
        // "ticket_closed_date_time","ticket_created_date_time","ticket_last_updated_date_time",
        // "address","lat","lng","location","image","acknowledged_at","html_url"

        $caseEnquiryId = $this->validateInteger($row['ticket_id'] ?? null, 'ticket_id');
        if ($caseEnquiryId === null) {
            throw new \Exception("ticket_id is required and cannot be null.");
        }

        return [
            'case_enquiry_id' => $caseEnquiryId,
            'open_dt' => $this->validateDateTime($row['ticket_created_date_time'] ?? null, 'ticket_created_date_time'),
            'sla_target_dt' => null, // Not available in Cambridge data
            'closed_dt' => $this->validateDateTime($row['ticket_closed_date_time'] ?? null, 'ticket_closed_date_time'),
            'on_time' => null, // Not available in Cambridge data
            'case_status' => $row['ticket_status'] ?? null,
            'closure_reason' => null, // Not directly available
            'case_title' => $row['issue_type'] ?? null, // Using issue_type as case_title
            'subject' => $row['issue_category'] ?? null,
            'reason' => null, // Not directly available
            'type' => $row['issue_type'] ?? null,
            'queue' => null, // Not available
            'department' => null, // Not available
            'threeoneonedescription' => $row['issue_description'] ?? null,
            'submitted_photo' => ($row['image'] ?? null) === '' ? null : ($row['image'] ?? null),
            'closed_photo' => null, // Not available
            'location' => $row['address'] ?? null,
            'fire_district' => null, // Not available
            'pwd_district' => null, // Not available
            'city_council_district' => null, // Not available
            'police_district' => null, // Not available
            'neighborhood' => null, // Not directly available
            'neighborhood_services_district' => null, // Not available
            'ward' => null, // Not available
            'precinct' => null, // Not available
            'location_street_name' => $row['address'] ?? null, // Using full address, could be parsed
            'location_zipcode' => null, // Not directly available, could parse from address
            'latitude' => $this->validateDouble($row['lat'] ?? null, 'lat'),
            'longitude' => $this->validateDouble($row['lng'] ?? null, 'lng'),
            'source' => $row['html_url'] ?? 'Cambridge SeeClickFix', // URL or a generic source
            'checksum' => null, // Boston specific
            'ward_number' => null, // Not available
            'language_code' => 'en-US', // Default to English
            'source_city' => 'Cambridge', // Hardcoded for this seeder
            'created_at' => now(), // Handled by DB upsert if new, or ignored if updating
            'updated_at' => now(),
        ];
    }

    private function validateInteger($value, string $field): ?int
    {
        if ($value === null || $value === '') {
            // Log::debug("CambridgeThreeOneOneSeeder: Integer field '{$field}' is null or empty, returning null.");
            return null;
        }
        if (!is_numeric($value)) {
            $cleanedValue = str_replace(',', '', $value);
            if (!is_numeric($cleanedValue)) {
                 throw new \Exception("Invalid integer for {$field}: {$value}");
            }
            $value = $cleanedValue;
        }
        if (floatval($value) != intval(floatval($value))) {
             throw new \Exception("Invalid integer format for {$field} (float detected): {$value}");
        }
        return intval($value);
    }

    private function validateDouble($value, string $field): ?float
    {
        if ($value === null || $value === '') {
            // Log::debug("CambridgeThreeOneOneSeeder: Double field '{$field}' is null or empty, returning null.");
            return null;
        }
        if (!is_numeric($value)) {
            throw new \Exception("Invalid double value for {$field}: {$value}");
        }
        return floatval($value);
    }

    private function validateDateTime($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            // Log::debug("CambridgeThreeOneOneSeeder: DateTime field '{$field}' is null or empty, returning null.");
            return null;
        }
        try {
            $date = Carbon::parse($value); // Carbon can parse various formats including ISO 8601
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Log::warning("CambridgeThreeOneOneSeeder: Could not parse datetime for field '{$field}' with value '{$value}'. Error: " . $e->getMessage());
            // Fallback for specific common formats if Carbon::parse is too broad or fails unexpectedly
            $formatsToTry = ['Y-m-d\TH:i:s.u', 'Y-m-d H:i:s', 'm/d/Y H:i:s'];
            foreach ($formatsToTry as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);
                    if ($date) return $date->format('Y-m-d H:i:s');
                } catch (\Exception $ex) {
                    // continue trying other formats
                }
            }
            throw new \Exception("Invalid datetime value for {$field}: {$value}. Original error: " . $e->getMessage());
        }
    }
}
