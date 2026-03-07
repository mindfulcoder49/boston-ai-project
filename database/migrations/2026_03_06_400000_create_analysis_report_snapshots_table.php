<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('job_id');
            $table->string('artifact_name');
            $table->json('payload');
            $table->unsignedBigInteger('s3_last_modified')->nullable(); // Unix timestamp from S3
            $table->timestamp('pulled_at')->nullable();
            $table->timestamps();

            $table->unique(['job_id', 'artifact_name']);
            $table->index('job_id');
            $table->index('artifact_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_report_snapshots');
    }
};
