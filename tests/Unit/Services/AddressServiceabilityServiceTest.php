<?php

namespace Tests\Unit\Services;

use App\Services\AddressServiceabilityService;
use Tests\TestCase;

class AddressServiceabilityServiceTest extends TestCase
{
    public function test_supported_boston_address_returns_supported_response(): void
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

        $service = new AddressServiceabilityService();

        $result = $service->determineSupport(42.3601, -71.0589, '1 Beacon St, Boston, MA 02108, USA');

        $this->assertTrue($result['supported']);
        $this->assertSame('boston', $result['matched_city_key']);
        $this->assertSame('Boston', $result['matched_city_name']);
        $this->assertSame('within_configured_coverage', $result['reason']);
    }

    public function test_supported_crime_only_city_returns_supported_response(): void
    {
        config()->set('cities.cities', [
            'chicago' => [
                'name' => 'Chicago',
                'latitude' => 41.8781,
                'longitude' => -87.6298,
                'serviceability' => [
                    'crime_address_funnel_enabled' => true,
                    'radius_miles' => 12,
                    'supported_regions' => ['IL'],
                    'supported_localities' => ['Chicago'],
                ],
            ],
        ]);

        $service = new AddressServiceabilityService();

        $result = $service->determineSupport(41.8819, -87.6278, '121 N LaSalle St, Chicago, IL 60602, USA');

        $this->assertTrue($result['supported']);
        $this->assertSame('chicago', $result['matched_city_key']);
    }

    public function test_address_outside_configured_coverage_returns_unsupported_response(): void
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
                    'supported_localities' => ['Boston'],
                ],
            ],
        ]);

        $service = new AddressServiceabilityService();

        $result = $service->determineSupport(34.0522, -118.2437, '200 N Spring St, Los Angeles, CA 90012, USA');

        $this->assertFalse($result['supported']);
        $this->assertNull($result['matched_city_key']);
        $this->assertSame('outside_configured_coverage', $result['reason']);
    }

    public function test_address_within_radius_but_outside_supported_locality_returns_unsupported_response(): void
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

        $service = new AddressServiceabilityService();

        $result = $service->determineSupport(42.3874, -71.0995, '93 Highland Ave, Somerville, MA 02143, USA');

        $this->assertFalse($result['supported']);
        $this->assertNull($result['matched_city_key']);
        $this->assertSame('outside_configured_coverage', $result['reason']);
        $this->assertSame('everett', $result['nearest_city_key']);
    }

    public function test_service_does_not_silently_coerce_unsupported_coordinates_to_nearest_city(): void
    {
        config()->set('cities.cities', [
            'seattle' => [
                'name' => 'Seattle',
                'latitude' => 47.6062,
                'longitude' => -122.3321,
                'serviceability' => [
                    'crime_address_funnel_enabled' => true,
                    'radius_miles' => 5,
                    'supported_regions' => ['WA'],
                    'supported_localities' => ['Seattle'],
                ],
            ],
            'new_york' => [
                'name' => 'New York',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'serviceability' => [
                    'crime_address_funnel_enabled' => false,
                    'radius_miles' => 10,
                ],
            ],
        ]);

        $service = new AddressServiceabilityService();

        $result = $service->determineSupport(47.9500, -122.2100, '123 Example Ave, Everett, WA 98201, USA');

        $this->assertFalse($result['supported']);
        $this->assertNull($result['matched_city_key']);
        $this->assertSame('seattle', $result['nearest_city_key']);
        $this->assertSame('Seattle', $result['nearest_city_name']);
    }
}
