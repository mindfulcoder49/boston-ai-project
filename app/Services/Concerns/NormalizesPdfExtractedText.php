<?php

namespace App\Services\Concerns;

trait NormalizesPdfExtractedText
{
    public function normalizeExtractedText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace("\f", "\n", $text);
        $text = str_replace("\0", '', $text);

        $lines = array_map(
            static fn (string $line): string => rtrim($line, " \t"),
            explode("\n", $text)
        );

        $normalized = implode("\n", $lines);
        $normalized = preg_replace("/\n{4,}/", "\n\n\n", $normalized) ?? $normalized;

        return rtrim($normalized) . "\n";
    }
}
