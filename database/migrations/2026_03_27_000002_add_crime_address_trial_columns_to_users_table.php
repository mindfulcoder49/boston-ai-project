<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('crime_address_trial_started_at')->nullable()->after('manual_subscription_tier');
            $table->timestamp('crime_address_trial_ends_at')->nullable()->after('crime_address_trial_started_at');
            $table->foreignId('crime_address_trial_location_id')
                ->nullable()
                ->after('crime_address_trial_ends_at')
                ->constrained('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crime_address_trial_location_id');
            $table->dropColumn([
                'crime_address_trial_started_at',
                'crime_address_trial_ends_at',
            ]);
        });
    }
};
