<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMontgomeryCountyMdCrimesTableRecent extends Migration
{
    protected $connection = 'montgomery_county_md_db';

    public function up()
    {
        Schema::connection($this->connection)->create('montgomery_county_md_crimes', function (Blueprint $table) {
            $table->unsignedBigInteger('incident_id')->primary();
            $table->string('offence_code')->nullable()->index();
            $table->string('case_number')->nullable()->index();
            $table->dateTime('date')->nullable()->index();
            $table->dateTime('start_date')->nullable()->index();
            $table->dateTime('end_date')->nullable()->index();
            $table->string('nibrs_code')->nullable()->index();
            $table->integer('victims')->nullable();
            $table->string('crimename1')->nullable()->index();
            $table->string('crimename2')->nullable()->index();
            $table->string('crimename3')->nullable()->index();
            $table->string('district')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable()->index();
            $table->string('agency')->nullable()->index();
            $table->string('place')->nullable()->index();
            $table->string('sector')->nullable()->index();
            $table->string('beat')->nullable()->index();
            $table->string('pra')->nullable()->index();
            $table->string('address_number')->nullable();
            $table->string('street_prefix_dir')->nullable();
            $table->string('address_street')->nullable()->index();
            $table->string('street_type')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('police_district_number')->nullable()->index();
            $table->point('geolocation', '4326')->spatialIndex();
            $table->integer('council_districts')->nullable()->index();
            $table->integer('councils')->nullable()->index();
            $table->integer('communities')->nullable()->index();
            $table->integer('zip_codes')->nullable();
            $table->integer('municipalities')->nullable();
            $table->integer('council_districts_from_i23j_3mj8')->nullable();
            $table->integer('service_regions')->nullable();
            $table->integer('montgomery_county_boundary')->nullable();
            $table->integer('council_districts_7')->nullable();
            $table->integer('computed_region_vu5j_pcmz')->nullable();
            $table->integer('computed_region_tx5f_5em3')->nullable();
            $table->integer('computed_region_kbsp_ykn9')->nullable();
            $table->integer('computed_region_d7bw_bq6x')->nullable();
            $table->integer('computed_region_rbt8_3x7n')->nullable();
            $table->integer('computed_region_a9cs_3ed7')->nullable();
            $table->integer('computed_region_r648_kzwt')->nullable();
            $table->integer('computed_region_d9ke_fpxt')->nullable();
            $table->integer('computed_region_6vgr_duib')->nullable();
            $table->point('location', '4326')->spatialIndex();
        });
    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('montgomery_county_md_crimes');
    }
}
