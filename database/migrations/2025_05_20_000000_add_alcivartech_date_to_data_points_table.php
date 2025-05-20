<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAlcivartechDateToDataPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_points', function (Blueprint $table) {
            $table->timestamp('alcivartech_date')->nullable()->after('location');
            
            // Add indexes for faster querying
            $table->index('alcivartech_date');
            $table->index('type'); // Index on type if frequently queried
            $table->index(['type', 'alcivartech_date']); // Composite index
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
            $table->dropIndex(['type', 'alcivartech_date']);
            $table->dropIndex(['type']);
            $table->dropIndex(['alcivartech_date']);
            $table->dropColumn('alcivartech_date');
        });
    }
}
