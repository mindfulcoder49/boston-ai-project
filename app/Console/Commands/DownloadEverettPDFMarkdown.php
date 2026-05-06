<?php

namespace App\Console\Commands;

use App\Support\OperationalSummaryLogger;
use Illuminate\Console\Command;
use App\Services\NodePdfMarkdownConverter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\PdfLinkExtractorService;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Client\Response;

class DownloadEverettPDFMarkdown extends Command
{
    protected $signature = 'app:download-everett-pdf-markdown';
    protected $description = 'Downloads Everett police PDFs directly and converts them to markdown locally.';

    public function __construct(
        private readonly PdfLinkExtractorService $pdfLinkExtractor,
        private readonly NodePdfMarkdownConverter $nodePdfMarkdownConverter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $config = config('everett_datasets');
        if (!$config || !isset($config['arrest_log_page_url_template']) || !isset($config['daily_log_page_url_template']) || !isset($config['years_to_process'])) {
            $this->error("Everett page URL templates or years_to_process not configured properly in config/everett_datasets.php.");
            return 1;
        }
        
        $pages = [];
        $arrestLogTemplate = $config['arrest_log_page_url_template'];
        $dailyLogTemplate = $config['daily_log_page_url_template'];
        $years = $config['years_to_process'];

        foreach ($years as $year) {
            $pages[] = str_replace('{year}', $year, $arrestLogTemplate);
            $pages[] = str_replace('{year}', $year, $dailyLogTemplate);
        }

        $pages = array_filter($pages);

        if (empty($pages)) {
            $this->error("Everett page URLs are empty after filtering. Check config/everett_datasets.php.");
            return 1;
        }

        $baseStorageDir = storage_path('app/datasets/everett');
        $mdOutputDirRelative = $config['markdown_output_directory'] ?? 'markdown_output';
        $pdfOutputDirRelative = $config['pdfs_directory'] ?? 'pdfs';
        $htmlOutputDirRelative = $config['html_pages_directory'] ?? 'html_pages';
        $mdOutputDir = $baseStorageDir . '/' . trim($mdOutputDirRelative, '/');
        $pdfOutputDir = $baseStorageDir . '/' . trim($pdfOutputDirRelative, '/');
        $htmlOutputDir = $baseStorageDir . '/' . trim($htmlOutputDirRelative, '/');

        File::ensureDirectoryExists($baseStorageDir);
        File::ensureDirectoryExists($mdOutputDir);
        File::ensureDirectoryExists($pdfOutputDir);
        File::ensureDirectoryExists($htmlOutputDir);

        $existingBaseFilenames = [];
        foreach (File::files($mdOutputDir) as $file) {
            $filename = $file->getFilename();
            if (preg_match('/^(.*?)_\d{8}_\d{6}\.md$/', $filename, $matches)) {
                $existingBaseFilenames[$matches[1]] = true;
            }
        }

        if (count($existingBaseFilenames) > 0) {
            $this->info("Found " . count($existingBaseFilenames) . " existing base filenames in {$mdOutputDir}. These will be skipped if encountered again.");
        }

        $summary = [
            'pages_total' => count($pages),
            'pages_failed' => 0,
            'pdf_links_found' => 0,
            'markdown_saved' => 0,
            'markdown_skipped_existing' => 0,
            'markdown_failed' => 0,
            'markdown_missing_source' => 0,
            'pdf_downloaded' => 0,
        ];

        OperationalSummaryLogger::emit($this, $this->getName(), 'start', [
            'page_count' => count($pages),
            'output_directory' => $mdOutputDir,
        ]);

        foreach ($pages as $pageUrl) {
            $this->info("Processing page: {$pageUrl} to find PDF links.");

            try {
                $response = Http::timeout(120)
                    ->withHeaders([
                        'User-Agent' => 'PublicDataWatch Everett ingestion',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    ])
                    ->withOptions(['allow_redirects' => true])
                    ->get($pageUrl);

                if (!$response->successful()) {
                    $this->error("Failed to fetch Everett page: {$pageUrl}. Status: {$response->status()}. Body: " . $response->body());
                    Log::error("Everett page fetch error for {$pageUrl}: " . $response->body());
                    $summary['pages_failed']++;
                    continue;
                }

                $htmlContent = $response->body();

                if (empty($htmlContent)) {
                    $this->warn("Received empty HTML content for page: {$pageUrl}. Skipping PDF link extraction.");
                    $summary['pages_failed']++;
                    continue;
                }

                File::put($htmlOutputDir . DIRECTORY_SEPARATOR . $this->htmlArtifactFilename($pageUrl), $htmlContent);

                $pdfLinks = $this->pdfLinkExtractor->extractFromHtml($htmlContent, $pageUrl);
                $this->info("Found " . count($pdfLinks) . " PDF links on {$pageUrl}");
                $summary['pdf_links_found'] += count($pdfLinks);

                foreach ($pdfLinks as $pdfLink) {
                    $this->info("Processing PDF link for Markdown: {$pdfLink}");

                    $pathinfoCurrentPdf = pathinfo((string) parse_url($pdfLink, PHP_URL_PATH));
                    $currentPdfBaseFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $pathinfoCurrentPdf['filename'] ?? 'document');

                    if (isset($existingBaseFilenames[$currentPdfBaseFilename])) {
                        $this->info("Skipping PDF link as a version (based on filename pattern '{$currentPdfBaseFilename}_[timestamp].md') already exists: {$pdfLink}");
                        $summary['markdown_skipped_existing']++;
                        continue; // Skip to the next PDF link
                    }

                    $timestamp = now()->format('Ymd_His');
                    $pdfFilepath = $pdfOutputDir . DIRECTORY_SEPARATOR . "{$currentPdfBaseFilename}_{$timestamp}.pdf";
                    $markdownFilepath = $mdOutputDir . DIRECTORY_SEPARATOR . "{$currentPdfBaseFilename}_{$timestamp}.md";

                    $pdfResponse = Http::timeout(600)
                        ->withHeaders([
                            'User-Agent' => 'PublicDataWatch Everett PDF downloader',
                            'Accept' => 'application/pdf,*/*',
                        ])
                        ->withOptions(['allow_redirects' => true])
                        ->get($pdfLink);

                    if (!$pdfResponse->successful()) {
                        if ($pdfResponse->status() === 404) {
                            $this->warn("Skipping missing Everett PDF source: {$pdfLink}");
                            Log::warning("Missing Everett PDF source.", [
                                'pdf_url' => $pdfLink,
                                'status' => $pdfResponse->status(),
                                'body' => $pdfResponse->body(),
                            ]);
                            $summary['markdown_missing_source']++;
                            continue;
                        }

                        $this->error("Failed to download Everett PDF for Markdown conversion: {$pdfLink}. Status: {$pdfResponse->status()}. Body: " . $pdfResponse->body());
                        Log::error("Everett PDF download error for {$pdfLink}: " . $pdfResponse->body());
                        $summary['markdown_failed']++;
                        continue;
                    }

                    if (! $this->isValidPdfResponse($pdfResponse)) {
                        $this->warn("Skipping Everett PDF source that did not return a PDF document: {$pdfLink}");
                        Log::warning('Everett PDF source returned a non-PDF or empty response.', [
                            'pdf_url' => $pdfLink,
                            'status' => $pdfResponse->status(),
                            'content_type' => $pdfResponse->header('Content-Type'),
                            'body_length' => strlen($pdfResponse->body()),
                        ]);
                        $summary['markdown_missing_source']++;
                        continue;
                    }

                    File::put($pdfFilepath, $pdfResponse->body());
                    $summary['pdf_downloaded']++;

                    try {
                        $conversion = $this->nodePdfMarkdownConverter->convertFile($pdfFilepath, $markdownFilepath);
                        $this->info("Saved Markdown to {$markdownFilepath}");
                        $summary['markdown_saved']++;
                        $existingBaseFilenames[$currentPdfBaseFilename] = true;
                        OperationalSummaryLogger::emit($this, $this->getName(), 'markdown_saved', [
                            'page_url' => $pageUrl,
                            'pdf_url' => $pdfLink,
                            'pdf_file' => $pdfFilepath,
                            'output_file' => $markdownFilepath,
                            'bytes_written' => $conversion['bytes'] ?? null,
                            'converter' => 'node_pdf_parse',
                            'node_binary' => $conversion['node_binary'] ?? null,
                            'node_version' => $conversion['node_version'] ?? null,
                        ]);
                    } catch (\Throwable $throwable) {
                        $this->error("Failed to convert Everett PDF to Markdown for: {$pdfLink}. Error: {$throwable->getMessage()}");
                        Log::error("Everett PDF markdown conversion error.", [
                            'pdf_url' => $pdfLink,
                            'pdf_path' => $pdfFilepath,
                            'error' => $throwable->getMessage(),
                        ]);
                        $summary['markdown_failed']++;
                    }
                }
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $this->error("HTTP Request Exception while processing {$pageUrl}: " . $e->getMessage());
                Log::error("Everett request exception for URL {$pageUrl}: " . $e->getMessage());
                $summary['pages_failed']++;
            } catch (\Exception $e) {
                $this->error("An unexpected error occurred while processing {$pageUrl}: " . $e->getMessage());
                Log::error("Unexpected error for URL {$pageUrl}: " . $e->getMessage());
                $summary['pages_failed']++;
            }
        }

        $this->info("Processing complete.");
        OperationalSummaryLogger::emit($this, $this->getName(), 'complete', $summary, ($summary['pages_failed'] > 0 || $summary['markdown_failed'] > 0 || $summary['markdown_missing_source'] > 0) ? 'warning' : 'info');

        return ($summary['pages_failed'] > 0 || $summary['markdown_failed'] > 0) ? 1 : 0;
    }

    private function isValidPdfResponse(Response $response): bool
    {
        $body = $response->body();
        if ($body === '') {
            return false;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (str_contains($contentType, 'application/pdf')) {
            return true;
        }

        return str_starts_with(ltrim($body), '%PDF-');
    }

    private function htmlArtifactFilename(string $pageUrl): string
    {
        $path = trim((string) parse_url($pageUrl, PHP_URL_PATH), '/');
        $safePath = preg_replace('/[^a-zA-Z0-9_-]/', '_', $path ?: 'page');

        return $safePath . '_' . now()->format('Ymd_His') . '.html';
    }
}
