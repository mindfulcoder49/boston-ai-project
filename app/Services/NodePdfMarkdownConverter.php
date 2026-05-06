<?php

namespace App\Services;

use App\Services\Concerns\NormalizesPdfExtractedText;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class NodePdfMarkdownConverter
{
    use NormalizesPdfExtractedText;

    private ?array $runtimeDescription = null;

    public function __construct(
        private readonly ?string $nodeBinary = null,
        private readonly ?string $extractorScriptPath = null,
    ) {
    }

    public function convertFile(string $pdfPath, string $markdownPath, ?string $rawTextPath = null): array
    {
        $rawText = $this->extractText($pdfPath, $rawTextPath);
        $markdown = $this->normalizeExtractedText($this->cleanExtractedText($rawText));

        File::ensureDirectoryExists(dirname($markdownPath));
        File::put($markdownPath, $markdown);

        $runtime = $this->describeRuntime();

        return [
            'markdown_path' => $markdownPath,
            'raw_text_path' => $rawTextPath,
            'bytes' => strlen($markdown),
            'line_count' => substr_count($markdown, "\n"),
            'node_binary' => $runtime['node_binary'],
            'node_version' => $runtime['node_version'],
            'extractor_script_path' => $runtime['extractor_script_path'],
        ];
    }

    public function convertToMarkdown(string $pdfPath): string
    {
        return $this->normalizeExtractedText(
            $this->cleanExtractedText($this->extractText($pdfPath))
        );
    }

    public function describeRuntime(): array
    {
        if (is_array($this->runtimeDescription)) {
            return $this->runtimeDescription;
        }

        $nodeBinary = $this->resolveNodeBinary();
        $process = new Process([$nodeBinary, '--version']);
        $process->setTimeout(30);
        $process->mustRun();

        $packageJsonPath = base_path('node_modules/pdf-parse/package.json');
        $packageVersion = null;
        if (File::exists($packageJsonPath)) {
            $decoded = json_decode((string) File::get($packageJsonPath), true);
            if (is_array($decoded)) {
                $packageVersion = $decoded['version'] ?? null;
            }
        }

        return $this->runtimeDescription = [
            'node_binary' => $nodeBinary,
            'node_version' => trim($process->getOutput()),
            'extractor_script_path' => $this->resolveExtractorScriptPath(),
            'pdf_parse_version' => $packageVersion,
        ];
    }

    public function resolveNodeBinary(): string
    {
        if (is_string($this->nodeBinary) && trim($this->nodeBinary) !== '') {
            return trim($this->nodeBinary);
        }

        $configuredNodePath = config('services.playwright.node_path');
        if (is_string($configuredNodePath) && trim($configuredNodePath) !== '') {
            return trim($configuredNodePath);
        }

        $executableFinder = new ExecutableFinder();
        $foundBinary = $executableFinder->find('node');
        if (is_string($foundBinary) && trim($foundBinary) !== '') {
            return trim($foundBinary);
        }

        $homeDirectory = getenv('HOME');
        if (is_string($homeDirectory) && trim($homeDirectory) !== '') {
            $candidates = glob(rtrim($homeDirectory, '/') . '/.nvm/versions/node/*/bin/node') ?: [];
            rsort($candidates, SORT_NATURAL);

            foreach ($candidates as $candidate) {
                if (is_string($candidate) && File::isFile($candidate) && is_executable($candidate)) {
                    return $candidate;
                }
            }
        }

        return 'node';
    }

    public function resolveExtractorScriptPath(): string
    {
        if (is_string($this->extractorScriptPath) && trim($this->extractorScriptPath) !== '') {
            return trim($this->extractorScriptPath);
        }

        $defaultPath = base_path('scripts/extract_pdf_text.cjs');
        if (! File::exists($defaultPath)) {
            throw new \RuntimeException('The Node PDF extractor script was not found at ' . $defaultPath . '.');
        }

        return $defaultPath;
    }

    protected function extractText(string $pdfPath, ?string $rawTextPath = null): string
    {
        $outputPath = $rawTextPath;
        $temporaryOutputPath = null;

        if (! is_string($outputPath) || trim($outputPath) === '') {
            $temporaryDirectory = storage_path('app/tmp/pdf_parse');
            File::ensureDirectoryExists($temporaryDirectory);

            $outputPath = tempnam($temporaryDirectory, 'pdf_parse_');
            if ($outputPath === false) {
                throw new \RuntimeException('Unable to allocate a temporary output path for pdf-parse extraction.');
            }

            $temporaryOutputPath = $outputPath;
        } else {
            File::ensureDirectoryExists(dirname($outputPath));
        }

        $process = new Process([
            $this->resolveNodeBinary(),
            $this->resolveExtractorScriptPath(),
            $pdfPath,
            $outputPath,
        ]);

        $process->setTimeout(120);

        try {
            $process->mustRun();

            return File::get($outputPath);
        } finally {
            if ($temporaryOutputPath !== null) {
                File::delete($temporaryOutputPath);
            }
        }
    }

    protected function cleanExtractedText(string $text): string
    {
        return preg_replace('/^\s*--\s+\d+\s+of\s+\d+\s+--\s*$/m', '', $text) ?? $text;
    }
}
