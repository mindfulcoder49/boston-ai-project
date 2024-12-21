<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyViolationsTable extends Migration
{
    public function up()
    {
        Schema::create('property_violations', function (Blueprint $table) {
            $table->id();
            $table->string('case_no')->nullable();
             $table->string('ap_case_defn_key')->nullable();
            $table->dateTime('status_dttm')->nullable();
            $table->string('status')->nullable();
            $table->string('code')->nullable();
            $table->string('value')->nullable();
            $table->text('description')->nullable();
            $table->string('violation_stno')->nullable();
            $table->string('violation_sthigh')->nullable();
            $table->string('violation_street')->nullable();
            $table->string('violation_suffix')->nullable();
            $table->string('violation_city')->nullable();
            $table->string('violation_state')->nullable();
            $table->string('violation_zip')->nullable();
            $table->string('ward')->nullable();
            $table->string('contact_addr1')->nullable();
            $table->string('contact_addr2')->nullable();
            $table->string('contact_city')->nullable();
            $table->string('contact_state')->nullable();
            $table->string('contact_zip')->nullable();
            $table->string('sam_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_violations');
    }
}