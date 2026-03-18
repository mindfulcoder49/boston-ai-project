<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class NewYork311Seeder extends Seeder
{
    public function run()
    {
        $directoryPath = 'datasets/new_york';
        $name = 'new_york-311s';
        $chunkSize = 500;
        $fullTableName = 'new_york_311s';
        $recentTableName = 'new_york_311s';

        $files = Storage::disk('local')->files($directoryPath);

        $dataset_311_files = array_filter($files, function ($file) use ($name) {
            return strpos(basename($file), $name) !== false;
        });

        if (empty($dataset_311_files)) {
            $this->command->warn("No NewYork 311 CSV files found in directory: " . storage_path('app/' . $directoryPath));
            return;
        }

        sort($dataset_311_files);
        $latestFile = end($dataset_311_files);
        $csvPath = Storage::disk('local')->path($latestFile);

        $this->command->info("Processing latest file: {$csvPath}");

        $fileHandle = fopen($csvPath, 'r');
        if ($fileHandle === false) {
            $this->command->error("Failed to open CSV file: {$csvPath}");
            return;
        }

        $header = fgetcsv($fileHandle);
        if ($header === false) {
            $this->command->error("Failed to read header from CSV file: {$csvPath}");
            fclose($fileHandle);
            return;
        }

        $headerMap = [
            'Unique Key' => 'unique_key',
            'Created Date' => 'created_date',
            'Closed Date' => 'closed_date',
            'Agency' => 'agency',
            'Agency Name' => 'agency_name',
            'Problem (formerly Complaint Type)' => 'complaint_type',
            'Problem Detail (formerly Descriptor)' => 'descriptor',
            'Additional Details' => 'additional_details',
            'Location Type' => 'location_type',
            'Incident Zip' => 'incident_zip',
            'Incident Address' => 'incident_address',
            'Street Name' => 'street_name',
            'Cross Street 1' => 'cross_street_1',
            'Cross Street 2' => 'cross_street_2',
            'Intersection Street 1' => 'intersection_street_1',
            'Intersection Street 2' => 'intersection_street_2',
            'Address Type' => 'address_type',
            'City' => 'city',
            'Landmark' => 'landmark',
            'Facility Type' => 'facility_type',
            'Status' => 'status',
            'Due Date' => 'due_date',
            'Resolution Description' => 'resolution_description',
            'Resolution Action Updated Date' => 'resolution_action_updated_date',
            'Community Board' => 'community_board',
            'Council District' => 'council_district',
            'Police Precinct' => 'police_precinct',
            'BBL' => 'bbl',
            'Borough' => 'borough',
            'X Coordinate (State Plane)' => 'x_coordinate_state_plane',
            'Y Coordinate (State Plane)' => 'y_coordinate_state_plane',
            'Open Data Channel Type' => 'open_data_channel_type',
            'Park Facility Name' => 'park_facility_name',
            'Park Borough' => 'park_borough',
            'Vehicle Type' => 'vehicle_type',
            'Taxi Company Borough' => 'taxi_company_borough',
            'Taxi Pick Up Location' => 'taxi_pick_up_location',
            'Bridge Highway Name' => 'bridge_highway_name',
            'Bridge Highway Direction' => 'bridge_highway_direction',
            'Road Ramp' => 'road_ramp',
            'Bridge Highway Segment' => 'bridge_highway_segment',
            'Latitude' => 'latitude',
            'Longitude' => 'longitude',
            'Location' => 'location',
            'Community Districts' => 'community_districts',
            'Borough Boundaries' => 'borough_boundaries',
            'Police Precincts' => 'police_precincts',
            'City Council Districts' => 'city_council_districts'
        ];

        $dbColumns = array_map(function ($col) use ($headerMap) {
            $trimmed = trim((string) $col);

            if (isset($headerMap[$trimmed])) {
                return $headerMap[$trimmed];
            }

            $normalized = preg_replace('/[^a-zA-Z0-9]+/', '_', $trimmed);
            $normalized = strtolower(trim((string) $normalized, '_'));
            $normalized = preg_replace('/_+/', '_', $normalized);

            return $normalized;
        }, $header);

        $dataToInsertFull = [];
        $dataToInsertRecent = [];
        $rowCount = 0;
        $totalUpsertedFull = 0;
        $totalUpsertedRecent = 0;
        $skippedRowCount = 0;

        DB::disableQueryLog();

        while (($row = fgetcsv($fileHandle)) !== false) {
            if (count($row) !== count($dbColumns)) {
                $skippedRowCount++;
                $rowCount++;
                continue;
            }

            $record = array_combine($dbColumns, $row);
            $transformedRecord = $this->transformRecord($record);

            if ($transformedRecord['location'] === null) {
                $skippedRowCount++;
                $rowCount++;
                continue;
            }

            $dataToInsertFull[] = $transformedRecord;

            if (isset($transformedRecord['created_date'])) {
                $recordDate = Carbon::parse($transformedRecord['created_date']);
                if ($recordDate->greaterThanOrEqualTo(Carbon::now()->subMonths(6))) {
                    $dataToInsertRecent[] = $transformedRecord;
                }
            }

            $rowCount++;

            if (count($dataToInsertFull) >= $chunkSize) {
                $this->upsertData('new_york_311_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
                $dataToInsertFull = [];
            }

            if (count($dataToInsertRecent) >= $chunkSize) {
                $this->upsertData('new_york_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
                $dataToInsertRecent = [];
            }

            if ($rowCount % 10000 === 0) {
                $this->command->line("Processed approximately {$rowCount} records...");
            }
        }

        if (!empty($dataToInsertFull)) {
            $this->upsertData('new_york_311_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
        }
        if (!empty($dataToInsertRecent)) {
            $this->upsertData('new_york_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
        }

        fclose($fileHandle);
        $this->command->info("Finished processing file: {$csvPath}. Total records: {$rowCount}, Full DB Upserted: {$totalUpsertedFull}, Recent DB Upserted: {$totalUpsertedRecent}, Skipped: {$skippedRowCount}.");

        DB::enableQueryLog();
        $this->command->info("NewYork 311 file processed.");
    }

    private function upsertData($connection, $tableName, &$data, &$totalUpserted)
    {
        if (empty($data)) {
            return;
        }

        try {
            DB::connection($connection)->table($tableName)->upsert(
                $data,
                ['unique_key'],
                array_diff(array_keys($data[0]), ['unique_key'])
            );
            $totalUpserted += count($data);
        } catch (\Exception $e) {
            Log::error("Error upserting data to {$connection}.{$tableName}: " . $e->getMessage(), ['exception' => $e]);
            $this->command->error("Error upserting data to {$connection}.{$tableName}. See log for details.");
        }
    }

    private function transformRecord(array $record): array
    {
        $cleanVal = fn($val) => ($val === null || strtolower(trim((string)$val)) === 'nan' || trim((string)$val) === '') ? null : trim((string)$val);

        $dateColumns = ['created_date', 'closed_date', 'due_date', 'resolution_action_updated_date'];
        $boolColumns = [];
        $pointColumns = [];

        $transformed = [];
        foreach ($record as $key => $value) {
            $cleaned = $cleanVal($value);
            if (in_array($key, $boolColumns)) {
                $transformed[$key] = filter_var($cleaned, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } elseif (in_array($key, $dateColumns)) {
                try {
                    $transformed[$key] = $cleaned ? Carbon::parse($cleaned)->toDateTimeString() : null;
                } catch (\Exception $e) {
                    $transformed[$key] = null;
                }
            } elseif (in_array($key, $pointColumns)) {
                // Skip for now - will be set from lat/lon below
                $transformed[$key] = null;
            } else {
                $transformed[$key] = $cleaned;
            }
        }

        // Validate and set geo coordinates
        $location = $this->validateAndCreateLocation($transformed['latitude'] ?? null, $transformed['longitude'] ?? null);
        $transformed['location'] = $location;

        // Set any other POINT columns from lat/lon as well
        foreach ($pointColumns as $pointCol) {
            $transformed[$pointCol] = $location;
        }

        unset($transformed['']);

        return $transformed;
    }

    /**
     * Validate latitude/longitude values and create location geometry.
     *
     * This method filters out invalid coordinates including:
     * - Non-numeric string values (e.g., "REDACTED", "N/A")
     * - Sentinel/placeholder values (e.g., -1.0, 0.0)
     * - Values outside the valid range for this city
     */
    private function validateAndCreateLocation($lat, $lng)
    {
        // Invalid string values detected in sample data
        $invalidStrings = [];

        // Sentinel/placeholder numeric values to reject
        $sentinelValues = [];

            // Valid coordinate ranges detected from data analysis
            $validLatMin = 40.5028;
            $validLatMax = 40.9037;
            $validLngMin = -74.2527;
            $validLngMax = -73.7004;

        // Check for null or empty values
        if ($lat === null || $lng === null || trim((string)$lat) === '' || trim((string)$lng) === '') {
            return null;
        }

        $latStr = trim((string)$lat);
        $lngStr = trim((string)$lng);

        // Check for invalid string values (case-insensitive)
        foreach ($invalidStrings as $invalid) {
            if (strcasecmp($latStr, $invalid) === 0 || strcasecmp($lngStr, $invalid) === 0) {
                return null;
            }
        }

        // Check if values are numeric
        if (!is_numeric($latStr) || !is_numeric($lngStr)) {
            return null;
        }

        $latFloat = (float)$latStr;
        $lngFloat = (float)$lngStr;

        // Check for sentinel values
        foreach ($sentinelValues as $sentinel) {
            // Check if lat AND lng are both the sentinel value (common pattern for placeholders)
            if (abs($latFloat - $sentinel) < 0.0001 && abs($lngFloat - $sentinel) < 0.0001) {
                return null;
            }
        }

        // Check valid coordinate ranges if defined
        if (isset($validLatMin) && isset($validLatMax) && isset($validLngMin) && isset($validLngMax)) {
            // Add a small buffer (0.5 degrees) to account for edge cases
            $buffer = 0.5;
            if ($latFloat < ($validLatMin - $buffer) || $latFloat > ($validLatMax + $buffer) ||
                $lngFloat < ($validLngMin - $buffer) || $lngFloat > ($validLngMax + $buffer)) {
                return null;
            }
        }

        // Basic sanity check for valid lat/lng ranges
        if ($latFloat < -90 || $latFloat > 90 || $lngFloat < -180 || $lngFloat > 180) {
            return null;
        }

        return DB::raw("ST_GeomFromText('POINT({$lngFloat} {$latFloat})')");
    }
}
