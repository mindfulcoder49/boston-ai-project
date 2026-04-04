<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\File;

class LocationReportMapAssetCache
{
    public function imagePath(Location $location, array $snapshot): string
    {
        $directory = $this->directory($location);
        File::ensureDirectoryExists($directory);

        return $directory . '/' . $this->fingerprint($snapshot) . '.png';
    }

    public function hasImage(Location $location, array $snapshot): bool
    {
        $path = $this->imagePath($location, $snapshot);

        return File::exists($path) && (int) File::size($path) > 0;
    }

    public function persistMetadata(Location $location, array $snapshot, array $context = []): void
    {
        $directory = $this->directory($location);
        File::ensureDirectoryExists($directory);

        $metadataPath = $directory . '/' . $this->fingerprint($snapshot) . '.json';

        File::put($metadataPath, json_encode([
            'fingerprint' => $this->fingerprint($snapshot),
            'captured_at' => now()->toIso8601String(),
            'snapshot' => $snapshot,
            'context' => $context,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function directory(Location $location): string
    {
        return storage_path('app/report_snapshots/cache/location_' . $location->getKey());
    }

    private function fingerprint(array $snapshot): string
    {
        $fingerprint = $snapshot['render_fingerprint'] ?? null;

        if (is_string($fingerprint) && trim($fingerprint) !== '') {
            return trim($fingerprint);
        }

        return sha1(json_encode($snapshot['markers'] ?? [], JSON_UNESCAPED_SLASHES));
    }
}
