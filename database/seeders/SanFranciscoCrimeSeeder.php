<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class SanFranciscoCrimeSeeder extends Seeder
{
    public function run()
    {
        $directoryPath = 'datasets/san_francisco';
        $name = 'san_francisco-crimes';
        $chunkSize = 500;
        $fullTableName = 'san_francisco_crimes';
        $recentTableName = 'san_francisco_crimes';

        $files = Storage::disk('local')->files($directoryPath);

        $crimeFiles = array_filter($files, function ($file) use ($name) {
            return strpos(basename($file), $name) !== false;
        });

        if (empty($crimeFiles)) {
            $this->command->warn("No SanFrancisco crime CSV files found in directory: " . storage_path('app/' . $directoryPath));
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

            if (isset($transformedRecord['incident_datetime'])) {
                $recordDate = Carbon::parse($transformedRecord['incident_datetime']);
                if ($recordDate->greaterThanOrEqualTo(Carbon::now()->subMonths(6))) {
                    $dataToInsertRecent[] = $transformedRecord;
                }
            }

            $rowCount++;

            if (count($dataToInsertFull) >= $chunkSize) {
                $this->upsertData('san_francisco_crime_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
                $dataToInsertFull = [];
            }

            if (count($dataToInsertRecent) >= $chunkSize) {
                $this->upsertData('san_francisco_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
                $dataToInsertRecent = [];
            }

            if ($rowCount % 10000 === 0) {
                $this->command->line("Processed approximately {$rowCount} records...");
            }
        }

        if (!empty($dataToInsertFull)) {
            $this->upsertData('san_francisco_crime_db', $fullTableName, $dataToInsertFull, $totalUpsertedFull);
        }
        if (!empty($dataToInsertRecent)) {
            $this->upsertData('san_francisco_db', $recentTableName, $dataToInsertRecent, $totalUpsertedRecent);
        }

        fclose($fileHandle);
        $this->command->info("Finished processing file: {$csvPath}. Total records: {$rowCount}, Full DB Upserted: {$totalUpsertedFull}, Recent DB Upserted: {$totalUpsertedRecent}, Skipped: {$skippedRowCount}.");

        DB::enableQueryLog();
        $this->command->info("SanFrancisco crime file processed.");
    }

    private function upsertData($connection, $tableName, &$data, &$totalUpserted)
    {
        if (empty($data)) {
            return;
        }

        try {
            DB::connection($connection)->table($tableName)->upsert(
                $data,
                ['row_id'],
                array_diff(array_keys($data[0]), ['row_id'])
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

        $dateColumns = ['incident_datetime', 'report_datetime', 'data_as_of', 'data_loaded_at'];
        $boolColumns = ['filed_online'];

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
            } else {
                $transformed[$key] = $cleaned;
            }
        }

        if (!empty($transformed['latitude']) && !empty($transformed['longitude'])) {
            $lat = (float)$transformed['latitude'];
            $lon = (float)$transformed['longitude'];
            $transformed['location'] = DB::raw("ST_GeomFromText('POINT({$lon} {$lat})')");
            // The CSV 'point' column is text like "(lat, lon)" - convert to geometry for the POINT column
            $transformed['point'] = DB::raw("ST_GeomFromText('POINT({$lon} {$lat})')");
        } else {
            $transformed['location'] = null;
            $transformed['point'] = null;
        }

        unset($transformed['']);

        return $transformed;
    }
}
