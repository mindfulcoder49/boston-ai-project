<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSanFranciscoCrimesTableRecent extends Migration
{
    protected $connection = 'san_francisco_db';

    public function up()
    {
        Schema::connection($this->connection)->create('san_francisco_crimes', function (Blueprint $table) {
            $table->unsignedBigInteger('row_id')->primary();
            $table->dateTime('incident_datetime')->index();
            $table->date('incident_date')->nullable()->index();
            $table->string('incident_time')->nullable();
            $table->year('incident_year')->nullable()->index();
            $table->string('incident_day_of_week')->nullable()->index();
            $table->dateTime('report_datetime')->nullable()->index();
            $table->string('incident_id')->index();
            $table->string('incident_number')->index();
            $table->string('cad_number')->nullable()->index();
            $table->string('report_type_code')->nullable()->index();
            $table->string('report_type_description')->nullable();
            $table->boolean('filed_online')->nullable()->index();
            $table->string('incident_code')->nullable()->index();
            $table->string('incident_category')->nullable()->index();
            $table->string('incident_subcategory')->nullable()->index();
            $table->text('incident_description')->nullable();
            $table->string('resolution')->nullable()->index();
            $table->string('intersection')->nullable()->index();
            $table->string('cnn')->nullable()->index();
            $table->string('police_district')->nullable()->index();
            $table->string('analysis_neighborhood')->nullable()->index();
            $table->string('supervisor_district')->nullable()->index();
            $table->string('supervisor_district_2012')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->point('point', '4326')->nullable();
            $table->dateTime('data_as_of')->nullable()->index();
            $table->dateTime('data_loaded_at')->nullable()->index();
            $table->integer('neighborhoods')->nullable()->index();
            $table->integer('esncag_boundary_file')->nullable();
            $table->integer('central_market_tenderloin_boundary_polygon_updated')->nullable();
            $table->integer('civic_center_harm_reduction_project_boundary')->nullable();
            $table->integer('hsoc_zones_as_of_2018_06_05')->nullable();
            $table->integer('invest_in_neighborhoods_iin_areas')->nullable();
            $table->integer('current_supervisor_districts')->nullable()->index();
            $table->integer('current_police_districts')->nullable()->index();
            $table->point('location', '4326')->spatialIndex();
        });
    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('san_francisco_crimes');
    }
}
