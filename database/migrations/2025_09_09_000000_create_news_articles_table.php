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
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('headline');
            $table->text('summary');
            $table->longText('content');
            $table->string('source_model_class');
            $table->unsignedBigInteger('source_report_id');
            $table->string('status')->default('draft')->index(); // e.g., draft, published, error
            $table->string('job_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['source_model_class', 'source_report_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
