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
            $table->text('source_city')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crime_data', function (Blueprint $table) {
            $table->dropColumn(['source_city']);
        });
    }
};
