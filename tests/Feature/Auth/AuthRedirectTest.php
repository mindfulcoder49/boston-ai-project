<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
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
            $table->string('role')->nullable();
            $table->rememberToken();
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
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('zoom_level')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('slug')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->json('filters')->nullable();
            $table->json('map_settings')->nullable();
            $table->json('configurable_filter_fields')->nullable();
            $table->string('map_type')->nullable();
            $table->timestamps();
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('h3_location_names');
        Schema::dropIfExists('saved_maps');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_login_redirects_to_safe_redirect_to_path(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect_to' => '/crime-address?address=1%20Beacon%20St&lat=42.3601&lng=-71.0589',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/crime-address?address=1%20Beacon%20St&lat=42.3601&lng=-71.0589');
    }

    public function test_registration_redirects_to_safe_redirect_to_path(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'redirect_to' => '/crime-address?address=1%20Beacon%20St&lat=42.3601&lng=-71.0589',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/crime-address?address=1%20Beacon%20St&lat=42.3601&lng=-71.0589');
    }
}
