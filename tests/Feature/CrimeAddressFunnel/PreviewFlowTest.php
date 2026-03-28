<?php

namespace Tests\Feature\CrimeAddressFunnel;

use App\Services\CrimeAddressPreviewBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PreviewFlowTest extends TestCase
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

    public function test_index_page_renders(): void
    {
        $response = $this->get(route('crime-address.index'));

        $response->assertOk();
    }

    public function test_unsupported_address_returns_unsupported_state(): void
    {
        config()->set('cities.cities', [
            'boston' => [
                'name' => 'Boston',
                'latitude' => 42.3601,
                'longitude' => -71.0589,
                'serviceability' => [
                    'crime_address_funnel_enabled' => true,
                    'radius_miles' => 10,
                    'supported_regions' => ['MA'],
                    'supported_localities' => ['Boston', 'Cambridge'],
                ],
            ],
        ]);

        $response = $this->postJson(route('crime-address.preview'), [
            'address' => '200 N Spring St, Los Angeles, CA 90012, USA',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
        ]);

        $response->assertOk()->assertJson([
            'supported' => false,
            'message' => 'We do not serve your address yet. We will look into adding your area and notify you if we do.',
        ]);
    }

    public function test_nearby_but_unsupported_locality_returns_unsupported_state(): void
    {
        config()->set('cities.cities', [
            'everett' => [
                'name' => 'Everett',
                'latitude' => 42.4084,
                'longitude' => -71.0537,
                'serviceability' => [
                    'crime_address_funnel_enabled' => true,
                    'radius_miles' => 4,
                    'supported_regions' => ['MA'],
                    'supported_localities' => ['Everett'],
                ],
            ],
        ]);

        $response = $this->postJson(route('crime-address.preview'), [
            'address' => '93 Highland Ave, Somerville, MA 02143, USA',
            'latitude' => 42.3874,
            'longitude' => -71.0995,
        ]);

        $response->assertOk()->assertJson([
            'supported' => false,
            'message' => 'We do not serve your address yet. We will look into adding your area and notify you if we do.',
        ]);
    }

    public function test_supported_address_returns_preview_payload(): void
    {
        config()->set('cities.cities', [
            'boston' => [
                'name' => 'Boston',
                'latitude' => 42.3601,
                'longitude' => -71.0589,
                'serviceability' => [
                    'crime_address_funnel_enabled' => true,
                    'radius_miles' => 10,
                    'supported_regions' => ['MA'],
                    'supported_localities' => ['Boston', 'Cambridge'],
                ],
            ],
        ]);

        $this->app->bind(CrimeAddressPreviewBuilder::class, fn () => new class extends CrimeAddressPreviewBuilder
        {
            public function build(array $serviceability, string $address, float $latitude, float $longitude, float $radius = 0.25): array
            {
                return [
                    'supported' => true,
                    'address' => $address,
                    'matched_city_key' => $serviceability['matched_city_key'],
                    'matched_city_name' => $serviceability['matched_city_name'],
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => $radius,
                    'map_data' => [
                        'incidents' => [],
                    ],
                    'incident_summary' => [
                        'total_incidents' => 3,
                    ],
                    'preview_report' => [
                        ['title' => 'What happened nearby', 'body' => 'Found 3 crime incidents nearby.'],
                    ],
                ];
            }
        });

        $response = $this->postJson(route('crime-address.preview'), [
            'address' => '1 Beacon St, Boston, MA 02108, USA',
            'latitude' => 42.3601,
            'longitude' => -71.0589,
        ]);

        $response->assertOk()->assertJson([
            'supported' => true,
            'matched_city_key' => 'boston',
            'matched_city_name' => 'Boston',
            'incident_summary' => [
                'total_incidents' => 3,
            ],
        ]);
    }
}
