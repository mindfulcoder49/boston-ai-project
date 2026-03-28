<?php

namespace Tests\Unit\Services;

use App\Models\CrimeData;
use App\Services\CrimeAddressPreviewBuilder;
use Tests\TestCase;

class CrimeAddressPreviewBuilderTest extends TestCase
{
    public function test_build_includes_crime_incidents_score_and_trend_context(): void
    {
        $builder = new class extends CrimeAddressPreviewBuilder
        {
            protected function fetchMapPayload(string $matchedCityKey, string $address, float $latitude, float $longitude, float $radius): array
            {
                return [
                    'dataPoints' => [
                        [
                            'data_point_id' => 1,
                            'latitude' => 42.3601,
                            'longitude' => -71.0589,
                            'alcivartech_type' => 'Crime',
                            'alcivartech_date' => '2026-03-26',
                            'crime_data' => [
                                'offense_code_group' => 'Larceny',
                                'offense_description' => 'Wallet taken from parked car',
                                'street_name' => 'Beacon St',
                            ],
                        ],
                        [
                            'data_point_id' => 2,
                            'latitude' => 42.3598,
                            'longitude' => -71.0594,
                            'alcivartech_type' => 'Crime',
                            'alcivartech_date' => '2026-03-25',
                            'crime_data' => [
                                'offense_code_group' => 'Assault',
                                'offense_description' => 'Assault and battery report',
                                'street_name' => 'Tremont St',
                            ],
                        ],
                        [
                            'data_point_id' => 3,
                            'alcivartech_type' => '311',
                            'alcivartech_date' => '2026-03-24',
                        ],
                    ],
                ];
            }

            protected function resolveCrimeModelClass(array $serviceability): ?string
            {
                return CrimeData::class;
            }

            protected function resolveLatestScoreReport(?string $crimeModelClass): ?array
            {
                return [
                    'job_id' => 'job-score-1',
                    'artifact_name' => 'stage6_historical_score_laravel-hist-score-crime-data-boston.json',
                    'resolution' => 8,
                ];
            }

            protected function resolveTrendContext(?string $crimeModelClass): ?array
            {
                return [
                    'job_id' => 'job-trend-1',
                    'summary' => [
                        'status' => 'ok',
                        'total_findings' => 12,
                        'affected_h3_count' => 5,
                        'top_categories' => ['Larceny', 'Assault', 'Vandalism'],
                    ],
                ];
            }
        };

        $preview = $builder->build([
            'matched_city_key' => 'boston',
            'matched_city_name' => 'Boston',
            'normalized_address' => '1 Beacon St, Boston, MA 02108, USA',
        ], '1 Beacon St, Boston, MA 02108, USA', 42.3601, -71.0589);

        $this->assertTrue($preview['supported']);
        $this->assertSame(2, $preview['map_data']['incident_count']);
        $this->assertSame(2, $preview['incident_summary']['total_incidents']);
        $this->assertSame('Larceny', $preview['incident_summary']['top_categories'][0]['category']);
        $this->assertSame('job-score-1', $preview['score_report']['job_id']);
        $this->assertSame('ok', $preview['trend_context']['summary']['status']);
        $this->assertCount(4, $preview['preview_report']);
        $this->assertSame('What happened nearby', $preview['preview_report'][0]['title']);
    }

    public function test_build_degrades_gracefully_when_score_and_trend_context_are_missing(): void
    {
        $builder = new class extends CrimeAddressPreviewBuilder
        {
            protected function fetchMapPayload(string $matchedCityKey, string $address, float $latitude, float $longitude, float $radius): array
            {
                return [
                    'dataPoints' => [
                        [
                            'data_point_id' => 1,
                            'latitude' => 42.3601,
                            'longitude' => -71.0589,
                            'alcivartech_type' => 'Crime',
                            'alcivartech_date' => '2026-03-26',
                            'crime_data' => [
                                'offense_code_group' => 'Burglary',
                                'offense_description' => 'Residential break-in',
                                'street_name' => 'Beacon St',
                            ],
                        ],
                    ],
                ];
            }

            protected function resolveCrimeModelClass(array $serviceability): ?string
            {
                return null;
            }
        };

        $preview = $builder->build([
            'matched_city_key' => 'everett',
            'matched_city_name' => 'Everett',
            'normalized_address' => '11 Broadway, Everett, MA 02149, USA',
        ], '11 Broadway, Everett, MA 02149, USA', 42.4084, -71.0537);

        $this->assertNull($preview['score_report']);
        $this->assertNull($preview['trend_context']);
        $this->assertSame(1, $preview['incident_summary']['total_incidents']);
        $this->assertStringContainsString(
            'Neighborhood scoring and city-level trend context are not currently available',
            $preview['preview_report'][2]['body'],
        );
    }

    public function test_build_extracts_everett_specific_fields_for_incident_content(): void
    {
        $builder = new class extends CrimeAddressPreviewBuilder
        {
            protected function fetchMapPayload(string $matchedCityKey, string $address, float $latitude, float $longitude, float $radius): array
            {
                return [
                    'dataPoints' => [
                        [
                            'data_point_id' => 99,
                            'latitude' => 42.4187,
                            'longitude' => -71.0449,
                            'alcivartech_type' => 'Crime',
                            'alcivartech_date' => '2026-03-26 20:47:00',
                            'everett_crime_data_data' => [
                                'incident_type_group' => 'Motor Vehicle Accident',
                                'incident_type' => 'MV-ACCIDENT',
                                'incident_description' => '2 CAR MVA',
                                'incident_address' => '760 BROADWAY ST',
                            ],
                        ],
                    ],
                ];
            }

            protected function resolveCrimeModelClass(array $serviceability): ?string
            {
                return null;
            }
        };

        $preview = $builder->build([
            'matched_city_key' => 'everett',
            'matched_city_name' => 'Everett',
            'normalized_address' => '851 Broadway, Everett, MA 02149, USA',
        ], '851 Broadway, Everett, MA 02149, USA', 42.4187, -71.0449);

        $this->assertSame('Motor Vehicle Accident', $preview['incident_summary']['top_categories'][0]['category']);
        $this->assertSame('Motor Vehicle Accident', $preview['map_data']['incidents'][0]['category']);
        $this->assertSame('2 CAR MVA', $preview['map_data']['incidents'][0]['description']);
        $this->assertSame('760 BROADWAY ST', $preview['map_data']['incidents'][0]['location_label']);
        $this->assertSame('2 CAR MVA', $preview['incident_summary']['recent_incidents'][0]['description']);
        $this->assertSame('760 BROADWAY ST', $preview['incident_summary']['recent_incidents'][0]['location_label']);
    }
}
