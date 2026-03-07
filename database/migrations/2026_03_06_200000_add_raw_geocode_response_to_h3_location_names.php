<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('h3_location_names', function (Blueprint $table) {
            $table->json('raw_geocode_response')->nullable()->after('geocoded_at');
        });
    }

    public function down(): void
    {
        Schema::table('h3_location_names', function (Blueprint $table) {
            $table->dropColumn('raw_geocode_response');
        });
    }
};
