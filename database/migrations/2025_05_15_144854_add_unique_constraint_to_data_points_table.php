<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToDataPointsTable extends Migration
{
    public function up()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // Add a generated column that combines all foreign keys
            $table->string('unique_key')->virtualAs("
                CONCAT(
                    type, 
                    COALESCE(crime_data_id, 0), 
                    COALESCE(three_one_one_case_id, 0), 
                    COALESCE(property_violation_id, 0), 
                    COALESCE(construction_off_hour_id, 0), 
                    COALESCE(building_permit_id, 0)
                )
            ");
    
            // Add a unique constraint to the generated column
            $table->unique('unique_key', 'unique_type_foreign_keys');
        });
    }

    public function down()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // Drop the unique constraint if the migration is rolled back
            $table->dropUnique('unique_type_foreign_keys');
            // Drop the generated column
            $table->dropColumn('unique_key');
            
        });
    }
}