<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSpatialIndexToLocalPersonCrashDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Add the 'location' column as nullable first using a raw statement for MariaDB compatibility.
        DB::connection('mysql')->statement('ALTER TABLE `person_crash_data` ADD `location` POINT NULL AFTER `lon`');

        // Step 2: Populate the new 'location' column from existing lat/lon data.
        DB::connection('mysql')->update(
            'UPDATE person_crash_data SET location = ST_GeomFromText(CONCAT("POINT(", lon, " ", lat, ")"), 4326) WHERE lon IS NOT NULL AND lat IS NOT NULL'
        );

        // Step 3: Modify the column to be NOT NULL and then add the spatial index.
        DB::connection('mysql')->statement('ALTER TABLE `person_crash_data` MODIFY `location` POINT NOT NULL');
        Schema::connection('mysql')->table('person_crash_data', function (Blueprint $table) {
            $table->spatialIndex('location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->table('person_crash_data', function (Blueprint $table) {
            $table->dropSpatialIndex(['location']);
            $table->dropColumn('location');
        });
    }
}
