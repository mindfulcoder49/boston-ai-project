<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCambridgeAddressesAndIntersectionsTables extends Migration
{
    public function up()
    {
        Schema::create('cambridge_addresses', function (Blueprint $table) {
            $table->bigInteger('address_id')->primary();
            $table->string('full_addr')->nullable();
            $table->string('street_number')->nullable();
            $table->string('stname')->nullable();
            $table->string('building_id')->nullable();
            $table->string('maplot')->nullable();
            $table->decimal('latitude', 15, 8)->nullable();
            $table->decimal('longitude', 15, 8)->nullable();
            $table->string('type')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('election_ward')->nullable();
            $table->string('election_precinct')->nullable();
            $table->string('election_polling_address')->nullable();
            $table->string('representation_district')->nullable();
            $table->string('senate_district')->nullable();
            $table->string('cad_reporting_district')->nullable();
            $table->string('police_sector')->nullable();
            $table->string('police_car_route')->nullable();
            $table->string('police_walking_route')->nullable();
            $table->string('police_neighborhood')->nullable();
            $table->string('police_business_district')->nullable();
            $table->string('commercial_district')->nullable();
            $table->string('census_tract_2010')->nullable();
            $table->string('census_block_group_2010')->nullable();
            $table->string('census_block_2010')->nullable();
            $table->string('census_block_id_2010')->nullable();
            $table->string('street_sweeping_district')->nullable();
            $table->string('census_tract_2020')->nullable();
            $table->string('census_block_group_2020')->nullable();
            $table->string('census_block_2020')->nullable();
            $table->string('census_block_id_2020')->nullable();
            $table->timestamps();
        });

        Schema::create('cambridge_intersections', function (Blueprint $table) {
            $table->bigIncrements('nodenumber');
            $table->string('intersection')->nullable();
            $table->integer('intersectingstreetcount')->nullable();
            $table->string('zip_code')->nullable();
            $table->decimal('longitude', 15, 8)->nullable();
            $table->decimal('latitude', 15, 8)->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('election_ward')->nullable();
            $table->string('election_precinct')->nullable();
            $table->string('election_polling_address')->nullable();
            $table->string('representation_district')->nullable();
            $table->string('senate_district')->nullable();
            $table->string('cad_reporting_district')->nullable();
            $table->string('police_sector')->nullable();
            $table->string('police_car_route')->nullable();
            $table->string('police_walking_route')->nullable();
            $table->string('police_neighborhood')->nullable();
            $table->string('police_business_district')->nullable();
            $table->string('street_sweeping_district')->nullable();
            $table->string('census_tract_2010')->nullable();
            $table->string('census_block_group_2010')->nullable();
            $table->string('census_block_2010')->nullable();
            $table->string('census_block_id_2010')->nullable();
            $table->string('commercial_district')->nullable();
            $table->string('census_tract_2020')->nullable();
            $table->string('census_block_group_2020')->nullable();
            $table->string('census_block_2020')->nullable();
            $table->string('census_block_id_2020')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cambridge_intersections');
        Schema::dropIfExists('cambridge_addresses');
    }
}
