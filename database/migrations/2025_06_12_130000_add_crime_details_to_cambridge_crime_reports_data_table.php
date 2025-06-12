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
        Schema::table('cambridge_crime_reports_data', function (Blueprint $table) {
            $table->text('crime_details')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cambridge_crime_reports_data', function (Blueprint $table) {
            $table->dropColumn('crime_details');
        });
    }
};
