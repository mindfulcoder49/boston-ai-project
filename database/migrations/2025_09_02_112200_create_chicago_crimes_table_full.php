<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateChicagoCrimesTableFull extends Migration
{
    protected $connection = 'chicago_crime_db';

    public function up()
    {
        Schema::connection($this->connection)->create('chicago_crimes', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('case_number')->nullable()->index();
            $table->dateTime('date')->nullable()->index();
            $table->string('block')->nullable();
            $table->string('iucr')->nullable()->index();
            $table->string('primary_type')->nullable()->index();
            $table->string('description')->nullable();
            $table->string('location_description')->nullable();
            $table->boolean('arrest')->nullable();
            $table->boolean('domestic')->nullable();
            $table->integer('beat')->nullable();
            $table->integer('district')->nullable();
            $table->integer('ward')->nullable();
            $table->string('community_area')->nullable();
            $table->string('fbi_code')->nullable();
            $table->decimal('x_coordinate', 12, 4)->nullable();
            $table->decimal('y_coordinate', 12, 4)->nullable();
            $table->year('year')->nullable()->index();
            $table->dateTime('updated_on')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->point('location', '4326')->spatialIndex();
        });
    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('chicago_crimes');
    }
}
