<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Playwright\Browser\Browser;
use Playwright\Configuration\PlaywrightConfigBuilder;
use Playwright\PlaywrightFactory;

class PlaywrightIngestionProbe
{
    public function fetchHtml(string $url, int $timeoutMs = 60000): array
    {
        return $this->withBrowser(function (Browser $browser) use ($url, $timeoutMs): array {
            $page = $browser->newPage();
            $response = $page->goto($url, [
                'waitUntil' => 'domcontentloaded',
                'timeout' => $timeoutMs,
            ]);

            return [
                'status' => $response?->status(),
                'final_url' => $page->url(),
                'title' => $page->title(),
                'html' => $page->content() ?? '',
            ];
        }, $timeoutMs);
    }

    public function signalDownload(string $url, string $downloadDir, int $timeoutMs = 60000): array
    {
        File::ensureDirectoryExists($downloadDir);

        return $this->withBrowser(function (Browser $browser) use ($url, $downloadDir, $timeoutMs): array {
            $context = $browser->newContext([
                'acceptDownloads' => true,
                'downloadsPath' => $downloadDir,
            ]);

            $page = $context->newPage();
            $status = null;
            $exceptionMessage = null;

            try {
                $status = $page->goto($url, [
                    'waitUntil' => 'domcontentloaded',
                    'timeout' => $timeoutMs,
                ])?->status();
            } catch (\Throwable $throwable) {
                $exceptionMessage = $throwable->getMessage();
            }

            // Give Playwright a moment in case the binding writes a file asynchronously.
            usleep(2_000_000);

            $savedFiles = array_map(static function (\SplFileInfo $file): array {
                return [
                    'name' => $file->getFilename(),
                    'bytes' => $file->getSize(),
                ];
            }, File::files($downloadDir));

            return [
                'status' => $status,
                'download_start_detected' => is_string($exceptionMessage) && str_contains($exceptionMessage, 'Download is starting'),
                'exception' => $exceptionMessage,
                'saved_files' => $savedFiles,
            ];
        }, $timeoutMs);
    }

    public function requestContextGet(string $url, int $timeoutMs = 60000): array
    {
        return $this->withBrowser(function (Browser $browser) use ($url): array {
            $context = $browser->newContext();

            try {
                $response = $context->request()->get($url);

                return [
                    'ok' => true,
                    'status' => $response->status(),
                    'content_type' => $response->headers()['content-type'] ?? null,
                    'bytes' => strlen($response->body()),
                ];
            } catch (\Throwable $throwable) {
                return [
                    'ok' => false,
                    'error' => get_class($throwable) . ': ' . $throwable->getMessage(),
                ];
            }
        }, $timeoutMs);
    }

    protected function withBrowser(callable $callback, int $timeoutMs): mixed
    {
        $configBuilder = PlaywrightConfigBuilder::fromEnv()
            ->withHeadless(true)
            ->withTimeoutMs($timeoutMs);

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

            return $callback($browser);
        } finally {
            if ($browser) {
                try {
                    $browser->close();
                } catch (\Throwable) {
                }
            }

            $client->close();
        }
    }

    protected function buildEnvironment(): array
    {
        $environment = [];

        if ($browsersPath = config('services.playwright.browsers_path')) {
            $environment['PLAYWRIGHT_BROWSERS_PATH'] = (string) $browsersPath;
        }

        $libraryPath = $this->resolveLibraryPath();
        if ($libraryPath !== null) {
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

        return implode(':', array_values(array_unique($paths)));
    }
}
