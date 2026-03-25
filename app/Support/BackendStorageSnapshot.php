<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class BackendStorageSnapshot
{
    public static function build(): array
    {
        $targets = config('backend_admin.storage.targets', []);
        $snapshots = [];

        foreach ($targets as $target) {
            $size = self::pathSize((string) ($target['path'] ?? ''));
            $warningBytes = (int) ($target['warning_bytes'] ?? 0);

            $snapshots[] = [
                'slug' => $target['slug'] ?? null,
                'label' => $target['label'] ?? ($target['slug'] ?? 'Unknown'),
                'path' => $target['path'] ?? null,
                'size_bytes' => $size,
                'size_human' => self::formatBytes($size),
                'status' => $warningBytes > 0 && $size >= $warningBytes ? 'warning' : 'healthy',
                'warning_bytes' => $warningBytes,
            ];
        }

        return [
            'targets' => $snapshots,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = max($bytes, 0);
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return round($value, 2) . ' ' . $units[$unitIndex];
    }

    private static function pathSize(string $path): int
    {
        if ($path === '' || !File::exists($path)) {
            return 0;
        }

        if (File::isFile($path)) {
            return (int) File::size($path);
        }

        $size = 0;

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
