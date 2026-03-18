<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_article_generation_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['trend', 'hotspot']);
            $table->foreignId('trend_id')->nullable()->constrained('trends')->nullOnDelete();
            $table->string('h3_index', 20)->nullable()->index();
            $table->tinyInteger('h3_resolution')->nullable();
            $table->string('location_name')->nullable();
            $table->string('city', 100)->nullable();
            $table->text('intro_prompt')->nullable();
            $table->json('included_categories')->nullable();
            $table->json('included_finding_types')->nullable();
            $table->json('included_hotspot_reports')->nullable();
            $table->enum('status', ['draft', 'finalized', 'active_auto'])->default('draft');
            $table->timestamp('last_generated_at')->nullable();
            $table->foreignId('last_news_article_id')->nullable()->constrained('news_articles')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'trend_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_generation_configs');
    }
};
