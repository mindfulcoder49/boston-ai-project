<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class AddressServiceabilityService
{
    public function determineSupport(float $latitude, float $longitude, ?string $formattedAddress = null): array
    {
        $cities = Config::get('cities.cities', []);
        $normalizedAddress = $this->normalizeAddress($formattedAddress);
        $parsedAddress = $this->parseFormattedAddress($normalizedAddress);

        $nearestCityKey = null;
        $nearestDistanceMiles = PHP_FLOAT_MAX;
        $matchedCityKey = null;
        $matchedDistanceMiles = null;

        foreach ($cities as $cityKey => $cityConfig) {
            $serviceability = $cityConfig['serviceability'] ?? [];

            if (!($serviceability['crime_address_funnel_enabled'] ?? false)) {
                continue;
            }

            $distanceMiles = $this->distanceMiles(
                $latitude,
                $longitude,
                (float) ($cityConfig['latitude'] ?? 0),
                (float) ($cityConfig['longitude'] ?? 0),
            );

            if ($distanceMiles < $nearestDistanceMiles) {
                $nearestDistanceMiles = $distanceMiles;
                $nearestCityKey = $cityKey;
            }

            if (!$this->isWithinConfiguredCoverage($serviceability, $parsedAddress, $distanceMiles)) {
                continue;
            }

            if ($matchedCityKey === null || $distanceMiles < $matchedDistanceMiles) {
                $matchedCityKey = $cityKey;
                $matchedDistanceMiles = $distanceMiles;
            }
        }

        if ($matchedCityKey === null) {
            return [
                'supported' => false,
                'matched_city_key' => null,
                'matched_city_name' => null,
                'reason' => 'outside_configured_coverage',
                'normalized_address' => $normalizedAddress,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'parsed_address' => $parsedAddress,
                'nearest_city_key' => $nearestCityKey,
                'nearest_city_name' => $nearestCityKey ? ($cities[$nearestCityKey]['name'] ?? null) : null,
                'nearest_city_distance_miles' => is_finite($nearestDistanceMiles) ? round($nearestDistanceMiles, 2) : null,
            ];
        }

        return [
            'supported' => true,
            'matched_city_key' => $matchedCityKey,
            'matched_city_name' => $cities[$matchedCityKey]['name'] ?? Str::headline($matchedCityKey),
            'reason' => 'within_configured_coverage',
            'normalized_address' => $normalizedAddress,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'parsed_address' => $parsedAddress,
            'distance_miles' => round($matchedDistanceMiles, 2),
        ];
    }

    protected function isWithinConfiguredCoverage(array $serviceability, array $parsedAddress, float $distanceMiles): bool
    {
        $radiusMiles = (float) ($serviceability['radius_miles'] ?? 0);

        if ($radiusMiles <= 0 || $distanceMiles > $radiusMiles) {
            return false;
        }

        $supportedRegions = array_map('mb_strtolower', $serviceability['supported_regions'] ?? []);
        if (!empty($supportedRegions) && !empty($parsedAddress['region'])) {
            if (!in_array(mb_strtolower($parsedAddress['region']), $supportedRegions, true)) {
                return false;
            }
        }

        $supportedLocalities = array_map('mb_strtolower', $serviceability['supported_localities'] ?? []);
        if (!empty($supportedLocalities) && !empty($parsedAddress['locality'])) {
            if (!in_array(mb_strtolower($parsedAddress['locality']), $supportedLocalities, true)) {
                return false;
            }
        }

        return true;
    }

    protected function normalizeAddress(?string $formattedAddress): ?string
    {
        if ($formattedAddress === null) {
            return null;
        }

        $normalized = Str::squish($formattedAddress);

        return $normalized !== '' ? $normalized : null;
    }

    protected function parseFormattedAddress(?string $formattedAddress): array
    {
        if (!$formattedAddress) {
            return [
                'street' => null,
                'locality' => null,
                'region' => null,
                'postal_code' => null,
                'country' => null,
            ];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $formattedAddress))));

        $street = $parts[0] ?? null;
        $locality = $parts[1] ?? null;
        $regionAndPostal = $parts[2] ?? null;
        $country = $parts[3] ?? null;

        $region = null;
        $postalCode = null;

        if ($regionAndPostal) {
            if (preg_match('/^(?<region>[A-Z]{2})(?:\s+(?<postal>[A-Z0-9\-]+))?$/', $regionAndPostal, $matches)) {
                $region = $matches['region'] ?? null;
                $postalCode = $matches['postal'] ?? null;
            } else {
                $region = $regionAndPostal;
            }
        }

        return [
            'street' => $street,
            'locality' => $locality,
            'region' => $region,
            'postal_code' => $postalCode,
            'country' => $country,
        ];
    }

    protected function distanceMiles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMiles = 3958.8;

        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));

        return $angle * $earthRadiusMiles;
    }
}
