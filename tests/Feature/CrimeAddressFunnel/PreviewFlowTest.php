<?php

namespace Tests\Feature\CrimeAddressFunnel;

use App\Models\AnalysisReportSnapshot;
use App\Models\EverettCrimeData;
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

        Schema::create('analysis_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('job_id');
            $table->string('artifact_name');
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('s3_last_modified')->nullable();
            $table->timestamp('pulled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trends', function (Blueprint $table) {
            $table->id();
            $table->string('model_class');
            $table->string('column_name');
            $table->string('job_id');
            $table->unsignedInteger('h3_resolution');
            $table->decimal('p_value_anomaly', 8, 6);
            $table->decimal('p_value_trend', 8, 6);
            $table->json('analysis_weeks_trend')->nullable();
            $table->unsignedInteger('analysis_weeks_anomaly')->default(4);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('trends');
        Schema::dropIfExists('analysis_report_snapshots');
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

    public function test_supported_address_surfaces_snapshot_backed_trend_and_score_context(): void
    {
        config()->set('cities.cities', [
            'everett' => [
                'name' => 'Everett',
                'latitude' => 42.4084,
                'longitude' => -71.0537,
                'models' => [EverettCrimeData::class],
                'serviceability' => [
                    'crime_address_funnel_enabled' => true,
                    'radius_miles' => 4,
                    'supported_regions' => ['MA'],
                    'supported_localities' => ['Everett'],
                    'crime_model_locality_map' => [
                        'everett' => EverettCrimeData::class,
                    ],
                ],
            ],
        ]);
        config()->set('analysis_schedule.stage6.jobs', [
            [
                'model' => EverettCrimeData::class,
                'column' => 'incident_type_group',
            ],
        ]);

        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-everett-crime-data-incident_type_group-res8-1772947509',
            'artifact_name' => 'stage4_h3_anomaly.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'incident_type_group',
                    'h3_resolution' => 8,
                    'p_value_anomaly' => 0.05,
                    'p_value_trend' => 0.05,
                ],
                'results' => [
                    [
                        'secondary_group' => 'Alarm Response',
                        'h3_index_8' => '882a107289fffff',
                        'historical_weekly_avg' => 1.0,
                        'anomaly_analysis' => [
                            [
                                'week' => '2026-03-22',
                                'count' => 3,
                                'anomaly_p_value' => 0.01,
                                'z_score' => 2.2,
                            ],
                        ],
                    ],
                ],
            ],
            's3_last_modified' => 1772947838,
        ]);

        AnalysisReportSnapshot::query()->create([
            'job_id' => 'laravel-hist-score-everett-crime-data-incident_type_group-res10-1772947512',
            'artifact_name' => 'stage6_historical_score_laravel-hist-score-everett-crime-data-incident_type_group-res10-1772947512.json',
            'payload' => [
                'parameters' => [
                    'model_class' => EverettCrimeData::class,
                    'column_name' => 'incident_type_group',
                    'h3_resolution' => 10,
                    'analysis_period_weeks' => 52,
                ],
                'source_job_id' => 'laravel-everett-crime-data-incident_type_group-res10-1772947512',
            ],
            's3_last_modified' => 1772948400,
        ]);

        $this->app->bind(CrimeAddressPreviewBuilder::class, fn () => new class extends CrimeAddressPreviewBuilder
        {
            protected function fetchMapPayload(string $matchedCityKey, string $address, float $latitude, float $longitude, float $radius): array
            {
                return [
                    'dataPoints' => [
                        [
                            'data_point_id' => 1,
                            'latitude' => 42.4187,
                            'longitude' => -71.0449,
                            'alcivartech_type' => 'Crime',
                            'alcivartech_date' => '2026-03-26 20:47:00',
                            'everett_crime_data_data' => [
                                'incident_type_group' => 'Alarm Response',
                                'incident_description' => '2 WAY 911-ALARM SOUNDING',
                                'incident_address' => '851 BROADWAY ST',
                            ],
                        ],
                    ],
                ];
            }

            protected function resolveCrimeModelClass(array $serviceability): ?string
            {
                return EverettCrimeData::class;
            }
        });

        $response = $this->postJson(route('crime-address.preview'), [
            'address' => '851 Broadway, Everett, MA 02149, USA',
            'latitude' => 42.418742,
            'longitude' => -71.04491,
        ]);

        $response->assertOk()->assertJson([
            'supported' => true,
            'matched_city_key' => 'everett',
            'trend_context' => [
                'job_id' => 'laravel-everett-crime-data-incident_type_group-res8-1772947509',
                'column_name' => 'incident_type_group',
            ],
            'score_report' => [
                'job_id' => 'laravel-hist-score-everett-crime-data-incident_type_group-res10-1772947512',
                'resolution' => 10,
            ],
        ]);
    }
}
