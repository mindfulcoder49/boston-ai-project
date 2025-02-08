<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataPointsTable extends Migration
{
    public function up()
    {
        Schema::create('data_points', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // To differentiate between datasets (e.g., 'crime', '311', 'violation')
            $table->point('location'); // Spatial point for lat/lng
            $table->timestamps();

            // Foreign keys to different datasets
            $table->unsignedBigInteger('crime_data_id')->nullable();
            $table->unsignedBigInteger('three_one_one_case_id')->nullable();
            $table->unsignedBigInteger('property_violation_id')->nullable();
            $table->unsignedBigInteger('construction_off_hour_id')->nullable();
            $table->unsignedBigInteger('building_permit_id')->nullable();

            // Foreign key constraints
            $table->foreign('crime_data_id')->references('id')->on('crime_data');
            $table->foreign('three_one_one_case_id')->references('id')->on('three_one_one_cases');
            $table->foreign('property_violation_id')->references('id')->on('property_violations');
            $table->foreign('construction_off_hour_id')->references('id')->on('construction_off_hours');
            $table->foreign('building_permit_id')->references('id')->on('building_permits');

            // Add spatial index for optimized geospatial queries
            $table->spatialIndex('location');
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_points');
    }
}
