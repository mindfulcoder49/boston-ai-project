<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeattleDataPointSeeder extends Seeder
{
    private const DAYS_TO_KEEP = 183; // Approx 6 months, matches the recent data import
    private const BATCH_SIZE = 1000;

    /**
     * Define the Mappable models to process for Seattle.
     */
    private const MODELS_TO_PROCESS = [
        \App\Models\SeattleCrime::class,
        // Add other Seattle models here in the future
    ];

    public function run()
    {
        $this->command->info("Starting SeattleDataPointSeeder...");
        Log::info("SeattleDataPointSeeder: Run started.");
        $cutoffDate = Carbon::now()->subDays(self::DAYS_TO_KEEP)->toDateTimeString();

        // Clean up old data points from the seattle_data_points table
        try {
            $deletedCount = DB::connection('seattle_db')->table('seattle_data_points')->where('alcivartech_date', '<', $cutoffDate)->delete();
            $this->command->info("Successfully deleted {$deletedCount} old Seattle data points.");
            Log::info("SeattleDataPointSeeder: Deleted {$deletedCount} old Seattle data points.");
        } catch (\Exception $e) {
            $this->command->error("Error deleting old Seattle data points: " . $e->getMessage());
            Log::error("SeattleDataPointSeeder: Error deleting old Seattle data points.", ['exception' => $e]);
        }

        foreach (self::MODELS_TO_PROCESS as $modelClass) {
            if (!in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                $this->command->error("Model {$modelClass} does not use the Mappable trait. Skipping.");
                continue;
            }
            $this->syncDataPointsForModel($modelClass, $cutoffDate);
        }
        $this->command->info("SeattleDataPointSeeder finished.");
        Log::info("SeattleDataPointSeeder: Run finished.");
    }

    private function syncDataPointsForModel(string $modelClass, string $cutoffDate)
    {
        $modelInstance = new $modelClass();
        $sourceTableName = $modelInstance->getTable();
        $humanName = $modelClass::getHumanName();

        $this->command->info("Processing Seattle data for model: {$humanName} (table: {$sourceTableName})");

        try {
            $latField = $modelClass::getLatitudeField();
            $lngField = $modelClass::getLongitudeField();
            $genericFkSourceField = $modelClass::getExternalIdName();
            $specificFkSourceField = $modelInstance->getKeyName();
            $specificFkColumnInDataPoints = Str::snake(class_basename($modelClass)) . '_id';
            $dateField = $modelClass::getDateField();

            $totalUpsertedCount = 0;

            // The source table is in the 'seattle_db' connection
            DB::connection('seattle_db')->table($sourceTableName)
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
                            continue;
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
                        DB::connection('seattle_db')->table('seattle_data_points')->upsert(
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
            Log::error("SeattleDataPointSeeder: Failed to process data for model '{$modelClass}'.", ['exception' => $e]);
        }
    }
}
