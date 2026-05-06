<?php

namespace App\Console\Commands;

use App\Support\OperationalSummaryLogger;
use Illuminate\Console\Command;
use App\Services\NodePdfMarkdownConverter;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\PdfLinkExtractorService;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Client\Response;

class DownloadEverettPDFMarkdown extends Command
{
    protected $signature = 'app:download-everett-pdf-markdown';
    protected $description = 'Downloads Everett police PDFs directly, with scraper fallback when needed, and converts them to markdown.';

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
                $pageFetch = $this->fetchPageHtml($pageUrl);
                if (! ($pageFetch['success'] ?? false)) {
                    $this->error("Failed to fetch Everett page: {$pageUrl}. " . ($pageFetch['message'] ?? 'Unknown error.'));
                    Log::error('Everett page fetch error.', [
                        'page_url' => $pageUrl,
                        'message' => $pageFetch['message'] ?? 'Unknown error.',
                    ]);
                    $summary['pages_failed']++;
                    continue;
                }

                $htmlContent = (string) ($pageFetch['html'] ?? '');

                if (empty($htmlContent)) {
                    $this->warn("Received empty HTML content for page: {$pageUrl}. Skipping PDF link extraction.");
                    $summary['pages_failed']++;
                    continue;
                }

                if (($pageFetch['source'] ?? 'direct') === 'scraper') {
                    $this->warn("Using scraper fallback to fetch Everett page: {$pageUrl}");
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

                    $conversion = $this->convertPdfToMarkdown($pdfLink, $pdfFilepath, $markdownFilepath);

                    if (($conversion['status'] ?? null) === 'missing') {
                        $this->warn("Skipping missing Everett PDF source: {$pdfLink}");
                        Log::warning('Missing Everett PDF source.', [
                            'pdf_url' => $pdfLink,
                            'message' => $conversion['message'] ?? null,
                        ]);
                        $summary['markdown_missing_source']++;
                        continue;
                    }

                    if (! ($conversion['success'] ?? false)) {
                        $this->error("Failed to convert Everett PDF to Markdown for: {$pdfLink}. Error: " . ($conversion['message'] ?? 'Unknown error.'));
                        Log::error("Everett PDF markdown conversion error.", [
                            'pdf_url' => $pdfLink,
                            'pdf_path' => $pdfFilepath,
                            'error' => $conversion['message'] ?? 'Unknown error.',
                        ]);
                        $summary['markdown_failed']++;
                        continue;
                    }

                    if (($conversion['converter'] ?? null) === 'scraper_service') {
                        $this->warn("Using scraper fallback to convert Everett PDF: {$pdfLink}");
                    }

                    if (($conversion['saved_pdf'] ?? false) === true) {
                        $summary['pdf_downloaded']++;
                    }

                    $this->info("Saved Markdown to {$markdownFilepath}");
                    $summary['markdown_saved']++;
                    $existingBaseFilenames[$currentPdfBaseFilename] = true;
                    OperationalSummaryLogger::emit($this, $this->getName(), 'markdown_saved', [
                        'page_url' => $pageUrl,
                        'pdf_url' => $pdfLink,
                        'pdf_file' => $pdfFilepath,
                        'output_file' => $markdownFilepath,
                        'bytes_written' => $conversion['bytes'] ?? null,
                        'converter' => $conversion['converter'] ?? null,
                        'node_binary' => $conversion['node_binary'] ?? null,
                        'node_version' => $conversion['node_version'] ?? null,
                    ]);
                }
            } catch (RequestException $e) {
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

    private function fetchPageHtml(string $pageUrl): array
    {
        $directFailure = null;

        try {
            $response = Http::connectTimeout(30)
                ->timeout(120)
                ->retry(2, 1000, throw: false)
                ->withHeaders([
                    'User-Agent' => 'PublicDataWatch Everett ingestion',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->withOptions(['allow_redirects' => true])
                ->get($pageUrl);

            if ($response->successful()) {
                $htmlContent = $response->body();
                if ($htmlContent !== '') {
                    return [
                        'success' => true,
                        'html' => $htmlContent,
                        'source' => 'direct',
                    ];
                }

                $directFailure = 'Direct page fetch returned an empty response body.';
            } else {
                $directFailure = 'Direct page fetch failed with status ' . $response->status() . '.';
            }
        } catch (\Throwable $throwable) {
            $directFailure = $throwable->getMessage();
        }

        $scraperFallback = $this->fetchPageHtmlViaScraper($pageUrl);
        if (($scraperFallback['success'] ?? false) === true) {
            return $scraperFallback;
        }

        return [
            'success' => false,
            'message' => trim(($directFailure ? 'Direct fetch: ' . $directFailure : '') . ' ' . (($scraperFallback['message'] ?? null) ? 'Scraper fallback: ' . $scraperFallback['message'] : '')),
        ];
    }

    private function fetchPageHtmlViaScraper(string $pageUrl): array
    {
        $scraperConfig = $this->scraperConfig();
        if ($scraperConfig === null) {
            return [
                'success' => false,
                'message' => 'Scraper fallback is not configured.',
            ];
        }

        try {
            $response = Http::withHeaders($this->scraperHeaders($scraperConfig))
                ->timeout(120)
                ->post($this->scraperEndpoint($scraperConfig), [
                    'url' => $pageUrl,
                    'wait' => (int) ($scraperConfig['wait_seconds'] ?? 5),
                ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Scraper page fetch failed with status ' . $response->status() . '.',
                ];
            }

            $htmlContent = $this->extractScraperTextPayload($response->body());
            if ($htmlContent === '') {
                return [
                    'success' => false,
                    'message' => 'Scraper page fetch returned an empty response body.',
                ];
            }

            return [
                'success' => true,
                'html' => $htmlContent,
                'source' => 'scraper',
            ];
        } catch (\Throwable $throwable) {
            return [
                'success' => false,
                'message' => $throwable->getMessage(),
            ];
        }
    }

    private function convertPdfToMarkdown(string $pdfLink, string $pdfFilepath, string $markdownFilepath): array
    {
        $directFailure = null;

        try {
            $pdfResponse = Http::connectTimeout(30)
                ->timeout(600)
                ->retry(2, 1000, throw: false)
                ->withHeaders([
                    'User-Agent' => 'PublicDataWatch Everett PDF downloader',
                    'Accept' => 'application/pdf,*/*',
                ])
                ->withOptions(['allow_redirects' => true])
                ->get($pdfLink);

            if ($pdfResponse->successful()) {
                if (! $this->isValidPdfResponse($pdfResponse)) {
                    return [
                        'status' => 'missing',
                        'message' => 'Direct PDF fetch returned a non-PDF or empty response.',
                    ];
                }

                File::put($pdfFilepath, $pdfResponse->body());
                $conversion = $this->nodePdfMarkdownConverter->convertFile($pdfFilepath, $markdownFilepath);

                return [
                    'success' => true,
                    'converter' => 'node_pdf_parse',
                    'saved_pdf' => true,
                    'bytes' => $conversion['bytes'] ?? null,
                    'node_binary' => $conversion['node_binary'] ?? null,
                    'node_version' => $conversion['node_version'] ?? null,
                ];
            }

            if ($pdfResponse->status() === 404) {
                return [
                    'status' => 'missing',
                    'message' => 'Direct PDF fetch returned 404.',
                ];
            }

            $directFailure = 'Direct PDF fetch failed with status ' . $pdfResponse->status() . '.';
        } catch (\Throwable $throwable) {
            $directFailure = $throwable->getMessage();
        }

        $scraperFallback = $this->convertPdfToMarkdownViaScraper($pdfLink, $markdownFilepath);
        if (($scraperFallback['success'] ?? false) === true || ($scraperFallback['status'] ?? null) === 'missing') {
            return $scraperFallback;
        }

        return [
            'success' => false,
            'message' => trim(($directFailure ? 'Direct fetch: ' . $directFailure : '') . ' ' . (($scraperFallback['message'] ?? null) ? 'Scraper fallback: ' . $scraperFallback['message'] : '')),
        ];
    }

    private function convertPdfToMarkdownViaScraper(string $pdfLink, string $markdownFilepath): array
    {
        $scraperConfig = $this->scraperConfig();
        if ($scraperConfig === null) {
            return [
                'success' => false,
                'message' => 'Scraper fallback is not configured.',
            ];
        }

        try {
            $response = Http::withHeaders($this->scraperHeaders($scraperConfig))
                ->timeout(600)
                ->post($this->scraperEndpoint($scraperConfig), [
                    'url' => $pdfLink,
                    'wait' => (int) ($scraperConfig['wait_seconds'] ?? 5),
                    'url_type' => 'pdf',
                    'output_markitdown' => true,
                ]);

            if (! $response->successful()) {
                if ($response->status() === 404) {
                    return [
                        'status' => 'missing',
                        'message' => 'Scraper fallback returned 404.',
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Scraper PDF conversion failed with status ' . $response->status() . '.',
                ];
            }

            $markdownText = $this->extractScraperTextPayload($response->body());
            if ($markdownText === '') {
                return [
                    'success' => false,
                    'message' => 'Scraper fallback returned an empty markdown response.',
                ];
            }

            File::put($markdownFilepath, $markdownText);

            return [
                'success' => true,
                'converter' => 'scraper_service',
                'saved_pdf' => false,
                'bytes' => strlen($markdownText),
                'node_binary' => null,
                'node_version' => null,
            ];
        } catch (\Throwable $throwable) {
            return [
                'success' => false,
                'message' => $throwable->getMessage(),
            ];
        }
    }

    private function extractScraperTextPayload(string $body): string
    {
        $decodedJson = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decodedJson)) {
            return $body;
        }

        foreach (['html', 'markdown', 'text', 'content'] as $key) {
            if (isset($decodedJson[$key]) && is_string($decodedJson[$key])) {
                return $decodedJson[$key];
            }
        }

        return '';
    }

    private function scraperConfig(): ?array
    {
        $scraperConfig = config('services.scraper_service');
        if (! is_array($scraperConfig) || empty($scraperConfig['base_url'])) {
            return null;
        }

        return $scraperConfig;
    }

    private function scraperEndpoint(array $scraperConfig): string
    {
        return rtrim((string) $scraperConfig['base_url'], '/') . '/scrape_url';
    }

    private function scraperHeaders(array $scraperConfig): array
    {
        return [
            'X-User-Id' => $scraperConfig['user_id'] ?? '1',
            'X-User-Name' => $scraperConfig['user_name'] ?? 'Guest',
            'X-User-Role' => $scraperConfig['user_role'] ?? 'guest',
        ];
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
