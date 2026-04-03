<?php

namespace Tests\Feature;

use App\Jobs\SendLocationReportEmail;
use App\Mail\SendLocationReport;
use App\Models\Location;
use App\Models\User;
use App\Services\LocationReportBuilder;
use App\Services\LocationReportEmailMapService;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SendLocationReportEmailTest extends TestCase
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

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('title');
            $table->longText('content');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_it_sends_the_report_email_with_an_inline_map_image_when_capture_succeeds(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $location = Location::factory()->for($user)->create([
            'name' => 'South Boston Home',
            'report' => 'daily',
            'language' => 'en',
        ]);

        $builder = Mockery::mock(LocationReportBuilder::class);
        $builder->shouldReceive('build')
            ->once()
            ->with($location, 0.25)
            ->andReturn([
                'final_report' => "## Location Report: South Boston Home\n\nSample report.",
                'daily_report_content' => "### April 3, 2026\n\nSample report.",
                'data_points_count' => 4,
                'section_diagnostics' => [],
            ]);

        $mapPath = storage_path('framework/testing/location-report-map-inline.png');
        File::ensureDirectoryExists(dirname($mapPath));
        File::put($mapPath, 'fake png bytes');

        $emailMapService = Mockery::mock(LocationReportEmailMapService::class);
        $emailMapService->shouldReceive('capture')
            ->once()
            ->with($location, 0.25)
            ->andReturn([
                'path' => $mapPath,
                'days' => 2,
                'snapshot' => ['selected_points' => 4],
            ]);

        $job = new SendLocationReportEmail($location);
        $job->handle(app(Mailer::class), $builder, $emailMapService);

        Mail::assertSent(SendLocationReport::class, function (SendLocationReport $mail) use ($location, $mapPath): bool {
            return $mail->location->is($location)
                && $mail->mapImagePath === $mapPath;
        });

        $this->assertDatabaseCount('reports', 1);
        $this->assertFalse(File::exists($mapPath));
    }

    public function test_it_still_sends_the_report_email_when_map_capture_fails(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $location = Location::factory()->for($user)->create([
            'name' => 'South Boston Home',
            'report' => 'daily',
            'language' => 'en',
        ]);

        $builder = Mockery::mock(LocationReportBuilder::class);
        $builder->shouldReceive('build')
            ->once()
            ->with($location, 0.25)
            ->andReturn([
                'final_report' => "## Location Report: South Boston Home\n\nSample report.",
                'daily_report_content' => "### April 3, 2026\n\nSample report.",
                'data_points_count' => 4,
                'section_diagnostics' => [],
            ]);

        $emailMapService = Mockery::mock(LocationReportEmailMapService::class);
        $emailMapService->shouldReceive('capture')
            ->once()
            ->with($location, 0.25)
            ->andThrow(new \RuntimeException('Chromium failed'));

        $job = new SendLocationReportEmail($location);
        $job->handle(app(Mailer::class), $builder, $emailMapService);

        Mail::assertSent(SendLocationReport::class, function (SendLocationReport $mail) use ($location): bool {
            return $mail->location->is($location)
                && $mail->mapImagePath === null;
        });

        $this->assertDatabaseCount('reports', 1);
    }
}
