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
            $afterColumn = 'everett_crime_data_id'; // Try to place after the last added FK
            if (!Schema::hasColumn('data_points', $afterColumn)) {
                $afterColumn = 'food_inspection_id'; // Fallback 1
                if (!Schema::hasColumn('data_points', $afterColumn)) {
                    $afterColumn = 'generic_foreign_id'; // Fallback 2
                    if (!Schema::hasColumn('data_points', $afterColumn)) {
                        $afterColumn = null; // Add at the end if no specific column found
                    }
                }
            }

            if ($afterColumn) {
                $table->unsignedBigInteger('person_crash_data_id')->nullable()->after($afterColumn);
            } else {
                $table->unsignedBigInteger('person_crash_data_id')->nullable();
            }
            
            $table->foreign('person_crash_data_id')
                  ->references('id')
                  ->on('person_crash_data')
                  ->onDelete('cascade'); // Or onDelete('set null')

            $table->index('person_crash_data_id');
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
            // Laravel's default index name convention is table_column_foreign
            // or table_column_index. Providing the column name array usually works.
            $table->dropForeign(['person_crash_data_id']);
            $table->dropIndex(['person_crash_data_id']); // Drop index by column name
            $table->dropColumn('person_crash_data_id');
        });
    }
};
