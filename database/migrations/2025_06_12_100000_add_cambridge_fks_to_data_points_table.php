<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
            $cambridgeModels = [
                \App\Models\CambridgeThreeOneOneCase::class,
                \App\Models\CambridgeBuildingPermitData::class,
                \App\Models\CambridgeCrimeReportData::class,
                \App\Models\CambridgeHousingViolationData::class,
                \App\Models\CambridgeSanitaryInspectionData::class,
            ];

            // Determine a suitable column to place the new FKs after.
            $afterColumn = 'everett_crime_data_id'; // Default, assuming it exists from previous migration
            if (!Schema::hasColumn('data_points', $afterColumn)) {
                $afterColumn = 'food_inspection_id'; // Fallback
                if (!Schema::hasColumn('data_points', $afterColumn)) {
                    $afterColumn = 'generic_foreign_id'; // Further fallback
                    if (!Schema::hasColumn('data_points', $afterColumn)) {
                        $afterColumn = null; // Add at the end if no specific column found
                    }
                }
            }

            foreach ($cambridgeModels as $modelClass) {
                $modelInstance = new $modelClass();
                $sourceTableName = $modelInstance->getTable();
                $fkColumn = Str::snake(class_basename($modelClass)) . '_id';

                if (!Schema::hasColumn('data_points', $fkColumn)) {
                    if ($afterColumn) {
                        // The use of unsignedBigInteger for $fkColumn is correct here,
                        // as it references the primary 'id' (an integer) of the source table.
                        $table->unsignedBigInteger($fkColumn)->nullable()->after($afterColumn);
                    } else {
                        $table->unsignedBigInteger($fkColumn)->nullable();
                    }

                    // Optional: Add foreign key constraints
                    // $table->foreign($fkColumn)
                    //       ->references('id')
                    //       ->on($sourceTableName)
                    //       ->onDelete('set null'); // Or 'cascade'

                    $table->index($fkColumn);
                    
                    // Update afterColumn for the next iteration to place subsequent columns correctly
                    $afterColumn = $fkColumn; 
                }
            }
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
            $cambridgeModels = [
                \App\Models\CambridgeThreeOneOneCase::class,
                \App\Models\CambridgeBuildingPermitData::class,
                \App\Models\CambridgeCrimeReportData::class,
                \App\Models\CambridgeHousingViolationData::class,
                \App\Models\CambridgeSanitaryInspectionData::class,
            ];

            foreach ($cambridgeModels as $modelClass) {
                $fkColumn = Str::snake(class_basename($modelClass)) . '_id';
                if (Schema::hasColumn('data_points', $fkColumn)) {
                    // Drop index first
                    // Laravel's default index name convention is table_column_index
                    // $table->dropIndex('data_points_' . $fkColumn . '_index'); 
                    // Or more simply if it's the only index on that column:
                    $table->dropIndex([$fkColumn]);


                    // Drop foreign key if it was created
                    // Note: You need to know the foreign key constraint name or pass an array of columns.
                    // Laravel's default FK name convention is table_column_foreign
                    // Example: $table->dropForeign('data_points_' . $fkColumn . '_foreign');
                    // If you used the array method for creating: $table->dropForeign([$fkColumn]);
                    // Check your database or previous migration if unsure about the exact name.
                    // For safety, this part is commented out unless you are sure about the FK names.
                    /*
                    $modelInstance = new $modelClass();
                    $sourceTableName = $modelInstance->getTable();
                    // Attempt to drop by column name array (works if constraint was named by Laravel default for single col FK)
                    // $table->dropForeign([$fkColumn]);
                    */

                    $table->dropColumn($fkColumn);
                }
            }
        });
    }
};
