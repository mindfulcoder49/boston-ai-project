<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     */
    protected $connection = 'chicago_db';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('chicago_data_points')) {
            return;
        }

        Schema::connection($this->connection)->create('chicago_data_points', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index()->comment('The source table name, e.g., chicago_crimes');
            $table->point('location', '4326')->spatialIndex();
            $table->string('generic_foreign_id')->comment('The original ID from the source system (e.g., case number)');
            $table->dateTime('alcivartech_date')->index()->comment('The primary date of the event for filtering');

            $table->unsignedBigInteger('chicago_crime_id')->nullable();
            $table->foreign('chicago_crime_id')->references('id')->on('chicago_crimes')->onDelete('cascade');

            $table->timestamps();
            $table->unique(['type', 'generic_foreign_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('chicago_data_points');
    }
};
