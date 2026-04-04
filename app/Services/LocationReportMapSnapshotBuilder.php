<?php

namespace App\Services;

use App\Models\Location;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class LocationReportMapSnapshotBuilder
{
    private const TITLE_FIELDS = [
        'service_name',
        'incident_type',
        'primary_type',
        'offense_description',
        'description',
        'category',
        'permit_type',
        'location_description',
    ];

    private const ADDRESS_FIELDS = [
        'incident_address',
        'address',
        'block',
        'location_description',
        'street_name',
        'street',
    ];

    private const STATUS_FIELDS = [
        'status',
        'closure_reason',
        'resolution_description',
        'case_status',
        'current_status',
    ];

    private const IDENTIFIER_FIELDS = [
        'service_request_id',
        'case_number',
        'incident_number',
        'permit_number',
        'permitnumber',
        'id',
    ];

    private const HOME_MARKER = [
        'shape' => 'home',
        'fill_color' => '#0F766E',
        'stroke_color' => '#FFFFFF',
        'text_color' => '#FFFFFF',
        'line_color' => 'rgba(15, 118, 110, 0.40)',
        'category_key' => 'home',
        'category_label' => 'Home',
    ];

    public function __construct(
        private readonly LocationReportDataService $dataService
    ) {}

    public function build(Location $location, float $radius = 0.25, int $days = 2, int $limit = 4): array
    {
        $radius = $this->sanitizeRadius($radius);
        $days = min(max($days, 1), 7);
        $limit = $this->sanitizeLimit($limit);
        $dataPoints = $this->dataService->fetch($location, $radius);
        $reference = $this->nowInReportTimezone();

        return $this->buildFromDataPoints(
            $location,
            $dataPoints,
            $radius,
            $reference->copy()->subDays($days - 1)->startOfDay(),
            $reference->copy()->endOfDay(),
            $limit
        );
    }

    public function buildForDate(Location $location, float $radius, CarbonInterface|string $date, int $limit = 8): array
    {
        $radius = $this->sanitizeRadius($radius);
        $limit = $this->sanitizeLimit($limit);
        $dataPoints = $this->dataService->fetch($location, $radius);
        $date = $date instanceof CarbonInterface
            ? Carbon::instance($date)->setTimezone($this->reportTimezone())
            : Carbon::parse((string) $date, $this->reportTimezone())->setTimezone($this->reportTimezone());

        return $this->buildFromDataPoints(
            $location,
            $dataPoints,
            $radius,
            $date->copy()->startOfDay(),
            $date->copy()->endOfDay(),
            $limit
        );
    }

    public function buildDailySeries(Location $location, float $radius = 0.25, int $days = 7, int $limit = 8): array
    {
        $radius = $this->sanitizeRadius($radius);
        $days = min(max($days, 1), 7);
        $limit = $this->sanitizeLimit($limit);
        $dataPoints = $this->dataService->fetch($location, $radius);
        $anchorDate = $this->latestIncidentDate($dataPoints) ?? $this->nowInReportTimezone();

        $snapshots = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $anchorDate->copy()->subDays($offset);
            $snapshots[] = $this->buildFromDataPoints(
                $location,
                $dataPoints,
                $radius,
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
                $limit
            );
        }

        return $snapshots;
    }

    private function buildFromDataPoints(
        Location $location,
        array $dataPoints,
        float $radius,
        CarbonInterface $windowStart,
        CarbonInterface $windowEnd,
        int $limit
    ): array {
        $rankedPoints = [];
        $countsByDate = [];

        foreach ($dataPoints as $dataPoint) {
            $date = $this->extractDate($dataPoint);
            if (!$date || $date->lt($windowStart) || $date->gt($windowEnd)) {
                continue;
            }

            $coordinates = $this->extractCoordinates($dataPoint);
            if ($coordinates === null) {
                continue;
            }

            $normalized = $this->normalizeDataPoint($dataPoint);
            $distanceMiles = $this->distanceMiles(
                (float) $location->latitude,
                (float) $location->longitude,
                $coordinates['latitude'],
                $coordinates['longitude']
            );

            $countsByDate[$date->toDateString()] = ($countsByDate[$date->toDateString()] ?? 0) + 1;

            $rankedPoints[] = [
                'point' => $dataPoint,
                'date' => $date,
                'distance_miles' => $distanceMiles,
                'coordinates' => $coordinates,
                'summary' => $this->summarizeDataPoint($normalized),
                'style' => $this->resolveVisualStyle($dataPoint, $normalized),
            ];
        }

        usort($rankedPoints, function (array $left, array $right): int {
            $dateComparison = $right['date']->getTimestamp() <=> $left['date']->getTimestamp();
            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            $distanceComparison = $left['distance_miles'] <=> $right['distance_miles'];
            if ($distanceComparison !== 0) {
                return $distanceComparison;
            }

            return strcmp(
                (string) ($left['point']->alcivartech_type ?? ''),
                (string) ($right['point']->alcivartech_type ?? '')
            );
        });

        $selectedPoints = array_slice($rankedPoints, 0, $limit);
        $markers = [[
            'label' => 'H',
            'kind' => 'home',
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'title' => $this->locationLabel($location),
            ...self::HOME_MARKER,
        ]];

        $incidents = [];

        foreach ($selectedPoints as $index => $row) {
            $label = (string) ($index + 1);
            $summary = $row['summary'];
            $style = $row['style'];

            $incident = [
                'label' => $label,
                'type' => (string) ($row['point']->alcivartech_type ?? 'Record'),
                'headline' => $summary['headline'],
                'address' => $summary['address'],
                'status' => $summary['status'],
                'identifier' => $summary['identifier'],
                'date' => $row['date']->toIso8601String(),
                'date_key' => $row['date']->toDateString(),
                'display_date' => $row['date']->isoFormat('LLL'),
                'distance_miles' => round($row['distance_miles'], 3),
                'latitude' => $row['coordinates']['latitude'],
                'longitude' => $row['coordinates']['longitude'],
                ...$style,
            ];

            $incidents[] = $incident;
            $markers[] = [
                'label' => $label,
                'kind' => 'incident',
                'latitude' => $incident['latitude'],
                'longitude' => $incident['longitude'],
                'title' => $incident['headline'],
                ...$style,
            ];
        }

        krsort($countsByDate);

        $snapshot = [
            'render_version' => (string) config('services.reports.email_map_cache_version', 'daily-v2'),
            'generated_at' => $this->nowInReportTimezone()->toIso8601String(),
            'location' => [
                'id' => $location->getKey(),
                'label' => $this->locationLabel($location),
                'address' => (string) ($location->address ?? ''),
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
            ],
            'radius_miles' => round($radius, 2),
            'window' => [
                'days' => $windowStart->copy()->startOfDay()->diffInDays($windowEnd->copy()->startOfDay()) + 1,
                'date' => $windowStart->toDateString() === $windowEnd->toDateString()
                    ? $windowStart->toDateString()
                    : null,
                'start' => $windowStart->toIso8601String(),
                'end' => $windowEnd->toIso8601String(),
                'display' => $windowStart->toDateString() === $windowEnd->toDateString()
                    ? $windowStart->isoFormat('LL')
                    : $windowStart->isoFormat('LL') . ' to ' . $windowEnd->isoFormat('LL'),
            ],
            'selection_policy' => $this->selectionPolicy($windowStart, $windowEnd, $limit),
            'total_points_in_radius' => count($dataPoints),
            'recent_points_in_window' => count($rankedPoints),
            'selected_points' => count($selectedPoints),
            'omitted_points' => max(count($rankedPoints) - count($selectedPoints), 0),
            'counts_by_date' => collect($countsByDate)
                ->map(fn (int $count, string $date) => ['date' => $date, 'count' => $count])
                ->values()
                ->all(),
            'markers' => $markers,
            'incidents' => $incidents,
            'empty' => empty($selectedPoints),
        ];

        $snapshot['render_fingerprint'] = $this->renderFingerprint($snapshot);

        return $snapshot;
    }

    private function selectionPolicy(CarbonInterface $windowStart, CarbonInterface $windowEnd, int $limit): string
    {
        if ($windowStart->toDateString() === $windowEnd->toDateString()) {
            return sprintf(
                'Showing up to %d incidents for %s, ranked by newest first and nearest to home when times tie.',
                $limit,
                $windowStart->isoFormat('LL')
            );
        }

        return sprintf(
            'Showing up to %d incidents from %s to %s, ranked by newest first and nearest to home when dates tie.',
            $limit,
            $windowStart->isoFormat('LL'),
            $windowEnd->isoFormat('LL')
        );
    }

    private function summarizeDataPoint(array $normalized): array
    {
        return [
            'headline' => $this->firstNonEmptyValue($normalized, self::TITLE_FIELDS)
                ?? (string) ($normalized['alcivartech_type'] ?? 'Record'),
            'address' => $this->firstNonEmptyValue($normalized, self::ADDRESS_FIELDS),
            'status' => $this->firstNonEmptyValue($normalized, self::STATUS_FIELDS),
            'identifier' => $this->firstNonEmptyValue($normalized, self::IDENTIFIER_FIELDS),
        ];
    }

    private function resolveVisualStyle(mixed $dataPoint, array $normalized): array
    {
        $modelHints = strtolower(implode(' ', array_filter([
            is_string($normalized['alcivartech_type'] ?? null) ? $normalized['alcivartech_type'] : null,
            is_string($normalized['alcivartech_model'] ?? null) ? $normalized['alcivartech_model'] : null,
            is_string($normalized['alcivartech_model_class'] ?? null) ? $normalized['alcivartech_model_class'] : null,
            is_object($dataPoint) && isset($dataPoint->alcivartech_type) ? (string) $dataPoint->alcivartech_type : null,
            is_object($dataPoint) && isset($dataPoint->alcivartech_model) ? (string) $dataPoint->alcivartech_model : null,
            is_object($dataPoint) && isset($dataPoint->alcivartech_model_class) ? class_basename((string) $dataPoint->alcivartech_model_class) : null,
        ])));

        $styleKey = 'other';

        if ($this->containsAny($modelHints, ['311', 'threeoneone', 'service request', 'service_request', 'newyork311'])) {
            $styleKey = 'service-request';
        } elseif ($this->containsAny($modelHints, ['crime', 'offense', 'larceny', 'assault', 'robbery'])) {
            $styleKey = 'crime';
        } elseif ($this->containsAny($modelHints, ['foodinspection', 'food inspection', 'sanitaryinspection', 'sanitary inspection'])) {
            $styleKey = 'food-inspection';
        } elseif ($this->containsAny($modelHints, ['permit', 'buildingpermit'])) {
            $styleKey = 'permit';
        } elseif ($this->containsAny($modelHints, ['housingviolation', 'propertyviolation', 'property violation', 'housing violation'])) {
            $styleKey = 'property-violation';
        } elseif ($this->containsAny($modelHints, ['constructionoffhour', 'construction off hour', 'off-hour', 'off hour'])) {
            $styleKey = 'construction-off-hour';
        } elseif ($this->containsAny($modelHints, ['crash', 'collision'])) {
            $styleKey = 'crash';
        }

        return match ($styleKey) {
            'crime' => [
                'category_key' => 'crime',
                'category_label' => 'Crime',
                'shape' => 'circle',
                'fill_color' => '#B42318',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(180, 35, 24, 0.38)',
            ],
            'service-request' => [
                'category_key' => 'service-request',
                'category_label' => '311',
                'shape' => 'rounded-square',
                'fill_color' => '#2563EB',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(37, 99, 235, 0.34)',
            ],
            'food-inspection' => [
                'category_key' => 'food-inspection',
                'category_label' => 'Food Inspection',
                'shape' => 'diamond',
                'fill_color' => '#D97706',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(217, 119, 6, 0.34)',
            ],
            'permit' => [
                'category_key' => 'permit',
                'category_label' => 'Permit',
                'shape' => 'square',
                'fill_color' => '#15803D',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(21, 128, 61, 0.32)',
            ],
            'property-violation' => [
                'category_key' => 'property-violation',
                'category_label' => 'Property Violation',
                'shape' => 'pill',
                'fill_color' => '#7C3AED',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(124, 58, 237, 0.28)',
            ],
            'construction-off-hour' => [
                'category_key' => 'construction-off-hour',
                'category_label' => 'Construction Off Hour',
                'shape' => 'bevel',
                'fill_color' => '#0F766E',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(15, 118, 110, 0.30)',
            ],
            'crash' => [
                'category_key' => 'crash',
                'category_label' => 'Crash',
                'shape' => 'tag',
                'fill_color' => '#C2410C',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(194, 65, 12, 0.32)',
            ],
            default => [
                'category_key' => 'other',
                'category_label' => 'Other',
                'shape' => 'rounded-square',
                'fill_color' => '#475569',
                'stroke_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
                'line_color' => 'rgba(71, 85, 105, 0.30)',
            ],
        };
    }

    private function renderFingerprint(array $snapshot): string
    {
        $markers = array_map(function (array $marker): array {
            return [
                'label' => $marker['label'] ?? null,
                'kind' => $marker['kind'] ?? null,
                'shape' => $marker['shape'] ?? null,
                'fill_color' => $marker['fill_color'] ?? null,
                'stroke_color' => $marker['stroke_color'] ?? null,
                'text_color' => $marker['text_color'] ?? null,
                'line_color' => $marker['line_color'] ?? null,
                'latitude' => isset($marker['latitude']) ? round((float) $marker['latitude'], 6) : null,
                'longitude' => isset($marker['longitude']) ? round((float) $marker['longitude'], 6) : null,
            ];
        }, $snapshot['markers'] ?? []);

        return sha1(json_encode([
            'version' => $snapshot['render_version'] ?? 'daily-v2',
            'location' => [
                'latitude' => round((float) ($snapshot['location']['latitude'] ?? 0), 6),
                'longitude' => round((float) ($snapshot['location']['longitude'] ?? 0), 6),
            ],
            'markers' => $markers,
        ], JSON_UNESCAPED_SLASHES));
    }

    private function normalizeDataPoint(mixed $dataPoint): array
    {
        if (is_array($dataPoint)) {
            $dataPoint = (object) $dataPoint;
        }

        if (!is_object($dataPoint)) {
            return [];
        }

        $normalized = [];

        foreach ((array) $dataPoint as $key => $value) {
            if (!is_string($key) || str_ends_with($key, '_json')) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $normalized[$key] = $value;
                continue;
            }

            if ((is_array($value) || is_object($value)) && str_ends_with($key, '_data')) {
                foreach ((array) $value as $nestedKey => $nestedValue) {
                    if (!is_string($nestedKey) || array_key_exists($nestedKey, $normalized)) {
                        continue;
                    }

                    if (is_scalar($nestedValue) || $nestedValue === null) {
                        $normalized[$nestedKey] = $nestedValue;
                    }
                }
            }
        }

        return $normalized;
    }

    private function firstNonEmptyValue(array $normalized, array $fields): ?string
    {
        foreach ($fields as $field) {
            $value = $normalized[$field] ?? null;
            if (!is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    private function extractDate(mixed $dataPoint): ?Carbon
    {
        $value = null;

        if (is_object($dataPoint)) {
            $value = $dataPoint->alcivartech_date ?? null;
        } elseif (is_array($dataPoint)) {
            $value = $dataPoint['alcivartech_date'] ?? null;
        }

        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value, $this->reportTimezone())->setTimezone($this->reportTimezone());
        } catch (\Throwable) {
            return null;
        }
    }

    private function latestIncidentDate(array $dataPoints): ?Carbon
    {
        $latest = null;

        foreach ($dataPoints as $dataPoint) {
            $date = $this->extractDate($dataPoint);
            if ($date === null) {
                continue;
            }

            if ($latest === null || $date->greaterThan($latest)) {
                $latest = $date;
            }
        }

        return $latest?->copy();
    }

    private function nowInReportTimezone(): Carbon
    {
        return Carbon::now($this->reportTimezone());
    }

    private function reportTimezone(): string
    {
        return (string) config('services.reports.timezone', config('backend_admin.daily_pipeline.timezone', config('app.timezone', 'UTC')));
    }

    private function extractCoordinates(mixed $dataPoint): ?array
    {
        if (is_array($dataPoint)) {
            $dataPoint = (object) $dataPoint;
        }

        if (!is_object($dataPoint)) {
            return null;
        }

        $latitude = isset($dataPoint->latitude) ? (float) $dataPoint->latitude : null;
        $longitude = isset($dataPoint->longitude) ? (float) $dataPoint->longitude : null;

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    private function distanceMiles(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMiles = 3958.7613;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2)
            + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadiusMiles;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeRadius(float $radius): float
    {
        return min(max($radius, 0.01), 0.50);
    }

    private function sanitizeLimit(int $limit): int
    {
        return min(max($limit, 1), 12);
    }

    private function locationLabel(Location $location): string
    {
        $name = trim((string) ($location->name ?? ''));

        return $name !== '' ? $name : (string) $location->address;
    }
}
