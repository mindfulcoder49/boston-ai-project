<?php

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Mail\Mailer;
use App\Http\Controllers\GenericMapController;
use App\Http\Controllers\ThreeOneOneCaseController;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Report;
use Illuminate\Support\Str;
// Add all models from LINKABLE_MODELS
use App\Models\CrimeData;
use App\Models\ThreeOneOneCase;
use App\Models\BuildingPermit;
use App\Models\PropertyViolation;
use App\Models\ConstructionOffHour;
use App\Models\FoodInspection;
use App\Models\EverettCrimeData;
use App\Models\CambridgeThreeOneOneCase;
use App\Models\CambridgeBuildingPermitData;
use App\Models\CambridgeCrimeReportData;
use App\Models\CambridgeHousingViolationData;
use App\Models\CambridgeSanitaryInspectionData;
use App\Models\PersonCrashData;

class SendLocationReportEmailNoAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $location;
    protected const MAX_DAYS_INDIVIDUAL_REPORTS = 7; // Number of recent days to report individually
    protected $radiusForReport; // Store radius used for data fetching

    // Copied from GenericMapController for model resolution
    private const LINKABLE_MODELS = [
        \App\Models\CrimeData::class,
        \App\Models\ThreeOneOneCase::class,
        \App\Models\PropertyViolation::class,
        \App\Models\ConstructionOffHour::class,
        \App\Models\BuildingPermit::class,
        \App\Models\FoodInspection::class,
        \App\Models\EverettCrimeData::class,
        \App\Models\CambridgeThreeOneOneCase::class,
        \App\Models\CambridgeBuildingPermitData::class,
        \App\Models\CambridgeCrimeReportData::class,
        \App\Models\CambridgeHousingViolationData::class,
        \App\Models\CambridgeSanitaryInspectionData::class,
        \App\Models\PersonCrashData::class,
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
        $this->radiusForReport = 0.25; // Default, or could be from $location->report_radius if you add such a field
    }

    // Helper to get model class from table name
    private function resolveModelClass(string $tableName): ?string
    {
        foreach (self::LINKABLE_MODELS as $modelClass) {
            if (app($modelClass)->getTable() === $tableName) {
                return $modelClass;
            }
        }
        return null;
    }

    /**
     * Execute the job.
     */
    public function handle(Mailer $mailer)
    {
        try {
            // --- 1. Get Map Data ---
            $mapController = new GenericMapController();
            // Simulate a request object for getRadialMapData
            $simulatedRequest = new Request([
                'centralLocation' => [
                    'latitude' => $this->location->latitude,
                    'longitude' => $this->location->longitude,
                    'address' => $this->location->address,
                ],
                'radius' => $this->radiusForReport, // Use stored radius
            ]);
            $mapDataResponse = $mapController->getRadialMapData($simulatedRequest);
            $mapData = $mapDataResponse->getData();

            if (empty($mapData->dataPoints)) {
                Log::info("No map data found for location ID: {$this->location->id} ({$this->location->address})");
                return;
            }

            // --- Resolve model class for each data point ---
            foreach ($mapData->dataPoints as $dataPoint) {
                $dataPoint->alcivartech_model_class = $this->resolveModelClass($dataPoint->alcivartech_model);
            }

            // --- 2. Pre-process and Group Data Points by Date and Model Class ---
            $groupedDataByDateAndModel = [];
            $sevenDaysAgo = Carbon::now()->subDays(self::MAX_DAYS_INDIVIDUAL_REPORTS)->startOfDay();

            foreach ($mapData->dataPoints as $dataPoint) {
                // Ensure alcivartech_date and the resolved model class are present
                if (!isset($dataPoint->alcivartech_date) || !isset($dataPoint->alcivartech_model_class)) {
                    Log::warning("Skipping data point due to missing date or resolved model class", (array)$dataPoint);
                    continue;
                }

                try {
                    $itemDate = Carbon::parse($dataPoint->alcivartech_date)->startOfDay();
                } catch (\Exception $e) {
                    Log::warning("Could not parse date for data point, skipping: {$dataPoint->alcivartech_date}", (array)$dataPoint);
                    continue;
                }

                $dateKey = $itemDate->format('Y-m-d');
                $modelClass = $dataPoint->alcivartech_model_class;

                if ($itemDate->gte($sevenDaysAgo)) {
                    // Data is within the last 7 days
                    if (!isset($groupedDataByDateAndModel[$dateKey])) {
                        $groupedDataByDateAndModel[$dateKey] = [];
                    }
                    if (!isset($groupedDataByDateAndModel[$dateKey][$modelClass])) {
                        $groupedDataByDateAndModel[$dateKey][$modelClass] = [];
                    }
                    $groupedDataByDateAndModel[$dateKey][$modelClass][] = $dataPoint;
                } else {
                    // Data is older than 7 days
                    if (!isset($groupedDataByDateAndModel['older'])) {
                        $groupedDataByDateAndModel['older'] = [];
                    }
                    if (!isset($groupedDataByDateAndModel['older'][$modelClass])) {
                        $groupedDataByDateAndModel['older'][$modelClass] = [];
                    }
                    $groupedDataByDateAndModel['older'][$modelClass][] = $dataPoint;
                }
            }

            // Sort the date keys so the report flows from most recent to oldest
            uksort($groupedDataByDateAndModel, function ($a, $b) {
                if ($a === 'older') return 1; // 'older' always comes last
                if ($b === 'older') return -1;
                return strtotime($b) - strtotime($a); // Sort by date descending
            });

            // --- 2.5. Enrich 311 Data with Live API Call ---
            $threeOneOneController = new ThreeOneOneCaseController();
            $threeOneOneModelClass = ThreeOneOneCase::class;
            $threeOneOneDataObjectKey = Str::snake(class_basename($threeOneOneModelClass)) . '_data';

            foreach ($groupedDataByDateAndModel as $dateOrOlderKey => &$modelsOnDate) { // Use reference
                if (isset($modelsOnDate[$threeOneOneModelClass]) && !empty($modelsOnDate[$threeOneOneModelClass])) {
                    $serviceRequestIds = [];
                    foreach ($modelsOnDate[$threeOneOneModelClass] as $dataPoint) {
                        $threeOneOneData = $dataPoint->{$threeOneOneDataObjectKey} ?? null;
                        if ($threeOneOneData && isset($threeOneOneData->service_request_id) && !empty($threeOneOneData->service_request_id)) {
                            $serviceRequestIds[] = $threeOneOneData->service_request_id;
                        }
                    }
                    $serviceRequestIds = array_unique($serviceRequestIds); // Ensure unique IDs

                    if (!empty($serviceRequestIds)) {
                        Log::info("Attempting to fetch live 311 data for location {$this->location->id}, date/group '{$dateOrOlderKey}', IDs: " . implode(', ', $serviceRequestIds));
                        try {
                            $idFetchingRequest = new Request();
                            $idFetchingRequest->merge(['service_request_ids' => $serviceRequestIds]);

                            $liveDataResponse = $threeOneOneController->getMultiple($idFetchingRequest);

                            if ($liveDataResponse->getStatusCode() === 200) {
                                $liveCasesData = json_decode($liveDataResponse->getContent(), false); // false for object output
                                $liveCases = $liveCasesData->data ?? $liveCasesData;

                                if (is_array($liveCases) && !empty($liveCases)) {
                                    $liveCasesMap = [];
                                    foreach ($liveCases as $liveCase) {
                                        if (isset($liveCase->service_request_id)) {
                                            $liveCasesMap[$liveCase->service_request_id] = $liveCase;
                                        }
                                    }

                                    $enrichedDataPoints = [];
                                    foreach ($modelsOnDate[$threeOneOneModelClass] as $originalDataPoint) {
                                        $original311Data = $originalDataPoint->{$threeOneOneDataObjectKey} ?? null;
                                        if ($original311Data && isset($original311Data->service_request_id) && isset($liveCasesMap[$original311Data->service_request_id])) {
                                            $liveVersion = $liveCasesMap[$original311Data->service_request_id];
                                            // Merge live data into the nested data object
                                            $originalDataPoint->{$threeOneOneDataObjectKey} = (object) array_merge((array) $original311Data, (array) $liveVersion);
                                            $enrichedDataPoints[] = $originalDataPoint;
                                            Log::info("Enriched 311 case {$original311Data->service_request_id} for location {$this->location->id}");
                                        } else {
                                            $enrichedDataPoints[] = $originalDataPoint; // Keep original if no live data found
                                        }
                                    }
                                    $modelsOnDate[$threeOneOneModelClass] = $enrichedDataPoints; // Update the array
                                } else {
                                    Log::info("No live 311 cases returned or empty array for location {$this->location->id}, date/group '{$dateOrOlderKey}'. IDs: " . implode(', ', $serviceRequestIds));
                                }
                            } else {
                                Log::warning("Failed to fetch live 311 data for location {$this->location->id}, date/group '{$dateOrOlderKey}'. Status: " . $liveDataResponse->getStatusCode() . " IDs: " . implode(', ', $serviceRequestIds));
                            }
                        } catch (\Exception $e) {
                            Log::error("Error fetching or merging live 311 data for location {$this->location->id}, date/group '{$dateOrOlderKey}': " . $e->getMessage() . " IDs: " . implode(', ', $serviceRequestIds));
                        }
                    }
                }
            }
            unset($modelsOnDate); // Unset reference after loop


            // --- 3. Generate Reports for Each Date and Type Group ---
            $dailyCombinedReports = []; // To store the markdown for each day's report

            foreach ($groupedDataByDateAndModel as $dateOrOlderKey => $modelsOnDate) {
                $dateReportParts = []; // Reports for this specific date/older_group

                $displayDate = ($dateOrOlderKey === 'older')
                    ? "Older than " . self::MAX_DAYS_INDIVIDUAL_REPORTS . " days"
                    : Carbon::parse($dateOrOlderKey)->locale($this->location->language)->isoFormat('LL');

                $dateReportParts[] = "### " . $displayDate . "\n"; // Date heading

                foreach ($modelsOnDate as $modelClass => $dataPointsForModel) {
                    if (empty($dataPointsForModel)) {
                        continue;
                    }

                    if (!$modelClass || !method_exists($modelClass, 'getPopupConfig')) {
                        Log::warning("Cannot generate raw report section because model class '{$modelClass}' is not valid or does not have getPopupConfig method.", ['location_id' => $this->location->id]);
                        continue;
                    }

                    $type = $modelClass::getHumanName();
                    $popupConfig = $modelClass::getPopupConfig();
                    $mainIdField = $popupConfig['mainIdentifierField'] ?? null;
                    $mainIdLabel = $popupConfig['mainIdentifierLabel'] ?? 'ID';
                    $descField = $popupConfig['descriptionField'] ?? null;
                    $descLabel = $popupConfig['descriptionLabel'] ?? 'Description';
                    $additionalFields = $popupConfig['additionalFields'] ?? [];
                    $dataObjectKey = Str::snake(class_basename($modelClass)) . '_data';

                    $itemsMarkdown = [];
                    foreach ($dataPointsForModel as $item) {
                        $data = $item->{$dataObjectKey} ?? null;
                        if (!$data) {
                            continue;
                        }

                        $itemParts = [];
                        
                        if ($mainIdField && isset($data->{$mainIdField})) {
                            $itemParts[] = "- **{$mainIdLabel}:** {$data->{$mainIdField}}";
                        } else {
                            $itemParts[] = "- Record";
                        }

                        if ($descField && isset($data->{$descField}) && !empty(trim($data->{$descField}))) {
                            $itemParts[] = "  - **{$descLabel}:** {$data->{$descField}}";
                        }

                        foreach ($additionalFields as $field) {
                            $key = $field['key'] ?? null;
                            $label = $field['label'] ?? 'Info';
                            if ($key && isset($data->{$key}) && !empty(trim((string)$data->{$key}))) {
                                $itemParts[] = "  - **{$label}:** {$data->{$key}}";
                            }
                        }
                        $itemsMarkdown[] = implode("\n", $itemParts);
                    }

                    $individualReport = implode("\n\n", $itemsMarkdown);

                    if (!empty($individualReport)) {
                        $dateReportParts[] = "#### $type\n" . $individualReport . "\n";
                    }
                }

                // Only add this date's section if it has actual report content
                if (count($dateReportParts) > 1) { // Greater than 1 because we add the date heading
                    $dailyCombinedReports[] = implode("\n", $dateReportParts);
                }
            }

            // --- 4. Combine All Daily Reports into a Single String ---
            $dailyReportContent = implode("\n---\n\n", $dailyCombinedReports); // Separate daily sections with a horizontal rule

            // --- 4.5 Prepend Location Details to the Final Report ---
            $locationDetailsHeader = "## Location Report: {$this->location->name_or_address}\n\n";
            if ($this->location->name && $this->location->name !== $this->location->address) {
                $locationDetailsHeader .= "- **Location Name:** {$this->location->name}\n";
            }
            $locationDetailsHeader .= "- **Address:** {$this->location->address}\n";
            $locationDetailsHeader .= "- **Coordinates:** Latitude {$this->location->latitude}, Longitude {$this->location->longitude}\n";
            $locationDetailsHeader .= "- **Radius Covered:** {$this->radiusForReport} miles\n";
            $locationDetailsHeader .= "- **Report Language:** {$this->location->language}\n";
            $locationDetailsHeader .= "- **Report Generated:** " . Carbon::now()->locale($this->location->language)->isoFormat('LLLL') . "\n\n";
            $locationDetailsHeader .= "---\n\n";
            
            $finalReport = $locationDetailsHeader . $dailyReportContent;


            // Log the generated report details
            if ($this->location->user && $this->location->user->subscription('default')) {
                 Log::info("Generated raw report for user: {$this->location->user->email} with subscription ID: {$this->location->user->subscription('default')->stripe_id} for location: {$this->location->address}");
            } else {
                Log::warning("Could not log full user/subscription details for location ID: {$this->location->id}. User or subscription missing.");
            }


            // --- 5. Save Report to Database (New Step) ---
            if (!empty($finalReport) && $this->location->user) { // Check $finalReport, not $dailyReportContent
                try {
                    $reportDateForTitle = Carbon::now()->format('Y-m-d');
                    Report::create([
                        'user_id' => $this->location->user_id,
                        'location_id' => $this->location->id,
                        'title' => "Location Report (Raw) for {$this->location->name_or_address} - {$reportDateForTitle}",
                        'content' => $finalReport, // Save the full report with header
                        'generated_at' => Carbon::now(),
                    ]);
                    Log::info("Raw report saved to database for user: {$this->location->user->email}, location: {$this->location->address}");
                } catch (\Exception $dbException) {
                    Log::error("Failed to save raw report to database for user: {$this->location->user->email}, location: {$this->location->address}. Error: {$dbException->getMessage()}");
                }
            }


            // --- 6. Send Email (if there's a report to send)---
            if (!empty($dailyReportContent)) { // Check if there was actual daily content, not just the header
                $mailer->to($this->location->user->email)
                       ->send(new \App\Mail\SendLocationReport($this->location, $finalReport)); // Send full report
                Log::info("Raw report email sent to user: {$this->location->user->email} for location: {$this->location->address}");
            } else {
                Log::info("No raw reports generated after date/type processing (empty dailyReportContent). No email was sent to {$this->location->user->email} for location: {$this->location->address}");
            }

        } catch (\Exception $e) {
            Log::error("Error processing raw report or sending email for location {$this->location->address}: {$e->getMessage()}");
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
