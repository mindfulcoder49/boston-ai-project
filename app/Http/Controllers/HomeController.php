<?php

namespace App\Http\Controllers;

use App\Support\MetricsSnapshotStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    public const HOME_PAGE_CACHE_KEY = 'home_page_data_v3';

    /**
     * Models that are geocoding helpers, not user-facing data types.
     */
    private const EXCLUDED_MODELS = [
        \App\Models\CambridgeMasterAddress::class,
        \App\Models\CambridgeMasterIntersection::class,
    ];

    /**
     * Mapping of model classes to their display area (city/region).
     * Boston's config includes Cambridge and Everett models, so we
     * split them into separate areas for the homepage.
     */
    private const MODEL_AREA_MAP = [
        \App\Models\CrimeData::class => 'Boston',
        \App\Models\ThreeOneOneCase::class => 'Boston',
        \App\Models\PropertyViolation::class => 'Boston',
        \App\Models\ConstructionOffHour::class => 'Boston',
        \App\Models\BuildingPermit::class => 'Boston',
        \App\Models\FoodInspection::class => 'Boston',
        \App\Models\PersonCrashData::class => 'Boston',
        \App\Models\EverettCrimeData::class => 'Everett',
        \App\Models\CambridgeThreeOneOneCase::class => 'Cambridge',
        \App\Models\CambridgeBuildingPermitData::class => 'Cambridge',
        \App\Models\CambridgeCrimeReportData::class => 'Cambridge',
        \App\Models\CambridgeHousingViolationData::class => 'Cambridge',
        \App\Models\CambridgeSanitaryInspectionData::class => 'Cambridge',
    ];

    /**
     * Map center coordinates for each display area.
     */
    private const AREA_COORDINATES = [
        'Boston' => ['lat' => 42.3601, 'lng' => -71.0589],
        'Cambridge' => ['lat' => 42.3736, 'lng' => -71.1097],
        'Everett' => ['lat' => 42.4084, 'lng' => -71.0537],
        'Chicago' => ['lat' => 41.8781, 'lng' => -87.6298],
        'San Francisco' => ['lat' => 37.7749, 'lng' => -122.4194],
        'Seattle' => ['lat' => 47.6062, 'lng' => -122.3321],
        'Montgomery County, MD' => ['lat' => 39.154, 'lng' => -77.24],
    ];

    /**
     * Map display areas to city landing routes where they exist.
     */
    private const AREA_LANDING_ROUTES = [
        'Boston' => 'city.landing.boston',
        'Everett' => 'city.landing.everett',
        'Chicago' => 'city.landing.chicago',
        'San Francisco' => 'city.landing.san_francisco',
        'New York' => 'city.landing.new_york',
        'Montgomery County, MD' => 'city.landing.montgomery_county_md',
        'Seattle' => 'city.landing.seattle',
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

            $areas = $this->buildAreas($cities);
            $dataCategories = $this->buildDataCategories($cities);
            $totalRecords = collect($metricsData)->sum('totalRecords');

            return [
                'cities' => array_values($areas),
                'dataCategories' => $dataCategories,
                'stats' => [
                    'totalRecords' => $totalRecords,
                    'cityCount' => count($areas),
                    'dataCategoryCount' => count($dataCategories),
                ],
            ];
        });

        return Inertia::render('Home', $homeData);
    }

    private function buildAreas(array $cities): array
    {
        $areas = [];

        // Build reverse lookup: model class -> data_map key
        $modelToDataMapKey = [];
        foreach (config('data_map.models', []) as $key => $modelClass) {
            $modelToDataMapKey[$modelClass] = $key;
        }

        foreach ($cities as $cityConfig) {
            foreach ($cityConfig['models'] as $modelClass) {
                if (in_array($modelClass, self::EXCLUDED_MODELS)) {
                    continue;
                }

                $areaName = self::MODEL_AREA_MAP[$modelClass] ?? $cityConfig['name'];
                $dataType = $modelClass::getAlcivartechTypeForStyling();
                $dataMapKey = $modelToDataMapKey[$modelClass] ?? null;

                if (!isset($areas[$areaName])) {
                    $coords = self::AREA_COORDINATES[$areaName] ?? [
                        'lat' => $cityConfig['latitude'],
                        'lng' => $cityConfig['longitude'],
                    ];
                    $areas[$areaName] = [
                        'name' => $areaName,
                        'lat' => $coords['lat'],
                        'lng' => $coords['lng'],
                        'dataTypes' => [],
                        'dataMapKeys' => [],
                    ];
                }

                if (!in_array($dataType, $areas[$areaName]['dataTypes'])) {
                    $areas[$areaName]['dataTypes'][] = $dataType;
                }
                if ($dataMapKey && !in_array($dataMapKey, $areas[$areaName]['dataMapKeys'])) {
                    $areas[$areaName]['dataMapKeys'][] = $dataMapKey;
                }
            }
        }

        // Add dataTypeCount and mapUrl for convenience
        foreach ($areas as &$area) {
            $area['dataTypeCount'] = count($area['dataTypes']);

            if (count($area['dataMapKeys']) === 1) {
                $area['mapUrl'] = route('data-map.index', ['dataType' => $area['dataMapKeys'][0]]);
            } elseif (count($area['dataMapKeys']) > 1) {
                $area['mapUrl'] = route('data-map.combined') . '?types=' . implode(',', $area['dataMapKeys']);
            } else {
                $area['mapUrl'] = route('data-map.combined');
            }

            $landingRoute = self::AREA_LANDING_ROUTES[$area['name']] ?? null;
            $area['landingUrl'] = ($landingRoute && Route::has($landingRoute))
                ? route($landingRoute)
                : null;
            $area['primaryUrl'] = $area['landingUrl'] ?: $area['mapUrl'];
        }

        return $areas;
    }

    private function buildDataCategories(array $cities): array
    {
        $categories = [];

        foreach ($cities as $cityConfig) {
            foreach ($cityConfig['models'] as $modelClass) {
                if (in_array($modelClass, self::EXCLUDED_MODELS)) {
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
}
