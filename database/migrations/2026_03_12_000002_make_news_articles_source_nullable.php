<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->string('source_model_class')->nullable()->change();
            $table->unsignedBigInteger('source_report_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->string('source_model_class')->nullable(false)->change();
            $table->unsignedBigInteger('source_report_id')->nullable(false)->change();
        });
    }
};
