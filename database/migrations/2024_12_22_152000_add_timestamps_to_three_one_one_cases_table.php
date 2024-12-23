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
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            // Remove unique constraint from 'case_enquiry_id'
            $table->dropUnique('three_one_one_cases_case_enquiry_id_unique');

            // Add timestamps
            $table->timestamps();
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
            // Restore the unique constraint
            $table->unique('case_enquiry_id');

            // Remove timestamps
            $table->dropTimestamps();
        });
    }
};
