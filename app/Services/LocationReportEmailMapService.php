<?php

namespace App\Services;

use App\Models\Location;

class LocationReportEmailMapService
{
    public function __construct(
        private readonly LocationReportMapSnapshotBuilder $snapshotBuilder,
        private readonly LocationReportMapSnapshotUrlGenerator $urlGenerator,
        private readonly LocationReportMapScreenshotService $screenshotService
    ) {}

    public function capture(Location $location, ?float $radius = null): ?array
    {
        $radius ??= (float) config('services.reports.email_map_radius', 0.25);
        $limit = (int) config('services.reports.email_map_limit', 4);

        foreach ($this->snapshotDayAttempts() as $days) {
            $snapshot = $this->snapshotBuilder->build($location, $radius, $days, $limit);

            if (($snapshot['selected_points'] ?? 0) < 1) {
                continue;
            }

            $renderUrl = $this->urlGenerator->generate($location, $radius, $days, $limit);
            $path = $this->screenshotService->capture($renderUrl);

            return [
                'path' => $path,
                'days' => $days,
                'snapshot' => $snapshot,
            ];
        }

        return null;
    }

    private function snapshotDayAttempts(): array
    {
        $primaryDays = (int) config('services.reports.email_map_days', 2);
        $fallbackDays = (int) config('services.reports.email_map_fallback_days', 7);

        return array_values(array_unique(array_filter([
            max($primaryDays, 1),
            max($fallbackDays, 1),
        ])));
    }
}
