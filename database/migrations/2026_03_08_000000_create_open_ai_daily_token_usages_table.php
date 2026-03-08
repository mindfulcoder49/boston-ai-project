<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_ai_daily_token_usages', function (Blueprint $table) {
            $table->id();
            $table->date('usage_date')->unique();
            $table->unsignedBigInteger('token_limit')->default(2500000);
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('reserved_completion_tokens')->default(0);
            $table->unsignedBigInteger('reserved_total_tokens')->default(0);
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_ai_daily_token_usages');
    }
};
