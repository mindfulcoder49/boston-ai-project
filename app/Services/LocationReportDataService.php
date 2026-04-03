<?php

namespace App\Services;

use App\Http\Controllers\GenericMapController;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationReportDataService
{
    public function fetch(Location $location, float $radius): array
    {
        $radius = min(max($radius, 0.01), 0.50);

        $guard = Auth::guard();
        $previousUser = $guard->user();

        if ($location->user) {
            $guard->setUser($location->user);
        }

        try {
            $mapController = app(GenericMapController::class);
            $simulatedRequest = new Request([
                'centralLocation' => [
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'address' => $location->address,
                ],
                'radius' => $radius,
            ]);

            $mapDataResponse = $mapController->getRadialMapData($simulatedRequest);
            $mapData = $mapDataResponse->getData();
        } finally {
            if ($previousUser) {
                $guard->setUser($previousUser);
            } else {
                $guard->forgetUser();
            }
        }

        $dataPoints = collect($mapData->dataPoints ?? [])
            ->sortByDesc(fn (object $point) => $this->resolvePointTimestamp($point))
            ->values()
            ->all();

        foreach ($dataPoints as $dataPoint) {
            $dataPoint->alcivartech_model_class = $this->resolveModelClass((string) ($dataPoint->alcivartech_model ?? ''));
        }

        return $dataPoints;
    }

    private function resolvePointTimestamp(object $point): int
    {
        try {
            return Carbon::parse((string) ($point->alcivartech_date ?? '1970-01-01 00:00:00'))->getTimestamp();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function resolveModelClass(string $tableName): ?string
    {
        foreach ($this->reportableModelClasses() as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            if (app($modelClass)->getTable() === $tableName) {
                return $modelClass;
            }
        }

        return null;
    }

    private function reportableModelClasses(): array
    {
        $cities = config('cities.cities', []);

        return collect($cities)
            ->pluck('models')
            ->flatten()
            ->filter(fn ($modelClass) => is_string($modelClass) && class_exists($modelClass))
            ->unique()
            ->values()
            ->all();
    }
}
