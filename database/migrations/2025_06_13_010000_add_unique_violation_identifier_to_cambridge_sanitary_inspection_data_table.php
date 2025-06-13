<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cambridge_sanitary_inspection_data', function (Blueprint $table) {
            $table->string('unique_violation_identifier')
                  ->after('geocoded_column_text') // Optional: to place it after a specific column
                  ->nullable(false) // Assuming it should not be nullable based on its purpose
                  ->comment('Concatenated identifier for upserting: case_number_group (or estab_name_hash) | code_number | date_cited');
            
            // Add unique constraint with a custom, shorter name
            $table->unique(['unique_violation_identifier'], 'csid_unique_violation_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cambridge_sanitary_inspection_data', function (Blueprint $table) {
            // Drop the unique index using the custom name
            $table->dropUnique('csid_unique_violation_id_unique');
            $table->dropColumn('unique_violation_identifier');
        });
    }
};
