<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('h3_location_names', function (Blueprint $table) {
            $table->id();
            $table->string('h3_index', 20)->unique();
            $table->tinyInteger('h3_resolution');
            $table->string('location_name', 255);
            $table->timestamp('geocoded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('h3_location_names');
    }
};
