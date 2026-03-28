<?php

namespace Tests\Feature\CrimeAddressFunnel;

use App\Models\CoverageRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoverageRequestTest extends TestCase
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

        Schema::create('coverage_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('requested_address');
            $table->string('normalized_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('source_page')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('request_count')->default(1);
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('coverage_requests');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_unsupported_preview_submission_stores_a_coverage_request(): void
    {
        $response = $this->postJson(route('crime-address.coverage-request.store'), [
            'requested_address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'normalized_address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
            'email' => 'coverage@example.com',
            'source_page' => '/crime-address',
        ]);

        $response->assertCreated()
            ->assertJson([
                'message' => 'We will look into adding your area and notify you if we do.',
                'created' => true,
            ]);

        $this->assertDatabaseHas('coverage_requests', [
            'email' => 'coverage@example.com',
            'requested_address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'source_page' => '/crime-address',
            'status' => 'pending',
            'request_count' => 1,
        ]);
    }

    public function test_authenticated_request_stores_user_id(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->postJson(route('crime-address.coverage-request.store'), [
            'requested_address' => '500 Example Rd, Phoenix, AZ 85004, USA',
            'email' => $user->email,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('coverage_requests', [
            'user_id' => $user->id,
            'email' => $user->email,
            'requested_address' => '500 Example Rd, Phoenix, AZ 85004, USA',
        ]);
    }

    public function test_duplicate_requests_increment_request_count_instead_of_creating_a_new_row(): void
    {
        CoverageRequest::factory()->create([
            'email' => 'coverage@example.com',
            'requested_address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'normalized_address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'request_count' => 1,
        ]);

        $response = $this->postJson(route('crime-address.coverage-request.store'), [
            'requested_address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'normalized_address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'email' => 'coverage@example.com',
            'source_page' => '/crime-address',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'We will look into adding your area and notify you if we do.',
                'created' => false,
            ]);

        $this->assertSame(1, CoverageRequest::count());
        $this->assertDatabaseHas('coverage_requests', [
            'email' => 'coverage@example.com',
            'request_count' => 2,
        ]);
    }
}
