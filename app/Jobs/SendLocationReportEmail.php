<?php

namespace App\Jobs;

use App\Mail\SendLocationReport;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
    public function handle()
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

            // --- 2. Generate Report with Gemini API (Non-Streaming) ---
            $apiKey = config('services.gemini.api_key');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";

            $client = new Client();

            // Prepare the request body.  Format data points as separate user messages.
            $contents = [
                [
                    'role' => 'user', // System prompt as user
                    'parts' => [
                        ['text' => $this->getSystemPrompt()],
                    ],
                ],
            ];

            // Add each data point as a separate user message.
            foreach ($mapData->dataPoints as $dataPoint) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        ['text' => json_encode($dataPoint)],
                    ],
                ];
            }

            // Add the final instruction.
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => "Generate a summary report for the data provided above."],
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

                // Extract the generated text.
                $report = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? 'No report generated.';

                if (empty(trim($report)) || $report === 'No report generated.') {
                    Log::warning("Empty or default report generated for location: {$this->location->address}. Response: " . json_encode($responseBody));
                    return;
                }

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


            // --- 3. Send Email ---
            Mail::to($this->location->user->email)->send(new SendLocationReport($this->location, $report));
            Log::info("Report email sent to user: {$this->location->user->email} for location: {$this->location->address}");

        } catch (\Exception $e) {
            Log::error("Error processing report or sending email for location {$this->location->address}: {$e->getMessage()}");
            Log::error("Error in file: " . $e->getFile() . " on line: " . $e->getLine());
            throw $e;
        }
    }

    private function getSystemPrompt() {
        return <<<EOT
You are a helpful assistant that generates concise summaries of city operations data.  The data will include events like crime reports, 311 service requests, building permits, property violations, and construction work.  Provide a clear and informative overview, highlighting the most significant events.  The report is for residents of the area. Be factual and do not speculate. Do not generate any kind of disclaimer or note at the end of the report.
EOT;
    }
}