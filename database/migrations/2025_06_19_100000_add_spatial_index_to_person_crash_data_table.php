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
        // Step 1: Add the 'location' column as nullable first.
        Schema::connection('person_crash_data_db')->table('person_crash_data', function (Blueprint $table) {
            $table->point('location', 4326)->after('lon')->nullable();
        });

        // Step 2: Populate the new 'location' column from existing lat/lon data.
        DB::connection('person_crash_data_db')->update(
            'UPDATE person_crash_data SET location = ST_SRID(POINT(lon, lat), 4326) WHERE lon IS NOT NULL AND lat IS NOT NULL'
        );

        // Step 3: Modify the column to be NOT NULL and then add the spatial index.
        // This requires all rows to have a non-null 'location' value.
        Schema::connection('person_crash_data_db')->table('person_crash_data', function (Blueprint $table) {
            $table->point('location', 4326)->nullable(false)->change();
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
        Schema::connection('person_crash_data_db')->table('person_crash_data', function (Blueprint $table) {
            $table->dropSpatialIndex(['location']);
            $table->dropColumn('location');
        });
    }
}
