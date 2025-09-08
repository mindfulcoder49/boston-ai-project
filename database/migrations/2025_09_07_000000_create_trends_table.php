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
        Schema::create('trends', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->string('column_name');
            $table->string('job_id');
            $table->integer('h3_resolution');
            $table->float('p_value_anomaly');
            $table->float('p_value_trend');
            $table->json('analysis_weeks_trend');
            $table->integer('analysis_weeks_anomaly');
            $table->timestamps();

            $table->unique([
                'model_class',
                'column_name',
                'h3_resolution',
                'p_value_anomaly',
                'p_value_trend',
                // 'analysis_weeks_trend' is now a JSON field and cannot be in a unique index on most DBs.
                'analysis_weeks_anomaly',
            ], 'trends_unique_parameters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trends');
    }
};
