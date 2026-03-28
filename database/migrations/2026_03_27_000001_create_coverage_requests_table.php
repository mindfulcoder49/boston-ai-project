<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coverage_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('requested_address');
            $table->string('normalized_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('source_page')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('request_count')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['email', 'normalized_address']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_requests');
    }
};
