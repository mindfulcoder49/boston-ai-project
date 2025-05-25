<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crime_data', function (Blueprint $table) {
            //add text description of the crime
            $table->text('crime_details')->nullable()->after('location');
            $table->dateTime('crime_start_time')->nullable()->after('location');
            $table->dateTime('crime_end_time')->nullable()->after('crime_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crime_data', function (Blueprint $table) {
            $table->dropColumn(['crime_start_time', 'crime_end_time', 'crime_details']);
        });
    }
};
