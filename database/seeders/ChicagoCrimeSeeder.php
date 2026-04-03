<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ChicagoCrimeSeeder extends Seeder
{
    private const SOURCE_TO_SCHEMA_MAP = [];

    private array $tableColumnsCache = [];
    private array $reportedDroppedColumns = [];

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
        
        $dbColumns = array_map(fn($col) => $this->normalizeHeaderColumn($col), $header);

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

            $record = $this->mapSourceRecordKeys(array_combine($dbColumns, $row));
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

        $data = array_values(array_filter(array_map(
            fn(array $record) => $this->filterRecordForTable($connection, $tableName, $record),
            $data
        )));

        if (empty($data)) {
            return;
        }

        try {
            DB::connection($connection)->table($tableName)->upsert(
                $data,
                ['id'], // Unique key
                array_diff(array_keys($data[0]), ['id']) // Columns to update
            );
            $totalUpserted += count($data);
        } catch (\Exception $e) {
            Log::error("Error upserting data to {$connection}.{$tableName}: " . $e->getMessage(), ['exception' => $e]);
            $this->command->error("Error upserting data to {$connection}.{$tableName}. See log for details.");
            throw $e;
        }
    }

    protected function normalizeHeaderColumn(string $column): string
    {
        $normalized = strtolower(trim($column));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    protected function mapSourceRecordKeys(array $record): array
    {
        $mapped = [];

        foreach ($record as $key => $value) {
            $mapped[self::SOURCE_TO_SCHEMA_MAP[$key] ?? $key] = $value;
        }

        return $mapped;
    }

    private function filterRecordForTable(string $connection, string $tableName, array $record): array
    {
        $allowedColumns = $this->getTableColumns($connection, $tableName);
        if (empty($allowedColumns)) {
            return $record;
        }

        $filtered = array_intersect_key($record, $allowedColumns);
        $droppedColumns = array_values(array_diff(array_keys($record), array_keys($filtered)));

        if (!empty($droppedColumns)) {
            $reportKey = $connection . '.' . $tableName;
            if (!isset($this->reportedDroppedColumns[$reportKey])) {
                $this->reportedDroppedColumns[$reportKey] = true;
                $message = sprintf(
                    'ChicagoCrimeSeeder dropped unsupported columns for %s: %s',
                    $reportKey,
                    implode(', ', $droppedColumns)
                );
                Log::warning($message);
                $this->command?->warn($message);
            }
        }

        return $filtered;
    }

    private function getTableColumns(string $connection, string $tableName): array
    {
        $cacheKey = $connection . '.' . $tableName;

        if (!isset($this->tableColumnsCache[$cacheKey])) {
            try {
                $this->tableColumnsCache[$cacheKey] = array_flip(
                    DB::connection($connection)->getSchemaBuilder()->getColumnListing($tableName)
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to read table columns for {$cacheKey}: " . $e->getMessage(), ['exception' => $e]);
                $this->tableColumnsCache[$cacheKey] = [];
            }
        }

        return $this->tableColumnsCache[$cacheKey];
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
            $transformed['location'] = DB::raw("ST_GeomFromText('POINT({$lon} {$lat})')");
        } else {
            $transformed['location'] = null;
        }
        
        // The 'location' text column from CSV is not needed for the DB
        unset($transformed['']); // Unset the malformed column if it exists

        return $transformed;
    }
}
