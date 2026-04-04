<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Services\LocationReportDataService;
use App\Services\LocationReportMapSnapshotBuilder;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class LocationReportMapSnapshotBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_builds_a_recent_deterministic_snapshot_without_silent_truncation(): void
    {
        Carbon::setTestNow('2026-04-03 12:00:00');

        $dataService = Mockery::mock(LocationReportDataService::class);
        $dataService
            ->shouldReceive('fetch')
            ->once()
            ->andReturn([
                (object) [
                    'alcivartech_type' => '311 Case',
                    'alcivartech_date' => '2026-04-03 10:00:00',
                    'latitude' => 42.3309,
                    'longitude' => -71.0508,
                    'three_one_one_case_data' => (object) [
                        'service_name' => 'Illegal Parking',
                        'incident_address' => '100 A St',
                        'status' => 'Open',
                        'service_request_id' => 'SR-1',
                    ],
                ],
                (object) [
                    'alcivartech_type' => 'Crime',
                    'alcivartech_date' => '2026-04-03 09:00:00',
                    'latitude' => 42.3312,
                    'longitude' => -71.0510,
                    'crime_data' => (object) [
                        'primary_type' => 'Larceny',
                        'block' => '200 B St',
                        'case_number' => 'CASE-2',
                    ],
                ],
                (object) [
                    'alcivartech_type' => 'Crime',
                    'alcivartech_date' => '2026-04-02 22:00:00',
                    'latitude' => 42.3320,
                    'longitude' => -71.0520,
                    'crime_data' => (object) [
                        'primary_type' => 'Assault',
                        'block' => '300 C St',
                        'case_number' => 'CASE-3',
                    ],
                ],
                (object) [
                    'alcivartech_type' => 'Crime',
                    'alcivartech_date' => '2026-03-31 22:00:00',
                    'latitude' => 42.3400,
                    'longitude' => -71.0600,
                    'crime_data' => (object) [
                        'primary_type' => 'Old Incident',
                    ],
                ],
            ]);

        $builder = new LocationReportMapSnapshotBuilder($dataService);
        $location = new Location([
            'name' => 'South Boston Home',
            'address' => '730 E Third St',
            'latitude' => 42.3310,
            'longitude' => -71.0512,
        ]);

        $snapshot = $builder->build($location, 0.25, 2, 2);

        $this->assertSame(3, $snapshot['recent_points_in_window']);
        $this->assertSame(2, $snapshot['selected_points']);
        $this->assertSame(1, $snapshot['omitted_points']);
        $this->assertFalse($snapshot['empty']);
        $this->assertCount(3, $snapshot['markers']);
        $this->assertSame('H', $snapshot['markers'][0]['label']);
        $this->assertSame('1', $snapshot['incidents'][0]['label']);
        $this->assertSame('Illegal Parking', $snapshot['incidents'][0]['headline']);
        $this->assertSame('311', $snapshot['incidents'][0]['category_label']);
        $this->assertSame('rounded-square', $snapshot['incidents'][0]['shape']);
        $this->assertSame('SR-1', $snapshot['incidents'][0]['identifier']);
        $this->assertSame('2', $snapshot['incidents'][1]['label']);
        $this->assertSame('Larceny', $snapshot['incidents'][1]['headline']);
        $this->assertSame('Crime', $snapshot['incidents'][1]['category_label']);
        $this->assertSame('circle', $snapshot['incidents'][1]['shape']);
        $this->assertSame('CASE-2', $snapshot['incidents'][1]['identifier']);
        $this->assertSame('2026-04-03', $snapshot['counts_by_date'][0]['date']);
        $this->assertSame(2, $snapshot['counts_by_date'][0]['count']);
        $this->assertNotEmpty($snapshot['render_fingerprint']);
    }

    public function test_it_builds_a_daily_series_with_stable_dates_and_cache_fingerprints(): void
    {
        Carbon::setTestNow('2026-04-03 12:00:00');

        $dataService = Mockery::mock(LocationReportDataService::class);
        $dataService
            ->shouldReceive('fetch')
            ->once()
            ->andReturn([
                (object) [
                    'alcivartech_type' => 'Food Inspection',
                    'alcivartech_date' => '2026-04-03 08:00:00',
                    'latitude' => 42.3309,
                    'longitude' => -71.0508,
                    'food_inspection_data' => (object) [
                        'description' => 'Expired milk',
                        'address' => '100 A St',
                    ],
                ],
                (object) [
                    'alcivartech_type' => 'Food Inspection',
                    'alcivartech_date' => '2026-04-02 08:00:00',
                    'latitude' => 42.3309,
                    'longitude' => -71.0508,
                    'food_inspection_data' => (object) [
                        'description' => 'Cold storage issue',
                        'address' => '100 A St',
                    ],
                ],
            ]);

        $builder = new LocationReportMapSnapshotBuilder($dataService);
        $location = new Location([
            'name' => 'South Boston Home',
            'address' => '730 E Third St',
            'latitude' => 42.3310,
            'longitude' => -71.0512,
        ]);

        $series = $builder->buildDailySeries($location, 0.25, 3, 8);

        $this->assertCount(3, $series);
        $this->assertSame('2026-04-03', $series[0]['window']['date']);
        $this->assertSame('2026-04-02', $series[1]['window']['date']);
        $this->assertSame('2026-04-01', $series[2]['window']['date']);
        $this->assertSame('diamond', $series[0]['incidents'][0]['shape']);
        $this->assertSame($series[0]['render_fingerprint'], $series[1]['render_fingerprint']);
        $this->assertTrue($series[2]['empty']);
    }

    public function test_it_anchors_the_daily_series_to_the_latest_incident_day_in_report_timezone(): void
    {
        config()->set('services.reports.timezone', 'America/New_York');
        Carbon::setTestNow(Carbon::parse('2026-04-04 01:30:00', 'UTC'));

        $dataService = Mockery::mock(LocationReportDataService::class);
        $dataService
            ->shouldReceive('fetch')
            ->once()
            ->andReturn([
                (object) [
                    'alcivartech_type' => '311 Case',
                    'alcivartech_date' => '2026-04-03 20:15:00',
                    'latitude' => 42.3309,
                    'longitude' => -71.0508,
                    'three_one_one_case_data' => (object) [
                        'service_name' => 'Illegal Parking',
                    ],
                ],
                (object) [
                    'alcivartech_type' => 'Crime',
                    'alcivartech_date' => '2026-04-02 10:00:00',
                    'latitude' => 42.3312,
                    'longitude' => -71.0510,
                    'crime_data' => (object) [
                        'primary_type' => 'Larceny',
                    ],
                ],
            ]);

        $builder = new LocationReportMapSnapshotBuilder($dataService);
        $location = new Location([
            'name' => 'South Boston Home',
            'address' => '730 E Third St',
            'latitude' => 42.3310,
            'longitude' => -71.0512,
        ]);

        $series = $builder->buildDailySeries($location, 0.25, 3, 8);

        $this->assertSame('2026-04-03', $series[0]['window']['date']);
        $this->assertSame('April 3, 2026', $series[0]['window']['display']);
        $this->assertFalse($series[0]['empty']);
        $this->assertSame('2026-04-02', $series[1]['window']['date']);
        $this->assertSame('2026-04-01', $series[2]['window']['date']);
    }
}
