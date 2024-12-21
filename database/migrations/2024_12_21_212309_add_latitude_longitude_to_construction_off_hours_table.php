<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLatitudeLongitudeToConstructionOffHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('construction_off_hours', function (Blueprint $table) {
            $table->decimal('latitude', 15, 13)->nullable()->after('ward');
            $table->decimal('longitude', 15, 13)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('construction_off_hours', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
}
