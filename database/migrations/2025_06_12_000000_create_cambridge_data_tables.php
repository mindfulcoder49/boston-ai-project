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
        // Cambridge 311 Service Requests
        Schema::create('cambridge_311_service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id_external')->unique()->comment('From CSV ticket_id');
            $table->string('city')->nullable();
            $table->string('issue_type')->nullable()->index();
            $table->string('issue_category')->nullable()->index();
            $table->string('ticket_status')->nullable()->index();
            $table->text('issue_description')->nullable();
            $table->dateTime('ticket_closed_date_time')->nullable();
            $table->dateTime('ticket_created_date_time')->nullable()->index();
            $table->dateTime('ticket_last_updated_date_time')->nullable();
            $table->string('address', 512)->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('location_text')->nullable()->comment('From CSV location field');
            $table->string('image_url', 2048)->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->string('html_url', 2048)->nullable();
            $table->timestamps();
        });

        // Cambridge Building Permits Data
        Schema::create('cambridge_building_permits_data', function (Blueprint $table) {
            $table->id();
            $table->string('permit_id_external')->unique()->comment('From CSV id');
            $table->string('address', 512)->nullable()->index();
            $table->text('address_geocoded')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->nullable()->index();
            $table->dateTime('applicant_submit_date')->nullable();
            $table->dateTime('issue_date')->nullable()->index();
            $table->integer('number_of_residential_units')->nullable();
            $table->string('current_property_use')->nullable()->index();
            $table->string('proposed_property_use')->nullable()->index();
            $table->decimal('total_cost_of_construction', 15, 2)->nullable();
            $table->text('detailed_description_of_work')->nullable();
            $table->integer('gross_square_footage')->nullable();
            $table->string('building_use')->nullable()->comment('From CSV building_use');
            $table->string('maplot_number')->nullable()->index();
            $table->json('raw_data')->nullable()->comment('To store all other fields from the 125-column CSV');
            $table->timestamps();
        });

        // Cambridge Crime Reports Data
        Schema::create('cambridge_crime_reports_data', function (Blueprint $table) {
            $table->id();
            $table->string('file_number_external')->unique()->comment('From CSV file_number');
            $table->dateTime('date_of_report')->nullable()->index();
            $table->string('crime_datetime_raw')->nullable()->comment('Original crime_date_time string');
            $table->dateTime('crime_start_time')->nullable()->index();
            $table->dateTime('crime_end_time')->nullable()->index();
            $table->string('crime')->nullable()->index()->comment('Offense description');
            $table->string('reporting_area')->nullable()->index();
            $table->string('neighborhood')->nullable()->index();
            $table->string('location_address', 512)->nullable()->index()->comment('Original location string');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        // Cambridge Housing Violation Data
        Schema::create('cambridge_housing_violation_data', function (Blueprint $table) {
            $table->id();
            $table->string('record_id_external')->unique()->comment('From CSV recordid');
            $table->string('full_address', 512)->nullable()->index();
            $table->string('parcel_number')->nullable()->index();
            $table->string('code')->nullable()->index();
            $table->text('description')->nullable();
            $table->text('corrective_action')->nullable();
            $table->string('correction_required_by')->nullable();
            $table->string('status')->nullable()->index()->comment('From CSV status_x');
            $table->date('application_submit_date')->nullable()->index();
            $table->date('issue_date')->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('point_text')->nullable()->comment('From CSV point');
            $table->timestamps();
        });

        // Cambridge Master Addresses
        Schema::create('cambridge_master_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('address_id_external')->unique()->comment('From CSV address_id');
            $table->string('full_addr', 512)->nullable()->index();
            $table->string('street_number')->nullable();
            $table->string('stname')->nullable()->index();
            $table->string('building_id')->nullable();
            $table->string('maplot')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('type')->nullable()->index();
            $table->string('zip_code', 10)->nullable()->index();
            $table->string('neighborhood')->nullable()->index();
            $table->string('election_ward')->nullable();
            $table->string('election_precinct')->nullable();
            $table->json('raw_data')->nullable()->comment('To store other less frequently queried fields');
            $table->timestamps();
        });

        // Cambridge Master Intersections
        Schema::create('cambridge_master_intersections', function (Blueprint $table) {
            $table->id();
            $table->string('node_number_external')->unique()->comment('From CSV nodenumber');
            $table->string('intersection_name', 512)->nullable()->index();
            $table->integer('intersecting_street_count')->nullable();
            $table->string('zip_code', 10)->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('neighborhood')->nullable()->index();
            $table->json('raw_data')->nullable()->comment('To store other less frequently queried fields');
            $table->timestamps();
        });

        // Cambridge Sanitary Inspection Data
        Schema::create('cambridge_sanitary_inspection_data', function (Blueprint $table) {
            $table->id(); // Primary key for each violation row
            $table->string('case_number_group')->index()->comment('From CSV case_number, groups violations');
            $table->string('address', 512)->nullable()->index();
            $table->string('parcel')->nullable()->index();
            $table->string('establishment_name')->nullable()->index();
            $table->string('code_number')->nullable()->index();
            $table->text('code_description')->nullable();
            $table->text('inspector_comments')->nullable();
            $table->dateTime('case_open_date')->nullable()->index();
            $table->dateTime('case_closed_date')->nullable();
            $table->dateTime('date_cited')->nullable()->index();
            $table->dateTime('date_corrected')->nullable();
            $table->string('code_case_status')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('geocoded_column_text')->nullable();
            $table->timestamps();
            // A unique constraint for a specific violation under an inspection might be:
            // $table->unique(['case_number_group', 'code_number', 'date_cited'], 'inspection_violation_unique');
            // This depends on data granularity and if these fields reliably make a row unique.
            // For now, relying on auto-incrementing ID and seeder logic to handle inserts.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cambridge_311_service_requests');
        Schema::dropIfExists('cambridge_building_permits_data');
        Schema::dropIfExists('cambridge_crime_reports_data');
        Schema::dropIfExists('cambridge_housing_violation_data');
        Schema::dropIfExists('cambridge_master_addresses');
        Schema::dropIfExists('cambridge_master_intersections');
        Schema::dropIfExists('cambridge_sanitary_inspection_data');
    }
};
