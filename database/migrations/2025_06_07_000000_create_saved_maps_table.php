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
        Schema::create('saved_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('creator_display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('map_type'); // 'single' or 'combined'
            $table->string('data_type')->nullable(); // For 'single' type, e.g., '311_cases'
            $table->json('filters'); // Stores the filter criteria
            $table->json('map_settings')->nullable(); // Stores map center, zoom, selected layers for combined
            $table->boolean('is_public')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_maps');
    }
};
