<?php

namespace App\Jobs;

use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Mail; // REMOVE THIS LINE
use Illuminate\Contracts\Mail\Mailer; // Import the Mailer contract
use App\Http\Controllers\GenericMapController;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;


class SendLocationReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $location;

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
            $request = new Request([
                'centralLocation' => [
                    'latitude' => $this->location->latitude,
                    'longitude' => $this->location->longitude,
                    'address' => $this->location->address,
                ],
                'radius' => 0.25,
            ]);
            $mapDataResponse = $mapController->getRadialMapData($request);
            $mapData = $mapDataResponse->getData();

            if (empty($mapData->dataPoints)) {
                Log::info("No map data found for location: {$this->location->address}");
                return;
            }

            // --- 2. Group Data Points by Type ---
            $groupedDataPoints = [];
            foreach ($mapData->dataPoints as $dataPoint) {
                $type = $dataPoint->alcivartech_type;  // Use the human-readable type.
                if (!isset($groupedDataPoints[$type])) {
                    $groupedDataPoints[$type] = [];
                }
                $groupedDataPoints[$type][] = $dataPoint;
            }

            // --- 3. Generate Reports for Each Group ---
            $reports = [];
            foreach ($groupedDataPoints as $type => $dataPoints) {
                $report = $this->generateReportForType($type, $dataPoints);
                if ($report !== 'No report generated.') { // Only add non-empty reports
                   $reports[$type] = $report;
                }
            }
            // --- 4. Combine Reports into a Single String ---
            $combinedReport = '';
            foreach ($reports as $type => $report) {
                $combinedReport .= "## $type Report:\n\n$report\n\n"; // Add a heading for each type.
            }

            // --- 5. Send Email (if there's a report to send)---
            if (!empty($combinedReport)) {
                $mailer->to($this->location->user->email)->send(new \App\Mail\SendLocationReport($this->location, $combinedReport));
                Log::info("Report email sent to user: {$this->location->user->email} for location: {$this->location->address}");
            } else {
               Log::info("No reports generated. No email was sent to {$this->location->user->email}");
            }

        } catch (\Exception $e) {
            Log::error("Error processing report or sending email for location {$this->location->address}: {$e->getMessage()}");
            Log::error("Error in file: " . $e->getFile() . " on line: " . $e->getLine());
            throw $e;
        }
    }

    private function generateReportForType(string $type, array $dataPoints): string
    {
        $apiKey = config('services.gemini.api_key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";
        $client = new Client();

        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $this->getSystemPrompt($type)], // Pass type to system prompt
                ],
            ],
        ];

        foreach ($dataPoints as $dataPoint) {
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => json_encode($dataPoint)],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => "Generate a summary report for the $type data provided above."],
            ],
        ];

        $requestBody = [
            'contents' => $contents,
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 1024,
            ]
        ];

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestBody,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            $report = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? 'No report generated.';
            return $report;

        } catch (RequestException $e) {
            Log::error("Guzzle Request Exception: " . $e->getMessage());
            if ($e->hasResponse()) {
                Log::error("Response Body: " . $e->getResponse()->getBody()->getContents());
            }
            throw $e;
        } catch (ClientException $e) {
            Log::error("Guzzle Client Exception: " . $e->getMessage());
            if ($e->hasResponse()) {
                Log::error("Response Body: " . $e->getResponse()->getBody()->getContents());
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error("Error generating report: " . $e->getMessage());
            Log::error("Error in file: " . $e->getFile() . " on line: " . $e->getLine());
            throw $e;
        }
    }
    private function getSystemPrompt(string $type) {
        // Customize the system prompt based on the data type.
        $basePrompt = "You are a helpful assistant that generates concise summaries of city operations data in markdown format. Create the report completely in the user specified language: " . $this->location->language . ". ";
        $typePrompt = "The following data is specifically about $type. ";
        $generalInstructions = "Provide a clear and informative overview, highlighting the most significant events. The report is for residents of the area. Be factual and do not speculate. Do not generate any kind of disclaimer or note at the end of the report.";

        return $basePrompt . $typePrompt . $generalInstructions;
    }
}