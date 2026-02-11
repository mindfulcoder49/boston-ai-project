<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MontgomeryCountyMdCrimeSeeder extends Seeder
{
    public function run()
    {
        $directoryPath = 'datasets/montgomery_county_md';
        $name = 'montgomery_county_md-crimes';
        $chunkSize = 500;
        $fullTableName = 'montgomery_county_md_crimes';
        $recentTableName = 'montgomery_county_md_crimes';

        $files = Storage::disk('local')->files($directoryPath);

        $crimeFiles = array_filter($files, function ($file) use ($name) {
            return strpos(basename($file), $name) !== false;
        });

        if (empty($crimeFiles)) {
            $this->command->warn("No MontgomeryCountyMd crime CSV files found in directory: " . storage_path('app/' . $directoryPath));
            return;
        }

        sort($crimeFiles);
        $latestFile = end($crimeFiles);
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

        $dbColumns = array_map(fn($col) => strtolower(str_replace(' ', '_', $col)), $header);

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

            if (isset($transformedRecord['date'])) {
                $recordDate = Carbon::parse($transformedRecord['date']);
                if ($recordDate->greaterThanOrEqualTo(Carbon::now()->subMonths(6))) {
                    $dataToInsertRecent[] = $transformedRecord;
                }
            }

            $rowCount++;

            if (count($dataToInsertFull) >= $chunkSize) {
                $this->upsertData('montgomery_county_md_crime_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
                $dataToInsertFull = [];
            }

            if (count($dataToInsertRecent) >= $chunkSize) {
                $this->upsertData('montgomery_county_md_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
                $dataToInsertRecent = [];
            }

            if ($rowCount % 10000 === 0) {
                $this->command->line("Processed approximately {$rowCount} records...");
            }
        }

        if (!empty($dataToInsertFull)) {
            $this->upsertData('montgomery_county_md_crime_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
        }
        if (!empty($dataToInsertRecent)) {
            $this->upsertData('montgomery_county_md_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
        }

        fclose($fileHandle);
        $this->command->info("Finished processing file: {$csvPath}. Total records: {$rowCount}, Full DB Upserted: {$totalUpsertedFull}, Recent DB Upserted: {$totalUpsertedRecent}, Skipped: {$skippedRowCount}.");

        DB::enableQueryLog();
        $this->command->info("MontgomeryCountyMd crime file processed.");
    }

    private function upsertData($connection, $tableName, &$data, &$totalUpserted)
    {
        if (empty($data)) {
            return;
        }

        try {
            DB::connection($connection)->table($tableName)->upsert(
                $data,
                ['incident_id'],
                array_diff(array_keys($data[0]), ['incident_id'])
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

        $dateColumns = ['date', 'start_date', 'end_date'];
        $boolColumns = [];
        $intColumns = ['victims'];
        $pointColumns = ['geolocation'];

        $transformed = [];
        foreach ($record as $key => $value) {
            $cleaned = $cleanVal($value);
            if (in_array($key, $boolColumns)) {
                $transformed[$key] = filter_var($cleaned, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } elseif (in_array($key, $intColumns)) {
                $transformed[$key] = $cleaned !== null && is_numeric($cleaned) ? (int)$cleaned : null;
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
        $sentinelValues = [0.0];

            // Valid coordinate ranges detected from data analysis
            $validLatMin = 38.9488;
            $validLatMax = 39.2910;
            $validLngMin = -77.4305;
            $validLngMax = -76.9220;

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
