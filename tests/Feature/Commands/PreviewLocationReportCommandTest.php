<?php

namespace Tests\Feature\Commands;

use App\Models\Location;
use App\Models\User;
use App\Services\LocationReportBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class PreviewLocationReportCommandTest extends TestCase
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
            $table->string('provider_id')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('provider_avatar')->nullable();
            $table->string('manual_subscription_tier')->nullable();
            $table->timestamp('crime_address_trial_started_at')->nullable();
            $table->timestamp('crime_address_trial_ends_at')->nullable();
            $table->unsignedBigInteger('crime_address_trial_location_id')->nullable();
            $table->string('role')->nullable();
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
    }

    protected function tearDown(): void
    {
        Mockery::close();

        Schema::dropIfExists('reports');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_preview_command_does_not_save_or_send_by_default(): void
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
        $builder
            ->shouldReceive('build')
            ->once()
            ->withArgs(function (Location $passedLocation, float $radius) use ($location): bool {
                $this->assertSame(0.25, $radius);

                return $passedLocation->is($location);
            })
            ->andReturn([
                'final_report' => "## Location Report: South Boston Home\n\nSample preview content.",
                'daily_report_content' => "### March 30, 2026\n\nSample preview content.",
                'location_details_header' => "## Location Report: South Boston Home\n\n",
                'data_points_count' => 9,
                'section_diagnostics' => [
                    [
                        'date_key' => '2026-03-30',
                        'display_date' => 'March 30, 2026',
                        'type' => 'Boston 311 Cases',
                        'record_count' => 9,
                        'generated' => true,
                    ],
                ],
            ]);

        $this->app->instance(LocationReportBuilder::class, $builder);

        $this->artisan('reports:preview', ['location_id' => $location->id])
            ->expectsOutputToContain('Report preview generated.')
            ->expectsOutputToContain('Preview only: no report saved, no email sent.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('reports', 0);
        Mail::assertNothingSent();
    }
}
