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
        Schema::table('data_points', function (Blueprint $table) {
            // Determine a suitable column to place the new FK after.
            // This is just a suggestion; adjust if you have a different preference.
            $afterColumn = 'food_inspection_id'; // Default, assuming it exists
            if (!Schema::hasColumn('data_points', 'food_inspection_id')) {
                // Fallback if food_inspection_id doesn't exist, e.g., place after 'generic_foreign_id'
                // or another known column. Adjust as per your actual table structure.
                $afterColumn = 'generic_foreign_id'; 
                 if (!Schema::hasColumn('data_points', 'generic_foreign_id')) {
                    $afterColumn = null; // Add at the end if no specific column found
                 }
            }

            if ($afterColumn) {
                $table->unsignedBigInteger('everett_crime_data_id')->nullable()->after($afterColumn);
            } else {
                $table->unsignedBigInteger('everett_crime_data_id')->nullable();
            }
            
            $table->foreign('everett_crime_data_id')
                  ->references('id')
                  ->on('everett_crime_data')
                  ->onDelete('cascade'); // Or onDelete('set null') if you prefer to keep data_points records

            $table->index('everett_crime_data_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // It's good practice to drop the index before the foreign key
            $table->dropIndex(['everett_crime_data_id']); // Laravel might generate index name like data_points_everett_crime_data_id_foreign
                                                        // but providing column name usually works for dropping.
                                                        // If specific index name is needed: $table->dropIndex('your_index_name_here');
            
            $table->dropForeign(['everett_crime_data_id']);
            $table->dropColumn('everett_crime_data_id');
        });
    }
};
