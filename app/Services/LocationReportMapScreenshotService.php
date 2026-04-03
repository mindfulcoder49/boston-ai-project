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

        foreach ($this->buildEnvironment() as $key => $value) {
            $configBuilder->addEnv($key, $value);
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

    protected function buildEnvironment(): array
    {
        $environment = [];

        if ($browsersPath = config('services.playwright.browsers_path')) {
            $environment['PLAYWRIGHT_BROWSERS_PATH'] = (string) $browsersPath;
        }

        if ($libraryPath = $this->resolveLibraryPath()) {
            $environment['LD_LIBRARY_PATH'] = $libraryPath;
        }

        return $environment;
    }

    protected function resolveLibraryPath(): ?string
    {
        $configuredPath = config('services.playwright.library_path');
        if (!is_string($configuredPath) || trim($configuredPath) === '') {
            return null;
        }

        $configuredPath = trim($configuredPath);
        if (!File::isDirectory($configuredPath)) {
            return null;
        }

        $paths = [$configuredPath];
        $currentLibraryPath = getenv('LD_LIBRARY_PATH');
        if (is_string($currentLibraryPath) && trim($currentLibraryPath) !== '') {
            foreach (explode(':', $currentLibraryPath) as $path) {
                $path = trim($path);
                if ($path !== '') {
                    $paths[] = $path;
                }
            }
        }

        $paths = array_values(array_unique($paths));

        return implode(':', $paths);
    }
}
