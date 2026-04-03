<?php

namespace App\Services;

use App\Models\Location;
use Carbon\Carbon;

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

    public function __construct(
        private readonly LocationReportDataService $dataService
    ) {}

    public function build(Location $location, float $radius = 0.25, int $days = 2, int $limit = 4): array
    {
        $radius = min(max($radius, 0.01), 0.50);
        $days = min(max($days, 1), 7);
        $limit = min(max($limit, 1), 6);

        $dataPoints = $this->dataService->fetch($location, $radius);
        $windowStart = Carbon::now()->subDays($days - 1)->startOfDay();
        $windowEnd = Carbon::now()->endOfDay();

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
        $markers = [
            [
                'label' => 'H',
                'kind' => 'home',
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'title' => $this->locationLabel($location),
            ],
        ];

        $incidents = [];
        foreach ($selectedPoints as $index => $row) {
            $label = (string) ($index + 1);
            $summary = $this->summarizeDataPoint($row['point']);

            $incident = [
                'label' => $label,
                'type' => (string) ($row['point']->alcivartech_type ?? 'Record'),
                'headline' => $summary['headline'],
                'address' => $summary['address'],
                'status' => $summary['status'],
                'identifier' => $summary['identifier'],
                'date' => $row['date']->toIso8601String(),
                'display_date' => $row['date']->isoFormat('LLL'),
                'distance_miles' => round($row['distance_miles'], 3),
                'latitude' => $row['coordinates']['latitude'],
                'longitude' => $row['coordinates']['longitude'],
            ];

            $incidents[] = $incident;
            $markers[] = [
                'label' => $label,
                'kind' => 'incident',
                'latitude' => $incident['latitude'],
                'longitude' => $incident['longitude'],
                'title' => $incident['headline'],
            ];
        }

        krsort($countsByDate);

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'location' => [
                'id' => $location->getKey(),
                'label' => $this->locationLabel($location),
                'address' => (string) ($location->address ?? ''),
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
            ],
            'radius_miles' => round($radius, 2),
            'window' => [
                'days' => $days,
                'start' => $windowStart->toIso8601String(),
                'end' => $windowEnd->toIso8601String(),
                'display' => $windowStart->toDateString() === $windowEnd->toDateString()
                    ? $windowStart->isoFormat('LL')
                    : $windowStart->isoFormat('LL') . ' to ' . $windowEnd->isoFormat('LL'),
            ],
            'selection_policy' => sprintf(
                'Showing up to %d incidents from the last %d day(s), ranked by newest first and nearest to home when dates tie.',
                $limit,
                $days
            ),
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
    }

    private function summarizeDataPoint(mixed $dataPoint): array
    {
        $normalized = $this->normalizeDataPoint($dataPoint);

        return [
            'headline' => $this->firstNonEmptyValue($normalized, self::TITLE_FIELDS)
                ?? (string) ($normalized['alcivartech_type'] ?? 'Record'),
            'address' => $this->firstNonEmptyValue($normalized, self::ADDRESS_FIELDS),
            'status' => $this->firstNonEmptyValue($normalized, self::STATUS_FIELDS),
            'identifier' => $this->firstNonEmptyValue($normalized, self::IDENTIFIER_FIELDS),
        ];
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
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
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

    private function locationLabel(Location $location): string
    {
        $name = trim((string) ($location->name ?? ''));

        return $name !== '' ? $name : (string) $location->address;
    }
}
