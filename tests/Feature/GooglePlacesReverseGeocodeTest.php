<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GooglePlacesReverseGeocodeTest extends TestCase
{
    public function test_reverse_geocode_google_place_returns_formatted_address(): void
    {
        config()->set('services.google_places.api_key', 'test-google-key');

        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'formatted_address' => '851 Broadway, Everett, MA 02149, USA',
                        'geometry' => [
                            'location' => [
                                'lat' => 42.418742,
                                'lng' => -71.04491,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('google-places.reverse-geocode'), [
            'latitude' => 42.418742,
            'longitude' => -71.04491,
        ]);

        $response->assertOk()->assertJson([
            'address' => '851 Broadway, Everett, MA 02149, USA',
            'lat' => 42.418742,
            'lng' => -71.04491,
        ]);
    }
}
