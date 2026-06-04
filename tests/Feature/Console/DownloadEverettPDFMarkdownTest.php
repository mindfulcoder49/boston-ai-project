<?php

namespace Tests\Feature\Console;

use App\Services\NodePdfMarkdownConverter;
use App\Services\PdfLinkExtractorService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DownloadEverettPDFMarkdownTest extends TestCase
{
    private string $markdownOutputDir;
    private string $pdfOutputDir;
    private string $htmlOutputDir;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 5, 6, 12, 34, 56, 'America/New_York'));

        $baseDir = storage_path('app/datasets/everett');
        $this->markdownOutputDir = $baseDir . '/test-markdown-output';
        $this->pdfOutputDir = $baseDir . '/test-pdfs';
        $this->htmlOutputDir = $baseDir . '/test-html';

        File::deleteDirectory($this->markdownOutputDir);
        File::deleteDirectory($this->pdfOutputDir);
        File::deleteDirectory($this->htmlOutputDir);

        config([
            'everett_datasets.arrest_log_page_url_template' => 'https://example.com/arrest_{year}.php',
            'everett_datasets.daily_log_page_url_template' => '',
            'everett_datasets.years_to_process' => [2022],
            'everett_datasets.markdown_output_directory' => 'test-markdown-output',
            'everett_datasets.pdfs_directory' => 'test-pdfs',
            'everett_datasets.html_pages_directory' => 'test-html',
            'services.scraper_service.prefer_for_everett' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        File::deleteDirectory($this->markdownOutputDir);
        File::deleteDirectory($this->pdfOutputDir);
        File::deleteDirectory($this->htmlOutputDir);

        parent::tearDown();
    }

    public function test_it_downloads_and_converts_a_pdf_locally(): void
    {
        $this->app->instance(PdfLinkExtractorService::class, new class extends PdfLinkExtractorService {
            public function extractFromHtml(string $html, string $baseUrl): array
            {
                return ['https://files.example.com/arr_log_20220501.pdf'];
            }
        });

        $this->app->instance(NodePdfMarkdownConverter::class, new class extends NodePdfMarkdownConverter
        {
            public function convertFile(string $pdfPath, string $markdownPath, ?string $rawTextPath = null): array
            {
                File::put($markdownPath, "ARREST LOG\n\nDOE, JOHN\n123 MAIN ST\nage: 34 arrest date: 01/05/2025 case: 123456\nASSAULT\n");

                return [
                    'markdown_path' => $markdownPath,
                    'bytes' => File::size($markdownPath),
                    'line_count' => 6,
                    'node_binary' => '/usr/bin/node',
                    'node_version' => 'v20.2.0',
                ];
            }
        });

        Http::fake([
            'https://example.com/arrest_2022.php' => Http::response('<html></html>', 200),
            'https://files.example.com/arr_log_20220501.pdf' => Http::response('%PDF-1.4 fake pdf', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Saved Markdown to')
            ->assertExitCode(0);

        $this->assertCount(1, File::glob($this->markdownOutputDir . '/arr_log_20220501_*.md'));
        $this->assertCount(1, File::glob($this->pdfOutputDir . '/arr_log_20220501_*.pdf'));
        $this->assertNotEmpty(File::files($this->htmlOutputDir));
    }

    public function test_missing_pdf_source_is_treated_as_warning_not_command_failure(): void
    {
        $this->app->instance(PdfLinkExtractorService::class, new class extends PdfLinkExtractorService {
            public function extractFromHtml(string $html, string $baseUrl): array
            {
                return ['https://files.example.com/missing.pdf'];
            }
        });

        Http::fake([
            'https://example.com/arrest_2022.php' => Http::response('<html></html>', 200),
            'https://files.example.com/missing.pdf' => Http::response(
                ['detail' => 'missing'],
                404
            ),
        ]);

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Skipping missing Everett PDF source: https://files.example.com/missing.pdf')
            ->assertExitCode(0);
    }

    public function test_non_pdf_success_response_is_treated_as_warning_not_command_failure(): void
    {
        $this->app->instance(PdfLinkExtractorService::class, new class extends PdfLinkExtractorService {
            public function extractFromHtml(string $html, string $baseUrl): array
            {
                return ['https://files.example.com/not-a-pdf.pdf'];
            }
        });

        Http::fake([
            'https://example.com/arrest_2022.php' => Http::response('<html></html>', 200),
            'https://files.example.com/not-a-pdf.pdf' => Http::response(
                '<html>missing</html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Skipping missing Everett PDF source: https://files.example.com/not-a-pdf.pdf')
            ->assertExitCode(0);

        $this->assertCount(0, File::glob($this->pdfOutputDir . '/not-a-pdf_*.pdf'));
        $this->assertCount(0, File::glob($this->markdownOutputDir . '/not-a-pdf_*.md'));
    }

    public function test_it_falls_back_to_scraper_when_direct_everett_fetches_fail(): void
    {
        config([
            'services.scraper_service.base_url' => 'https://scraper.example.com',
            'services.scraper_service.user_id' => '1',
            'services.scraper_service.user_name' => 'Tester',
            'services.scraper_service.user_role' => 'admin',
            'services.scraper_service.wait_seconds' => 1,
            'services.scraper_service.prefer_for_everett' => false,
        ]);

        Http::fake(function (Request $request) {
            if ($request->url() === 'https://example.com/arrest_2022.php' && $request->method() === 'GET') {
                return Http::response('upstream unavailable', 500);
            }

            if ($request->url() === 'https://files.example.com/fallback.pdf' && $request->method() === 'GET') {
                return Http::response('upstream unavailable', 500);
            }

            if ($request->url() === 'https://scraper.example.com/scrape_url' && $request->method() === 'POST') {
                $payload = $request->data();

                if (($payload['url'] ?? null) === 'https://example.com/arrest_2022.php') {
                    return Http::response(['html' => '<html><a href="https://files.example.com/fallback.pdf">fallback</a></html>'], 200);
                }

                if (($payload['url'] ?? null) === 'https://files.example.com/fallback.pdf') {
                    return Http::response("ARREST LOG\n\nDOE, JOHN\n123 MAIN ST\nage: 34 arrest date: 01/05/2025 case: 123456\nASSAULT\n", 200);
                }
            }

            return Http::response('unexpected request', 500);
        });

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Using scraper fallback to fetch Everett page: https://example.com/arrest_2022.php')
            ->expectsOutputToContain('Using scraper fallback to convert Everett PDF: https://files.example.com/fallback.pdf')
            ->assertExitCode(0);

        $this->assertCount(1, File::glob($this->markdownOutputDir . '/fallback_*.md'));
        $this->assertCount(0, File::glob($this->pdfOutputDir . '/fallback_*.pdf'));
    }

    public function test_it_can_prefer_scraper_for_everett_fetches(): void
    {
        config([
            'services.scraper_service.base_url' => 'https://scraper.example.com',
            'services.scraper_service.user_id' => '1',
            'services.scraper_service.user_name' => 'Tester',
            'services.scraper_service.user_role' => 'admin',
            'services.scraper_service.wait_seconds' => 1,
            'services.scraper_service.prefer_for_everett' => true,
        ]);

        Http::fake(function (Request $request) {
            if ($request->url() === 'https://example.com/arrest_2022.php') {
                return Http::response('direct path should not be used', 500);
            }

            if ($request->url() === 'https://files.example.com/preferred.pdf') {
                return Http::response('direct PDF path should not be used', 500);
            }

            if ($request->url() === 'https://scraper.example.com/scrape_url' && $request->method() === 'POST') {
                $payload = $request->data();

                if (($payload['url'] ?? null) === 'https://example.com/arrest_2022.php') {
                    return Http::response(['html' => '<html><a href="https://files.example.com/preferred.pdf">preferred</a></html>'], 200);
                }

                if (($payload['url'] ?? null) === 'https://files.example.com/preferred.pdf') {
                    return Http::response("ARREST LOG\n\nDOE, JOHN\n123 MAIN ST\nage: 34 arrest date: 01/05/2025 case: 123456\nASSAULT\n", 200);
                }
            }

            return Http::response('unexpected request', 500);
        });

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Using scraper fallback to fetch Everett page: https://example.com/arrest_2022.php')
            ->expectsOutputToContain('Using scraper fallback to convert Everett PDF: https://files.example.com/preferred.pdf')
            ->assertExitCode(0);

        $this->assertCount(1, File::glob($this->markdownOutputDir . '/preferred_*.md'));

        Http::assertNotSent(fn (Request $request) => $request->url() === 'https://example.com/arrest_2022.php');
        Http::assertNotSent(fn (Request $request) => $request->url() === 'https://files.example.com/preferred.pdf');
    }

    public function test_non_404_pdf_conversion_failure_still_fails_the_command(): void
    {
        $this->app->instance(PdfLinkExtractorService::class, new class extends PdfLinkExtractorService {
            public function extractFromHtml(string $html, string $baseUrl): array
            {
                return ['https://files.example.com/broken.pdf'];
            }
        });

        $this->app->instance(NodePdfMarkdownConverter::class, new class extends NodePdfMarkdownConverter
        {
            public function convertFile(string $pdfPath, string $markdownPath, ?string $rawTextPath = null): array
            {
                throw new \RuntimeException('Broken converter');
            }
        });

        Http::fake([
            'https://example.com/arrest_2022.php' => Http::response('<html></html>', 200),
            'https://files.example.com/broken.pdf' => Http::response('%PDF-1.4 fake pdf', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Failed to convert Everett PDF to Markdown for: https://files.example.com/broken.pdf. Error: Direct fetch: Broken converter')
            ->assertExitCode(1);
    }
}
