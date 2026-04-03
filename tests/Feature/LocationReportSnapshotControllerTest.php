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

class LocationReportSnapshotControllerTest extends TestCase
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

    public function test_signed_snapshot_route_renders_the_hidden_page(): void
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
        $builder->shouldReceive('build')
            ->once()
            ->andReturn([
                'location' => [
                    'label' => 'South Boston Home',
                ],
                'window' => [
                    'days' => 2,
                    'display' => 'April 2, 2026 to April 3, 2026',
                ],
                'radius_miles' => 0.25,
                'selection_policy' => 'Showing up to 4 incidents from the last 2 day(s), ranked by newest first and nearest to home when dates tie.',
                'recent_points_in_window' => 2,
                'selected_points' => 2,
                'omitted_points' => 0,
                'counts_by_date' => [],
                'markers' => [],
                'incidents' => [],
                'empty' => true,
            ]);

        $this->app->instance(LocationReportMapSnapshotBuilder::class, $builder);

        $url = URL::temporarySignedRoute('reports.location-snapshot', now()->addMinutes(15), [
            'location' => $location->id,
            'radius' => 0.25,
            'days' => 2,
            'limit' => 4,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Paid Report Map Preview')
            ->assertSee('South Boston Home');
    }

    public function test_snapshot_route_requires_a_valid_signature(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $location = Location::factory()->for($user)->create([
            'name' => 'South Boston Home',
            'report' => 'daily',
            'language' => 'en',
        ]);

        $this->get(route('reports.location-snapshot', [
            'location' => $location->id,
            'radius' => 0.25,
            'days' => 2,
            'limit' => 4,
        ]))->assertForbidden();
    }
}
