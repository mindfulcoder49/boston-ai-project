<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class AdminLongWorkerHeartbeat
{
    public static function record(string $command, string $status, array $context = []): void
    {
        $path = config('backend_admin.dependency_health.worker_heartbeat_path');
        File::ensureDirectoryExists(dirname($path));

        File::put($path, json_encode(array_merge([
            'last_seen_at' => Carbon::now()->toIso8601String(),
            'command' => $command,
            'status' => $status,
        ], $context), JSON_PRETTY_PRINT));
    }

    public static function latest(): ?array
    {
        $path = config('backend_admin.dependency_health.worker_heartbeat_path');

        if (!File::exists($path)) {
            return null;
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function freshness(?Carbon $now = null): array
    {
        $heartbeat = self::latest();
        $now ??= Carbon::now();

        if (!$heartbeat || empty($heartbeat['last_seen_at'])) {
            return [
                'status' => 'unknown',
                'label' => 'No worker evidence',
                'age_minutes' => null,
                'last_seen_at' => null,
                'command' => $heartbeat['command'] ?? null,
                'worker_status' => $heartbeat['status'] ?? null,
            ];
        }

        $lastSeen = Carbon::parse($heartbeat['last_seen_at']);
        $ageMinutes = $lastSeen->diffInMinutes($now);
        $maxAge = (int) config('backend_admin.dependency_health.worker_heartbeat_max_age_minutes', 180);

        $status = $ageMinutes <= $maxAge ? 'healthy' : 'warning';
        $label = $status === 'healthy' ? 'Recent worker activity' : 'Worker evidence is stale';

        return [
            'status' => $status,
            'label' => $label,
            'age_minutes' => $ageMinutes,
            'last_seen_at' => $heartbeat['last_seen_at'],
            'command' => $heartbeat['command'] ?? null,
            'worker_status' => $heartbeat['status'] ?? null,
        ];
    }
}
