<?php

namespace Tests\Feature;

use App\Jobs\SendLocationReportEmail;
use App\Mail\SendLocationReport;
use App\Models\Location;
use App\Models\User;
use App\Services\LocationReportBuilder;
use App\Services\LocationReportEmailMapService;
use App\Services\LocationReportMapSnapshotUrlGenerator;
use App\Support\TrialLifecycleEmailVariant;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
            $table->string('manual_subscription_tier')->nullable();
            $table->timestamp('crime_address_trial_started_at')->nullable();
            $table->timestamp('crime_address_trial_ends_at')->nullable();
            $table->unsignedBigInteger('crime_address_trial_location_id')->nullable();
            $table->timestamp('crime_address_trial_grace_report_sent_at')->nullable();
            $table->timestamp('crime_address_trial_ended_notice_sent_at')->nullable();
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
        $emailMapService->shouldReceive('captureLatestDay')
            ->once()
            ->with($location, 0.25)
            ->andReturn([
                'path' => $mapPath,
                'snapshot' => [
                    'window' => [
                        'display' => 'April 3, 2026',
                    ],
                    'selected_points' => 4,
                    'incidents' => [
                        [
                            'label' => '1',
                            'headline' => 'Noise Complaint',
                            'display_date' => 'April 3, 2026 9:36 PM',
                            'address' => '621 E 1st St, South Boston, MA 02127, USA',
                            'distance_miles' => 0.04,
                            'category_label' => '311',
                            'shape' => 'rounded-square',
                            'fill_color' => '#2563EB',
                            'stroke_color' => '#FFFFFF',
                            'text_color' => '#FFFFFF',
                        ],
                    ],
                ],
            ]);

        $snapshotUrlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $snapshotUrlGenerator->shouldReceive('generatePublicDailyMapsPage')
            ->once()
            ->with($location)
            ->andReturn('https://example.test/location-maps');

        $job = new SendLocationReportEmail($location);
        $job->handle(app(Mailer::class), $builder, $emailMapService, $snapshotUrlGenerator);

        Mail::assertSent(SendLocationReport::class, function (SendLocationReport $mail) use ($location, $mapPath): bool {
            return $mail->location->is($location)
                && ($mail->recentMap['path'] ?? null) === $mapPath
                && (($mail->recentMap['snapshot']['incidents'][0]['label'] ?? null) === '1')
                && $mail->publicMapsUrl === 'https://example.test/location-maps'
                && $mail->variant === TrialLifecycleEmailVariant::STANDARD;
        });

        $this->assertDatabaseCount('reports', 1);
        File::delete($mapPath);
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
        $emailMapService->shouldReceive('captureLatestDay')
            ->once()
            ->with($location, 0.25)
            ->andThrow(new \RuntimeException('Chromium failed'));

        $snapshotUrlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $snapshotUrlGenerator->shouldReceive('generatePublicDailyMapsPage')
            ->once()
            ->with($location)
            ->andReturn('https://example.test/location-maps');

        $job = new SendLocationReportEmail($location);
        $job->handle(app(Mailer::class), $builder, $emailMapService, $snapshotUrlGenerator);

        Mail::assertSent(SendLocationReport::class, function (SendLocationReport $mail) use ($location): bool {
            return $mail->location->is($location)
                && $mail->recentMap === null
                && $mail->publicMapsUrl === 'https://example.test/location-maps'
                && $mail->variant === TrialLifecycleEmailVariant::STANDARD;
        });

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_grace_report_variant_marks_the_followup_send_timestamp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'crime_address_trial_started_at' => now()->subDays(8),
            'crime_address_trial_ends_at' => now()->subDay(),
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
        $emailMapService->shouldReceive('captureLatestDay')
            ->once()
            ->with($location, 0.25)
            ->andReturn(null);

        $snapshotUrlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $snapshotUrlGenerator->shouldReceive('generatePublicDailyMapsPage')
            ->once()
            ->with($location)
            ->andReturn('https://example.test/location-maps');

        $job = new SendLocationReportEmail($location, TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT);
        $job->handle(app(Mailer::class), $builder, $emailMapService, $snapshotUrlGenerator);

        Mail::assertSent(SendLocationReport::class, function (SendLocationReport $mail): bool {
            return $mail->variant === TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT
                && $mail->envelope()->subject === 'Your trial ended. We sent one more report for ' . $mail->location->address;
        });

        $this->assertNotNull($user->fresh()->crime_address_trial_grace_report_sent_at);
    }

    public function test_it_uses_a_longer_timeout_for_report_generation_and_screenshot_capture(): void
    {
        $job = new SendLocationReportEmail(new Location());

        $this->assertSame(1, $job->tries);
        $this->assertSame(600, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
    }

    public function test_it_rethrows_mail_render_failures_so_the_queue_marks_the_job_failed(): void
    {
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
        $emailMapService->shouldReceive('captureLatestDay')
            ->once()
            ->with($location, 0.25)
            ->andReturn(null);

        $snapshotUrlGenerator = Mockery::mock(LocationReportMapSnapshotUrlGenerator::class);
        $snapshotUrlGenerator->shouldReceive('generatePublicDailyMapsPage')
            ->once()
            ->with($location)
            ->andReturn('https://example.test/location-maps');

        $failingMailer = Mockery::mock(Mailer::class);
        $failingMailer->shouldReceive('to')
            ->once()
            ->with($user->email)
            ->andReturnSelf();
        $failingMailer->shouldReceive('send')
            ->once()
            ->with(Mockery::type(SendLocationReport::class))
            ->andThrow(new \RuntimeException('mail transport blew up: '.Str::random(8)));

        $job = new SendLocationReportEmail($location);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('mail transport blew up');

        $job->handle($failingMailer, $builder, $emailMapService, $snapshotUrlGenerator);
    }
}
