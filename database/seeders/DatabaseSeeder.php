<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TrashSchedulesByAddressSeeder::class,
            CrimeDataSeeder::class,
            ThreeOneOneSeeder::class,
            BuildingPermitsSeeder::class,
            PropertyViolationsSeeder::class,
            ConstructionOffHoursSeeder::class,
            FoodInspectionsSeeder::class,
            CambridgeBuildingPermitsSeeder::class,
            CambridgeThreeOneOneSeeder::class,
            CambridgeFoodInspectionSeeder::class,
            CambridgePropertyViolationsSeeder::class,
            CambridgeAddressesSeeder::class,
            CambridgeIntersectionsSeeder::class,
            CambridgeCrimeDataSeederMerge::class,
            CambridgePoliceLogSeeder::class,
            EverettCrimeDataSeeder::class, // Add this line
        ]);
    }
}
