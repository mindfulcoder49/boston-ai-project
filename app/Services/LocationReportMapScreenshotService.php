<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Playwright\Configuration\PlaywrightConfigBuilder;
use Playwright\PlaywrightFactory;

class LocationReportMapScreenshotService
{
    public function capture(string $url, ?string $outputPath = null): string
    {
        $outputPath ??= $this->defaultOutputPath();
        File::ensureDirectoryExists(dirname($outputPath));

        $configBuilder = PlaywrightConfigBuilder::fromEnv()
            ->withHeadless(true)
            ->withTimeoutMs((int) config('services.playwright.timeout_ms', 45000));

        if ($nodePath = config('services.playwright.node_path')) {
            $configBuilder->withNodePath((string) $nodePath);
        }

        if ($browsersPath = config('services.playwright.browsers_path')) {
            $configBuilder->addEnv('PLAYWRIGHT_BROWSERS_PATH', (string) $browsersPath);
        }

        $config = $configBuilder->build();
        $client = PlaywrightFactory::create($config);
        $browser = null;

        try {
            $browser = $client->chromium()->launch();
            $page = $browser->newPage();
            $page->setViewportSize(
                (int) config('services.playwright.viewport_width', 1400),
                (int) config('services.playwright.viewport_height', 900)
            );
            $page->goto($url, [
                'waitUntil' => 'domcontentloaded',
                'timeout' => (float) config('services.playwright.timeout_ms', 45000),
            ]);
            $page->waitForSelector('#snapshot-root[data-ready="1"]', [
                'state' => 'attached',
                'timeout' => (float) config('services.playwright.timeout_ms', 45000),
            ]);
            $page->screenshot($outputPath, [
                'fullPage' => true,
                'type' => 'png',
            ]);
        } finally {
            if ($browser) {
                try {
                    $browser->close();
                } catch (\Throwable) {
                }
            }

            $client->close();
        }

        return $outputPath;
    }

    private function defaultOutputPath(): string
    {
        return storage_path('app/report_snapshots/' . Carbon::now()->format('YmdHis') . '_location_snapshot.png');
    }
}
