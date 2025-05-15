<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToDataPointsTable extends Migration
{
    public function up()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // Add a unique constraint to prevent duplicate records
            $table->unique(
                ['type', 'crime_data_id', 'three_one_one_case_id', 'property_violation_id', 'construction_off_hour_id', 'building_permit_id'],
                'unique_type_foreign_keys'
            );
        });
    }

    public function down()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // Drop the unique constraint if the migration is rolled back
            $table->dropUnique('unique_type_foreign_keys');
        });
    }
}