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
use App\Http\Controllers\ThreeOneOneCaseController; // Added import
use App\Http\Controllers\AiAssistantController; // Added import
use Illuminate\Http\Request;
// GuzzleHttp imports are no longer needed here if AiAssistantController handles its own client
// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\RequestException;
// use GuzzleHttp\Exception\ClientException;
use Carbon\Carbon; // Import Carbon for date manipulation
use App\Models\Report; // Added import

class SendLocationReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $location;
    protected const MAX_DAYS_INDIVIDUAL_REPORTS = 7; // Number of recent days to report individually
    protected $radiusForReport; // Store radius used for data fetching

    /**
     * Create a new job instance.
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
        $this->radiusForReport = 0.25; // Default, or could be from $location->report_radius if you add such a field
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
                // 'language_codes' => [$this->location->language] // If getRadialMapData uses this
            ]);
            $mapDataResponse = $mapController->getRadialMapData($simulatedRequest);
            $mapData = $mapDataResponse->getData();

            if (empty($mapData->dataPoints)) {
                Log::info("No map data found for location ID: {$this->location->id} ({$this->location->address})");
                return;
            }

            // --- 2. Pre-process and Group Data Points by Date and Type ---
            // This is the core new data structure:
            // [
            //   'YYYY-MM-DD' => [  // Date string as key
            //     'Crime' => [dataPoint1, dataPoint2, ...],
            //     '311 Case' => [dataPoint3, dataPoint4, ...],
            //     ...
            //   ],
            //   'older' => [ // Special key for data older than MAX_DAYS_INDIVIDUAL_REPORTS
            //     'Crime' => [...],
            //     ...
            //   ]
            // ]
            $groupedDataByDateAndType = [];
            $sevenDaysAgo = Carbon::now()->subDays(self::MAX_DAYS_INDIVIDUAL_REPORTS)->startOfDay();

            foreach ($mapData->dataPoints as $dataPoint) {
                // Ensure alcivartech_date and alcivartech_type are present
                if (!isset($dataPoint->alcivartech_date) || !isset($dataPoint->alcivartech_type)) {
                    Log::warning("Skipping data point due to missing date or type", (array)$dataPoint);
                    continue;
                }

                try {
                    $itemDate = Carbon::parse($dataPoint->alcivartech_date)->startOfDay();
                } catch (\Exception $e) {
                    Log::warning("Could not parse date for data point, skipping: {$dataPoint->alcivartech_date}", (array)$dataPoint);
                    continue;
                }

                $dateKey = $itemDate->format('Y-m-d');
                $typeKey = $dataPoint->alcivartech_type;

                if ($itemDate->gte($sevenDaysAgo)) {
                    // Data is within the last 7 days
                    if (!isset($groupedDataByDateAndType[$dateKey])) {
                        $groupedDataByDateAndType[$dateKey] = [];
                    }
                    if (!isset($groupedDataByDateAndType[$dateKey][$typeKey])) {
                        $groupedDataByDateAndType[$dateKey][$typeKey] = [];
                    }
                    $groupedDataByDateAndType[$dateKey][$typeKey][] = $dataPoint;
                } else {
                    // Data is older than 7 days
                    if (!isset($groupedDataByDateAndType['older'])) {
                        $groupedDataByDateAndType['older'] = [];
                    }
                    if (!isset($groupedDataByDateAndType['older'][$typeKey])) {
                        $groupedDataByDateAndType['older'][$typeKey] = [];
                    }
                    $groupedDataByDateAndType['older'][$typeKey][] = $dataPoint;
                }
            }

            // Sort the date keys so the report flows from most recent to oldest
            uksort($groupedDataByDateAndType, function ($a, $b) {
                if ($a === 'older') return 1; // 'older' always comes last
                if ($b === 'older') return -1;
                return strtotime($b) - strtotime($a); // Sort by date descending
            });

            // --- 2.5. Enrich 311 Data with Live API Call ---
            $threeOneOneController = new ThreeOneOneCaseController();
            foreach ($groupedDataByDateAndType as $dateOrOlderKey => &$typesOnDate) { // Use reference for $typesOnDate
                if (isset($typesOnDate['311 Case']) && !empty($typesOnDate['311 Case'])) {
                    $serviceRequestIds = [];
                    foreach ($typesOnDate['311 Case'] as $dataPoint) {
                        if (isset($dataPoint->service_request_id) && !empty($dataPoint->service_request_id)) {
                            $serviceRequestIds[] = $dataPoint->service_request_id;
                        }
                    }
                    $serviceRequestIds = array_unique($serviceRequestIds); // Ensure unique IDs

                    if (!empty($serviceRequestIds)) {
                        Log::info("Attempting to fetch live 311 data for location {$this->location->id}, date/group '{$dateOrOlderKey}', IDs: " . implode(', ', $serviceRequestIds));
                        try {
                            // Prepare a request object for getMultiple.
                            // Assumes getMultiple expects 'service_request_ids' in the request input.
                            $idFetchingRequest = new Request();
                            $idFetchingRequest->merge(['service_request_ids' => $serviceRequestIds]);

                            $liveDataResponse = $threeOneOneController->getMultiple($idFetchingRequest);

                            if ($liveDataResponse->getStatusCode() === 200) {
                                $liveCasesData = json_decode($liveDataResponse->getContent(), false); // false for object output

                                // Check if response is a Laravel Resource (e.g., has a 'data' property)
                                $liveCases = $liveCasesData->data ?? $liveCasesData;


                                if (is_array($liveCases) && !empty($liveCases)) {
                                    $liveCasesMap = [];
                                    foreach ($liveCases as $liveCase) {
                                        if (isset($liveCase->service_request_id)) {
                                            $liveCasesMap[$liveCase->service_request_id] = $liveCase;
                                        }
                                    }

                                    $enrichedDataPoints = [];
                                    foreach ($typesOnDate['311 Case'] as $originalDataPoint) {
                                        if (isset($originalDataPoint->service_request_id) && isset($liveCasesMap[$originalDataPoint->service_request_id])) {
                                            $liveVersion = $liveCasesMap[$originalDataPoint->service_request_id];
                                            // Merge: live data supplements/overrides historical.
                                            $merged = (object) array_merge((array) $originalDataPoint, (array) $liveVersion);
                                            $enrichedDataPoints[] = $merged;
                                            Log::info("Enriched 311 case {$originalDataPoint->service_request_id} for location {$this->location->id}");
                                        } else {
                                            $enrichedDataPoints[] = $originalDataPoint; // Keep original if no live data found
                                        }
                                    }
                                    $typesOnDate['311 Case'] = $enrichedDataPoints; // Update the array
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
            unset($typesOnDate); // Unset reference after loop


            // --- 3. Generate Reports for Each Date and Type Group ---
            $dailyCombinedReports = []; // To store the markdown for each day's report

            foreach ($groupedDataByDateAndType as $dateOrOlderKey => $typesOnDate) {
                $dateReportParts = []; // Reports for this specific date/older_group

                // Determine the display date for the report section header
                $displayDate = ($dateOrOlderKey === 'older')
                    ? "Older than " . self::MAX_DAYS_INDIVIDUAL_REPORTS . " days"
                    : Carbon::parse($dateOrOlderKey)->locale($this->location->language)->isoFormat('LL'); // e.g., "May 12, 2025" (localized)

                $dateReportParts[] = "### " . $displayDate . "\n"; // Date heading

                foreach ($typesOnDate as $type => $dataPointsForTypeAndDate) {
                    if (empty($dataPointsForTypeAndDate)) {
                        continue; // Skip if no data for this type on this date
                    }

                    // Pass the date context to the prompt if needed, or just the type
                    $promptType = ($dateOrOlderKey === 'older') ? "$type (Older Events)" : "$type (Events from $displayDate)";
                    // Call the static method from AiAssistantController
                    $individualReport = AiAssistantController::generateReportSection(
                        $promptType,
                        $dataPointsForTypeAndDate,
                        $this->location->language
                    );

                    if ($individualReport && $individualReport !== 'No report generated.' && $individualReport !== 'Report content generation was blocked due to safety settings.' && !str_starts_with($individualReport, "Error generating report section for")) {
                        // Prepend the type to the individual report if not already included by Gemini
                        // (Gemini prompt asks for a report on $type, so it might already be there)
                        // For clarity, we can add it:
                        $dateReportParts[] = "#### $type\n" . $individualReport . "\n";
                    } else if ($individualReport === 'Report content generation was blocked due to safety settings.' || str_starts_with($individualReport, "Error generating report section for")) {
                        // Log the issue but don't add it to the user-facing report
                        Log::warning("Report section generation issue for email: {$individualReport}", [
                            'location_id' => $this->location->id,
                            'type_context' => $promptType,
                            'language' => $this->location->language
                        ]);
                         // Optionally add a generic placeholder to the report if needed
                        // $dateReportParts[] = "#### $type\n_A report section for $type could not be generated at this time._\n";
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
                 Log::info("Generated report for user: {$this->location->user->email} with subscription ID: {$this->location->user->subscription('default')->stripe_id} for location: {$this->location->address}");
                 Log::info("User Info: " . json_encode($this->location->user->toArray()));
                 Log::info("Subscription Info: " . json_encode($this->location->user->subscription('default')->toArray()));
            } else {
                Log::warning("Could not log full user/subscription details for location ID: {$this->location->id}. User or subscription missing.");
            }


            // --- 5. Save Report to Database (New Step) ---
            if (!empty($finalReport) && $this->location->user) { // Check $finalReport, not $dailyReportContent
                try {
                    $reportDateForTitle = Carbon::now()->format('Y-m-d'); // Or use a date derived from the report content if more appropriate
                    Report::create([
                        'user_id' => $this->location->user_id,
                        'location_id' => $this->location->id,
                        'title' => "Location Report for {$this->location->name_or_address} - {$reportDateForTitle}",
                        'content' => $finalReport, // Save the full report with header
                        'generated_at' => Carbon::now(),
                    ]);
                    Log::info("Report saved to database for user: {$this->location->user->email}, location: {$this->location->address}");
                } catch (\Exception $dbException) {
                    Log::error("Failed to save report to database for user: {$this->location->user->email}, location: {$this->location->address}. Error: {$dbException->getMessage()}");
                    // Decide if you want to proceed with email if DB save fails. For now, it continues.
                }
            }


            // --- 6. Send Email (if there's a report to send)---
            if (!empty($dailyReportContent)) { // Check if there was actual daily content, not just the header
                $mailer->to($this->location->user->email)
                       ->send(new \App\Mail\SendLocationReport($this->location, $finalReport)); // Send full report
                Log::info("Report email sent to user: {$this->location->user->email} for location: {$this->location->address}");
            } else {
                Log::info("No reports generated after date/type processing (empty dailyReportContent). No email was sent to {$this->location->user->email} for location: {$this->location->address}");
            }

        } catch (\Exception $e) {
            Log::error("Error processing report or sending email for location {$this->location->address}: {$e->getMessage()}");
            Log::error("Stack trace: " . $e->getTraceAsString()); // More detailed stack trace
            // Optionally rethrow if you want the job to be marked as failed and potentially retried
            // throw $e;
        }
    }
}