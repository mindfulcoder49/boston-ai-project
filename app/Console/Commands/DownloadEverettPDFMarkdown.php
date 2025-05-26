<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Keep Log if you plan to use it, otherwise it can be removed if not used.
use App\Services\PdfLinkExtractorService;
use Illuminate\Support\Facades\File; // For directory and file operations

class DownloadEverettPDFMarkdown extends Command
{
    protected $signature = 'app:download-everett-pdf-markdown';
    protected $description = 'Downloads PDF links from Everett pages and gets Markdown via scraper service';

    protected $pdfLinkExtractor;

    public function __construct(PdfLinkExtractorService $pdfLinkExtractor)
    {
        parent::__construct();
        $this->pdfLinkExtractor = $pdfLinkExtractor;
    }

    public function handle()
    {
        $config = config('everett_datasets');
        if (!$config || !isset($config['arrest_log_page_url']) || !isset($config['daily_log_page_url'])) {
            $this->error("Everett page URLs not configured properly in config/everett_datasets.php.");
            return 1;
        }
        
        $pages = [
            $config['arrest_log_page_url'],
            $config['daily_log_page_url'],
        ];
        $pages = array_filter($pages);

        if (empty($pages)) {
            $this->error("Everett page URLs are empty after filtering. Check config/everett_datasets.php.");
            return 1;
        }

        $scraperConfig = config('services.scraper_service');
        if (!$scraperConfig || empty($scraperConfig['base_url'])) {
            $this->error("Scraper service configuration missing or base_url is not set in config/services.php.");
            return 1;
        }
        $scraperEndpoint = rtrim($scraperConfig['base_url'], '/') . '/scrape_url';
        
        // Use a more specific path within storage, e.g., storage/app/markdown_output
        $mdOutputDirRelative = $config['markdown_output_directory'] ?? 'markdown_output';
        $mdOutputDir = storage_path('app/datasets/everett/' . trim($mdOutputDirRelative, '/'));

        if (!File::isDirectory($mdOutputDir)) {
            File::makeDirectory($mdOutputDir, 0775, true, true);
        }
        
        $headers = [
            'X-User-Id'   => $scraperConfig['user_id'] ?? '1',
            'X-User-Name' => $scraperConfig['user_name'] ?? 'Guest',
            'X-User-Role' => $scraperConfig['user_role'] ?? 'guest',
            // The scraper API seems to return plain text for markdown or JSON for HTML.
            // 'Accept' => 'application/json' might be okay if scraper wraps HTML in JSON.
            // If scraper returns raw HTML, 'text/html, application/json' might be better.
            // For now, let's assume the scraper handles content negotiation or the existing JSON parsing works.
        ];

        foreach ($pages as $pageUrl) {
            $this->info("Processing page: {$pageUrl} to find PDF links.");
            // Payload to get HTML content of the page itself
            $pageScrapePayload = [
                'url'  => $pageUrl,
                'wait' => (int)($scraperConfig['wait_seconds'] ?? 5),
                // 'output_markitdown' => false, // Explicitly false or remove for HTML
            ];
            
            try {
                $response = Http::withHeaders($headers)->timeout(120)->post($scraperEndpoint, $pageScrapePayload);

                if (!$response->successful()) {
                    $this->error("Failed to scrape page: {$pageUrl}. Status: {$response->status()}. Body: " . $response->body());
                    Log::error("Scraper service error for page URL {$pageUrl}: " . $response->body());
                    continue;
                }

                $htmlContent = $response->body();
                // Attempt to decode if JSON, otherwise assume raw HTML
                $decodedJson = json_decode($htmlContent, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($decodedJson['html'])) {
                        $htmlContent = $decodedJson['html'];
                    } elseif (isset($decodedJson['text']) && !isset($decodedJson['html'])) {
                        // If 'text' exists and 'html' doesn't, it might be pre-converted markdown or plain text.
                        // For link extraction, we need HTML. If scraper returns markdown here, this is an issue.
                        // Assuming for now that if 'output_markitdown' is false/absent, we get HTML or JSON-wrapped HTML.
                        $htmlContent = $decodedJson['text']; 
                    }
                }
                // If it wasn't JSON, $htmlContent remains as is (hopefully HTML)

                if (empty($htmlContent)) {
                    $this->warn("Received empty or non-HTML content for page: {$pageUrl}. Skipping PDF link extraction.");
                    continue;
                }

                $pdfLinks = $this->pdfLinkExtractor->extractFromHtml($htmlContent, $pageUrl);
                $this->info("Found " . count($pdfLinks) . " PDF links on {$pageUrl}");

                foreach ($pdfLinks as $pdfLink) {
                    $this->info("Processing PDF link for Markdown: {$pdfLink}");
                    // Payload to get Markdown from the PDF link
                    $pdfToMarkdownPayload = [
                        'url'               => $pdfLink,
                        'wait'              => (int)($scraperConfig['wait_seconds'] ?? 5), // Wait might not be relevant for direct PDF download
                        'url_type'          => 'pdf',
                        'output_markitdown' => true,
                    ];
                    
                    $mdResponse = Http::withHeaders($headers)
                                      ->timeout(600) // Increased timeout for PDF processing
                                      ->post($scraperEndpoint, $pdfToMarkdownPayload);

                    if (!$mdResponse->successful()) {
                        $this->error("Failed to convert PDF to Markdown for: {$pdfLink}. Status: {$mdResponse->status()}. Body: " . $mdResponse->body());
                        Log::error("Scraper service error for PDF URL {$pdfLink}: " . $mdResponse->body());
                        continue;
                    }

                    $markdownText = $mdResponse->body(); // Expecting plain text Markdown
                    
                    // Sanitize filename (more robustly)
                    $pathinfo = pathinfo($pdfLink);
                    $baseFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $pathinfo['filename'] ?? 'document');
                    $timestamp = now()->format('Ymd_His');
                    $filename = "{$baseFilename}_{$timestamp}.md";
                    $filepath = $mdOutputDir . DIRECTORY_SEPARATOR . $filename;

                    File::put($filepath, $markdownText);
                    $this->info("Saved Markdown to {$filepath}");
                }
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $this->error("HTTP Request Exception while processing {$pageUrl}: " . $e->getMessage());
                Log::error("Scraper service request exception for URL {$pageUrl}: " . $e->getMessage());
            } catch (\Exception $e) {
                $this->error("An unexpected error occurred while processing {$pageUrl}: " . $e->getMessage());
                Log::error("Unexpected error for URL {$pageUrl}: " . $e->getMessage());
            }
        }

        $this->info("Processing complete.");
        return 0;
    }
}
