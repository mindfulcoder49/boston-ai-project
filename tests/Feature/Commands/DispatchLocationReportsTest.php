<?php

namespace Tests\Feature\Commands;

use App\Jobs\SendLocationReportEmail;
use App\Jobs\SendLocationReportEmailNoAI;
use App\Jobs\SendTrialEndedNoticeEmail;
use App\Models\Location;
use App\Models\User;
use App\Support\TrialLifecycleEmailVariant;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

class DispatchLocationReportsTest extends TestCase
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
            $table->timestamp('crime_address_trial_grace_report_sent_at')->nullable();
            $table->timestamp('crime_address_trial_ended_notice_sent_at')->nullable();
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('address')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('report')->nullable();
            $table->string('language')->nullable();
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

        Queue::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_active_trial_user_receives_one_dispatch_for_the_trial_location(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDay(),
            'crime_address_trial_ends_at' => now()->addDays(6),
        ]);
        $nonTrialLocation = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);
        $trialLocation = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);

        $user->forceFill([
            'crime_address_trial_location_id' => $trialLocation->id,
        ])->save();

        $this->artisan('reports:send')->assertSuccessful();

        Queue::assertPushed(SendLocationReportEmail::class, 1);
        Queue::assertPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedLocationId($job) === $trialLocation->id);
        Queue::assertPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedVariant($job) === TrialLifecycleEmailVariant::STANDARD);
        Queue::assertNotPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedLocationId($job) === $nonTrialLocation->id);
        Queue::assertNotPushed(SendLocationReportEmailNoAI::class);
        Queue::assertNotPushed(SendTrialEndedNoticeEmail::class);
    }

    public function test_last_day_of_trial_changes_the_report_variant(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDays(6),
            'crime_address_trial_ends_at' => now()->endOfDay(),
        ]);
        $location = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);

        $user->forceFill([
            'crime_address_trial_location_id' => $location->id,
        ])->save();

        $this->artisan('reports:send')->assertSuccessful();

        Queue::assertPushed(SendLocationReportEmail::class, 1);
        Queue::assertPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedVariant($job) === TrialLifecycleEmailVariant::TRIAL_LAST_DAY);
        Queue::assertNotPushed(SendTrialEndedNoticeEmail::class);
    }

    public function test_day_after_trial_end_receives_one_grace_report_dispatch(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDays(8),
            'crime_address_trial_ends_at' => now()->subDay()->endOfDay(),
        ]);
        $location = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);

        $user->forceFill([
            'crime_address_trial_location_id' => $location->id,
        ])->save();

        $this->artisan('reports:send')->assertSuccessful();

        Queue::assertPushed(SendLocationReportEmail::class, 1);
        Queue::assertPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedVariant($job) === TrialLifecycleEmailVariant::TRIAL_GRACE_REPORT);
        Queue::assertNotPushed(SendTrialEndedNoticeEmail::class);
    }

    public function test_two_days_after_trial_end_receives_a_final_stop_notice(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDays(9),
            'crime_address_trial_ends_at' => now()->subDays(2)->endOfDay(),
        ]);
        $location = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);

        $user->forceFill([
            'crime_address_trial_location_id' => $location->id,
        ])->save();

        $this->artisan('reports:send')->assertSuccessful();

        Queue::assertNotPushed(SendLocationReportEmail::class);
        Queue::assertPushed(SendTrialEndedNoticeEmail::class, 1);
        Queue::assertPushed(SendTrialEndedNoticeEmail::class, fn ($job) => $this->queuedNoticeUserId($job) === $user->id);
    }

    public function test_older_expired_trial_user_receives_no_dispatch(): void
    {
        Carbon::setTestNow('2026-04-05 07:00:00');

        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDays(12),
            'crime_address_trial_ends_at' => now()->subDays(4)->endOfDay(),
        ]);
        $location = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);

        $user->forceFill([
            'crime_address_trial_location_id' => $location->id,
        ])->save();

        $this->artisan('reports:send')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_basic_user_receives_only_one_dispatch(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'manual_subscription_tier' => 'basic',
        ]);
        $firstLocation = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);
        Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);

        $this->artisan('reports:send')->assertSuccessful();

        Queue::assertPushed(SendLocationReportEmail::class, 1);
        Queue::assertPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedLocationId($job) === $firstLocation->id);
        Queue::assertNotPushed(SendLocationReportEmailNoAI::class);
    }

    public function test_pro_user_receives_all_daily_locations(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'manual_subscription_tier' => 'pro',
        ]);
        $firstLocation = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);
        $secondLocation = Location::factory()->for($user)->create([
            'report' => 'daily',
        ]);

        $this->artisan('reports:send')->assertSuccessful();

        Queue::assertPushed(SendLocationReportEmail::class, 2);
        Queue::assertPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedLocationId($job) === $firstLocation->id);
        Queue::assertPushed(SendLocationReportEmail::class, fn ($job) => $this->queuedLocationId($job) === $secondLocation->id);
        Queue::assertNotPushed(SendLocationReportEmailNoAI::class);
    }

    private function queuedLocationId(object $job): int
    {
        $property = new ReflectionProperty($job, 'location');
        $property->setAccessible(true);

        /** @var Location $location */
        $location = $property->getValue($job);

        return $location->id;
    }

    private function queuedVariant(object $job): string
    {
        $property = new ReflectionProperty($job, 'variant');
        $property->setAccessible(true);

        return (string) $property->getValue($job);
    }

    private function queuedNoticeUserId(object $job): int
    {
        $property = new ReflectionProperty($job, 'user');
        $property->setAccessible(true);

        /** @var User $user */
        $user = $property->getValue($job);

        return $user->id;
    }
}
