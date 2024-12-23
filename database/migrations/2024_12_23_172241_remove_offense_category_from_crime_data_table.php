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
        // Drop offense category column from crime_data table
        Schema::table('crime_data', function (Blueprint $table) {
            $table->dropColumn('offense_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crime_data', function (Blueprint $table) {
            //make nullable and after offense_code
            $table->string('offense_category')->nullable()->after('offense_code');
        });
    }
};
