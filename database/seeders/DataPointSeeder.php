<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // Add Log facade

class DataPointSeeder extends Seeder
{
    private const DAYS_TO_KEEP = 183; // Approx 6 months (6 * 30.5)
    private const BATCH_SIZE = 1000; // Number of records to upsert in each batch

    private const MODELS = [
        'crime_data' => [
            'lat' => 'lat', 'lng' => 'long',
            'id_field_for_generic_fk' => 'id', // Source column for data_points.generic_foreign_id
            'id_field_for_specific_fk' => 'id', // Source column for data_points.crime_data_id
            'foreign_key' => 'crime_data_id',   // Column name in data_points for specific FK
            'date_field_for_source_filter' => 'occurred_on_date', // Date field in source table for filtering recent items
            'alcivartech_date_field' => 'occurred_on_date' // Source column for data_points.alcivartech_date
        ],
        'three_one_one_cases' => [
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'three_one_one_case_id',
            'date_field_for_source_filter' => 'open_dt',
            'alcivartech_date_field' => 'open_dt'
        ],
        'property_violations' => [
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'property_violation_id',
            'date_field_for_source_filter' => 'status_dttm',
            'alcivartech_date_field' => 'status_dttm'
        ],
        'construction_off_hours' => [
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'construction_off_hour_id',
            'date_field_for_source_filter' => 'start_datetime',
            'alcivartech_date_field' => 'start_datetime'
        ],
        'building_permits' => [
            'lat' => 'y_latitude', 'lng' => 'x_longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'building_permit_id',
            'date_field_for_source_filter' => 'issued_date',
            'alcivartech_date_field' => 'issued_date'
        ],
        'food_inspections' => [ // Corrected from food_establishment_violations and using food_inspections table structure
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'external_id', // external_id from food_inspections for generic_foreign_id
            'id_field_for_specific_fk' => 'id',        // PK id from food_inspections for food_inspection_id
            'foreign_key' => 'food_inspection_id',
            'date_field_for_source_filter' => 'resultdttm', // Using resultdttm as the primary date
            'alcivartech_date_field' => 'resultdttm'
        ],
    ];

    public function run()
    {
        $this->command->info("Starting DataPointSeeder...");
        Log::info("DataPointSeeder: Run started.");
        $cutoffDate = Carbon::now()->subDays(self::DAYS_TO_KEEP)->toDateTimeString();

        // Delete old records from `data_points` based on alcivartech_date
        try {
            $deletedCount = DB::table('data_points')->where('alcivartech_date', '<', $cutoffDate)->delete();
            $this->command->info("Successfully deleted {$deletedCount} old data points with alcivartech_date older than " . self::DAYS_TO_KEEP . " days.");
            Log::info("DataPointSeeder: Deleted {$deletedCount} old data points with alcivartech_date older than " . self::DAYS_TO_KEEP . " days.");
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
        Log::info("DataPointSeeder: Starting sync for table '{$table}'. Cutoff date for source: {$cutoffDate}");
        Log::info("DataPointSeeder: Fields used for table '{$table}'", $fields);

        try {
            $sourceDateField = $fields['date_field_for_source_filter'];
            
            $totalProcessedCount = 0;
            $totalUpsertedCount = 0;
            $totalSkippedCount = 0;

            // Process in chunks from the source table to avoid memory issues with very large source tables
            DB::table($table)
                ->where($sourceDateField, '>=', $cutoffDate)
                ->orderBy($fields['id_field_for_specific_fk']) // Order by PK for consistent chunking
                ->chunkById(self::BATCH_SIZE, function ($newDataChunk) use ($table, $fields, &$totalProcessedCount, &$totalUpsertedCount, &$totalSkippedCount) {
                    
                    $this->command->info("Processing chunk of " . $newDataChunk->count() . " records from {$table}.");
                    Log::info("DataPointSeeder: Processing chunk of " . $newDataChunk->count() . " records from '{$table}'.");

                    if ($newDataChunk->isEmpty()) {
                        $this->command->warn("Empty chunk encountered for {$table}.");
                        Log::warning("DataPointSeeder: Empty chunk encountered for '{$table}'.");
                        return true; // Continue to next chunk
                    }

                    $batchInsert = [];
                    $chunkSkippedCount = 0;

                    foreach ($newDataChunk as $row) {
                        $totalProcessedCount++;
                        $genericFkValue = $row->{$fields['id_field_for_generic_fk']} ?? null;
                        $specificFkValue = $row->{$fields['id_field_for_specific_fk']} ?? null;
                        $alcivartechDateValue = $row->{$fields['alcivartech_date_field']} ?? null;

                        if (!isset($row->{$fields['lat']}) || is_null($row->{$fields['lat']}) ||
                            !isset($row->{$fields['lng']}) || is_null($row->{$fields['lng']}) ||
                            is_null($genericFkValue) || is_null($specificFkValue) || is_null($alcivartechDateValue)) {
                            
                            $identifier = $genericFkValue ?? $specificFkValue ?? 'unknown_id_in_chunk';
                            // Log only a few of these to avoid flooding logs, or use a counter
                            if ($chunkSkippedCount < 5) { // Log first 5 skipped in a chunk
                                Log::warning("DataPointSeeder: Skipping record from '{$table}' (ID: {$identifier}) in chunk due to missing essential data.", ['row_data_sample' => $row]);
                            }
                            $chunkSkippedCount++;
                            continue;
                        }

                        if (!is_numeric($row->{$fields['lat']}) || !is_numeric($row->{$fields['lng']})) {
                            $identifier = $genericFkValue;
                             if ($chunkSkippedCount < 5) {
                                Log::warning("DataPointSeeder: Skipping record from '{$table}' (ID: {$identifier}) in chunk due to non-numeric lat/lng.", [
                                    'lat' => $row->{$fields['lat']},
                                    'lng' => $row->{$fields['lng']}
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
                                Log::warning("DataPointSeeder: Invalid date format in chunk for '{$table}' (ID: {$identifier}).", ['date_value' => $alcivartechDateValue]);
                            }
                            $chunkSkippedCount++;
                            continue;
                        }

                        $batchInsert[] = [
                            'type' => $table,
                            'location' => DB::raw("ST_GeomFromText('POINT({$row->{$fields['lng']}} {$row->{$fields['lat']}})')"),
                            $fields['foreign_key'] => $specificFkValue,
                            'generic_foreign_id' => $genericFkValue,
                            'alcivartech_date' => $parsedDate,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    
                    $totalSkippedCount += $chunkSkippedCount;
                    if ($chunkSkippedCount > 0) {
                        $this->command->info("Skipped {$chunkSkippedCount} records from the current chunk of {$table}.");
                        Log::info("DataPointSeeder: Skipped {$chunkSkippedCount} records from the current chunk of '{$table}'.");
                    }

                    if (!empty($batchInsert)) {
                        $this->command->info("Preparing to upsert batch of " . count($batchInsert) . " valid records for {$table}.");
                        Log::info("DataPointSeeder: Preparing to upsert batch of " . count($batchInsert) . " valid records for '{$table}'.");
                        try {
                            DB::table('data_points')->upsert(
                                $batchInsert,
                                ['type', 'generic_foreign_id'],
                                ['location', 'updated_at', $fields['foreign_key'], 'alcivartech_date']
                            );
                            $totalUpsertedCount += count($batchInsert);
                            $this->command->info("Successfully upserted batch of " . count($batchInsert) . " records for {$table}. Total upserted so far: {$totalUpsertedCount}");
                            Log::info("DataPointSeeder: Successfully upserted batch of " . count($batchInsert) . " records for '{$table}'. Total upserted so far for this table: {$totalUpsertedCount}");
                        } catch (\Exception $e) {
                            $this->command->error("Error upserting batch for {$table}: " . $e->getMessage());
                            Log::error("DataPointSeeder: Error upserting batch for '{$table}'.", ['exception' => $e, 'batch_size' => count($batchInsert)]);
                            // Optionally, decide if you want to stop or continue on batch error
                        }
                    } else {
                        $this->command->info("No valid records to insert in this batch for {$table}.");
                        Log::info("DataPointSeeder: No valid records to insert in this batch for '{$table}'.");
                    }
                    return true; // Continue to the next chunk
                }, $fields['id_field_for_specific_fk']); // Column to use for chunking, usually the primary key

            $this->command->info("Finished processing for {$table}. Total records processed: {$totalProcessedCount}, Total records upserted: {$totalUpsertedCount}, Total records skipped: {$totalSkippedCount}.");
            Log::info("DataPointSeeder: Finished processing for '{$table}'. Processed: {$totalProcessedCount}, Upserted: {$totalUpsertedCount}, Skipped: {$totalSkippedCount}.");

        } catch (\Exception $e) {
            $this->command->error("Failed to process data for table {$table}: " . $e->getMessage());
            Log::error("DataPointSeeder: Failed to process data for table '{$table}'.", ['exception' => $e]);
        }
        $this->command->info("Finished sync logic for table: {$table}");
        Log::info("DataPointSeeder: Finished sync logic for table '{$table}'.");
    }
}
