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
        Schema::table('everett_crime_data', function (Blueprint $table) {
            $table->string('incident_type_group')->nullable()->after('incident_type');
            $table->index('incident_type_group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('everett_crime_data', function (Blueprint $table) {
            $table->dropIndex(['incident_type_group']);
            $table->dropColumn('incident_type_group');
        });
    }
};
