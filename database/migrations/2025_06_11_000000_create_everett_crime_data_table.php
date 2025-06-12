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
        Schema::create('everett_crime_data', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->date('incident_log_file_date')->nullable();
            $table->date('incident_entry_date_parsed')->nullable(); // To store parsed date part of occurred_on_datetime
            $table->time('incident_time_parsed')->nullable(); // To store parsed time part of occurred_on_datetime
            $table->dateTime('occurred_on_datetime')->nullable();
            $table->smallInteger('year')->nullable();
            $table->tinyInteger('month')->nullable();
            $table->string('day_of_week', 15)->nullable();
            $table->tinyInteger('hour')->nullable();
            $table->string('incident_type')->nullable();
            $table->string('incident_address')->nullable();
            $table->decimal('incident_latitude', 10, 7)->nullable();
            $table->decimal('incident_longitude', 10, 7)->nullable();
            $table->text('incident_description')->nullable();
            $table->string('arrest_name')->nullable();
            $table->text('arrest_address')->nullable();
            $table->smallInteger('arrest_age')->nullable();
            $table->date('arrest_date_parsed')->nullable();
            $table->text('arrest_charges')->nullable();
            $table->text('crime_details_concatenated')->nullable(); // For the concatenated string of various details
            $table->string('source_city', 50)->default('Everett');
            $table->timestamps();

            $table->index('occurred_on_datetime');
            $table->index('incident_type');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('everett_crime_data');
    }
};
