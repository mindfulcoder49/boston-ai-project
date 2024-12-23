<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Add language_code column to three_one_one_cases table
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->string('language_code', 5)->nullable()->after('checksum');
        });

        // Add language_code column to crime_data table
        Schema::table('crime_data', function (Blueprint $table) {
            $table->string('language_code', 5)->nullable()->after('offense_category');
        });

        // Add language_code column to building_permits table
        Schema::table('building_permits', function (Blueprint $table) {
            $table->string('language_code', 5)->nullable()->after('status');
        });

        // Add language_code column to construction_off_hours table
        Schema::table('construction_off_hours', function (Blueprint $table) {
            $table->string('language_code', 5)->nullable()->after('ward');
        });

        // Add language_code column to property_violations table
        Schema::table('property_violations', function (Blueprint $table) {
            $table->string('language_code', 5)->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove language_code column from three_one_one_cases table
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });

        // Remove language_code column from crime_data table
        Schema::table('crime_data', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });

        // Remove language_code column from building_permits table
        Schema::table('building_permits', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });

        // Remove language_code column from construction_off_hours table
        Schema::table('construction_off_hours', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });

        // Remove language_code column from property_violations table
        Schema::table('property_violations', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });
    }
};
