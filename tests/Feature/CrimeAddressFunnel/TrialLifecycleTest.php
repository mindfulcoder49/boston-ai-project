<?php

namespace Tests\Feature\CrimeAddressFunnel;

use App\Jobs\SendLocationReportEmail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrialLifecycleTest extends TestCase
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

        Schema::create('saved_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
        });

        Schema::create('h3_location_names', function (Blueprint $table) {
            $table->id();
            $table->string('h3_index')->unique();
            $table->integer('h3_resolution')->nullable();
            $table->string('location_name')->nullable();
            $table->timestamp('geocoded_at')->nullable();
            $table->json('raw_geocode_response')->nullable();
            $table->timestamps();
        });

        Queue::fake();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('h3_location_names');
        Schema::dropIfExists('saved_maps');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_authenticated_user_can_start_crime_address_trial(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->postJson(route('crime-address.trial.start'), [
            'address' => '1 Beacon St, Boston, MA 02108, USA',
            'latitude' => 42.3601,
            'longitude' => -71.0589,
        ]);

        $response->assertOk()->assertJson([
            'active' => true,
            'location' => [
                'address' => '1 Beacon St, Boston, MA 02108, USA',
            ],
        ]);

        $user->refresh();

        $this->assertNotNull($user->crime_address_trial_started_at);
        $this->assertNotNull($user->crime_address_trial_ends_at);
        $this->assertNotNull($user->crime_address_trial_location_id);
        $this->assertDatabaseHas('locations', [
            'id' => $user->crime_address_trial_location_id,
            'user_id' => $user->id,
            'address' => '1 Beacon St, Boston, MA 02108, USA',
            'report' => 'daily',
        ]);
        Queue::assertPushed(SendLocationReportEmail::class);
    }

    public function test_expired_trial_cannot_be_started_again(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDays(8),
            'crime_address_trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson(route('crime-address.trial.start'), [
            'address' => '1 Beacon St, Boston, MA 02108, USA',
            'latitude' => 42.3601,
            'longitude' => -71.0589,
        ]);

        $response->assertStatus(409)->assertJson([
            'message' => 'Your free trial has ended. Choose a plan to continue receiving reports.',
        ]);
    }

    public function test_active_trial_user_sees_trial_state_on_preview_page(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDay(),
            'crime_address_trial_ends_at' => now()->addDays(6),
        ]);

        $response = $this->actingAs($user)->get(route('crime-address.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('CrimeAddress/Index')
            ->where('auth.user.has_crime_address_trial', true)
            ->where('auth.user.has_used_crime_address_trial', true)
            ->where('auth.user.crime_address_trial_ends_at', $user->crime_address_trial_ends_at->toDateString())
        );
    }

    public function test_expired_trial_user_sees_expired_trial_state_on_preview_page(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'crime_address_trial_started_at' => now()->subDays(8),
            'crime_address_trial_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('crime-address.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('CrimeAddress/Index')
            ->where('auth.user.has_crime_address_trial', false)
            ->where('auth.user.has_used_crime_address_trial', true)
            ->where('auth.user.crime_address_trial_ends_at', $user->crime_address_trial_ends_at->toDateString())
        );
    }
}
