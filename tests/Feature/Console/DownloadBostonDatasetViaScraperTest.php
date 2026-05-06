<?php

namespace Tests\Feature\Console;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DownloadBostonDatasetViaScraperTest extends TestCase
{
    private string $expectedTimestamp;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2099, 1, 2, 3, 4, 5, 'America/New_York'));
        $this->expectedTimestamp = now()->format('Ymd_His');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (File::glob(storage_path('app/datasets/crime-incident-reports_*.csv')) as $path) {
            if (str_contains($path, $this->expectedTimestamp)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    public function test_it_downloads_the_selected_boston_dataset_directly(): void
    {
        config([
            'boston_datasets.base_url' => 'https://data.example.com/dump',
            'boston_datasets.datasets' => [
                [
                    'name' => 'crime-incident-reports',
                    'resource_id' => 'dataset-123',
                    'format' => 'csv',
                ],
            ],
        ]);

        Http::fake([
            'https://data.example.com/dump/dataset-123?format=csv' => Http::response("a,b\n1,2\n", 200),
        ]);

        $this->artisan('app:download-boston-dataset-via-scraper --names=crime-incident-reports')
            ->expectsOutputToContain('Downloaded Boston dataset:')
            ->assertExitCode(0);

        $this->assertFileExists(storage_path("app/datasets/crime-incident-reports_{$this->expectedTimestamp}.csv"));
    }

    public function test_it_fails_when_the_direct_download_fails(): void
    {
        config([
            'boston_datasets.base_url' => 'https://data.example.com/dump',
            'boston_datasets.datasets' => [
                [
                    'name' => 'crime-incident-reports',
                    'resource_id' => 'dataset-123',
                    'format' => 'csv',
                ],
            ],
        ]);

        Http::fake([
            'https://data.example.com/dump/dataset-123?format=csv' => Http::response('boom', 500),
        ]);

        $this->artisan('app:download-boston-dataset-via-scraper --names=crime-incident-reports')
            ->expectsOutputToContain('Direct download failed with status 500')
            ->assertExitCode(1);
    }
}
