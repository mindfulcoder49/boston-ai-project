<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // It's good practice to ensure doctrine/dbal is installed
        // composer require doctrine/dbal

        Schema::table('data_points', function (Blueprint $table) {
            // Change generic_foreign_id to VARCHAR(255) to accommodate longer IDs.
            // The ->change() method requires doctrine/dbal.
            $table->string('generic_foreign_id', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_points', function (Blueprint $table) {
            // Reverting to a hypothetical previous shorter length or original type.
            // If reverting to unsignedBigInteger, it would be:
            // $table->unsignedBigInteger('generic_foreign_id')->change();
            // However, the provided down method changes it to string(50).
            // For consistency with the initial problem, if it was unsignedBigInteger,
            // the rollback should ideally attempt to restore that if truly needed,
            // but changing to string(255) is the forward fix.
            $table->string('generic_foreign_id', 50)->change();
        });
    }
};
