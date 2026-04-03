<?php

namespace Tests\Feature\Commands;

use App\Models\Location;
use App\Models\User;
use App\Services\LocationReportMapScreenshotService;
use App\Services\LocationReportMapSnapshotBuilder;
use App\Services\LocationReportMapSnapshotUrlGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class PreviewLocationReportMapCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys=ON');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('report')->nullable();
            $table->string('language')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();

        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_snapshot_map_command_does_not_capture_by_default(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $location = Location::factory()->for($user)->create([
            'name' => 'South Boston Home',
            'report' => 'daily',
            'language' => 'en',
        ]);

        $builder = Mockery::mock(LocationReportMapSnapshotBuilder::class);
        $builder->shouldReceive('build')
            ->once()
            ->andReturn([
                'selection_policy' => 'Showing up to 4 incidents from the last 2 day(s), ranked by newest first and nearest to home when dates tie.',
                'recent_points_in_window' => 3,
                'selected_points' => 2,
                'omitted_points' => 1,
                'empty' => false,
            ]);

        $urlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $urlGenerator->shouldReceive('generate')
            ->once()
            ->andReturn('https://example.test/snapshot');

        $screenshotService = Mockery::mock(LocationReportMapScreenshotService::class);
        $screenshotService->shouldNotReceive('capture');

        $this->app->instance(LocationReportMapSnapshotBuilder::class, $builder);
        $this->app->instance(LocationReportMapSnapshotUrlGenerator::class, $urlGenerator);
        $this->app->instance(LocationReportMapScreenshotService::class, $screenshotService);

        $this->artisan('reports:snapshot-map', ['location_id' => $location->id])
            ->expectsOutputToContain('Map snapshot prepared.')
            ->expectsOutputToContain('Render URL: https://example.test/snapshot')
            ->expectsOutputToContain('Incidents shown on map: 2')
            ->assertExitCode(0);
    }
}
