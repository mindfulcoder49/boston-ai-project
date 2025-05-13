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
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use Carbon\Carbon; // Import Carbon for date manipulation

class SendLocationReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $location;
    protected const MAX_DAYS_INDIVIDUAL_REPORTS = 7; // Number of recent days to report individually

    /**
     * Create a new job instance.
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
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
                'radius' => 0.25, // Default radius, or could be configurable per location
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
                    : Carbon::parse($dateOrOlderKey)->isoFormat('LL'); // e.g., "May 12, 2025" (localized)

                $dateReportParts[] = "### " . $displayDate . "\n"; // Date heading

                foreach ($typesOnDate as $type => $dataPointsForTypeAndDate) {
                    if (empty($dataPointsForTypeAndDate)) {
                        continue; // Skip if no data for this type on this date
                    }

                    // Pass the date context to the prompt if needed, or just the type
                    $promptType = ($dateOrOlderKey === 'older') ? "$type (Older Events)" : "$type (Events from $displayDate)";
                    $individualReport = $this->generateReportForType($promptType, $dataPointsForTypeAndDate);

                    if ($individualReport && $individualReport !== 'No report generated.') {
                        // Prepend the type to the individual report if not already included by Gemini
                        // (Gemini prompt asks for a report on $type, so it might already be there)
                        // For clarity, we can add it:
                        $dateReportParts[] = "#### $type\n" . $individualReport . "\n";
                    }
                }

                // Only add this date's section if it has actual report content
                if (count($dateReportParts) > 1) { // Greater than 1 because we add the date heading
                    $dailyCombinedReports[] = implode("\n", $dateReportParts);
                }
            }

            // --- 4. Combine All Daily Reports into a Single String ---
            $finalReport = implode("\n---\n\n", $dailyCombinedReports); // Separate daily sections with a horizontal rule

            // Log the generated report details
            if ($this->location->user && $this->location->user->subscription('default')) {
                 Log::info("Generated report for user: {$this->location->user->email} with subscription ID: {$this->location->user->subscription('default')->stripe_id} for location: {$this->location->address}");
                 Log::info("User Info: " . json_encode($this->location->user->toArray()));
                 Log::info("Subscription Info: " . json_encode($this->location->user->subscription('default')->toArray()));
            } else {
                Log::warning("Could not log full user/subscription details for location ID: {$this->location->id}. User or subscription missing.");
            }


            // --- 5. Send Email (if there's a report to send)---
            if (!empty($finalReport)) {
                $mailer->to($this->location->user->email)
                       ->send(new \App\Mail\SendLocationReport($this->location, $finalReport));
                Log::info("Report email sent to user: {$this->location->user->email} for location: {$this->location->address}");
            } else {
                Log::info("No reports generated after date/type processing. No email was sent to {$this->location->user->email} for location: {$this->location->address}");
            }

        } catch (\Exception $e) {
            Log::error("Error processing report or sending email for location {$this->location->address}: {$e->getMessage()}");
            Log::error("Stack trace: " . $e->getTraceAsString()); // More detailed stack trace
            // Optionally rethrow if you want the job to be marked as failed and potentially retried
            // throw $e;
        }
    }

    /**
     * Generates a report for a specific type of data points using Gemini.
     *
     * @param string $typeContext A string describing the type and potentially the date context (e.g., "Crime (Events from May 12, 2025)")
     * @param array $dataPoints Array of data point objects for this specific type and date.
     * @return string The generated report snippet, or 'No report generated.'
     */
    private function generateReportForType(string $typeContext, array $dataPoints): string
    {
        if (empty($dataPoints)) {
            return 'No report generated.'; // Should not happen if called correctly, but good safeguard
        }

        $apiKey = config('services.gemini.api_key');
        //$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-04-17:generateContent?key=$apiKey";
        $client = new Client();

        $contents = [];
        // Add each data point as a separate user message part for Gemini to process
        foreach ($dataPoints as $dataPoint) {
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => json_encode($dataPoint)], // Send the raw data point
                ],
            ];
        }

        // Add the system prompt as the final user message
        $contents[] = [
            'role' => 'user', // Treat system prompt as a user instruction in this context
            'parts' => [
                // Pass the specific type and date context to the system prompt
                ['text' => $this->getSystemPromptForContext($typeContext)],
            ],
        ];

        $requestBody = [
            'contents' => $contents,
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 800, // Adjusted based on expected length per section
            ]
        ];

        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $requestBody,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($responseBody['candidates'][0]['content']['parts'][0]['text']);
            } elseif (isset($responseBody['promptFeedback']['blockReason'])) {
                 Log::warning("Gemini content generation blocked.", [
                    'reason' => $responseBody['promptFeedback']['blockReason'],
                    'safetyRatings' => $responseBody['promptFeedback']['safetyRatings'] ?? [],
                    'typeContext' => $typeContext
                ]);
                return 'Report content generation was blocked due to safety settings.';
            }
            Log::warning("No text content found in Gemini response for type: $typeContext", ['responseBody' => $responseBody]);
            return 'No report generated.';

        } catch (RequestException | ClientException $e) {
            Log::error("Guzzle Exception during Gemini call for type: $typeContext: " . $e->getMessage());
            if ($e->hasResponse()) {
                Log::error("Gemini Response Body: " . $e->getResponse()->getBody()->getContents());
            }
            // Don't throw here to allow other report parts to process, return an error message instead
            return "Error generating report section for $typeContext.";
        } catch (\Exception $e) {
            Log::error("Error generating report section for $typeContext: " . $e->getMessage());
            // Don't throw here
            return "Error generating report section for $typeContext.";
        }
    }

    /**
     * Generates the system prompt for Gemini, customized for the data type and context.
     *
     * @param string $typeContext Description of the data (e.g., "Crime events from May 12, 2025")
     * @return string
     */
    private function getSystemPromptForContext(string $typeContext): string
    {
        $basePrompt = "You are a helpful assistant. Generate a narrative summary in markdown format for the provided city operations data. ";
        $languageInstruction = "The report MUST be entirely in **{$this->location->language}**. ";
        $typeInstruction = "This section is specifically about: **{$typeContext}**. ";
        $focusInstruction = "Focus ONLY on the data points provided in this current conversation turn for this specific section. ";
        $formattingInstruction = "Summarize the incidents. Be factual and do not speculate. Do NOT include any disclaimers, introductory, or concluding remarks for THIS INDIVIDUAL SECTION. Keep it brief and to the point for this section.";
        $importanceInstruction = "It is of UTMOST IMPORTANCE that the report section is in the requested language: **{$this->location->language}**. Ignoring this will be detrimental.";

        return $basePrompt . $languageInstruction . $typeInstruction . $focusInstruction . $formattingInstruction . $importanceInstruction;
    }
}