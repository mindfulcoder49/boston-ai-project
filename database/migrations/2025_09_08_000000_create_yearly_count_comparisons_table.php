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
        Schema::create('yearly_count_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->string('group_by_col');
            $table->integer('baseline_year');
            $table->string('job_id');
            $table->timestamps();

            $table->unique([
                'model_class',
                'group_by_col',
                'baseline_year',
            ], 'yearly_count_comparisons_unique_params');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yearly_count_comparisons');
    }
};
