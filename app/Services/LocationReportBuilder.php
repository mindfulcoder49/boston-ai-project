<?php

namespace App\Services;

use App\Http\Controllers\ThreeOneOneCaseController;
use App\Models\Location;
use App\Models\ThreeOneOneCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocationReportBuilder
{
    private const MAX_DAYS_INDIVIDUAL_REPORTS = 7;

    public function __construct(
        private readonly LocationReportSectionGenerator $sectionGenerator,
        private readonly LocationReportDataService $dataService
    ) {}

    public function build(Location $location, float $radius = 0.25): array
    {
        $radius = min(max($radius, 0.01), 0.50);
        $dataPoints = $this->dataService->fetch($location, $radius);

        $locationDetailsHeader = $this->buildLocationDetailsHeader($location, $radius);

        if (empty($dataPoints)) {
            return [
                'final_report' => $locationDetailsHeader,
                'daily_report_content' => '',
                'location_details_header' => $locationDetailsHeader,
                'data_points_count' => 0,
                'section_diagnostics' => [],
            ];
        }

        $groupedDataByDateAndModel = $this->groupDataPointsByDateAndModel($dataPoints);
        $this->enrichThreeOneOneData($location, $groupedDataByDateAndModel);

        $dailyCombinedReports = [];
        $sectionDiagnostics = [];

        foreach ($groupedDataByDateAndModel as $dateOrOlderKey => $modelsOnDate) {
            $displayDate = $this->displayDateForKey($dateOrOlderKey, (string) ($location->language ?: 'en'));
            $dateReportParts = ["### {$displayDate}\n"];

            foreach ($modelsOnDate as $modelClass => $dataPointsForModel) {
                if (empty($dataPointsForModel) || !is_string($modelClass) || !class_exists($modelClass)) {
                    continue;
                }

                $type = method_exists($modelClass, 'getHumanName')
                    ? $modelClass::getHumanName()
                    : class_basename($modelClass);

                $promptType = $dateOrOlderKey === 'older'
                    ? "{$type} (Older Events)"
                    : "{$type} (Events from {$displayDate})";

                $individualReport = $this->sectionGenerator->generate(
                    $promptType,
                    $dataPointsForModel,
                    (string) ($location->language ?: 'en')
                );

                $sectionDiagnostics[] = [
                    'date_key' => $dateOrOlderKey,
                    'display_date' => $displayDate,
                    'type' => $type,
                    'model_class' => $modelClass,
                    'record_count' => count($dataPointsForModel),
                    'generated' => !$this->shouldSkipSection($individualReport),
                ];

                if ($this->shouldSkipSection($individualReport)) {
                    Log::warning("Report section generation issue for email: {$individualReport}", [
                        'location_id' => $location->id,
                        'type_context' => $promptType,
                        'language' => $location->language,
                    ]);
                    continue;
                }

                $cleanedSection = $this->stripLeadingHeadings($individualReport);
                if ($cleanedSection === '') {
                    continue;
                }

                $dateReportParts[] = "#### {$type}\n{$cleanedSection}\n";
            }

            if (count($dateReportParts) > 1) {
                $dailyCombinedReports[] = implode("\n", $dateReportParts);
            }
        }

        $dailyReportContent = implode("\n---\n\n", $dailyCombinedReports);

        return [
            'final_report' => $locationDetailsHeader . $dailyReportContent,
            'daily_report_content' => $dailyReportContent,
            'location_details_header' => $locationDetailsHeader,
            'data_points_count' => count($dataPoints),
            'section_diagnostics' => $sectionDiagnostics,
        ];
    }

    private function groupDataPointsByDateAndModel(array $dataPoints): array
    {
        $groupedDataByDateAndModel = [];
        $sevenDaysAgo = Carbon::now()->subDays(self::MAX_DAYS_INDIVIDUAL_REPORTS)->startOfDay();

        foreach ($dataPoints as $dataPoint) {
            if (!isset($dataPoint->alcivartech_date) || !isset($dataPoint->alcivartech_model_class)) {
                Log::warning('Skipping data point due to missing date or resolved model class', (array) $dataPoint);
                continue;
            }

            try {
                $itemDate = Carbon::parse($dataPoint->alcivartech_date)->startOfDay();
            } catch (\Exception) {
                Log::warning("Could not parse date for data point, skipping: {$dataPoint->alcivartech_date}", (array) $dataPoint);
                continue;
            }

            $dateKey = $itemDate->gte($sevenDaysAgo)
                ? $itemDate->format('Y-m-d')
                : 'older';

            $groupedDataByDateAndModel[$dateKey][$dataPoint->alcivartech_model_class][] = $dataPoint;
        }

        uksort($groupedDataByDateAndModel, function ($a, $b) {
            if ($a === 'older') {
                return 1;
            }

            if ($b === 'older') {
                return -1;
            }

            return strtotime($b) <=> strtotime($a);
        });

        return $groupedDataByDateAndModel;
    }

    private function enrichThreeOneOneData(Location $location, array &$groupedDataByDateAndModel): void
    {
        $threeOneOneController = new ThreeOneOneCaseController();
        $threeOneOneModelClass = ThreeOneOneCase::class;
        $threeOneOneDataObjectKey = Str::snake(class_basename($threeOneOneModelClass)) . '_data';

        foreach ($groupedDataByDateAndModel as $dateOrOlderKey => &$modelsOnDate) {
            if (!isset($modelsOnDate[$threeOneOneModelClass]) || empty($modelsOnDate[$threeOneOneModelClass])) {
                continue;
            }

            $serviceRequestIds = [];
            foreach ($modelsOnDate[$threeOneOneModelClass] as $dataPoint) {
                $threeOneOneData = $dataPoint->{$threeOneOneDataObjectKey} ?? null;
                if ($threeOneOneData && isset($threeOneOneData->service_request_id) && !empty($threeOneOneData->service_request_id)) {
                    $serviceRequestIds[] = $threeOneOneData->service_request_id;
                }
            }

            $serviceRequestIds = array_values(array_unique($serviceRequestIds));
            if (empty($serviceRequestIds)) {
                continue;
            }

            try {
                $idFetchingRequest = new Request([
                    'service_request_ids' => $serviceRequestIds,
                ]);

                $liveDataResponse = $threeOneOneController->getMultiple($idFetchingRequest);
                if ($liveDataResponse->getStatusCode() !== 200) {
                    Log::warning("Failed to fetch live 311 data for location {$location->id}, date/group '{$dateOrOlderKey}'. Status: " . $liveDataResponse->getStatusCode(), [
                        'service_request_ids' => $serviceRequestIds,
                    ]);
                    continue;
                }

                $liveCasesData = json_decode($liveDataResponse->getContent(), false);
                $liveCases = $liveCasesData->data ?? $liveCasesData;
                if (!is_array($liveCases) || empty($liveCases)) {
                    continue;
                }

                $liveCasesMap = [];
                foreach ($liveCases as $liveCase) {
                    if (isset($liveCase->service_request_id)) {
                        $liveCasesMap[$liveCase->service_request_id] = $liveCase;
                    }
                }

                foreach ($modelsOnDate[$threeOneOneModelClass] as $dataPoint) {
                    $original311Data = $dataPoint->{$threeOneOneDataObjectKey} ?? null;
                    $serviceRequestId = $original311Data->service_request_id ?? null;

                    if (!$serviceRequestId || !isset($liveCasesMap[$serviceRequestId])) {
                        continue;
                    }

                    $dataPoint->{$threeOneOneDataObjectKey} = (object) array_merge(
                        (array) $original311Data,
                        (array) $liveCasesMap[$serviceRequestId]
                    );
                }
            } catch (\Throwable $e) {
                Log::error("Error fetching or merging live 311 data for location {$location->id}, date/group '{$dateOrOlderKey}': {$e->getMessage()}", [
                    'service_request_ids' => $serviceRequestIds,
                ]);
            }
        }

        unset($modelsOnDate);
    }

    private function shouldSkipSection(string $sectionContent): bool
    {
        return $sectionContent === 'No report generated.'
            || $sectionContent === 'Report content generation was blocked due to safety settings.'
            || str_starts_with($sectionContent, 'Error generating report section for');
    }

    private function stripLeadingHeadings(string $markdown): string
    {
        return trim((string) preg_replace('/\A(?:\s*#{1,6}[^\n]*\n)+/u', '', trim($markdown)));
    }

    private function buildLocationDetailsHeader(Location $location, float $radius): string
    {
        $locationLabel = $this->locationLabel($location);

        $header = "## Location Report: {$locationLabel}\n\n";

        if ($location->name && $location->name !== $location->address) {
            $header .= "- **Location Name:** {$location->name}\n";
        }

        $header .= "- **Address:** {$location->address}\n";
        $header .= "- **Coordinates:** Latitude {$location->latitude}, Longitude {$location->longitude}\n";
        $header .= "- **Radius Covered:** {$radius} miles\n";
        $header .= "- **Report Language:** {$location->language}\n";
        $header .= '- **Report Generated:** ' . Carbon::now()->locale((string) ($location->language ?: 'en'))->isoFormat('LLLL') . "\n\n";
        $header .= "---\n\n";

        return $header;
    }

    private function displayDateForKey(string $dateKey, string $language): string
    {
        if ($dateKey === 'older') {
            return 'Older than ' . self::MAX_DAYS_INDIVIDUAL_REPORTS . ' days';
        }

        return Carbon::parse($dateKey)->locale($language)->isoFormat('LL');
    }

    private function locationLabel(Location $location): string
    {
        $name = trim((string) ($location->name ?? ''));

        return $name !== '' ? $name : (string) $location->address;
    }
}
