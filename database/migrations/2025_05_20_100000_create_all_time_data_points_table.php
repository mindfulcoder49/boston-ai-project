<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTimeDataPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('all_time_data_points', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // To differentiate between datasets
            $table->unsignedBigInteger('generic_foreign_id')->nullable();
            $table->point('location'); // Spatial point for lat/lng
            $table->timestamp('alcivartech_date')->nullable();
            $table->timestamps();

            // Foreign keys to different datasets - these ensure data integrity if the source records are deleted.
            // However, for an "all_time" table, you might reconsider if cascading deletes from source tables are desired.
            // If source data can be deleted, these FKs might cause issues or lead to data loss in all_time_data_points.
            // For now, keeping them consistent with data_points.
            $table->unsignedBigInteger('crime_data_id')->nullable();
            $table->unsignedBigInteger('three_one_one_case_id')->nullable();
            $table->unsignedBigInteger('property_violation_id')->nullable();
            $table->unsignedBigInteger('construction_off_hour_id')->nullable();
            $table->unsignedBigInteger('building_permit_id')->nullable();
            $table->unsignedBigInteger('food_inspection_id')->nullable(); // Assuming this was added via food_inspections migration

            // Foreign key constraints (consider implications of onDelete for an all-time table)
            $table->foreign('crime_data_id')->references('id')->on('crime_data')->onDelete('cascade');
            $table->foreign('three_one_one_case_id')->references('id')->on('three_one_one_cases')->onDelete('cascade');
            $table->foreign('property_violation_id')->references('id')->on('property_violations')->onDelete('cascade');
            $table->foreign('construction_off_hour_id')->references('id')->on('construction_off_hours')->onDelete('cascade');
            $table->foreign('building_permit_id')->references('id')->on('building_permits')->onDelete('cascade');
            $table->foreign('food_inspection_id')->references('id')->on('food_inspections')->onDelete('cascade');

            // Indexes
            $table->spatialIndex('location');
            $table->unique(['type', 'generic_foreign_id'], 'all_time_unique_type_generic_foreign_id');
            $table->index('alcivartech_date');
            $table->index('type');
            $table->index(['type', 'alcivartech_date'], 'all_time_type_alcivartech_date_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('all_time_data_points');
    }
}
