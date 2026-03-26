<?php

namespace Tests\Feature\Console;

use App\Services\PdfLinkExtractorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DownloadEverettPDFMarkdownTest extends TestCase
{
    private string $markdownOutputDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markdownOutputDir = storage_path('app/datasets/everett/test-markdown-output');
        File::deleteDirectory($this->markdownOutputDir);

        config([
            'services.scraper_service.base_url' => 'http://scraper.test',
            'services.scraper_service.user_id' => '1',
            'services.scraper_service.user_name' => 'Tester',
            'services.scraper_service.user_role' => 'admin',
            'services.scraper_service.wait_seconds' => 1,
            'everett_datasets.arrest_log_page_url_template' => 'https://example.com/arrest_{year}.php',
            'everett_datasets.daily_log_page_url_template' => '',
            'everett_datasets.years_to_process' => [2022],
            'everett_datasets.markdown_output_directory' => 'test-markdown-output',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->markdownOutputDir);

        parent::tearDown();
    }

    public function test_missing_pdf_source_is_treated_as_warning_not_command_failure(): void
    {
        $this->app->instance(PdfLinkExtractorService::class, new class extends PdfLinkExtractorService {
            public function extractFromHtml(string $html, string $baseUrl): array
            {
                return ['https://files.example.com/missing.pdf'];
            }
        });

        Http::fake(function (Request $request) {
            $url = $request->data()['url'] ?? null;

            return match ($url) {
                'https://example.com/arrest_2022.php' => Http::response('<html></html>', 200),
                'https://files.example.com/missing.pdf' => Http::response(
                    ['detail' => 'Failed to download file content for https://files.example.com/missing.pdf'],
                    404
                ),
                default => Http::response('unexpected request', 500),
            };
        });

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Skipping missing Everett PDF source: https://files.example.com/missing.pdf')
            ->assertExitCode(0);
    }

    public function test_non_404_pdf_conversion_failure_still_fails_the_command(): void
    {
        $this->app->instance(PdfLinkExtractorService::class, new class extends PdfLinkExtractorService {
            public function extractFromHtml(string $html, string $baseUrl): array
            {
                return ['https://files.example.com/broken.pdf'];
            }
        });

        Http::fake(function (Request $request) {
            $url = $request->data()['url'] ?? null;

            return match ($url) {
                'https://example.com/arrest_2022.php' => Http::response('<html></html>', 200),
                'https://files.example.com/broken.pdf' => Http::response(
                    ['detail' => 'Scraper exploded'],
                    500
                ),
                default => Http::response('unexpected request', 500),
            };
        });

        $this->artisan('app:download-everett-pdf-markdown')
            ->expectsOutputToContain('Failed to convert PDF to Markdown for: https://files.example.com/broken.pdf. Status: 500.')
            ->assertExitCode(1);
    }
}
