<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('h3_hotspot_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trend_id')->constrained('trends')->cascadeOnDelete();
            $table->string('h3_index', 20);
            $table->tinyInteger('h3_resolution');
            $table->integer('anomaly_count')->default(0);
            $table->integer('trend_count')->default(0);
            $table->json('top_anomalies');
            $table->json('top_trends');
            $table->timestamps();

            $table->unique(['trend_id', 'h3_index']);
            $table->index(['h3_resolution', 'trend_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('h3_hotspot_findings');
    }
};
