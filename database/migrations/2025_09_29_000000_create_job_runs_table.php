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
        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique()->comment('The unique ID of the job from the queue.');
            $table->string('job_class');
            $table->string('status')->default('pending')->comment('e.g., pending, running, completed, failed');
            $table->nullableMorphs('related_model');
            $table->json('payload')->nullable();
            $table->text('output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_runs');
    }
};
