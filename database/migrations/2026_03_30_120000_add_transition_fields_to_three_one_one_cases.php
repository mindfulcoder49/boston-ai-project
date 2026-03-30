<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->string('service_request_id')->nullable()->after('case_enquiry_id');
            $table->text('closure_comments')->nullable()->after('closure_reason');
            $table->text('service_name')->nullable()->after('type');
            $table->string('source_system')->nullable()->after('source');
        });

        DB::table('three_one_one_cases')
            ->select(['id', 'case_enquiry_id', 'type'])
            ->orderBy('id')
            ->chunkById(1000, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('three_one_one_cases')
                        ->where('id', $row->id)
                        ->update([
                            'service_request_id' => $row->case_enquiry_id ? (string) $row->case_enquiry_id : null,
                            'service_name' => $row->type,
                            'source_system' => 'legacy_open311',
                        ]);
                }
            });

        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->unique('service_request_id', 'three_one_one_cases_service_request_id_unique');
            $table->index('source_system', 'three_one_one_cases_source_system_idx');
        });
    }

    public function down(): void
    {
        Schema::table('three_one_one_cases', function (Blueprint $table) {
            $table->dropUnique('three_one_one_cases_service_request_id_unique');
            $table->dropIndex('three_one_one_cases_source_system_idx');
            $table->dropColumn([
                'service_request_id',
                'closure_comments',
                'service_name',
                'source_system',
            ]);
        });
    }
};
