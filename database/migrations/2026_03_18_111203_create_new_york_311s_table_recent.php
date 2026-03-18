<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNewYork311sTableRecent extends Migration
{
    protected $connection = 'new_york_db';

    public function up()
    {
        Schema::connection($this->connection)->create('new_york_311s', function (Blueprint $table) {
            $table->unsignedBigInteger('unique_key')->primary();
            $table->dateTime('created_date')->index();
            $table->dateTime('closed_date')->nullable()->index();
            $table->string('agency')->nullable()->index();
            $table->string('agency_name')->nullable()->index();
            $table->string('complaint_type')->nullable()->index();
            $table->string('descriptor')->nullable()->index();
            $table->text('additional_details')->nullable();
            $table->string('location_type')->nullable();
            $table->string('incident_zip')->nullable()->index();
            $table->string('incident_address')->nullable()->index();
            $table->string('street_name')->nullable()->index();
            $table->string('cross_street_1')->nullable();
            $table->string('cross_street_2')->nullable();
            $table->string('intersection_street_1')->nullable();
            $table->string('intersection_street_2')->nullable();
            $table->string('address_type')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('landmark')->nullable();
            $table->string('facility_type')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->dateTime('due_date')->nullable()->index();
            $table->text('resolution_description')->nullable();
            $table->dateTime('resolution_action_updated_date')->nullable()->index();
            $table->string('community_board')->nullable()->index();
            $table->string('council_district')->nullable()->index();
            $table->string('police_precinct')->nullable()->index();
            $table->string('bbl')->nullable();
            $table->string('borough')->nullable()->index();
            $table->decimal('x_coordinate_state_plane', 12, 4)->nullable();
            $table->decimal('y_coordinate_state_plane', 12, 4)->nullable();
            $table->string('open_data_channel_type')->nullable()->index();
            $table->string('park_facility_name')->nullable();
            $table->string('park_borough')->nullable()->index();
            $table->string('vehicle_type')->nullable();
            $table->string('taxi_company_borough')->nullable();
            $table->string('taxi_pick_up_location')->nullable();
            $table->string('bridge_highway_name')->nullable();
            $table->string('bridge_highway_direction')->nullable();
            $table->string('road_ramp')->nullable();
            $table->string('bridge_highway_segment')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('community_districts')->nullable()->index();
            $table->integer('borough_boundaries')->nullable()->index();
            $table->integer('police_precincts')->nullable()->index();
            $table->integer('city_council_districts')->nullable()->index();
            $table->point('location', '4326')->spatialIndex();
        });
    }

    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('new_york_311s');
    }
}
