<?php

namespace App\Services;

use App\Services\Concerns\NormalizesPdfExtractedText;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class LocalPdfMarkdownConverter
{
    use NormalizesPdfExtractedText;

    public function __construct(private readonly string $binary = 'pdftotext')
    {
    }

    public function convertFile(string $pdfPath, string $markdownPath): array
    {
        $markdown = $this->convertToMarkdown($pdfPath);

        File::ensureDirectoryExists(dirname($markdownPath));
        File::put($markdownPath, $markdown);

        return [
            'markdown_path' => $markdownPath,
            'bytes' => strlen($markdown),
            'line_count' => substr_count($markdown, "\n"),
        ];
    }

    public function convertToMarkdown(string $pdfPath): string
    {
        $text = $this->extractText($pdfPath);

        return $this->normalizeExtractedText($text);
    }

    protected function extractText(string $pdfPath): string
    {
        $process = new Process([
            $this->binary,
            '-layout',
            '-nopgbrk',
            '-enc',
            'UTF-8',
            $pdfPath,
            '-',
        ]);

        $process->setTimeout(120);
        $process->mustRun();

        return $process->getOutput();
    }
}
