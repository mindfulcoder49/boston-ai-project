<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // Add Log facade

class DataPointSeeder extends Seeder
{
    private const DAYS_TO_KEEP = 14; // Change this to adjust the timeframe

    private const MODELS = [
        'crime_data' => ['lat' => 'lat', 'lng' => 'long', 'id' => 'id', 'date_field' => 'occurred_on_date', 'foreign_key' => 'crime_data_id'],
        'three_one_one_cases' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'open_dt', 'foreign_key' => 'three_one_one_case_id'],
        'property_violations' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'status_dttm', 'foreign_key' => 'property_violation_id'],
        'construction_off_hours' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'start_datetime', 'foreign_key' => 'construction_off_hour_id'],
        'building_permits' => ['lat' => 'y_latitude', 'lng' => 'x_longitude', 'id' => 'id', 'date_field' => 'issued_date', 'foreign_key' => 'building_permit_id'],
        'food_inspections' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'violdttm', 'foreign_key' => 'food_inspection_id'],
        'food_inspections' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'external_id', 'date_field' => 'resultdttm', 'foreign_key' => 'food_inspection_id'],
    ];

    public function run()
    {
        $this->command->info("Starting DataPointSeeder...");
        Log::info("DataPointSeeder: Run started.");
        $cutoffDate = Carbon::now()->subDays(self::DAYS_TO_KEEP)->toDateTimeString();

        // Delete old records from `data_points`
        try {
            $deletedCount = DB::table('data_points')->where('created_at', '<', $cutoffDate)->delete();
            $this->command->info("Successfully deleted {$deletedCount} old data points older than " . self::DAYS_TO_KEEP . " days.");
            Log::info("DataPointSeeder: Deleted {$deletedCount} old data points older than " . self::DAYS_TO_KEEP . " days.");
        } catch (\Exception $e) {
            $this->command->error("Error deleting old data points: " . $e->getMessage());
            Log::error("DataPointSeeder: Error deleting old data points.", ['exception' => $e]);
        }

        foreach (self::MODELS as $table => $fields) {
            $this->syncDataPoints($table, $fields, $cutoffDate);
        }
        $this->command->info("DataPointSeeder finished.");
        Log::info("DataPointSeeder: Run finished.");
    }

    private function syncDataPoints(string $table, array $fields, string $cutoffDate)
    {
        $this->command->info("Processing data for table: {$table}");
        Log::info("DataPointSeeder: Starting sync for table '{$table}'. Cutoff date: {$cutoffDate}");
        //Log the fields being used
        Log::info("DataPointSeeder: Fields used for table '{$table}'", $fields);

        try {
            $newData = DB::table($table)
                ->where($fields['date_field'], '>=', $cutoffDate)
                // It's better to check for nullity of lat/lng on a per-row basis later
                // as some rows might have it and others not.
                // ->whereNotNull($fields['lat']) 
                // ->whereNotNull($fields['lng'])
                ->get();

            $this->command->info("Fetched " . $newData->count() . " potential records from {$table}.");
            Log::info("DataPointSeeder: Fetched " . $newData->count() . " potential records from '{$table}'.");

            if ($newData->isEmpty()) {
                $this->command->warn("No new records found for {$table} within the date range.");
                Log::warning("DataPointSeeder: No new records found for '{$table}' within the date range.");
                return;
            }

            $batchInsert = [];
            $skippedCount = 0;
            foreach ($newData as $row) {
                // Validate essential fields for creating a point
                if (!isset($row->{$fields['lat']}) || is_null($row->{$fields['lat']}) ||
                    !isset($row->{$fields['lng']}) || is_null($row->{$fields['lng']}) ||
                    !isset($row->{$fields['id']}) || is_null($row->{$fields['id']})) {
                    
                    $identifier = $row->{$fields['id']} ?? 'unknown_id';
                    $this->command->warn("Skipping record from {$table} (ID: {$identifier}) due to missing lat, lng, or id.");
                    Log::warning("DataPointSeeder: Skipping record from '{$table}' (ID: {$identifier}) due to missing lat/lng/id.", ['row_data' => $row]);
                    $skippedCount++;
                    continue;
                }

                // Ensure lat/lng are numeric before creating POINT
                if (!is_numeric($row->{$fields['lat']}) || !is_numeric($row->{$fields['lng']})) {
                    $identifier = $row->{$fields['id']};
                    $this->command->warn("Skipping record from {$table} (ID: {$identifier}) due to non-numeric lat/lng values.");
                    Log::warning("DataPointSeeder: Skipping record from '{$table}' (ID: {$identifier}) due to non-numeric lat/lng.", [
                        'lat' => $row->{$fields['lat']},
                        'lng' => $row->{$fields['lng']},
                        'row_data' => $row
                    ]);
                    $skippedCount++;
                    continue;
                }


                $batchInsert[] = [
                    'type' => $table,
                    'location' => DB::raw("ST_GeomFromText('POINT({$row->{$fields['lng']}} {$row->{$fields['lat']}})')"),
                    $fields['foreign_key'] => $row->{$fields['id']},
                    'generic_foreign_id' => $row->{$fields['id']}, // Add generic foreign ID
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            if ($skippedCount > 0) {
                $this->command->info("Skipped {$skippedCount} records from {$table} due to missing/invalid data.");
            }

            if (!empty($batchInsert)) {
                $this->command->info("Preparing to upsert " . count($batchInsert) . " valid records for {$table}.");
                Log::info("DataPointSeeder: Preparing to upsert " . count($batchInsert) . " valid records for '{$table}'.");
                try {
                    // Update upsert to use type and generic_foreign_id as unique keys
                    // and specify columns to update on conflict.
                    DB::table('data_points')->upsert(
                        $batchInsert,
                        ['type', 'generic_foreign_id'], // Unique constraint columns
                        ['location', 'updated_at', $fields['foreign_key']] // Columns to update on duplicate
                    );
                    $this->command->info("Successfully upserted " . count($batchInsert) . " records for {$table}.");
                    Log::info("DataPointSeeder: Successfully upserted " . count($batchInsert) . " records for '{$table}'.");
                } catch (\Exception $e) {
                    $this->command->error("Error upserting data for {$table}: " . $e->getMessage());
                    Log::error("DataPointSeeder: Error upserting data for '{$table}'.", ['exception' => $e, 'batch_sample' => array_slice($batchInsert, 0, 2)]);
                }
            } else {
                $this->command->info("No valid records to insert for {$table} after filtering.");
                Log::info("DataPointSeeder: No valid records to insert for '{$table}' after filtering.");
            }

        } catch (\Exception $e) {
            $this->command->error("Failed to process data for table {$table}: " . $e->getMessage());
            Log::error("DataPointSeeder: Failed to process data for table '{$table}'.", ['exception' => $e]);
        }
        $this->command->info("Finished processing data for table: {$table}");
        Log::info("DataPointSeeder: Finished sync for table '{$table}'.");
    }
}
