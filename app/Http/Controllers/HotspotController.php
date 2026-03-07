<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\HotspotFinding;

class HotspotController extends Controller
{
    private static array $CITY_PREFIXES = [
        'Cambridge'          => 'Cambridge',
        'Everett'            => 'Everett',
        'Chicago'            => 'Chicago',
        'SanFrancisco'       => 'San Francisco',
        'Seattle'            => 'Seattle',
        'MontgomeryCountyMd' => 'Montgomery County MD',
    ];

    private static array $CITY_CENTERS = [
        'Boston'               => [42.3601, -71.0589],
        'Cambridge'            => [42.3736, -71.1097],
        'Everett'              => [42.4034, -71.0537],
        'Chicago'              => [41.8781, -87.6298],
        'San Francisco'        => [37.7749, -122.4194],
        'Seattle'              => [47.6062, -122.3321],
        'Montgomery County MD' => [39.1547, -77.2405],
    ];

    public function index()
    {
        $cities = $this->getAvailableCities();
        if (empty($cities)) {
            return Inertia::render('Hotspots/Show', [
                'city'                 => null,
                'cities'               => [],
                'hotspotsByResolution' => [],
                'cityCenter'           => [42.3601, -71.0589],
            ]);
        }
        return redirect()->route('hotspots.show', ['citySlug' => $cities[0]['slug']]);
    }

    public function show(string $citySlug)
    {
        $cities    = $this->getAvailableCities();
        $cityEntry = collect($cities)->firstWhere('slug', $citySlug);

        if (!$cityEntry) {
            abort(404, 'City not found or has no trend data.');
        }

        return Inertia::render('Hotspots/Show', [
            'city'                 => $cityEntry,
            'cities'               => $cities,
            'hotspotsByResolution' => $this->buildHotspots($cityEntry['name']),
            'cityCenter'           => self::$CITY_CENTERS[$cityEntry['name']] ?? [42.3601, -71.0589],
        ]);
    }

    // ---------- helpers ----------

    private function inferCity(string $modelClass): string
    {
        $base = class_basename($modelClass);
        foreach (self::$CITY_PREFIXES as $prefix => $city) {
            if (str_starts_with($base, $prefix)) {
                return $city;
            }
        }
        return 'Boston';
    }

    private function getAvailableCities(): array
    {
        $seen   = [];
        $cities = [];

        foreach (HotspotFinding::select('model_class')->distinct()->get() as $row) {
            if (!class_exists($row->model_class)) continue;
            $name = $this->inferCity($row->model_class);
            if (!isset($seen[$name])) {
                $seen[$name] = true;
                $cities[] = ['name' => $name, 'slug' => Str::slug($name)];
            }
        }

        usort($cities, fn($a, $b) =>
            $a['name'] === 'Boston' ? -1 : ($b['name'] === 'Boston' ? 1 : strcmp($a['name'], $b['name']))
        );

        return $cities;
    }

    private function buildHotspots(string $cityName): array
    {
        $findings = HotspotFinding::all()->filter(
            fn($f) => class_exists($f->model_class) && $this->inferCity($f->model_class) === $cityName
        );

        if ($findings->isEmpty()) {
            return [];
        }

        $hexagons = [];

        foreach ($findings as $f) {
            $resolution = $f->h3_resolution;
            $h3Index    = $f->h3_index;

            $label = class_exists($f->model_class)
                ? $f->model_class::getHumanName() . ' — ' . Str::of($f->column_name)->replace('_', ' ')->title()
                : $f->model_class . ' — ' . $f->column_name;

            if (!isset($hexagons[$resolution][$h3Index])) {
                $hexagons[$resolution][$h3Index] = [
                    'h3_index'      => $h3Index,
                    'report_count'  => 0,
                    'anomaly_count' => 0,
                    'trend_count'   => 0,
                    'reports'       => [],
                ];
            }

            $hexagons[$resolution][$h3Index]['report_count']++;
            $hexagons[$resolution][$h3Index]['anomaly_count'] += $f->anomaly_count;
            $hexagons[$resolution][$h3Index]['trend_count']   += $f->trend_count;
            $hexagons[$resolution][$h3Index]['reports'][]      = [
                'job_id'        => $f->job_id,
                'label'         => $label,
                'anomalies'     => $f->anomaly_count,
                'trends'        => $f->trend_count,
                'top_anomalies' => $f->top_anomalies ?? [],
                'top_trends'    => $f->top_trends    ?? [],
            ];
        }

        $result = [];

        foreach ($hexagons as $resolution => $hexMap) {
            foreach ($hexMap as &$h) {
                usort($h['reports'], fn($a, $b) =>
                    ($b['anomalies'] + $b['trends']) - ($a['anomalies'] + $a['trends'])
                );
            }
            unset($h);

            uasort($hexMap, fn($a, $b) =>
                $b['report_count'] !== $a['report_count']
                    ? $b['report_count'] - $a['report_count']
                    : ($b['anomaly_count'] + $b['trend_count']) - ($a['anomaly_count'] + $a['trend_count'])
            );

            $result[$resolution] = array_slice(array_values($hexMap), 0, 100);
        }

        ksort($result);
        return $result;
    }
}
