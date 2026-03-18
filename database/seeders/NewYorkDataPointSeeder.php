<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewYorkDataPointSeeder extends Seeder
{
    private const DAYS_TO_KEEP = 183; // Approx 6 months, matches the recent data import
    private const BATCH_SIZE = 1000;

    /**
     * Define the Mappable models to process for NewYork.
     */
    private const MODELS_TO_PROCESS = [
        \App\Models\NewYork311::class,
        // Add other NewYork models here in the future
    ];

    public function run()
    {
        $this->command->info("Starting NewYorkDataPointSeeder...");
        Log::info("NewYorkDataPointSeeder: Run started.");
        $cutoffDate = Carbon::now()->subDays(self::DAYS_TO_KEEP)->toDateTimeString();

        // Clean up old data points from the new_york_data_points table
        try {
            $deletedCount = DB::connection('new_york_db')->table('new_york_data_points')->where('alcivartech_date', '<', $cutoffDate)->delete();
            $this->command->info("Successfully deleted {$deletedCount} old NewYork data points.");
            Log::info("NewYorkDataPointSeeder: Deleted {$deletedCount} old NewYork data points.");
        } catch (\Exception $e) {
            $this->command->error("Error deleting old NewYork data points: " . $e->getMessage());
            Log::error("NewYorkDataPointSeeder: Error deleting old NewYork data points.", ['exception' => $e]);
        }

        foreach (self::MODELS_TO_PROCESS as $modelClass) {
            if (!in_array(\App\Models\Concerns\Mappable::class, class_uses_recursive($modelClass))) {
                $this->command->error("Model {$modelClass} does not use the Mappable trait. Skipping.");
                continue;
            }
            $this->syncDataPointsForModel($modelClass, $cutoffDate);
        }
        $this->command->info("NewYorkDataPointSeeder finished.");
        Log::info("NewYorkDataPointSeeder: Run finished.");
    }

    private function syncDataPointsForModel(string $modelClass, string $cutoffDate)
    {
        $modelInstance = new $modelClass();
        $sourceTableName = $modelInstance->getTable();
        $humanName = $modelClass::getHumanName();

        $this->command->info("Processing NewYork data for model: {$humanName} (table: {$sourceTableName})");

        try {
            $latField = $modelClass::getLatitudeField();
            $lngField = $modelClass::getLongitudeField();
            $genericFkSourceField = $modelClass::getExternalIdName();
            $specificFkSourceField = $modelInstance->getKeyName();
            $specificFkColumnInDataPoints = 'new_york_311_id';
            $dateField = $modelClass::getDateField();

            $totalUpsertedCount = 0;

            // The source table is in the 'new_york_db' connection
            DB::connection('new_york_db')->table($sourceTableName)
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
                        DB::connection('new_york_db')->table('new_york_data_points')->upsert(
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
            Log::error("NewYorkDataPointSeeder: Failed to process data for model '{$modelClass}'.", ['exception' => $e]);
        }
    }
}
