<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Added Str facade

class DataPointSeeder extends Seeder
{
    private const DAYS_TO_KEEP = 183; // Approx 6 months
    private const BATCH_SIZE = 1000; // Number of records to process/upsert in each batch

    /**
     * Define the Mappable models to process.
     * Each entry should be the fully qualified class name of the model.
     */
    private const MODELS_TO_PROCESS = [ /*
        // Boston Models (ensure these implement Mappable)
        \App\Models\CrimeData::class,
        \App\Models\ThreeOneOneCase::class,
        \App\Models\PropertyViolation::class,
        // \App\Models\ConstructionOffHour::class, // Assuming model name, replace if different
        \App\Models\BuildingPermit::class, // Boston Building Permits
        \App\Models\FoodInspection::class,

        // Everett Models
        \App\Models\EverettCrimeData::class,

        // Cambridge Models
        \App\Models\CambridgeThreeOneOneCase::class,
        \App\Models\CambridgeBuildingPermitData::class,
        \App\Models\CambridgeCrimeReportData::class,
        \App\Models\CambridgeHousingViolationData::class,
        \App\Models\CambridgeSanitaryInspectionData::class,
         */
        // New Model
        \App\Models\PersonCrashData::class,
    ];

    public function run()
    {
        $this->command->info("Starting DataPointSeeder...");
        Log::info("DataPointSeeder: Run started.");
        $cutoffDate = Carbon::now()->subDays(self::DAYS_TO_KEEP)->toDateTimeString();

        try {
            $deletedCount = DB::table('data_points')->where('alcivartech_date', '<', $cutoffDate)->delete();
            $this->command->info("Successfully deleted {$deletedCount} old data points with alcivartech_date older than " . self::DAYS_TO_KEEP . " days.");
            Log::info("DataPointSeeder: Deleted {$deletedCount} old data points with alcivartech_date older than " . self::DAYS_TO_KEEP . " days.");
        } catch (\Exception $e) {
            $this->command->error("Error deleting old data points: " . $e->getMessage());
            Log::error("DataPointSeeder: Error deleting old data points.", ['exception' => $e]);
        }

        foreach (self::MODELS_TO_PROCESS as $modelClass) {
            if (!class_exists($modelClass)) {
                $this->command->error("Model class not found: {$modelClass}. Skipping.");
                Log::error("DataPointSeeder: Model class not found: {$modelClass}. Skipping.");
                continue;
            }
            // Check if model uses Mappable trait
            if (!in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                $this->command->error("Model {$modelClass} does not use the Mappable trait. Skipping.");
                Log::error("DataPointSeeder: Model {$modelClass} does not use the Mappable trait. Skipping.");
                continue;
            }
            $this->syncDataPointsForModel($modelClass, $cutoffDate);
        }
        $this->command->info("DataPointSeeder finished.");
        Log::info("DataPointSeeder: Run finished.");
    }

    private function syncDataPointsForModel(string $modelClass, string $cutoffDate)
    {
        $modelInstance = new $modelClass();
        $sourceTableName = $modelInstance->getTable();
        $humanName = $modelClass::getHumanName();

        $this->command->info("Processing data for model: {$humanName} (table: {$sourceTableName})");
        Log::info("DataPointSeeder: Starting sync for model '{$modelClass}' (table '{$sourceTableName}'). Cutoff date for source: {$cutoffDate}");

        try {
            $latField = $modelClass::getLatitudeField();
            $lngField = $modelClass::getLongitudeField();
            $genericFkSourceField = $modelClass::getExternalIdName(); // Field in source table for generic_foreign_id
            $specificFkSourceField = $modelInstance->getKeyName(); // Primary key of the source table
            $specificFkColumnInDataPoints = Str::snake(class_basename($modelClass)) . '_id'; // e.g., 'crime_data_id'
            $dateField = $modelClass::getDateField(); // Date field in source for filtering and alcivartech_date
            
            $totalProcessedCount = 0;
            $totalUpsertedCount = 0;
            $totalSkippedCount = 0;

            DB::table($sourceTableName)
                ->where($dateField, '>=', $cutoffDate)
                ->orderBy($specificFkSourceField) // Order by PK for consistent chunking
                ->chunkById(self::BATCH_SIZE, function ($newDataChunk) use (
                    $modelClass, $sourceTableName, $latField, $lngField, $genericFkSourceField, $specificFkSourceField, $specificFkColumnInDataPoints, $dateField,
                    &$totalProcessedCount, &$totalUpsertedCount, &$totalSkippedCount
                ) {
                    
                    $this->command->info("Processing chunk of " . $newDataChunk->count() . " records from {$sourceTableName}.");
                    Log::info("DataPointSeeder: Processing chunk of " . $newDataChunk->count() . " records from '{$sourceTableName}'.");

                    if ($newDataChunk->isEmpty()) {
                        $this->command->warn("Empty chunk encountered for {$sourceTableName}.");
                        Log::warning("DataPointSeeder: Empty chunk encountered for '{$sourceTableName}'.");
                        return true;
                    }

                    $batchInsert = [];
                    $chunkSkippedCount = 0;

                    foreach ($newDataChunk as $row) {
                        $totalProcessedCount++;
                        $genericFkValue = $row->{$genericFkSourceField} ?? null;
                        $specificFkValue = $row->{$specificFkSourceField} ?? null;
                        $alcivartechDateValue = $row->{$dateField} ?? null;
                        $latitudeValue = $row->{$latField} ?? null;
                        $longitudeValue = $row->{$lngField} ?? null;

                        if (is_null($latitudeValue) || is_null($longitudeValue) ||
                            is_null($genericFkValue) || is_null($specificFkValue) || is_null($alcivartechDateValue)) {
                            
                            $identifier = $genericFkValue ?? $specificFkValue ?? 'unknown_id_in_chunk';
                            if ($chunkSkippedCount < 5) {
                                Log::warning("DataPointSeeder: Skipping record from '{$sourceTableName}' (ID: {$identifier}) in chunk due to missing essential data.", ['row_sample' => array_slice((array)$row, 0, 5)]);
                            }
                            $chunkSkippedCount++;
                            continue;
                        }

                        if (!is_numeric($latitudeValue) || !is_numeric($longitudeValue)) {
                            $identifier = $genericFkValue;
                             if ($chunkSkippedCount < 5) {
                                Log::warning("DataPointSeeder: Skipping record from '{$sourceTableName}' (ID: {$identifier}) in chunk due to non-numeric lat/lng.", [
                                    'lat' => $latitudeValue,
                                    'lng' => $longitudeValue
                                ]);
                            }
                            $chunkSkippedCount++;
                            continue;
                        }
                        
                        try {
                            $parsedDate = Carbon::parse($alcivartechDateValue);
                        } catch (\Exception $e) {
                            $identifier = $genericFkValue;
                            if ($chunkSkippedCount < 5) {
                                Log::warning("DataPointSeeder: Invalid date format in chunk for '{$sourceTableName}' (ID: {$identifier}).", ['date_value' => $alcivartechDateValue]);
                            }
                            $chunkSkippedCount++;
                            continue;
                        }

                        $batchInsert[] = [
                            'type' => $sourceTableName, // Using table name as type
                            'location' => DB::raw("ST_GeomFromText('POINT({$longitudeValue} {$latitudeValue})')"),
                            $specificFkColumnInDataPoints => $specificFkValue,
                            'generic_foreign_id' => (string)$genericFkValue, // Ensure generic_foreign_id is string
                            'alcivartech_date' => $parsedDate,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    
                    $totalSkippedCount += $chunkSkippedCount;
                    if ($chunkSkippedCount > 0) {
                        $this->command->info("Skipped {$chunkSkippedCount} records from the current chunk of {$sourceTableName}.");
                        Log::info("DataPointSeeder: Skipped {$chunkSkippedCount} records from the current chunk of '{$sourceTableName}'.");
                    }

                    if (!empty($batchInsert)) {
                        $this->command->info("Preparing to upsert batch of " . count($batchInsert) . " valid records for {$sourceTableName}.");
                        Log::info("DataPointSeeder: Preparing to upsert batch of " . count($batchInsert) . " valid records for '{$sourceTableName}'.");
                        try {
                            DB::table('data_points')->upsert(
                                $batchInsert,
                                ['type', 'generic_foreign_id'], // Unique keys for upsert
                                ['location', 'updated_at', $specificFkColumnInDataPoints, 'alcivartech_date'] // Columns to update
                            );
                            $totalUpsertedCount += count($batchInsert);
                            $this->command->info("Successfully upserted batch of " . count($batchInsert) . " records for {$sourceTableName}. Total upserted so far: {$totalUpsertedCount}");
                            Log::info("DataPointSeeder: Successfully upserted batch of " . count($batchInsert) . " records for '{$sourceTableName}'. Total upserted so far for this model: {$totalUpsertedCount}");
                        } catch (\Exception $e) {
                            $this->command->error("Error upserting batch for {$sourceTableName}: " . $e->getMessage());
                            Log::error("DataPointSeeder: Error upserting batch for '{$sourceTableName}'.", ['exception' => $e, 'batch_size' => count($batchInsert)]);
                        }
                    } else {
                        $this->command->info("No valid records to insert in this batch for {$sourceTableName}.");
                        Log::info("DataPointSeeder: No valid records to insert in this batch for '{$sourceTableName}'.");
                    }
                    return true; 
                }, $specificFkSourceField); 

            $this->command->info("Finished processing for {$modelClass::getHumanName()}. Total records processed: {$totalProcessedCount}, Total records upserted: {$totalUpsertedCount}, Total records skipped: {$totalSkippedCount}.");
            Log::info("DataPointSeeder: Finished processing for '{$modelClass}'. Processed: {$totalProcessedCount}, Upserted: {$totalUpsertedCount}, Skipped: {$totalSkippedCount}.");

        } catch (\Exception $e) {
            $this->command->error("Failed to process data for model {$modelClass}: " . $e->getMessage());
            Log::error("DataPointSeeder: Failed to process data for model '{$modelClass}'.", ['exception' => $e]);
        }
        $this->command->info("Finished sync logic for model: {$modelClass::getHumanName()}");
        Log::info("DataPointSeeder: Finished sync logic for model '{$modelClass}'.");
    }
}
