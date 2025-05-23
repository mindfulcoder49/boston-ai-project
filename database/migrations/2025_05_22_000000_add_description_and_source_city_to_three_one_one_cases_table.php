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
    public function up(): void
    {
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->text('threeoneonedescription')->nullable()->after('ward_number'); // Or after another relevant column
            $table->string('source_city', 100)->nullable()->after('threeoneonedescription');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->dropColumn('threeoneonedescription');
            $table->dropColumn('source_city');
        });
    }
};
