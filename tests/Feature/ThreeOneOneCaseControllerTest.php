<?php

namespace Tests\Feature;

use App\Models\ThreeOneOneCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThreeOneOneCaseControllerTest extends TestCase
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

    public function test_live_multiple_returns_local_modernized_case_without_boston_api(): void
    {
        ThreeOneOneCase::query()->create([
            'service_request_id' => 'BCS-00000001',
            'case_enquiry_id' => null,
            'open_dt' => '2026-03-20 09:00:00',
            'case_status' => 'In progress',
            'reason' => 'Street Lights',
            'type' => 'Street Light Outages',
            'service_name' => 'Street Light Outage',
            'department' => 'Public Works Department (PWD)',
            'source_system' => 'modernized_311',
            'source_city' => 'Boston',
        ]);

        config()->set('services.bostongov.api_key', null);

        $response = $this->postJson('/api/311-case/live-multiple', [
            'case_enquiry_ids' => ['BCS-00000001'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.service_request_id', 'BCS-00000001')
            ->assertJsonPath('data.0.case_status', 'In progress')
            ->assertJsonPath('data.0.source_system', 'modernized_311');
    }

    public function test_live_multiple_merges_legacy_api_data_with_local_cases_and_keeps_modernized_cases(): void
    {
        ThreeOneOneCase::query()->create([
            'service_request_id' => '101006000001',
            'case_enquiry_id' => 101006000001,
            'open_dt' => '2026-03-01 10:00:00',
            'case_status' => 'Open',
            'reason' => 'Enforcement & Abandoned Vehicles',
            'type' => 'Parking Enforcement',
            'service_name' => 'Parking Enforcement',
            'source_system' => 'legacy_open311',
            'source_city' => 'Boston',
        ]);

        ThreeOneOneCase::query()->create([
            'service_request_id' => 'BCS-00000001',
            'case_enquiry_id' => null,
            'open_dt' => '2026-03-20 09:00:00',
            'case_status' => 'In progress',
            'reason' => 'Street Lights',
            'type' => 'Street Light Outages',
            'service_name' => 'Street Light Outage',
            'department' => 'Public Works Department (PWD)',
            'source_system' => 'modernized_311',
            'source_city' => 'Boston',
        ]);

        config()->set('services.bostongov.api_key', 'test-key');
        Http::fake([
            'https://311.boston.gov/open311/v2/requests.json*' => Http::response([
                [
                    'service_request_id' => '101006000001',
                    'status' => 'closed',
                    'description' => 'Vehicle removed',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/311-case/live-multiple', [
            'case_enquiry_ids' => ['101006000001', 'BCS-00000001'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.service_request_id', '101006000001')
            ->assertJsonPath('data.0.status', 'closed')
            ->assertJsonPath('data.0.type', 'Parking Enforcement')
            ->assertJsonPath('data.1.service_request_id', 'BCS-00000001')
            ->assertJsonPath('data.1.service_name', 'Street Light Outage');
    }
}
