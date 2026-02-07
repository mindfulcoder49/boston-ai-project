<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanFranciscoDataPointsTable extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'san_francisco_db';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('san_francisco_data_points', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index()->comment('The source table name, e.g., san_francisco_crimes');
            $table->point('location', '4326')->spatialIndex();
            $table->string('generic_foreign_id')->comment('The original ID from the source system');
            $table->dateTime('alcivartech_date')->index()->comment('The primary date of the event for filtering');

            // Foreign key for SanFranciscoCrime
            $table->unsignedBigInteger('san_francisco_crime_id')->nullable();
            $table->foreign('san_francisco_crime_id')->references('row_id')->on('san_francisco_crimes')->onDelete('cascade');

            // Add other SanFrancisco-specific FKs here in the future

            $table->timestamps();

            // Add a unique constraint to prevent duplicate entries
            $table->unique(['type', 'generic_foreign_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('san_francisco_data_points');
    }
}
