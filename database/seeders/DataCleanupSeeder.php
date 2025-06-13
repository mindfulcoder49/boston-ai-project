<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataCleanupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('DataCleanupSeeder: Starting data cleanup process.');
        $this->command->info('DataCleanupSeeder: Starting data cleanup process.');

        // Delete Cambridge data from three_one_one_cases
        $deletedThreeOneOne = DB::table('three_one_one_cases')->where('source_city', 'Cambridge')->delete();
        Log::info("DataCleanupSeeder: Deleted {$deletedThreeOneOne} records from three_one_one_cases where source_city was Cambridge.");
        $this->command->info("Deleted {$deletedThreeOneOne} records from three_one_one_cases (Cambridge).");

        // Delete Cambridge data from food_inspections
        $deletedFoodInspections = DB::table('food_inspections')->where('city', 'Cambridge')->delete();
        Log::info("DataCleanupSeeder: Deleted {$deletedFoodInspections} records from food_inspections where city was Cambridge.");
        $this->command->info("Deleted {$deletedFoodInspections} records from food_inspections (Cambridge).");

        // Delete Cambridge data from building_permits
        $deletedBuildingPermits = DB::table('building_permits')->where('city', 'Cambridge')->delete();
        Log::info("DataCleanupSeeder: Deleted {$deletedBuildingPermits} records from building_permits where city was Cambridge.");
        $this->command->info("Deleted {$deletedBuildingPermits} records from building_permits (Cambridge).");

        // Delete non-Boston data from crime_data
        // This will remove records where source_city is not 'Boston' or where source_city is NULL.
        $deletedCrimeData = DB::table('crime_data')
            ->where(function ($query) {
                $query->where('source_city', '!=', 'Boston')
                      ->orWhereNull('source_city');
            })
            ->delete();
        Log::info("DataCleanupSeeder: Deleted {$deletedCrimeData} records from crime_data where source_city was not Boston or was NULL.");
        $this->command->info("Deleted {$deletedCrimeData} records from crime_data (non-Boston or NULL source_city).");

        Log::info('DataCleanupSeeder: Data cleanup process finished.');
        $this->command->info('DataCleanupSeeder: Data cleanup process finished.');
    }
}
