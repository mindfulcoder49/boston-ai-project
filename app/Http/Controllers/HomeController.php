<?php

namespace App\Http\Controllers;

use App\Support\MetricsSnapshotStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HomeController extends Controller
{
    public const HOME_PAGE_CACHE_KEY = 'home_page_data_v4';

    /**
     * Models that are geocoding helpers, not user-facing data types.
     */
    private const EXCLUDED_MODELS = [
        \App\Models\CambridgeMasterAddress::class,
        \App\Models\CambridgeMasterIntersection::class,
    ];

    /**
     * Descriptions for data categories.
     */
    private const CATEGORY_DESCRIPTIONS = [
        'Crime' => 'Police incident reports including arrests, investigations, and offense tracking.',
        '311 Case' => 'City service requests from residents — potholes, streetlights, noise complaints, and more.',
        'Building Permit' => 'Construction and renovation permits filed with the city inspectional services.',
        'Property Violation' => 'Code enforcement actions for unsafe structures, failed inspections, and maintenance issues.',
        'Food Inspection' => 'Health department restaurant and food establishment inspection results.',
        'Construction Off Hour' => 'After-hours and weekend construction permits and noise variances.',
        'Car Crash' => 'Motor vehicle crash reports with injury and fatality data.',
    ];

    public function index(MetricsSnapshotStore $metricsSnapshotStore)
    {
        $homeData = Cache::remember(self::HOME_PAGE_CACHE_KEY, 3600, function () use ($metricsSnapshotStore) {
            $cities = config('cities.cities', []);
            $metricsData = $metricsSnapshotStore->currentPayload()['data'] ?? [];

            $cityEntries = $this->buildCityEntries($cities);
            $dataCategories = $this->buildDataCategories($cities);
            $totalRecords = collect($metricsData)->sum('totalRecords');

            return [
                'cities' => array_values($cityEntries),
                'dataCategories' => $dataCategories,
                'stats' => [
                    'totalRecords' => $totalRecords,
                    'cityCount' => count($cityEntries),
                    'dataCategoryCount' => count($dataCategories),
                ],
            ];
        });

        return Inertia::render('Home', $homeData);
    }

    private function buildCityEntries(array $cities): array
    {
        $cityEntries = [];

        $modelToDataMapKey = [];
        foreach (config('data_map.models', []) as $key => $modelClass) {
            $modelToDataMapKey[$modelClass] = $key;
        }

        foreach ($cities as $cityKey => $cityConfig) {
            $dataTypes = [];
            $dataMapKeys = [];

            foreach ($cityConfig['models'] as $modelClass) {
                if (in_array($modelClass, self::EXCLUDED_MODELS, true)) {
                    continue;
                }

                $dataType = $modelClass::getAlcivartechTypeForStyling();
                $dataMapKey = $modelToDataMapKey[$modelClass] ?? null;

                if (!in_array($dataType, $dataTypes, true)) {
                    $dataTypes[] = $dataType;
                }

                if ($dataMapKey && !in_array($dataMapKey, $dataMapKeys, true)) {
                    $dataMapKeys[] = $dataMapKey;
                }
            }

            $landingRoute = "city.landing.{$cityKey}";
            $landingUrl = Route::has($landingRoute) ? route($landingRoute) : null;
            $stateCode = $this->resolveStateCode($cityConfig);
            $mapUrl = $this->buildMapUrl($dataMapKeys);

            $cityEntries[$cityKey] = [
                'key' => $cityKey,
                'name' => $cityConfig['name'],
                'locationLabel' => $this->buildLocationLabel($cityConfig['name'], $stateCode),
                'stateCode' => $stateCode,
                'lat' => $cityConfig['latitude'],
                'lng' => $cityConfig['longitude'],
                'dataTypes' => $dataTypes,
                'dataMapKeys' => $dataMapKeys,
                'dataTypeCount' => count($dataTypes),
                'mapUrl' => $mapUrl,
                'landingUrl' => $landingUrl,
                'primaryUrl' => $landingUrl ?: $mapUrl,
                'coverageNote' => $this->buildCoverageNote($cityConfig, $stateCode),
            ];
        }

        return $cityEntries;
    }

    private function buildDataCategories(array $cities): array
    {
        $categories = [];

        foreach ($cities as $cityConfig) {
            foreach ($cityConfig['models'] as $modelClass) {
                if (in_array($modelClass, self::EXCLUDED_MODELS, true)) {
                    continue;
                }

                $type = $modelClass::getAlcivartechTypeForStyling();

                if (!isset($categories[$type])) {
                    $categories[$type] = [
                        'name' => $type,
                        'description' => self::CATEGORY_DESCRIPTIONS[$type] ?? '',
                    ];
                }
            }
        }

        return array_values($categories);
    }

    private function buildMapUrl(array $dataMapKeys): string
    {
        if (count($dataMapKeys) === 1) {
            return route('data-map.index', ['dataType' => $dataMapKeys[0]]);
        }

        if (count($dataMapKeys) > 1) {
            return route('data-map.combined') . '?types=' . implode(',', $dataMapKeys);
        }

        return route('data-map.combined');
    }

    private function resolveStateCode(array $cityConfig): ?string
    {
        $stateCode = collect($cityConfig['serviceability']['supported_regions'] ?? [])
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->first(fn ($value) => $value !== '');

        return is_string($stateCode) ? $stateCode : null;
    }

    private function buildLocationLabel(string $name, ?string $stateCode): string
    {
        if ($stateCode === null) {
            return $name;
        }

        if (Str::endsWith($name, ", {$stateCode}")) {
            return $name;
        }

        return "{$name}, {$stateCode}";
    }

    private function buildCoverageNote(array $cityConfig, ?string $stateCode): string
    {
        $name = (string) ($cityConfig['name'] ?? '');
        $serviceability = $cityConfig['serviceability'] ?? [];
        $supportedLocalities = collect($serviceability['supported_localities'] ?? [])
            ->map(fn ($locality) => trim((string) $locality))
            ->filter();

        $extraLocalities = $supportedLocalities
            ->reject(fn ($locality) => Str::lower($locality) === Str::lower($name))
            ->values();

        if ($extraLocalities->isNotEmpty()) {
            $suffix = $stateCode ? ", {$stateCode}" : '';
            $formatted = $extraLocalities->map(fn ($locality) => "{$locality}{$suffix}");

            return 'Also supports ' . $formatted->join(', ') . ' address lookups.';
        }

        if (Str::contains(Str::lower($name), 'county')) {
            return 'Regional page for countywide address checks and broader map coverage.';
        }

        if (($serviceability['crime_address_funnel_enabled'] ?? false) === false) {
            return 'City page with civic-data search tailored to what this region actually publishes.';
        }

        return 'City page for direct address search, map context, and local reports.';
    }
}
