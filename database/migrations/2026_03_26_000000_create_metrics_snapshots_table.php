<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('snapshot_key')->unique();
            $table->json('data');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('generated_at');
            $table->index('last_updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics_snapshots');
    }
};
