<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\User;
use App\Services\LocationReportEmailMapService;
use App\Services\LocationReportMapScreenshotService;
use App\Services\LocationReportMapSnapshotBuilder;
use App\Services\LocationReportMapSnapshotUrlGenerator;
use Mockery;
use Tests\TestCase;

class LocationReportEmailMapServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_falls_back_to_a_wider_window_when_the_primary_window_is_empty(): void
    {
        config()->set('services.reports.email_map_days', 2);
        config()->set('services.reports.email_map_fallback_days', 7);
        config()->set('services.reports.email_map_limit', 4);
        config()->set('services.reports.email_map_radius', 0.25);

        $location = new Location([
            'latitude' => 42.36,
            'longitude' => -71.05,
            'address' => '1 Main St',
            'user_id' => 1,
        ]);
        $location->setRelation('user', new User(['id' => 1]));

        $builder = Mockery::mock(LocationReportMapSnapshotBuilder::class);
        $builder->shouldReceive('build')
            ->once()
            ->with($location, 0.25, 2, 4)
            ->andReturn(['selected_points' => 0]);
        $builder->shouldReceive('build')
            ->once()
            ->with($location, 0.25, 7, 4)
            ->andReturn(['selected_points' => 3]);

        $urlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $urlGenerator->shouldReceive('generate')
            ->once()
            ->with($location, 0.25, 7, 4)
            ->andReturn('https://example.test/snapshot');

        $screenshotService = Mockery::mock(LocationReportMapScreenshotService::class);
        $screenshotService->shouldReceive('capture')
            ->once()
            ->with('https://example.test/snapshot')
            ->andReturn('/tmp/location-map.png');

        $service = new LocationReportEmailMapService($builder, $urlGenerator, $screenshotService);
        $result = $service->capture($location);

        $this->assertSame('/tmp/location-map.png', $result['path']);
        $this->assertSame(7, $result['days']);
    }

    public function test_it_returns_null_when_no_snapshot_window_has_incidents(): void
    {
        config()->set('services.reports.email_map_days', 2);
        config()->set('services.reports.email_map_fallback_days', 7);
        config()->set('services.reports.email_map_limit', 4);
        config()->set('services.reports.email_map_radius', 0.25);

        $location = new Location([
            'latitude' => 42.36,
            'longitude' => -71.05,
            'address' => '1 Main St',
            'user_id' => 1,
        ]);
        $location->setRelation('user', new User(['id' => 1]));

        $builder = Mockery::mock(LocationReportMapSnapshotBuilder::class);
        $builder->shouldReceive('build')
            ->once()
            ->with($location, 0.25, 2, 4)
            ->andReturn(['selected_points' => 0]);
        $builder->shouldReceive('build')
            ->once()
            ->with($location, 0.25, 7, 4)
            ->andReturn(['selected_points' => 0]);

        $urlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $urlGenerator->shouldNotReceive('generate');

        $screenshotService = Mockery::mock(LocationReportMapScreenshotService::class);
        $screenshotService->shouldNotReceive('capture');

        $service = new LocationReportEmailMapService($builder, $urlGenerator, $screenshotService);

        $this->assertNull($service->capture($location));
    }
}
