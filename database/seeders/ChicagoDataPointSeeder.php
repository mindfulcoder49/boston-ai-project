<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChicagoDataPointSeeder extends Seeder
{
    private const DAYS_TO_KEEP = 183; // Approx 6 months, matches the recent data import
    private const BATCH_SIZE = 1000;

    /**
     * Define the Mappable models to process for Chicago.
     */
    private const MODELS_TO_PROCESS = [
        \App\Models\ChicagoCrime::class,
        // Add other Chicago models here in the future
    ];

    public function run()
    {
        $this->command->info("Starting ChicagoDataPointSeeder...");
        Log::info("ChicagoDataPointSeeder: Run started.");
        $cutoffDate = Carbon::now()->subDays(self::DAYS_TO_KEEP)->toDateTimeString();

        // Clean up old data points from the chicago_data_points table
        try {
            $deletedCount = DB::connection('chicago_db')->table('chicago_data_points')->where('alcivartech_date', '<', $cutoffDate)->delete();
            $this->command->info("Successfully deleted {$deletedCount} old Chicago data points.");
            Log::info("ChicagoDataPointSeeder: Deleted {$deletedCount} old Chicago data points.");
        } catch (\Exception $e) {
            $this->command->error("Error deleting old Chicago data points: " . $e->getMessage());
            Log::error("ChicagoDataPointSeeder: Error deleting old Chicago data points.", ['exception' => $e]);
        }

        foreach (self::MODELS_TO_PROCESS as $modelClass) {
            if (!in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                $this->command->error("Model {$modelClass} does not use the Mappable trait. Skipping.");
                continue;
            }
            $this->syncDataPointsForModel($modelClass, $cutoffDate);
        }
        $this->command->info("ChicagoDataPointSeeder finished.");
        Log::info("ChicagoDataPointSeeder: Run finished.");
    }

    private function syncDataPointsForModel(string $modelClass, string $cutoffDate)
    {
        $modelInstance = new $modelClass();
        // We are seeding from the recent data table, which is in the 'chicago_db'
        $sourceTableName = $modelInstance->getTable();
        $humanName = $modelClass::getHumanName();

        $this->command->info("Processing Chicago data for model: {$humanName} (table: {$sourceTableName})");

        try {
            $latField = $modelClass::getLatitudeField();
            $lngField = $modelClass::getLongitudeField();
            $genericFkSourceField = $modelClass::getExternalIdName();
            $specificFkSourceField = $modelInstance->getKeyName();
            $specificFkColumnInDataPoints = Str::snake(class_basename($modelClass)) . '_id';
            $dateField = $modelClass::getDateField();
            
            $totalUpsertedCount = 0;

            // The source table is in the 'chicago_db' connection
            DB::connection('chicago_db')->table($sourceTableName)
                ->where($dateField, '>=', $cutoffDate)
                ->orderBy($specificFkSourceField)
                ->chunkById(self::BATCH_SIZE, function ($newDataChunk) use (
                    $sourceTableName, $latField, $lngField, $genericFkSourceField, $specificFkSourceField, $specificFkColumnInDataPoints, $dateField,
                    &$totalUpsertedCount
                ) {
                    $batchInsert = [];
                    foreach ($newDataChunk as $row) {
                        $latitudeValue = $row->{$latField} ?? null;
                        $longitudeValue = $row->{$lngField} ?? null;
                        $genericFkValue = $row->{$genericFkSourceField} ?? null;
                        $specificFkValue = $row->{$specificFkSourceField} ?? null;
                        $alcivartechDateValue = $row->{$dateField} ?? null;

                        if (is_null($latitudeValue) || is_null($longitudeValue) || is_null($genericFkValue) || is_null($specificFkValue) || is_null($alcivartechDateValue)) {
                            continue; // Skip records with essential missing data
                        }

                        $batchInsert[] = [
                            'type' => $sourceTableName,
                            'location' => DB::raw("ST_GeomFromText('POINT({$longitudeValue} {$latitudeValue})')"),
                            $specificFkColumnInDataPoints => $specificFkValue,
                            'generic_foreign_id' => (string)$genericFkValue,
                            'alcivartech_date' => Carbon::parse($alcivartechDateValue),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($batchInsert)) {
                        DB::connection('chicago_db')->table('chicago_data_points')->upsert(
                            $batchInsert,
                            ['type', 'generic_foreign_id'],
                            ['location', 'updated_at', $specificFkColumnInDataPoints, 'alcivartech_date']
                        );
                        $totalUpsertedCount += count($batchInsert);
                        $this->command->info("Upserted " . count($batchInsert) . " records for {$sourceTableName}. Total so far: {$totalUpsertedCount}");
                    }
                }, $specificFkSourceField);

            $this->command->info("Finished processing for {$humanName}. Total records upserted: {$totalUpsertedCount}.");

        } catch (\Exception $e) {
            $this->command->error("Failed to process data for model {$modelClass}: " . $e->getMessage());
            Log::error("ChicagoDataPointSeeder: Failed to process data for model '{$modelClass}'.", ['exception' => $e]);
        }
    }
}
