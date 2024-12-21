<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Add indexes to the three_one_one_cases table
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->index('latitude', 'three_one_one_cases_lat_idx');
            $table->index('longitude', 'three_one_one_cases_long_idx');
        });

        // Add indexes to the crime_data table
        Schema::table('crime_data', function (Blueprint $table) {
            $table->index('lat', 'crime_data_lat_idx');
            $table->index('long', 'crime_data_long_idx');
        });

        // Add indexes to the building_permits table
        Schema::table('building_permits', function (Blueprint $table) {
            $table->index('y_latitude', 'building_permits_y_lat_idx');
            $table->index('x_longitude', 'building_permits_x_long_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop indexes from the three_one_one_cases table
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->dropIndex('three_one_one_cases_lat_idx');
            $table->dropIndex('three_one_one_cases_long_idx');
        });

        // Drop indexes from the crime_data table
        Schema::table('crime_data', function (Blueprint $table) {
            $table->dropIndex('crime_data_lat_idx');
            $table->dropIndex('crime_data_long_idx');
        });

        // Drop indexes from the building_permits table
        Schema::table('building_permits', function (Blueprint $table) {
            $table->dropIndex('building_permits_y_lat_idx');
            $table->dropIndex('building_permits_x_long_idx');
        });
    }
};
