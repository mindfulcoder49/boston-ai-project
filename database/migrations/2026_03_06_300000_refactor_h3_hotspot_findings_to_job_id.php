<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing rows are incompatible with the new schema; re-run
        // app:materialize-hotspot-findings after migrating to repopulate.
        DB::table('h3_hotspot_findings')->truncate();

        Schema::table('h3_hotspot_findings', function (Blueprint $table) {
            $table->dropForeign(['trend_id']);
            $table->dropUnique(['trend_id', 'h3_index']);
            $table->dropIndex(['h3_resolution', 'trend_id']);
            $table->dropColumn('trend_id');

            $table->string('job_id')->after('id');
            $table->string('model_class')->after('job_id');
            $table->string('column_name')->after('model_class');

            $table->unique(['job_id', 'h3_index']);
            $table->index(['h3_resolution', 'job_id']);
        });
    }

    public function down(): void
    {
        DB::table('h3_hotspot_findings')->truncate();

        Schema::table('h3_hotspot_findings', function (Blueprint $table) {
            $table->dropUnique(['job_id', 'h3_index']);
            $table->dropIndex(['h3_resolution', 'job_id']);
            $table->dropColumn(['job_id', 'model_class', 'column_name']);

            $table->foreignId('trend_id')->constrained('trends')->cascadeOnDelete();
            $table->unique(['trend_id', 'h3_index']);
            $table->index(['h3_resolution', 'trend_id']);
        });
    }
};
