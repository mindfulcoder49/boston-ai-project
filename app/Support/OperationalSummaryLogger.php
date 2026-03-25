<?php

namespace App\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OperationalSummaryLogger
{
    private const MARKER = '[OP_SUMMARY] ';

    public static function emit(?Command $command, string $component, string $event, array $context = [], string $level = 'info'): void
    {
        $payload = array_merge([
            'component' => $component,
            'event' => $event,
        ], $context);

        $line = self::MARKER . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        Log::log($level, $line);

        if (!$command) {
            return;
        }

        if ($level === 'error') {
            $command->error($line);
            return;
        }

        if ($level === 'warning') {
            $command->warn($line);
            return;
        }

        $command->line($line);
    }

    public static function extractFromText(string $content): array
    {
        $events = [];

        foreach (preg_split("/\r\n|\n|\r/", $content) as $line) {
            $line = trim($line);

            if ($line === '' || !str_contains($line, self::MARKER)) {
                continue;
            }

            $markerPosition = strpos($line, self::MARKER);
            $payload = trim(substr($line, $markerPosition + strlen(self::MARKER)));
            $decoded = json_decode($payload, true);

            if (!is_array($decoded)) {
                continue;
            }

            $events[] = $decoded;
        }

        return $events;
    }

    public static function extractFromFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        return self::extractFromText((string) file_get_contents($path));
    }
}
