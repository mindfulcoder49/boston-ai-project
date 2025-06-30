<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSpatialIndexToPersonCrashDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::connection('person_crash_data_db')->hasColumn('person_crash_data', 'location')) {
            // Step 1: Add the 'location' column as nullable first using a raw statement for MariaDB compatibility.
            DB::connection('person_crash_data_db')->statement('ALTER TABLE `person_crash_data` ADD `location` POINT NULL AFTER `lon`');

            // Step 2: Populate the new 'location' column from existing lat/lon data.
            DB::connection('person_crash_data_db')->update(
                'UPDATE person_crash_data SET location = ST_GeomFromText(CONCAT("POINT(", lon, " ", lat, ")"), 4326) WHERE lon IS NOT NULL AND lat IS NOT NULL'
            );

            // Step 3: Modify the column to be NOT NULL and then add the spatial index.
            DB::connection('person_crash_data_db')->statement('ALTER TABLE `person_crash_data` MODIFY `location` POINT NOT NULL');
            Schema::connection('person_crash_data_db')->table('person_crash_data', function (Blueprint $table) {
                $table->spatialIndex('location');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::connection('person_crash_data_db')->hasColumn('person_crash_data', 'location')) {
            Schema::connection('person_crash_data_db')->table('person_crash_data', function (Blueprint $table) {
                $table->dropSpatialIndex(['location']);
                $table->dropColumn('location');
            });
        }
    }
}
