<?php

namespace Tests\Unit\Services;

use App\Services\LocationReportMapScreenshotService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocationReportMapScreenshotServiceTest extends TestCase
{
    private ?string $originalLibraryPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalLibraryPath = getenv('LD_LIBRARY_PATH') !== false
            ? (string) getenv('LD_LIBRARY_PATH')
            : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalLibraryPath === null) {
            putenv('LD_LIBRARY_PATH');
        } else {
            putenv('LD_LIBRARY_PATH=' . $this->originalLibraryPath);
        }

        parent::tearDown();
    }

    public function test_it_adds_the_vendored_library_directory_to_the_playwright_environment(): void
    {
        $libraryDirectory = sys_get_temp_dir() . '/playwright-libs-' . uniqid();
        File::ensureDirectoryExists($libraryDirectory);

        config()->set('services.playwright.library_path', $libraryDirectory);
        config()->set('services.playwright.browsers_path', '/tmp/ms-playwright');
        putenv('LD_LIBRARY_PATH=/usr/local/lib64');

        $service = new class extends LocationReportMapScreenshotService {
            public function exposedBuildEnvironment(): array
            {
                return $this->buildEnvironment();
            }
        };

        $environment = $service->exposedBuildEnvironment();

        $this->assertSame('/tmp/ms-playwright', $environment['PLAYWRIGHT_BROWSERS_PATH']);
        $this->assertSame($libraryDirectory . ':/usr/local/lib64', $environment['LD_LIBRARY_PATH']);

        File::deleteDirectory($libraryDirectory);
    }

    public function test_it_skips_the_library_override_when_the_directory_is_missing(): void
    {
        config()->set('services.playwright.library_path', sys_get_temp_dir() . '/missing-playwright-libs-' . uniqid());
        config()->set('services.playwright.browsers_path', null);
        putenv('LD_LIBRARY_PATH=/usr/local/lib64');

        $service = new class extends LocationReportMapScreenshotService {
            public function exposedBuildEnvironment(): array
            {
                return $this->buildEnvironment();
            }
        };

        $environment = $service->exposedBuildEnvironment();

        $this->assertArrayNotHasKey('LD_LIBRARY_PATH', $environment);
        $this->assertArrayNotHasKey('PLAYWRIGHT_BROWSERS_PATH', $environment);
    }
}
