<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AllTimeDataPointSeeder extends Seeder
{
    private const BATCH_SIZE = 1000; // Number of records to upsert in each batch

    // Re-use the MODELS definition from DataPointSeeder
    private const MODELS = [
        'crime_data' => [
            'lat' => 'lat', 'lng' => 'long',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'crime_data_id',
            'alcivartech_date_field' => 'occurred_on_date'
        ],
        'three_one_one_cases' => [
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'three_one_one_case_id',
            'alcivartech_date_field' => 'open_dt'
        ],
        'property_violations' => [
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'property_violation_id',
            'alcivartech_date_field' => 'status_dttm'
        ],
        'construction_off_hours' => [
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'construction_off_hour_id',
            'alcivartech_date_field' => 'start_datetime'
        ],
        'building_permits' => [
            'lat' => 'y_latitude', 'lng' => 'x_longitude',
            'id_field_for_generic_fk' => 'id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'building_permit_id',
            'alcivartech_date_field' => 'issued_date'
        ],
        'food_inspections' => [
            'lat' => 'latitude', 'lng' => 'longitude',
            'id_field_for_generic_fk' => 'external_id',
            'id_field_for_specific_fk' => 'id',
            'foreign_key' => 'food_inspection_id',
            'alcivartech_date_field' => 'resultdttm'
        ],
    ];

    public function run()
    {
        $this->command->info("Starting AllTimeDataPointSeeder...");
        Log::info("AllTimeDataPointSeeder: Run started.");

        // No deletion of old records for this seeder.

        foreach (self::MODELS as $table => $fields) {
            $this->syncAllTimeDataPoints($table, $fields);
        }
        $this->command->info("AllTimeDataPointSeeder finished.");
        Log::info("AllTimeDataPointSeeder: Run finished.");
    }

    private function syncAllTimeDataPoints(string $table, array $fields)
    {
        $this->command->info("Processing all-time data for table: {$table}");
        Log::info("AllTimeDataPointSeeder: Starting sync for table '{$table}'.");
        Log::info("AllTimeDataPointSeeder: Fields used for table '{$table}'", $fields);

        try {
            $totalProcessedCount = 0;
            $totalUpsertedCount = 0;
            $totalSkippedCount = 0;

            // Process all records from the source table in chunks
            DB::table($table)
                // No date filtering on source table, fetch all records
                ->orderBy($fields['id_field_for_specific_fk']) // Order by PK for consistent chunking
                ->chunkById(self::BATCH_SIZE, function ($newDataChunk) use ($table, $fields, &$totalProcessedCount, &$totalUpsertedCount, &$totalSkippedCount) {
                    
                    $this->command->info("Processing chunk of " . $newDataChunk->count() . " records from {$table} for all_time_data_points.");
                    Log::info("AllTimeDataPointSeeder: Processing chunk of " . $newDataChunk->count() . " records from '{$table}'.");

                    if ($newDataChunk->isEmpty()) {
                        return true; 
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
                            if ($chunkSkippedCount < 5) {
                                Log::warning("AllTimeDataPointSeeder: Skipping record from '{$table}' (ID: {$identifier}) in chunk due to missing essential data.", ['row_data_sample' => $row]);
                            }
                            $chunkSkippedCount++;
                            continue;
                        }

                        if (!is_numeric($row->{$fields['lat']}) || !is_numeric($row->{$fields['lng']})) {
                            $identifier = $genericFkValue;
                             if ($chunkSkippedCount < 5) {
                                Log::warning("AllTimeDataPointSeeder: Skipping record from '{$table}' (ID: {$identifier}) in chunk due to non-numeric lat/lng.", [
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
                                Log::warning("AllTimeDataPointSeeder: Invalid date format in chunk for '{$table}' (ID: {$identifier}).", ['date_value' => $alcivartechDateValue]);
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
                        $this->command->info("Skipped {$chunkSkippedCount} records from the current chunk of {$table} for all_time_data_points.");
                    }

                    if (!empty($batchInsert)) {
                        $this->command->info("Preparing to upsert batch of " . count($batchInsert) . " valid records into all_time_data_points for {$table}.");
                        Log::info("AllTimeDataPointSeeder: Preparing to upsert batch of " . count($batchInsert) . " valid records into all_time_data_points for '{$table}'.");
                        try {
                            DB::table('all_time_data_points')->upsert( // Target all_time_data_points
                                $batchInsert,
                                ['type', 'generic_foreign_id'],
                                ['location', 'updated_at', $fields['foreign_key'], 'alcivartech_date']
                            );
                            $totalUpsertedCount += count($batchInsert);
                            $this->command->info("Successfully upserted batch of " . count($batchInsert) . " records into all_time_data_points for {$table}. Total upserted so far: {$totalUpsertedCount}");
                        } catch (\Exception $e) {
                            $this->command->error("Error upserting batch into all_time_data_points for {$table}: " . $e->getMessage());
                            Log::error("AllTimeDataPointSeeder: Error upserting batch for '{$table}'.", ['exception' => $e, 'batch_size' => count($batchInsert)]);
                        }
                    } else {
                        $this->command->info("No valid records to insert in this batch into all_time_data_points for {$table}.");
                    }
                    return true; 
                }, $fields['id_field_for_specific_fk']); 

            $this->command->info("Finished processing for {$table} into all_time_data_points. Total records processed: {$totalProcessedCount}, Total records upserted: {$totalUpsertedCount}, Total records skipped: {$totalSkippedCount}.");
            Log::info("AllTimeDataPointSeeder: Finished processing for '{$table}'. Processed: {$totalProcessedCount}, Upserted: {$totalUpsertedCount}, Skipped: {$totalSkippedCount}.");

        } catch (\Exception $e) {
            $this->command->error("Failed to process data for table {$table} for all_time_data_points: " . $e->getMessage());
            Log::error("AllTimeDataPointSeeder: Failed to process data for table '{$table}'.", ['exception' => $e]);
        }
        $this->command->info("Finished sync logic for table: {$table} for all_time_data_points");
    }
}
