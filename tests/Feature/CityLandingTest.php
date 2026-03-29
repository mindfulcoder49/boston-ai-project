<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CityLandingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('h3_location_names_map');

        Schema::dropIfExists('h3_location_names');
        Schema::create('h3_location_names', function (Blueprint $table): void {
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
        Cache::forget('h3_location_names_map');
        Schema::dropIfExists('h3_location_names');

        parent::tearDown();
    }

    public function test_boston_city_landing_has_multi_dataset_copy_and_crime_preview_link(): void
    {
        $response = $this->get(route('city.landing.boston'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.key', 'boston')
            ->where('city.searchPlaceholder', 'Search a Boston address')
            ->where('city.tagline', 'Boston is the fullest PublicDataWatch city: crime, 311, permits, inspections, violations, and crashes in one place.')
            ->where('city.focusAreas', ['Crime', '311', 'Permits', 'Inspections', 'Crashes'])
            ->where('city.relatedLinks', fn ($links) => collect($links)->contains(
                fn (array $link) => $link['label'] === 'Try one-address crime preview'
                    && $link['url'] === route('crime-address.index')
            ))
            ->where('city.highlights', fn ($highlights) => count($highlights) === 3)
        );
    }

    public function test_chicago_city_landing_uses_chicago_specific_crime_copy(): void
    {
        $response = $this->get(route('city.landing.chicago'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.key', 'chicago')
            ->where('city.searchPlaceholder', 'Search a Chicago address')
            ->where('city.seoTitle', 'Chicago Crime Map and Neighborhood Incident Data | PublicDataWatch')
            ->where('city.focusAreas', ['Crime reports', 'Address search', 'Block-level first pass'])
        );
    }

    public function test_everett_city_landing_preserves_the_fast_block_check_positioning(): void
    {
        $response = $this->get(route('city.landing.everett'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.key', 'everett')
            ->where('city.searchPlaceholder', 'Search an Everett address')
            ->where('city.tagline', 'Check recent Everett crime around an address fast.')
            ->where('city.focusAreas', ['Crime reports', 'Address search', 'Fast block check'])
        );
    }

    public function test_new_york_city_landing_uses_311_specific_copy_without_crime_preview_link(): void
    {
        $response = $this->get(route('city.landing.new_york'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.key', 'new_york')
            ->where('city.tagline', 'See recent New York 311 requests around an address.')
            ->where('city.seoTitle', 'New York 311 Map and City Service Request Data | PublicDataWatch')
            ->where('city.focusAreas', ['311 requests', 'Address search', 'Quality-of-life signals'])
            ->where('city.relatedLinks', fn ($links) => !collect($links)->contains(
                fn (array $link) => $link['label'] === 'Try one-address crime preview'
            ))
        );
    }

    public function test_montgomery_county_city_landing_mentions_countywide_scope(): void
    {
        $response = $this->get(route('city.landing.montgomery_county_md'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.key', 'montgomery_county_md')
            ->where('city.searchPlaceholder', 'Search a Montgomery County address')
            ->where('city.focusAreas', ['Countywide crime', 'Address search', 'Cross-community checks'])
            ->where('city.dataUpdateNote', 'Montgomery County coverage on this page is countywide crime data, so addresses from several local communities can be served by the same landing page.')
        );
    }

    public function test_san_francisco_city_landing_uses_san_francisco_specific_copy(): void
    {
        $response = $this->get(route('city.landing.san_francisco'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.key', 'san_francisco')
            ->where('city.searchPlaceholder', 'Search a San Francisco address')
            ->where('city.tagline', 'See recent San Francisco crime around an address without jumping straight into the full map.')
            ->where('city.focusAreas', ['Crime reports', 'Address search', 'Neighborhood check'])
        );
    }

    public function test_city_landing_exposes_initial_location_and_city_routing_metadata(): void
    {
        $response = $this->get(route('city.landing.boston', [
            'address' => '851 Broadway, Everett, MA 02149, USA',
            'lat' => '42.418742',
            'lng' => '-71.044910',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.initialLocation.address', '851 Broadway, Everett, MA 02149, USA')
            ->where('city.initialLocation.latitude', 42.418742)
            ->where('city.initialLocation.longitude', -71.04491)
            ->where('cityRouting', fn ($targets) => collect($targets)->contains(
                fn (array $target) => $target['key'] === 'everett'
                    && $target['url'] === route('city.landing.everett')
                    && in_array('everett', $target['matchLocalities'], true)
            ))
        );
    }

    public function test_seattle_city_landing_uses_seattle_specific_copy(): void
    {
        $response = $this->get(route('city.landing.seattle'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('CityMapLite')
            ->where('city.key', 'seattle')
            ->where('city.searchPlaceholder', 'Search a Seattle address')
            ->where('city.tagline', 'Check recent Seattle crime around an address fast.')
            ->where('city.focusAreas', ['Crime reports', 'Address search', 'Fast neighborhood scan'])
        );
    }
}
