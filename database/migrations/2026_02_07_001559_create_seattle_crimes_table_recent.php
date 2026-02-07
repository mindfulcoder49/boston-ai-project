<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSeattleCrimesTableRecent extends Migration
{
    protected $connection = 'seattle_db';

    public function up()
    {
        Schema::connection($this->connection)->create('seattle_crimes', function (Blueprint $table) {
            $table->string('report_number')->index();
            $table->dateTime('report_date_time')->nullable()->index();
            $table->unsignedBigInteger('offense_id')->primary();
            $table->dateTime('offense_date')->nullable()->index();
            $table->string('nibrs_group_a_b')->nullable()->index();
            $table->string('nibrs_crime_against_category')->nullable()->index();
            $table->string('offense_sub_category')->nullable()->index();
            $table->string('shooting_type_group')->nullable();
            $table->string('block_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('beat')->nullable()->index();
            $table->string('precinct')->nullable()->index();
            $table->string('sector')->nullable()->index();
            $table->string('neighborhood')->nullable()->index();
            $table->string('reporting_area')->nullable()->index();
            $table->string('offense_category')->nullable()->index();
            $table->text('nibrs_offense_code_description')->nullable();
            $table->string('nibrs_offense_code')->nullable()->index();
            $table->point('location', '4326')->spatialIndex();
        });
    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('seattle_crimes');
    }
}
