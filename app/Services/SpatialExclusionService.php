<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class SpatialExclusionService
{
    public function exclusionsForModel(string $modelClass): array
    {
        $config = config('spatial_exclusions', []);

        return $config[$modelClass] ?? [];
    }

    public function hasExclusions(string $modelClass): bool
    {
        return !empty($this->exclusionsForModel($modelClass));
    }

    public function isExcludedCoordinate(string $modelClass, mixed $latitude, mixed $longitude): bool
    {
        $targetKey = $this->normalizeCoordinateKey($latitude, $longitude);

        if ($targetKey === null) {
            return false;
        }

        foreach ($this->exclusionsForModel($modelClass) as $exclusion) {
            $candidateKey = $this->normalizeCoordinateKey(
                $exclusion['latitude'] ?? null,
                $exclusion['longitude'] ?? null,
            );

            if ($candidateKey !== null && $candidateKey === $targetKey) {
                return true;
            }
        }

        return false;
    }

    public function applyToQuery(EloquentBuilder|QueryBuilder $query, string $modelClass, ?string $tableName = null): void
    {
        $exclusions = $this->exclusionsForModel($modelClass);
        if (empty($exclusions)) {
            return;
        }

        $model = new $modelClass();
        $latColumn = ($tableName ?: $model->getTable()) . '.' . $modelClass::getLatitudeField();
        $lonColumn = ($tableName ?: $model->getTable()) . '.' . $modelClass::getLongitudeField();

        foreach ($exclusions as $exclusion) {
            $latitude = $exclusion['latitude'] ?? null;
            $longitude = $exclusion['longitude'] ?? null;

            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                continue;
            }

            $query->where(function ($builder) use ($latColumn, $lonColumn, $latitude, $longitude) {
                $builder
                    ->whereNull($latColumn)
                    ->orWhereNull($lonColumn)
                    ->orWhere($latColumn, '!=', $latitude)
                    ->orWhere($lonColumn, '!=', $longitude);
            });
        }
    }

    protected function normalizeCoordinateKey(mixed $latitude, mixed $longitude): ?string
    {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        return number_format((float) $latitude, 7, '.', '') . ',' . number_format((float) $longitude, 7, '.', '');
    }
}
