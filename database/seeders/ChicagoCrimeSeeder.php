<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ChicagoCrimeSeeder extends Seeder
{
    public function run()
    {
        $directoryPath = 'datasets/chicago';
        $name = 'chicago-crimes';
        $chunkSize = 500;
        $fullTableName = 'chicago_crimes';
        $recentTableName = 'chicago_crimes';

        $files = Storage::disk('local')->files($directoryPath);

        $crimeFiles = array_filter($files, function ($file) use ($name) {
            return strpos(basename($file), $name) !== false;
        });

        if (empty($crimeFiles)) {
            $this->command->warn("No Chicago crime CSV files found in directory: " . storage_path('app/' . $directoryPath));
            return;
        }

        // Sort files to be sure, then get the last one.
        sort($crimeFiles);
        $latestFile = end($crimeFiles);
        $csvPath = Storage::path($latestFile);

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
                // Handle malformed CSV rows, especially with the location field newlines
                if (count($row) === 1 && isset($record)) {
                    // This is likely a continuation of the previous row's location field
                    $last_key = array_key_last($record);
                    $record[$last_key] .= " " . $row[0]; // Append to the last field
                    continue; // Skip to next fgetcsv
                }
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
                $crashDate = Carbon::parse($transformedRecord['date']);
                if ($crashDate->greaterThanOrEqualTo(Carbon::now()->subMonths(6))) {
                    $dataToInsertRecent[] = $transformedRecord;
                }
            }

            $rowCount++;

            if (count($dataToInsertFull) >= $chunkSize) {
                $this->upsertData('chicago_crime_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
                $dataToInsertFull = [];
            }

            if (count($dataToInsertRecent) >= $chunkSize) {
                $this->upsertData('chicago_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
                $dataToInsertRecent = [];
            }
            
            if ($rowCount % 10000 === 0) {
                $this->command->line("Processed approximately {$rowCount} records...");
            }
        }

        if (!empty($dataToInsertFull)) {
            $this->upsertData('chicago_crime_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
        }
        if (!empty($dataToInsertRecent)) {
            $this->upsertData('chicago_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
        }

        fclose($fileHandle);
        $this->command->info("Finished processing file: {$csvPath}. Total records: {$rowCount}, Full DB Upserted: {$totalUpsertedFull}, Recent DB Upserted: {$totalUpsertedRecent}, Skipped: {$skippedRowCount}.");

        DB::enableQueryLog();
        $this->command->info("Chicago crime file processed.");
    }

    private function upsertData($connection, $tableName, &$data, &$totalUpserted)
    {
        if (empty($data)) {
            return;
        }

        try {
            $columns = array_keys($data[0]);
            $columnsSql = '`' . implode('`,`', $columns) . '`';

            $updateColumns = array_diff($columns, ['id']);
            $updateSql = implode(', ', array_map(function ($col) {
                return "`{$col}` = VALUES(`{$col}`)";
            }, $updateColumns));

            $valuesPlaceholders = [];
            $bindings = [];
            foreach ($data as $row) {
                $rowPlaceholders = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    if ($value instanceof \Illuminate\Database\Query\Expression) {
                        $rowPlaceholders[] = $value->getValue(DB::connection($connection)->getQueryGrammar());
                    } else {
                        $rowPlaceholders[] = '?';
                        $bindings[] = $value;
                    }
                }
                $valuesPlaceholders[] = '(' . implode(',', $rowPlaceholders) . ')';
            }

            $valuesSql = implode(',', $valuesPlaceholders);

            $sql = "INSERT INTO `{$tableName}` ({$columnsSql}) VALUES {$valuesSql} ON DUPLICATE KEY UPDATE {$updateSql}";

            DB::connection($connection)->insert($sql, $bindings);

            $totalUpserted += count($data);
        } catch (\Exception $e) {
            Log::error("Error upserting data to {$connection}.{$tableName}: " . $e->getMessage());
            $this->command->error("Error upserting data to {$connection}.{$tableName}. See log for details.");
        }
    }

    private function transformRecord(array $record): array
    {
        $cleanVal = fn($val) => ($val === null || strtolower(trim((string)$val)) === 'nan' || trim((string)$val) === '') ? null : trim((string)$val);

        $transformed = [];
        foreach ($record as $key => $value) {
            $cleaned = $cleanVal($value);
            if (in_array($key, ['arrest', 'domestic'])) {
                $transformed[$key] = filter_var($cleaned, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            } elseif (in_array($key, ['date', 'updated_on'])) {
                try {
                    $transformed[$key] = $cleaned ? Carbon::parse($cleaned)->toDateTimeString() : null;
                } catch (\Exception $e) {
                    $transformed[$key] = null;
                }
            } else {
                $transformed[$key] = $cleaned;
            }
        }

        if (!empty($transformed['latitude']) && !empty($transformed['longitude'])) {
            $lat = (float)$transformed['latitude'];
            $lon = (float)$transformed['longitude'];
            $transformed['location'] = DB::raw("ST_SRID(POINT($lon, $lat), 4326)");
        } else {
            $transformed['location'] = null;
        }
        
        // The 'location' text column from CSV is not needed for the DB
        unset($transformed['']); // Unset the malformed column if it exists

        return $transformed;
    }
}
