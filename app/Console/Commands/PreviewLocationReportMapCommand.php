<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\LocationReportMapScreenshotService;
use App\Services\LocationReportMapSnapshotBuilder;
use App\Services\LocationReportMapSnapshotUrlGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PreviewLocationReportMapCommand extends Command
{
    protected $signature = 'reports:snapshot-map
                            {location_id : Generate a deterministic map snapshot for a saved location}
                            {--radius=0.25 : Radius in miles}
                            {--days=2 : Number of recent days to include}
                            {--limit=4 : Maximum incidents to show on the map}
                            {--json : Output JSON instead of a text summary}
                            {--write : Write snapshot data to storage/app/report_snapshots}
                            {--capture : Capture a PNG screenshot of the hidden render page}';

    protected $description = 'Preview the deterministic map snapshot that can power report-email images.';

    public function __construct(
        private readonly LocationReportMapSnapshotBuilder $snapshotBuilder,
        private readonly LocationReportMapSnapshotUrlGenerator $urlGenerator,
        private readonly LocationReportMapScreenshotService $screenshotService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $location = Location::with('user')->find($this->argument('location_id'));

        if (!$location) {
            $this->error('Location not found.');
            return 1;
        }

        $radius = (float) $this->option('radius');
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');

        $snapshot = $this->snapshotBuilder->build($location, $radius, $days, $limit);
        $renderUrl = $this->urlGenerator->generate($location, $radius, $days, $limit);

        $payload = [
            'location_id' => $location->id,
            'user_id' => $location->user_id,
            'render_url' => $renderUrl,
            'snapshot' => $snapshot,
        ];

        $basePath = null;
        if ($this->option('write') || $this->option('capture')) {
            $directory = storage_path('app/report_snapshots');
            File::ensureDirectoryExists($directory);
            $basePath = $directory . '/' . Carbon::now()->format('YmdHis') . "_location_{$location->id}_snapshot";
        }

        if ($this->option('capture')) {
            $screenshotPath = $this->screenshotService->capture($renderUrl, $basePath ? "{$basePath}.png" : null);
            $payload['screenshot_path'] = $screenshotPath;
            $this->line("Map screenshot captured at {$screenshotPath}");
        }

        if ($this->option('write') && $basePath) {
            $jsonPath = "{$basePath}.json";
            File::put($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->line("Snapshot data written to {$jsonPath}");
        }

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->line('Map snapshot prepared.');
        $this->line('Render URL: ' . $renderUrl);
        $this->line('Selection policy: ' . $snapshot['selection_policy']);
        $this->line('Recent incidents in window: ' . $snapshot['recent_points_in_window']);
        $this->line('Incidents shown on map: ' . $snapshot['selected_points']);

        if (($snapshot['omitted_points'] ?? 0) > 0) {
            $this->line('Omitted from map image: ' . $snapshot['omitted_points']);
        }

        if (!empty($snapshot['empty'])) {
            $this->warn('No incidents were found in the requested window; the render page will show only the home marker.');
        }

        return 0;
    }
}
