<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Services\LocationReportMapSnapshotBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class LocationReportDailyMapsPageControllerTest extends TestCase
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

    public function test_signed_daily_maps_page_renders_the_public_view(): void
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
        $builder->shouldReceive('buildDailySeries')
            ->once()
            ->andReturn([
                [
                    'window' => [
                        'display' => 'April 3, 2026',
                    ],
                    'radius_miles' => 0.25,
                    'selected_points' => 1,
                    'recent_points_in_window' => 1,
                    'omitted_points' => 0,
                    'markers' => [],
                    'incidents' => [],
                    'empty' => true,
                ],
            ]);

        $this->app->instance(LocationReportMapSnapshotBuilder::class, $builder);

        $url = URL::signedRoute('reports.location-daily-maps', [
            'location' => $location->id,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Recent Daily Maps')
            ->assertSee('daily-map-0')
            ->assertSee('April 3, 2026');
    }

    public function test_daily_maps_page_requires_a_valid_signature(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $location = Location::factory()->for($user)->create([
            'name' => 'South Boston Home',
            'report' => 'daily',
            'language' => 'en',
        ]);

        $this->get(route('reports.location-daily-maps', [
            'location' => $location->id,
        ]))->assertForbidden();
    }
}
