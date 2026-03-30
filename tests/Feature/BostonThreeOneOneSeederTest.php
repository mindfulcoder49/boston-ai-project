<?php

namespace Tests\Feature;

use Database\Seeders\ThreeOneOneSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BostonThreeOneOneSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys=ON');

        Schema::create('three_one_one_cases', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('case_enquiry_id')->nullable()->unique();
            $table->string('service_request_id')->nullable()->unique();
            $table->dateTime('open_dt')->nullable();
            $table->text('sla_target_dt')->nullable();
            $table->dateTime('closed_dt')->nullable();
            $table->text('on_time')->nullable();
            $table->text('case_status')->nullable();
            $table->text('closure_reason')->nullable();
            $table->text('closure_comments')->nullable();
            $table->text('case_title')->nullable();
            $table->text('subject')->nullable();
            $table->text('reason')->nullable();
            $table->text('type')->nullable();
            $table->text('service_name')->nullable();
            $table->text('queue')->nullable();
            $table->text('department')->nullable();
            $table->text('submitted_photo')->nullable();
            $table->text('closed_photo')->nullable();
            $table->text('location')->nullable();
            $table->text('fire_district')->nullable();
            $table->text('pwd_district')->nullable();
            $table->text('city_council_district')->nullable();
            $table->text('police_district')->nullable();
            $table->text('neighborhood')->nullable();
            $table->text('neighborhood_services_district')->nullable();
            $table->text('ward')->nullable();
            $table->text('precinct')->nullable();
            $table->text('location_street_name')->nullable();
            $table->double('location_zipcode')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->text('source')->nullable();
            $table->string('source_system')->nullable();
            $table->text('ward_number')->nullable();
            $table->string('language_code')->nullable();
            $table->text('threeoneonedescription')->nullable();
            $table->string('source_city')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('three_one_one_cases');

        parent::tearDown();
    }

    public function test_seeder_normalizes_and_loads_legacy_and_new_boston_311_files(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('datasets/311-service-requests-2026_fixture.csv', implode("\n", [
            'case_enquiry_id,open_dt,sla_target_dt,closed_dt,on_time,case_status,closure_reason,case_title,subject,reason,type,queue,department,submitted_photo,closed_photo,location,fire_district,pwd_district,city_council_district,police_district,neighborhood,neighborhood_services_district,ward,precinct,location_street_name,location_zipcode,latitude,longitude,geom_4326,source',
            '101006000001,"2026-03-01 10:00:00","2026-03-02 10:00:00","","OVERDUE","Open","","Parking Enforcement","Transportation - Traffic Division","Enforcement & Abandoned Vehicles","Parking Enforcement","BTDT_Parking Enforcement","BTDT","","","430 E First St  South Boston  MA  02127","6","05","2","C6","South Boston","South Boston","7","0102","E First St","02127","42.3399","-71.0277","","Constituent Call"',
        ]));

        Storage::disk('local')->put('datasets/311-service-requests-new-system_fixture.csv', implode("\n", [
            'case_id,open_date,case_topic,service_name,assigned_department,assigned_team,case_status,closure_reason,closure_comments,close_date,target_close_date,on_time,report_source,full_address,street_number,street_name,zip_code,neighborhood,public_works_district,city_council_district,fire_district,police_district,ward,precinct,submitted_photo,closed_photo,longitude,latitude',
            'BCS-00000001,"2026-03-20 09:00:00+00","Street Light Outage","Street Light Outage","Public Works Department (PWD)","PWD Street Lighting","In progress","","","", "2026-03-23 09:00:00+00","OVERDUE","Call","30 B St, Boston, MA 02127","30","B St","02127","South Boston","5","2","6","C6","7","0102","","","-71.0270","42.3350"',
        ]));

        $this->seed(ThreeOneOneSeeder::class);

        $this->assertDatabaseCount('three_one_one_cases', 2);

        $this->assertDatabaseHas('three_one_one_cases', [
            'service_request_id' => '101006000001',
            'case_enquiry_id' => 101006000001,
            'reason' => 'Enforcement & Abandoned Vehicles',
            'type' => 'Parking Enforcement',
            'service_name' => 'Parking Enforcement',
            'source_system' => 'legacy_open311',
        ]);

        $this->assertDatabaseHas('three_one_one_cases', [
            'service_request_id' => 'BCS-00000001',
            'reason' => 'Street Lights',
            'type' => 'Street Light Outages',
            'service_name' => 'Street Light Outage',
            'source_system' => 'modernized_311',
            'queue' => 'PWD Street Lighting',
        ]);
    }
}
