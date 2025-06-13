<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataCleanupSeeder extends Seeder
{
    /**
     * Define a batch size for chunking delete operations.
     */
    private const CLEANUP_BATCH_SIZE = 1000;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('DataCleanupSeeder: Starting data cleanup process.');
        $this->command->info('DataCleanupSeeder: Starting data cleanup process.');

        // --- Cleanup 311 Cases ---
        DB::transaction(function () {
            $cambridgeThreeOneOneCaseIds = DB::table('three_one_one_cases')
                ->where('source_city', 'Cambridge')
                ->pluck('id');

            if ($cambridgeThreeOneOneCaseIds->isNotEmpty()) {
                $deletedDataPointsThreeOneOne = DB::table('data_points')
                    ->whereIn('three_one_one_case_id', $cambridgeThreeOneOneCaseIds)
                    ->delete();
                Log::info("DataCleanupSeeder: Deleted {$deletedDataPointsThreeOneOne} related records from data_points for Cambridge 311 cases.");
                $this->command->info("Deleted {$deletedDataPointsThreeOneOne} related records from data_points for Cambridge 311 cases.");
            }

            $deletedThreeOneOne = DB::table('three_one_one_cases')->where('source_city', 'Cambridge')->delete();
            Log::info("DataCleanupSeeder: Deleted {$deletedThreeOneOne} records from three_one_one_cases where source_city was Cambridge.");
            $this->command->info("Deleted {$deletedThreeOneOne} records from three_one_one_cases (Cambridge).");
        });
        Log::info('DataCleanupSeeder: Finished cleaning 311 cases.');
        $this->command->info('Finished cleaning 311 cases.');

        // --- Cleanup Food Inspections ---
        DB::transaction(function () {
            $cambridgeFoodInspectionIds = DB::table('food_inspections')->where('city', 'Cambridge')->pluck('id');
            if ($cambridgeFoodInspectionIds->isNotEmpty()) {
                $deletedDataPointsFood = DB::table('data_points')->whereIn('food_inspection_id', $cambridgeFoodInspectionIds)->delete();
                Log::info("DataCleanupSeeder: Deleted {$deletedDataPointsFood} related records from data_points for Cambridge food inspections.");
                $this->command->info("Deleted {$deletedDataPointsFood} related records from data_points for Cambridge food inspections.");
            }
            
            $deletedFoodInspections = DB::table('food_inspections')->where('city', 'Cambridge')->delete();
            Log::info("DataCleanupSeeder: Deleted {$deletedFoodInspections} records from food_inspections where city was Cambridge.");
            $this->command->info("Deleted {$deletedFoodInspections} records from food_inspections (Cambridge).");
        });
        Log::info('DataCleanupSeeder: Finished cleaning food inspections.');
        $this->command->info('Finished cleaning food inspections.');

        // --- Cleanup Building Permits ---
        DB::transaction(function () {
            $cambridgeBuildingPermitIds = DB::table('building_permits')->where('city', 'Cambridge')->pluck('id'); 
            if ($cambridgeBuildingPermitIds->isNotEmpty()) {
                $deletedDataPointsPermits = DB::table('data_points')->whereIn('building_permit_id', $cambridgeBuildingPermitIds)->delete();
                Log::info("DataCleanupSeeder: Deleted {$deletedDataPointsPermits} related records from data_points for Cambridge building permits.");
                $this->command->info("Deleted {$deletedDataPointsPermits} related records from data_points for Cambridge building permits.");
            }

            $deletedBuildingPermits = DB::table('building_permits')->where('city', 'Cambridge')->delete();
            Log::info("DataCleanupSeeder: Deleted {$deletedBuildingPermits} records from building_permits where city was Cambridge.");
            $this->command->info("Deleted {$deletedBuildingPermits} records from building_permits (Cambridge).");
        });
        Log::info('DataCleanupSeeder: Finished cleaning building permits.');
        $this->command->info('Finished cleaning building permits.');

        // --- Cleanup Crime Data (in chunks) ---
        DB::transaction(function () {
            $totalDeletedDataPointsCrime = 0;
            $totalDeletedCrimeData = 0;

            DB::table('crime_data')
                ->where(function ($query) {
                    $query->where('source_city', '!=', 'Boston')
                          ->orWhereNull('source_city');
                })
                ->select('id') // Important: Only select 'id' for chunkById
                ->orderBy('id') // chunkById requires an ordered column, typically the primary key
                ->chunkById(self::CLEANUP_BATCH_SIZE, function ($crimeRecordsChunk) use (&$totalDeletedDataPointsCrime, &$totalDeletedCrimeData) {
                    $idsToDelete = $crimeRecordsChunk->pluck('id');

                    if ($idsToDelete->isNotEmpty()) {
                        // Delete related data_points for this chunk
                        $deletedDPChunk = DB::table('data_points')
                            ->whereIn('crime_data_id', $idsToDelete)
                            ->delete();
                        $totalDeletedDataPointsCrime += $deletedDPChunk;
                        Log::info("DataCleanupSeeder (Chunk): Deleted {$deletedDPChunk} related data_points for non-Boston crime data.");
                        $this->command->info("Deleted {$deletedDPChunk} data_points (crime chunk).");

                        // Delete crime_data records for this chunk
                        $deletedCChunk = DB::table('crime_data')->whereIn('id', $idsToDelete)->delete();
                        $totalDeletedCrimeData += $deletedCChunk;
                        Log::info("DataCleanupSeeder (Chunk): Deleted {$deletedCChunk} crime_data records.");
                        $this->command->info("Deleted {$deletedCChunk} crime_data records (chunk).");
                    }
                }, 'id'); // Explicitly use 'id' column for chunking

            Log::info("DataCleanupSeeder: Total deleted related records from data_points for non-Boston crime data: {$totalDeletedDataPointsCrime}.");
            $this->command->info("Total deleted data_points (crime): {$totalDeletedDataPointsCrime}.");
            Log::info("DataCleanupSeeder: Total deleted records from crime_data where source_city was not Boston or was NULL: {$totalDeletedCrimeData}.");
            $this->command->info("Total deleted crime_data (non-Boston): {$totalDeletedCrimeData}.");
        });
        Log::info('DataCleanupSeeder: Finished cleaning crime data.');
        $this->command->info('Finished cleaning crime data.');

        Log::info('DataCleanupSeeder: Data cleanup process finished.');
        $this->command->info('DataCleanupSeeder: Data cleanup process finished.');
    }
}
