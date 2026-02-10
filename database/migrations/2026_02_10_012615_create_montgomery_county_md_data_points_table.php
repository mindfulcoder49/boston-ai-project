<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMontgomeryCountyMdDataPointsTable extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'montgomery_county_md_db';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('montgomery_county_md_data_points', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index('montgomery_county_md_data_points_type_index')->comment('The source table name, e.g., montgomery_county_md_crimes');
            $table->point('location', '4326')->spatialIndex();
            $table->string('generic_foreign_id')->comment('The original ID from the source system');
            $table->dateTime('alcivartech_date')->index('montgomery_county_md_data_points_alcivartech_date_index')->comment('The primary date of the event for filtering');

            // Foreign key for MontgomeryCountyMdCrime
            $table->unsignedBigInteger('montgomery_county_md_crime_id')->nullable();
            $table->foreign('montgomery_county_md_crime_id', 'montgomery_county_md_data_points_montgomery_count_1b6e18_foreign')->references('incident_id')->on('montgomery_county_md_crimes')->onDelete('cascade');

            // Add other MontgomeryCountyMd-specific FKs here in the future

            $table->timestamps();

            // Add a unique constraint to prevent duplicate entries
            $table->unique(['type', 'generic_foreign_id'], 'montgomery_county_md_data_points_type_generic_foreign_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('montgomery_county_md_data_points');
    }
}
