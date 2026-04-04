<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(HomeController::HOME_PAGE_CACHE_KEY);
        config()->set('metrics.data', [
            ['totalRecords' => 1250],
            ['totalRecords' => 3750],
        ]);

        Schema::dropIfExists('h3_location_names');
        Schema::dropIfExists('saved_maps');

        Schema::create('saved_maps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('h3_location_names', function (Blueprint $table): void {
            $table->id();
            $table->string('h3_index')->unique();
            $table->string('location_name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Cache::forget(HomeController::HOME_PAGE_CACHE_KEY);
        Cache::forget('h3_location_names_map');
        Schema::dropIfExists('h3_location_names');
        Schema::dropIfExists('saved_maps');

        parent::tearDown();
    }

    public function test_homepage_exposes_real_city_pages_with_city_and_state_labels(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('stats.totalRecords', 5000)
            ->where('stats.cityCount', 7)
            ->where('cities', fn ($cities) => collect($cities)->contains(
                fn (array $city) => $city['key'] === 'boston'
                    && $city['locationLabel'] === 'Boston, MA'
                    && $city['landingUrl'] === route('city.landing.boston')
                    && $city['coverageNote'] === 'Also supports Cambridge, MA address lookups.'
            ) && collect($cities)->contains(
                fn (array $city) => $city['key'] === 'new_york'
                    && $city['locationLabel'] === 'New York, NY'
                    && $city['landingUrl'] === route('city.landing.new_york')
            ) && collect($cities)->contains(
                fn (array $city) => $city['key'] === 'montgomery_county_md'
                    && $city['locationLabel'] === 'Montgomery County, MD'
                    && $city['landingUrl'] === route('city.landing.montgomery_county_md')
            ))
        );
    }
}
