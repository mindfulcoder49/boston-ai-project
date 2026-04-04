<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\User;
use App\Services\LocationReportEmailMapService;
use App\Services\LocationReportMapAssetCache;
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

    public function test_it_reuses_cached_daily_map_images_when_the_snapshot_matches(): void
    {
        config()->set('services.reports.email_map_days', 7);
        config()->set('services.reports.email_map_limit', 8);
        config()->set('services.reports.email_map_radius', 0.25);

        $location = new Location([
            'latitude' => 42.36,
            'longitude' => -71.05,
            'address' => '1 Main St',
            'user_id' => 1,
        ]);
        $location->setRelation('user', new User(['id' => 1]));

        $snapshot = [
            'window' => ['date' => '2026-04-03', 'days' => 1],
            'selected_points' => 2,
            'render_fingerprint' => 'fingerprint-1',
            'incidents' => [
                ['label' => '1'],
            ],
        ];

        $builder = Mockery::mock(LocationReportMapSnapshotBuilder::class);
        $builder->shouldReceive('buildDailySeries')
            ->once()
            ->with($location, 0.25, 7, 8)
            ->andReturn([$snapshot]);

        $urlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $urlGenerator->shouldNotReceive('generateForDate');

        $screenshotService = Mockery::mock(LocationReportMapScreenshotService::class);
        $screenshotService->shouldNotReceive('capture');

        $assetCache = Mockery::mock(LocationReportMapAssetCache::class);
        $assetCache->shouldReceive('imagePath')
            ->once()
            ->with($location, $snapshot)
            ->andReturn('/tmp/location-map.png');
        $assetCache->shouldReceive('hasImage')
            ->twice()
            ->with($location, $snapshot)
            ->andReturn(true);
        $assetCache->shouldNotReceive('persistMetadata');

        $service = new LocationReportEmailMapService($builder, $urlGenerator, $screenshotService, $assetCache);
        $result = $service->captureDailySeries($location);

        $this->assertCount(1, $result);
        $this->assertSame('/tmp/location-map.png', $result[0]['path']);
        $this->assertTrue($result[0]['cached']);
    }

    public function test_it_captures_and_persists_uncached_daily_map_images(): void
    {
        config()->set('services.reports.email_map_days', 7);
        config()->set('services.reports.email_map_limit', 8);
        config()->set('services.reports.email_map_radius', 0.25);

        $location = new Location([
            'latitude' => 42.36,
            'longitude' => -71.05,
            'address' => '1 Main St',
            'user_id' => 1,
        ]);
        $location->setRelation('user', new User(['id' => 1]));

        $snapshot = [
            'window' => ['date' => '2026-04-03', 'days' => 1],
            'selected_points' => 2,
            'render_fingerprint' => 'fingerprint-2',
            'incidents' => [
                ['label' => '1'],
            ],
        ];

        $builder = Mockery::mock(LocationReportMapSnapshotBuilder::class);
        $builder->shouldReceive('buildDailySeries')
            ->once()
            ->with($location, 0.25, 7, 8)
            ->andReturn([$snapshot]);

        $urlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $urlGenerator->shouldReceive('generateForDate')
            ->once()
            ->with($location, 0.25, '2026-04-03', 8)
            ->andReturn('https://example.test/snapshot');

        $screenshotService = Mockery::mock(LocationReportMapScreenshotService::class);
        $screenshotService->shouldReceive('capture')
            ->once()
            ->with('https://example.test/snapshot', '/tmp/location-map.png');

        $assetCache = Mockery::mock(LocationReportMapAssetCache::class);
        $assetCache->shouldReceive('imagePath')
            ->once()
            ->with($location, $snapshot)
            ->andReturn('/tmp/location-map.png');
        $assetCache->shouldReceive('hasImage')
            ->with($location, $snapshot)
            ->andReturn(false, true);
        $assetCache->shouldReceive('persistMetadata')
            ->once()
            ->with($location, $snapshot, Mockery::on(fn (array $context): bool => ($context['render_url'] ?? null) === 'https://example.test/snapshot'));

        $service = new LocationReportEmailMapService($builder, $urlGenerator, $screenshotService, $assetCache);
        $result = $service->captureDailySeries($location);

        $this->assertCount(1, $result);
        $this->assertSame('/tmp/location-map.png', $result[0]['path']);
        $this->assertFalse($result[0]['cached']);
    }
}
