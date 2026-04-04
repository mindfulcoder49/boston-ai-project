<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Log;

class LocationReportEmailMapService
{
    public function __construct(
        private readonly LocationReportMapSnapshotBuilder $snapshotBuilder,
        private readonly LocationReportMapSnapshotUrlGenerator $urlGenerator,
        private readonly LocationReportMapScreenshotService $screenshotService,
        private readonly LocationReportMapAssetCache $assetCache
    ) {}

    public function capture(Location $location, ?float $radius = null): ?array
    {
        foreach ($this->captureDailySeries($location, $radius) as $map) {
            if (($map['snapshot']['selected_points'] ?? 0) > 0 && is_string($map['path'] ?? null)) {
                return [
                    'path' => $map['path'],
                    'days' => 1,
                    'snapshot' => $map['snapshot'],
                ];
            }
        }

        return null;
    }

    public function captureLatestDay(Location $location, ?float $radius = null): ?array
    {
        $radius ??= (float) config('services.reports.email_map_radius', 0.25);
        $limit = (int) config('services.reports.email_map_limit', 8);
        $days = (int) config('services.reports.email_map_days', 7);
        $snapshots = $this->snapshotBuilder->buildDailySeries($location, $radius, $days, $limit);
        $snapshot = collect($snapshots)->first(
            fn (array $candidate): bool => (int) ($candidate['selected_points'] ?? 0) > 0
        );

        if (!is_array($snapshot)) {
            return null;
        }

        return $this->captureSnapshot($location, $radius, $limit, $snapshot);
    }

    public function captureDailySeries(Location $location, ?float $radius = null): array
    {
        $radius ??= (float) config('services.reports.email_map_radius', 0.25);
        $limit = (int) config('services.reports.email_map_limit', 8);
        $days = (int) config('services.reports.email_map_days', 7);

        $snapshots = $this->snapshotBuilder->buildDailySeries($location, $radius, $days, $limit);

        return array_map(
            fn (array $snapshot) => $this->captureSnapshot($location, $radius, $limit, $snapshot),
            $snapshots
        );
    }

    private function captureSnapshot(Location $location, float $radius, int $limit, array $snapshot): array
    {
        $path = $this->assetCache->imagePath($location, $snapshot);
        $cached = $this->assetCache->hasImage($location, $snapshot);
        $error = null;

        if (!$cached) {
            try {
                $renderUrl = isset($snapshot['window']['date']) && is_string($snapshot['window']['date']) && $snapshot['window']['date'] !== ''
                    ? $this->urlGenerator->generateForDate($location, $radius, $snapshot['window']['date'], $limit)
                    : $this->urlGenerator->generate(
                        $location,
                        $radius,
                        (int) ($snapshot['window']['days'] ?? 1),
                        $limit
                    );

                $this->screenshotService->capture($renderUrl, $path);
                $this->assetCache->persistMetadata($location, $snapshot, [
                    'location_id' => $location->getKey(),
                    'radius' => $radius,
                    'limit' => $limit,
                    'render_url' => $renderUrl,
                ]);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();

                Log::warning('Location report map capture failed for daily snapshot.', [
                    'location_id' => $location->getKey(),
                    'window_date' => $snapshot['window']['date'] ?? null,
                    'message' => $error,
                ]);
            }
        }

        return [
            'path' => $this->assetCache->hasImage($location, $snapshot) ? $path : null,
            'cached' => $cached,
            'error' => $error,
            'snapshot' => $snapshot,
        ];
    }
}
