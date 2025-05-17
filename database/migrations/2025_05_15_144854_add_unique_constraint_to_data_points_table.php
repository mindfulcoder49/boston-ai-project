<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToDataPointsTable extends Migration
{
    public function up()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // Add the new generic_foreign_id column
            $table->unsignedBigInteger('generic_foreign_id')->nullable()->after('type');

            // Add a unique constraint on type and generic_foreign_id
            $table->unique(['type', 'generic_foreign_id'], 'unique_type_generic_foreign_id');
        });
    }

    public function down()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // Drop the unique constraint added in the new 'up' method
            $table->dropUnique('unique_type_generic_foreign_id');
            // Drop the column added in the new 'up' method
            $table->dropColumn('generic_foreign_id');

        });
    }
}