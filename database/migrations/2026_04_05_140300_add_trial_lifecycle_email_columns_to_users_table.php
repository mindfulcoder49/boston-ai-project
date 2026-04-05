<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('crime_address_trial_grace_report_sent_at')
                ->nullable()
                ->after('crime_address_trial_location_id');
            $table->timestamp('crime_address_trial_ended_notice_sent_at')
                ->nullable()
                ->after('crime_address_trial_grace_report_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'crime_address_trial_grace_report_sent_at',
                'crime_address_trial_ended_notice_sent_at',
            ]);
        });
    }
};
