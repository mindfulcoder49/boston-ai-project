<?php

namespace App\Services;

use App\Http\Controllers\GenericMapController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CrimeAddressPreviewBuilder
{
    public function build(array $serviceability, string $address, float $latitude, float $longitude, float $radius = 0.25): array
    {
        $mapPayload = $this->fetchMapPayload($serviceability['matched_city_key'], $address, $latitude, $longitude, $radius);
        $crimePoints = $this->extractCrimePoints($mapPayload);
        $incidentSummary = $this->buildIncidentSummary($crimePoints, $radius);
        $crimeModelClass = $this->resolveCrimeModelClass($serviceability);
        $scoreReport = $this->resolveLatestScoreReport($crimeModelClass);
        $trendContext = $this->resolveTrendContext($crimeModelClass);

        return [
            'supported' => true,
            'address' => $serviceability['normalized_address'] ?? $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
            'matched_city_key' => $serviceability['matched_city_key'],
            'matched_city_name' => $serviceability['matched_city_name'],
            'crime_model_class' => $crimeModelClass,
            'map_data' => [
                'center' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
                'incidents' => $crimePoints->all(),
                'incident_count' => $crimePoints->count(),
            ],
            'incident_summary' => $incidentSummary,
            'score_report' => $scoreReport,
            'trend_context' => $trendContext,
            'preview_report' => $this->buildPreviewReportSections($serviceability, $incidentSummary, $trendContext, $scoreReport),
        ];
    }

    protected function fetchMapPayload(string $matchedCityKey, string $address, float $latitude, float $longitude, float $radius): array
    {
        $request = new Request([
            'centralLocation' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
            ],
            'radius' => $radius,
            'city' => $matchedCityKey,
        ]);

        return app(GenericMapController::class)->getRadialMapData($request)->getData(true);
    }

    protected function extractCrimePoints(array $mapPayload): Collection
    {
        return collect($mapPayload['dataPoints'] ?? [])
            ->filter(fn (array $point) => ($point['alcivartech_type'] ?? null) === 'Crime')
            ->sortByDesc(fn (array $point) => strtotime((string) ($point['alcivartech_date'] ?? '1970-01-01')))
            ->map(function (array $point) {
                return [
                    'id' => $point['data_point_id'] ?? null,
                    'latitude' => $point['latitude'] ?? null,
                    'longitude' => $point['longitude'] ?? null,
                    'date' => $point['alcivartech_date'] ?? null,
                    'category' => $this->extractIncidentCategory($point),
                    'description' => $this->extractIncidentDescription($point),
                    'location_label' => $this->extractIncidentLocationLabel($point),
                    'model' => $point['alcivartech_model'] ?? null,
                ];
            })
            ->values();
    }

    protected function buildIncidentSummary(Collection $crimePoints, float $radius): array
    {
        $topCategories = $crimePoints
            ->groupBy(fn (array $point) => $point['category'] ?: 'Uncategorized')
            ->map(fn (Collection $points, string $category) => [
                'category' => $category,
                'count' => $points->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(5)
            ->all();

        $recentIncidents = $crimePoints
            ->take(8)
            ->map(fn (array $point) => [
                'date' => $point['date'],
                'category' => $point['category'],
                'description' => $point['description'],
                'location_label' => $point['location_label'],
            ])
            ->all();

        return [
            'radius_miles' => $radius,
            'total_incidents' => $crimePoints->count(),
            'top_categories' => $topCategories,
            'recent_incidents' => $recentIncidents,
        ];
    }

    protected function resolveCrimeModelClass(array $serviceability): ?string
    {
        $cityKey = $serviceability['matched_city_key'] ?? null;
        if (!$cityKey) {
            return null;
        }

        $cityConfig = config("cities.cities.{$cityKey}", []);
        $locality = Str::lower((string) ($serviceability['parsed_address']['locality'] ?? ''));
        $localityMap = $cityConfig['serviceability']['crime_model_locality_map'] ?? [];

        if ($locality !== '' && isset($localityMap[$locality]) && class_exists($localityMap[$locality])) {
            return $localityMap[$locality];
        }

        foreach ($cityConfig['models'] ?? [] as $modelClass) {
            if (!class_exists($modelClass) || !method_exists($modelClass, 'getAlcivartechTypeForStyling')) {
                continue;
            }

            if ($modelClass::getAlcivartechTypeForStyling() === 'Crime') {
                return $modelClass;
            }
        }

        return null;
    }

    protected function resolveLatestScoreReport(?string $crimeModelClass): ?array
    {
        return app(AnalysisArtifactLocator::class)->findPreferredScoreReport($crimeModelClass);
    }

    protected function resolveTrendContext(?string $crimeModelClass): ?array
    {
        return app(AnalysisArtifactLocator::class)->findPreferredTrendContext($crimeModelClass);
    }

    protected function buildPreviewReportSections(array $serviceability, array $incidentSummary, ?array $trendContext, ?array $scoreReport): array
    {
        $address = $serviceability['normalized_address'] ?? 'this address';
        $cityName = $serviceability['matched_city_name'] ?? 'your area';
        $sections = [];
        $incidentCount = (int) ($incidentSummary['total_incidents'] ?? 0);

        $sections[] = [
            'title' => 'What happened nearby',
            'body' => $incidentCount > 0
                ? sprintf(
                    'Found %d crime incidents within %.2f miles of %s in the current preview window.',
                    $incidentCount,
                    $incidentSummary['radius_miles'],
                    $address,
                )
                : sprintf(
                    'No recent crime incidents were found within %.2f miles of %s in the current preview window.',
                    $incidentSummary['radius_miles'],
                    $address,
                ),
        ];

        if ($incidentCount > 0 && !empty($incidentSummary['top_categories'])) {
            $topCategoryText = collect($incidentSummary['top_categories'])
                ->map(fn (array $category) => "{$category['category']} ({$category['count']})")
                ->implode(', ');

            $sections[] = [
                'title' => 'Most common incident categories',
                'body' => $topCategoryText,
            ];
        }

        if (($trendContext['summary']['status'] ?? null) === 'ok') {
            $summary = $trendContext['summary'];
            $sections[] = [
                'title' => 'Trend context',
                'body' => sprintf(
                    '%s has %d significant recent findings across %d hexagons. The strongest categories right now are %s.',
                    $cityName,
                    $summary['total_findings'] ?? 0,
                    $summary['affected_h3_count'] ?? 0,
                    collect($summary['top_categories'] ?? [])->take(3)->implode(', ')
                ),
            ];
        }

        $sections[] = [
            'title' => 'Neighborhood score context',
            'body' => $this->buildScoreContextBody($scoreReport, $trendContext),
        ];

        return $sections;
    }

    protected function buildScoreContextBody(?array $scoreReport, ?array $trendContext): string
    {
        if ($scoreReport) {
            return 'A location-specific neighborhood score is available for this address and will load with the preview.';
        }

        if (($trendContext['summary']['status'] ?? null) === 'ok') {
            return 'Neighborhood scoring is not currently available for this area, but recent incidents and city-level trends are shown below.';
        }

        return 'Neighborhood scoring and city-level trend context are not currently available for this area yet. Recent incidents are shown below.';
    }

    protected function extractIncidentCategory(array $point): string
    {
        return $this->extractNestedField($point, [
            'incident_type_group',
            'incident_type',
            'offense_code_group',
            'primary_type',
            'offense_category',
            'incident_category',
            'crime',
            'offense_parent_group',
            'nibrs_crime_against_category',
        ]) ?? 'Crime incident';
    }

    protected function extractIncidentDescription(array $point): ?string
    {
        return $this->extractNestedField($point, [
            'incident_description',
            'offense_description',
            'description',
            'incident_subcategory',
            'crime_details',
            'offense',
            'location_description',
            'crime_details_concatenated',
        ]);
    }

    protected function extractIncidentLocationLabel(array $point): ?string
    {
        return $this->extractNestedField($point, [
            'incident_address',
            'block',
            'street_name',
            'street',
            'address',
            'location_description',
        ]);
    }

    protected function extractNestedField(array $point, array $candidateFields): ?string
    {
        foreach ($point as $key => $value) {
            if (!Str::endsWith((string) $key, '_data') || !is_array($value)) {
                continue;
            }

            foreach ($candidateFields as $field) {
                $fieldValue = $value[$field] ?? null;
                if (is_string($fieldValue) && trim($fieldValue) !== '') {
                    return trim($fieldValue);
                }
            }
        }

        return null;
    }
}
