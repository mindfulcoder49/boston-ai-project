<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoodInspectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('food_inspections', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('external_id')->unique()->comment('From CSV _id');
            $table->string('businessname')->nullable();
            $table->string('dbaname')->nullable();
            $table->string('legalowner')->nullable();
            $table->string('namelast')->nullable();
            $table->string('namefirst')->nullable();
            $table->string('licenseno')->nullable();
            $table->timestamp('issdttm')->nullable();
            $table->timestamp('expdttm')->nullable();
            $table->string('licstatus')->nullable();
            $table->string('licensecat')->nullable();
            $table->text('descript')->nullable();
            $table->string('result')->nullable();
            $table->timestamp('resultdttm')->nullable();
            $table->string('violation')->nullable();
            $table->string('viol_level')->nullable();
            $table->text('violdesc')->nullable();
            $table->timestamp('violdttm')->nullable();
            $table->string('viol_status')->nullable();
            $table->timestamp('status_date')->nullable();
            $table->text('comments')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('property_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('language_code')->nullable();
            $table->timestamps();
        });

        Schema::table('data_points', function (Blueprint $table) {
            $table->unsignedBigInteger('food_inspection_id')->nullable()->after('building_permit_id');
            $table->foreign('food_inspection_id')
                  ->references('id')
                  ->on('food_inspections')
                  ->onDelete('cascade'); // Or set null if appropriate
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_points', function (Blueprint $table) {
            $table->dropForeign(['food_inspection_id']);
            $table->dropColumn('food_inspection_id');
        });
        Schema::dropIfExists('food_inspections');
    }
}
