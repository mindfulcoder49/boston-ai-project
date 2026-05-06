<?php

namespace App\Console\Commands;

use App\Services\EverettMarkdownCompatibilityProbe;
use App\Services\LocalPdfMarkdownConverter;
use App\Services\NodePdfMarkdownConverter;
use App\Services\PdfLinkExtractorService;
use App\Services\PlaywrightIngestionProbe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TestLocalIngestionFeasibilityCommand extends Command
{
    protected $signature = 'app:test-local-ingestion-feasibility
                            {--boston-dataset=crime-incident-reports : Boston dataset name from config/boston_datasets.php}
                            {--everett-year=2025 : Everett page year to probe}
                            {--output-dir= : Directory for probe artifacts}';

    protected $description = 'Run local Boston and Everett ingestion feasibility tests without the external scraper API.';

    public function __construct(
        private readonly PlaywrightIngestionProbe $playwrightProbe,
        private readonly PdfLinkExtractorService $pdfLinkExtractor,
        private readonly LocalPdfMarkdownConverter $pdfMarkdownConverter,
        private readonly NodePdfMarkdownConverter $nodePdfMarkdownConverter,
        private readonly EverettMarkdownCompatibilityProbe $everettCompatibilityProbe,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $outputDir = $this->resolveOutputDir();
        File::ensureDirectoryExists($outputDir);

        $this->line('Writing feasibility artifacts to ' . $outputDir);

        $summary = [
            'generated_at' => now()->toIso8601String(),
            'output_dir' => $outputDir,
            'boston' => $this->probeBoston($outputDir),
            'everett' => [
                'daily' => $this->probeEverettLog('daily', $outputDir),
                'arrest' => $this->probeEverettLog('arrest', $outputDir),
            ],
        ];

        $summaryPath = $outputDir . '/summary.json';
        File::put($summaryPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->line('Summary written to ' . $summaryPath);
        $this->line('Boston HTTP download bytes: ' . ($summary['boston']['http_download']['bytes'] ?? 'n/a'));
        $this->line('Boston Playwright browser download signal: ' . (($summary['boston']['playwright_download_signal']['download_start_detected'] ?? false) ? 'yes' : 'no'));
        $this->line('Boston Playwright request context ok: ' . (($summary['boston']['playwright_request_context']['ok'] ?? false) ? 'yes' : 'no'));
        $this->line('Everett daily HTTP page links: ' . ($summary['everett']['daily']['page']['http']['pdf_links_found'] ?? 0));
        $this->line('Everett daily Playwright page links: ' . ($summary['everett']['daily']['page']['playwright']['pdf_links_found'] ?? 0));
        $this->line('Everett daily native parsed entries: ' . ($summary['everett']['daily']['conversions']['native_pdftotext']['compatibility']['parsed_entries_count'] ?? 0));
        $this->line('Everett daily node parsed entries: ' . ($summary['everett']['daily']['conversions']['node_pdf_parse']['compatibility']['parsed_entries_count'] ?? 0));
        $this->line('Everett arrest native parsed entries: ' . ($summary['everett']['arrest']['conversions']['native_pdftotext']['compatibility']['parsed_entries_count'] ?? 0));
        $this->line('Everett arrest node parsed entries: ' . ($summary['everett']['arrest']['conversions']['node_pdf_parse']['compatibility']['parsed_entries_count'] ?? 0));

        $coreSuccess = (
            ($summary['boston']['http_download']['ok'] ?? false)
            && ($summary['everett']['daily']['page']['http']['ok'] ?? false)
            && ($summary['everett']['daily']['conversions']['node_pdf_parse']['compatibility']['compatible'] ?? false)
            && ($summary['everett']['arrest']['conversions']['node_pdf_parse']['compatibility']['compatible'] ?? false)
        );

        return $coreSuccess ? self::SUCCESS : self::FAILURE;
    }

    protected function probeBoston(string $outputDir): array
    {
        $datasetName = (string) $this->option('boston-dataset');
        $dataset = collect(config('boston_datasets.datasets', []))
            ->firstWhere('name', $datasetName);

        if (!is_array($dataset)) {
            return [
                'error' => 'Boston dataset not found in config/boston_datasets.php.',
            ];
        }

        $baseUrl = rtrim((string) config('boston_datasets.base_url'), '/');
        $url = $baseUrl . '/' . $dataset['resource_id'] . '?format=' . $dataset['format'];
        $bostonDir = $outputDir . '/boston';
        File::ensureDirectoryExists($bostonDir);

        $httpPath = $bostonDir . '/' . $datasetName . '.' . $dataset['format'];
        $httpDownload = $this->downloadViaHttp($url, $httpPath);

        $playwrightDownloadSignal = $this->playwrightProbe->signalDownload(
            $url,
            $bostonDir . '/playwright_downloads'
        );

        $playwrightRequestContext = $this->playwrightProbe->requestContextGet($url);

        return [
            'dataset' => [
                'name' => $dataset['name'],
                'resource_id' => $dataset['resource_id'],
                'format' => $dataset['format'],
                'url' => $url,
            ],
            'http_download' => $httpDownload,
            'playwright_download_signal' => $playwrightDownloadSignal,
            'playwright_request_context' => $playwrightRequestContext,
        ];
    }

    protected function probeEverettLog(string $kind, string $outputDir): array
    {
        $year = (string) $this->option('everett-year');
        $templateKey = $kind === 'daily'
            ? 'everett_datasets.daily_log_page_url_template'
            : 'everett_datasets.arrest_log_page_url_template';

        $pageUrl = str_replace('{year}', $year, (string) config($templateKey));
        $everettDir = $outputDir . '/everett/' . $kind;
        File::ensureDirectoryExists($everettDir);

        $httpPageProbe = $this->fetchPageViaHttp($pageUrl, $everettDir . '/page_http.html');
        $httpPdfLinks = $this->pdfLinkExtractor->extractFromHtml(
            (string) ($httpPageProbe['html'] ?? ''),
            $pageUrl
        );
        $httpPdfLinksPath = $everettDir . '/pdf_links_http.json';
        File::put($httpPdfLinksPath, json_encode($httpPdfLinks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $playwrightPageProbe = $this->playwrightProbe->fetchHtml($pageUrl);
        $playwrightHtmlPath = $everettDir . '/page_playwright.html';
        File::put($playwrightHtmlPath, $playwrightPageProbe['html'] ?? '');
        $playwrightPdfLinks = $this->pdfLinkExtractor->extractFromHtml(
            (string) ($playwrightPageProbe['html'] ?? ''),
            $pageUrl
        );
        $playwrightPdfLinksPath = $everettDir . '/pdf_links_playwright.json';
        File::put($playwrightPdfLinksPath, json_encode($playwrightPdfLinks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $selectedPageSource = ! empty($httpPdfLinks) ? 'http' : 'playwright';
        $pdfLinks = $selectedPageSource === 'http' ? $httpPdfLinks : $playwrightPdfLinks;

        if (empty($pdfLinks)) {
            return [
                'page' => [
                    'selected_source' => $selectedPageSource,
                    'http' => [
                        'url' => $pageUrl,
                        'ok' => $httpPageProbe['ok'] ?? false,
                        'status' => $httpPageProbe['status'] ?? null,
                        'html_path' => $httpPageProbe['html_path'] ?? null,
                        'pdf_links_path' => $httpPdfLinksPath,
                        'pdf_links_found' => count($httpPdfLinks),
                    ],
                    'playwright' => [
                        'url' => $pageUrl,
                        'status' => $playwrightPageProbe['status'] ?? null,
                        'title' => $playwrightPageProbe['title'] ?? null,
                        'html_path' => $playwrightHtmlPath,
                        'pdf_links_path' => $playwrightPdfLinksPath,
                        'pdf_links_found' => count($playwrightPdfLinks),
                    ],
                ],
                'error' => 'No Everett PDF links were extracted from the page HTML.',
            ];
        }

        $pdfUrl = $pdfLinks[count($pdfLinks) - 1];
        $pdfFilename = basename(parse_url($pdfUrl, PHP_URL_PATH) ?: ($kind . '.pdf'));
        $pdfPath = $everettDir . '/' . $pdfFilename;
        $httpDownload = $this->downloadViaHttp($pdfUrl, $pdfPath);

        $playwrightDownloadSignal = $this->playwrightProbe->signalDownload(
            $pdfUrl,
            $everettDir . '/playwright_downloads'
        );

        $nativeMarkdownPath = $everettDir . '/' . preg_replace('/\.pdf$/i', '.native.md', $pdfFilename);
        $nodeMarkdownPath = $everettDir . '/' . preg_replace('/\.pdf$/i', '.node.md', $pdfFilename);
        $nodeRawTextPath = $everettDir . '/' . preg_replace('/\.pdf$/i', '.node.raw.txt', $pdfFilename);

        return [
            'page' => [
                'selected_source' => $selectedPageSource,
                'http' => [
                    'url' => $pageUrl,
                    'ok' => $httpPageProbe['ok'] ?? false,
                    'status' => $httpPageProbe['status'] ?? null,
                    'html_path' => $httpPageProbe['html_path'] ?? null,
                    'pdf_links_path' => $httpPdfLinksPath,
                    'pdf_links_found' => count($httpPdfLinks),
                ],
                'playwright' => [
                    'url' => $pageUrl,
                    'status' => $playwrightPageProbe['status'] ?? null,
                    'title' => $playwrightPageProbe['title'] ?? null,
                    'html_path' => $playwrightHtmlPath,
                    'pdf_links_path' => $playwrightPdfLinksPath,
                    'pdf_links_found' => count($playwrightPdfLinks),
                ],
            ],
            'selected_pdf_url' => $pdfUrl,
            'http_download' => $httpDownload,
            'playwright_download_signal' => $playwrightDownloadSignal,
            'conversions' => [
                'native_pdftotext' => $this->attemptConversion(
                    $kind,
                    fn () => $this->pdfMarkdownConverter->convertFile($pdfPath, $nativeMarkdownPath),
                    $nativeMarkdownPath
                ),
                'node_pdf_parse' => $this->attemptConversion(
                    $kind,
                    fn () => $this->nodePdfMarkdownConverter->convertFile($pdfPath, $nodeMarkdownPath, $nodeRawTextPath),
                    $nodeMarkdownPath
                ),
            ],
        ];
    }

    protected function downloadViaHttp(string $url, string $outputPath): array
    {
        $response = Http::timeout(600)
            ->withOptions(['allow_redirects' => true])
            ->get($url);

        $body = $response->body();
        File::put($outputPath, $body);

        $firstLine = strtok($body, "\n");

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'bytes' => strlen($body),
            'output_path' => $outputPath,
            'first_line' => is_string($firstLine) ? trim($firstLine) : null,
        ];
    }

    protected function fetchPageViaHttp(string $url, string $outputPath): array
    {
        $response = Http::timeout(120)
            ->withOptions(['allow_redirects' => true])
            ->get($url);

        $body = $response->body();
        File::put($outputPath, $body);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'bytes' => strlen($body),
            'html_path' => $outputPath,
            'html' => $body,
        ];
    }

    protected function attemptConversion(string $kind, callable $converter, string $markdownPath): array
    {
        try {
            $conversion = $converter();
            $compatibility = $this->everettCompatibilityProbe->probeText(
                File::get($markdownPath),
                $kind
            );

            return [
                'ok' => true,
                'conversion' => $conversion,
                'compatibility' => $compatibility,
            ];
        } catch (\Throwable $throwable) {
            return [
                'ok' => false,
                'error' => get_class($throwable) . ': ' . $throwable->getMessage(),
            ];
        }
    }

    protected function resolveOutputDir(): string
    {
        $customOutputDir = $this->option('output-dir');
        if (is_string($customOutputDir) && trim($customOutputDir) !== '') {
            return $customOutputDir;
        }

        return storage_path('app/feasibility/local_ingestion/' . now()->format('Ymd_His'));
    }
}
