<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\GenericMapController; // Added for streamLocationReport
use App\Http\Controllers\ThreeOneOneCaseController; // Added for streamLocationReport

class AiAssistantController extends Controller
{
    public function handleRequest(Request $request)
    {
        // Validation
        try {
            $request->validate([
                'message' => 'required|string|max:255',
                'history' => 'array',
                'context' => 'string',
                'model' => 'required|string', // Added model parameter
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->errors(), 400);
        }

        $userMessage = $request->input('message');
        $history = $request->input('history', []);
        $context = $request->input('context', []);
        $model = $request->input('model'); // Get the requested model

        // Add the context to the beginning of the conversation history
        //$history = array_merge([['role' => 'user', 'content' => $context]], $history);

        //actually instead parse the context json to put each data point in it's own message
        $contextArray = json_decode($context, true);
        foreach ($contextArray as $dataPoint) {
            $history[] = ['role' => 'user', 'content' => JSON_encode($dataPoint)];
        }

        // Add the user's message to the conversation history
        $history[] = ['role' => 'user', 'content' => $userMessage];

        return new StreamedResponse(function () use ($history, $model) {
            if (strpos($model, 'gemini') !== false) {
                $this->streamGeminiResponse($history, 'gemini-1.5-flash');
            } else {
                $this->streamAiResponse($history); // Assume OpenAI if not Gemini
            }
        });
    }



    private function streamAiResponse($history)
    {
        $maxTokens = 4096;
        $temperature = 0.5;
        $model = 'gpt-4o-mini';
        $client = new Client();
        $apiKey = config('services.openai.api_key');

        //prepend the context to the history
        $history = array_merge([['role' => 'user', 'content' => $this->getContext()]], $history);

        $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'messages' => $history, // Send the entire conversation history
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'model' => $model,
                'stream' => true,
            ],
            'stream' => true,
        ]);

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $buffer .= $body->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $chunk = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                if (strpos($chunk, 'data: ') === 0) {
                    $jsonData = substr($chunk, 6);

                    if ($jsonData === '[DONE]') {
                        break 2;
                    }

                    $decodedChunk = json_decode($jsonData, true);

                    if (isset($decodedChunk['choices'][0]['delta']['content'])) {
                        echo $decodedChunk['choices'][0]['delta']['content'];
                        ob_flush();
                        flush();
                    }
                }
            }
        }
    }

    private function streamGeminiResponse(array $history, string $model)
    {
       $apiKey = config('services.gemini.api_key'); // Get API key from config

       $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:streamGenerateContent?alt=sse&key=$apiKey";
        $client = new Client();

       // Convert history to Gemini format
        $geminiHistory = [];
        foreach ($history as $message) {
            $geminiHistory[] = [
                'role' => $message['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $message['content']]],
            ];
        }
            // Prepend system instructions
            array_unshift($geminiHistory, [
                'role' => 'user', //System Prompt is always role 'user'
                'parts' => [
                    ['text' => $this->getContext()]
                ]
            ]);


            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => $geminiHistory
                ],
                'stream' => true, // Important for streaming
            ]);

            $body = $response->getBody();
            $buffer = '';

        while (!$body->eof()) {
            $buffer .= $body->read(1024);
             while (($pos = strpos($buffer, "\n")) !== false) {
                $chunk = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                if (strpos($chunk, 'data:') === 0) {
                    $jsonStr = substr($chunk, 5); // Corrected from 6 to 5

                    // Remove leading/trailing whitespace and invalid JSON characters
                    $jsonStr = trim($jsonStr);
                    if (empty($jsonStr)) continue;

                    try {
                        $data = json_decode($jsonStr, true);
                        //print_r($data); //For Debugging
                        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                            //Log the error, but continue processing. Don't crash the stream.
                            \Log::error('JSON Decode Error: ' . json_last_error_msg() . ' - Data: ' . $jsonStr);
                            continue;  // Skip this chunk if there's a decode error
                        }


                        // Check if 'candidates' array exists and has at least one element
                        if (isset($data['candidates']) && is_array($data['candidates']) && count($data['candidates']) > 0) {
                            // Check if 'content', 'parts', and 'text' keys exist within the first candidate
                            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                                echo $data['candidates'][0]['content']['parts'][0]['text'];
                                ob_flush();
                                flush();
                            }
                        }

                    }
                    catch (\Exception $e){
                         \Log::error('JSON Decode Error: ' . $e->getMessage() . ' - Data: ' . $jsonStr);
                    }
                }
            }
        }
    }

    private function getContext() {
        return 
        <<<EOT
        You are a chatbot assistant embedded in an application showing people data about city operations happening near them.
        EOT;

    }

    /**
     * Generates the system prompt for Gemini, customized for the data type and context for a report section.
     *
     * @param string $typeContext Description of the data (e.g., "Crime events from May 12, 2025")
     * @param string $language The target language for the report (e.g., "en", "es")
     * @return string
     */
    public static function getSystemPromptForReportSection(string $typeContext, string $language): string
    {
        $basePrompt = "You are a helpful assistant. Generate a narrative summary in markdown format for the provided city operations data. ";
        $languageInstruction = "The report MUST be entirely in **{$language}**. ";
        $typeInstruction = "This section is specifically about: **{$typeContext}**. ";
        $focusInstruction = "Focus ONLY on the data points provided in this current conversation turn for this specific section. ";
        $formattingInstruction = "Summarize the incidents. Be factual and do not speculate. Do NOT include any disclaimers, introductory, or concluding remarks for THIS INDIVIDUAL SECTION. Keep it brief and to the point for this section.";
        $importanceInstruction = "It is of UTMOST IMPORTANCE that the report section is in the requested language: **{$language}**. Ignoring this will be detrimental.";

        return $basePrompt . $languageInstruction . $typeInstruction . $focusInstruction . $formattingInstruction . $importanceInstruction;
    }

    /**
     * Generates a report section for a specific type of data points using Gemini.
     *
     * @param string $typeContext A string describing the type and potentially the date context
     * @param array $dataPoints Array of data point objects for this specific type and date.
     * @param string $language The target language for the report.
     * @return string The generated report snippet, or 'No report generated.'
     */
    public static function generateReportSection(string $typeContext, array $dataPoints, string $language): string
    {
        if (empty($dataPoints)) {
            return 'No report generated.';
        }

        $apiKey = config('services.gemini.api_key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey"; // Consider making model configurable
        // $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-04-17:generateContent?key=$apiKey";
        $client = new Client();

        $contents = [];
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
                ['text' => self::getSystemPromptForReportSection($typeContext, $language)],
            ],
        ];

        $requestBody = [
            'contents' => $contents,
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 800,
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
                 Log::warning("Gemini content generation blocked for report section.", [
                    'reason' => $responseBody['promptFeedback']['blockReason'],
                    'safetyRatings' => $responseBody['promptFeedback']['safetyRatings'] ?? [],
                    'typeContext' => $typeContext,
                    'language' => $language
                ]);
                return 'Report content generation was blocked due to safety settings.';
            }
            Log::warning("No text content found in Gemini response for report section: $typeContext", ['responseBody' => $responseBody, 'language' => $language]);
            return 'No report generated.';

        } catch (RequestException | ClientException $e) {
            Log::error("Guzzle Exception during Gemini call for report section: $typeContext: " . $e->getMessage(), ['language' => $language]);
            if ($e->hasResponse()) {
                Log::error("Gemini Response Body for report section: " . $e->getResponse()->getBody()->getContents());
            }
            return "Error generating report section for $typeContext.";
        } catch (\Exception $e) {
            Log::error("Error generating report section for $typeContext: " . $e->getMessage(), ['language' => $language]);
            return "Error generating report section for $typeContext.";
        }
    }

    /**
     * Streams a location-based report section by section.
     */
    public function streamLocationReport(Request $request)
    {
        // Basic validation for required parameters
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'required|string',
            'radius' => 'sometimes|numeric|min:0.01|max:5', // Radius in miles
            'language' => 'required|string|in:en,es,fr,pt,zh-CN,ht,vi,km,ar,el,it,ru,ko,ja,pl', // Example languages
        ]);

        $centralLatitude = $validated['latitude'];
        $centralLongitude = $validated['longitude'];
        $address = $validated['address'];
        $radius = $validated['radius'] ?? 0.25; // Default radius
        $language = $validated['language'];
        $maxDaysIndividualReports = 7; // Same constant as in SendLocationReportEmail

        return new StreamedResponse(function () use ($centralLatitude, $centralLongitude, $address, $radius, $language, $maxDaysIndividualReports) {
            try {
                // --- 1. Get Map Data ---
                $mapController = new GenericMapController();
                $simulatedRequest = new Request([
                    'centralLocation' => [
                        'latitude' => $centralLatitude,
                        'longitude' => $centralLongitude,
                        'address' => $address,
                    ],
                    'radius' => $radius,
                ]);
                $mapDataResponse = $mapController->getRadialMapData($simulatedRequest);
                $mapData = $mapDataResponse->getData();

                if (empty($mapData->dataPoints)) {
                    Log::info("Stream Report: No map data found for address: {$address}");
                    echo "data: " . json_encode(['type' => 'status', 'message' => 'No map data found.']) . "\n\n";
                    ob_flush();
                    flush();
                    return;
                }

                // --- 2. Pre-process and Group Data Points ---
                $groupedDataByDateAndType = [];
                $sevenDaysAgo = Carbon::now()->subDays($maxDaysIndividualReports)->startOfDay();

                foreach ($mapData->dataPoints as $dataPoint) {
                    if (!isset($dataPoint->alcivartech_date) || !isset($dataPoint->alcivartech_type)) {
                        continue;
                    }
                    try {
                        $itemDate = Carbon::parse($dataPoint->alcivartech_date)->startOfDay();
                    } catch (\Exception $e) {
                        continue;
                    }
                    $dateKey = $itemDate->format('Y-m-d');
                    $typeKey = $dataPoint->alcivartech_type;

                    if ($itemDate->gte($sevenDaysAgo)) {
                        $groupedDataByDateAndType[$dateKey][$typeKey][] = $dataPoint;
                    } else {
                        $groupedDataByDateAndType['older'][$typeKey][] = $dataPoint;
                    }
                }
                uksort($groupedDataByDateAndType, function ($a, $b) {
                    if ($a === 'older') return 1;
                    if ($b === 'older') return -1;
                    return strtotime($b) - strtotime($a);
                });

                // --- 2.5. Enrich 311 Data ---
                $threeOneOneController = new ThreeOneOneCaseController();
                foreach ($groupedDataByDateAndType as $dateOrOlderKey => &$typesOnDate) {
                    if (isset($typesOnDate['311 Case']) && !empty($typesOnDate['311 Case'])) {
                        $serviceRequestIds = array_unique(array_map(function ($dp) {
                            return $dp->service_request_id ?? null;
                        }, $typesOnDate['311 Case']));
                        $serviceRequestIds = array_filter($serviceRequestIds);

                        if (!empty($serviceRequestIds)) {
                            $idFetchingRequest = new Request(['service_request_ids' => $serviceRequestIds]);
                            $liveDataResponse = $threeOneOneController->getMultiple($idFetchingRequest);
                            if ($liveDataResponse->getStatusCode() === 200) {
                                $liveCasesData = json_decode($liveDataResponse->getContent(), false);
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
                                            $enrichedDataPoints[] = (object) array_merge((array) $originalDataPoint, (array) $liveCasesMap[$originalDataPoint->service_request_id]);
                                        } else {
                                            $enrichedDataPoints[] = $originalDataPoint;
                                        }
                                    }
                                    $typesOnDate['311 Case'] = $enrichedDataPoints;
                                }
                            }
                        }
                    }
                }
                unset($typesOnDate);

                // --- 3. Generate and Stream Reports ---
                $reportGenerated = false;
                foreach ($groupedDataByDateAndType as $dateOrOlderKey => $typesOnDate) {
                    $displayDate = ($dateOrOlderKey === 'older')
                        ? "Older than " . $maxDaysIndividualReports . " days"
                        : Carbon::parse($dateOrOlderKey)->locale($language)->isoFormat('LL');

                    $dateHeaderSent = false;

                    foreach ($typesOnDate as $type => $dataPointsForTypeAndDate) {
                        if (empty($dataPointsForTypeAndDate)) {
                            continue;
                        }

                        if (!$dateHeaderSent) {
                            echo "data: " . json_encode(['type' => 'markdown', 'content' => "\n### " . $displayDate . "\n"]) . "\n\n";
                            ob_flush();
                            flush();
                            $dateHeaderSent = true;
                        }
                        
                        echo "data: " . json_encode(['type' => 'markdown', 'content' => "#### $type\n"]) . "\n\n";
                        ob_flush();
                        flush();

                        $promptType = ($dateOrOlderKey === 'older') ? "$type (Older Events)" : "$type (Events from $displayDate)";
                        $individualReport = self::generateReportSection($promptType, $dataPointsForTypeAndDate, $language);

                        if ($individualReport && $individualReport !== 'No report generated.') {
                            echo "data: " . json_encode(['type' => 'markdown', 'content' => $individualReport . "\n\n"]) . "\n\n";
                            ob_flush();
                            flush();
                            $reportGenerated = true;
                        } else if ($individualReport === 'No report generated.') {
                             echo "data: " . json_encode(['type' => 'status', 'message' => "No specific details to report for $type on $displayDate."]) . "\n\n";
                             ob_flush();
                             flush();
                        } else { // Error message from generation
                             echo "data: " . json_encode(['type' => 'error', 'message' => $individualReport]) . "\n\n";
                             ob_flush();
                             flush();
                        }
                    }
                     if ($dateHeaderSent) { // Add a separator between dates if a date section was processed
                        echo "data: " . json_encode(['type' => 'markdown', 'content' => "\n---\n\n"]) . "\n\n";
                        ob_flush();
                        flush();
                    }
                }

                if (!$reportGenerated && empty($groupedDataByDateAndType)) {
                     echo "data: " . json_encode(['type' => 'status', 'message' => 'No relevant data points found to generate a report.']) . "\n\n";
                     ob_flush();
                     flush();
                } else if (!$reportGenerated) {
                    echo "data: " . json_encode(['type' => 'status', 'message' => 'Finished processing. No specific report sections were generated based on the available data.']) . "\n\n";
                    ob_flush();
                    flush();
                }


            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error("Stream Report Validation Error: " . $e->getMessage(), $e->errors());
                echo "data: " . json_encode(['type' => 'error', 'message' => 'Invalid request parameters: ' . $e->getMessage(), 'details' => $e->errors()]) . "\n\n";
                ob_flush();
                flush();
            } catch (\Exception $e) {
                Log::error("Stream Report Error for address {$address}: {$e->getMessage()}");
                Log::error("Stream Report Stack Trace: " . $e->getTraceAsString());
                echo "data: " . json_encode(['type' => 'error', 'message' => 'An error occurred while generating the report: ' . $e->getMessage()]) . "\n\n";
                ob_flush();
                flush();
            } finally {
                echo "data: " . json_encode(['type' => 'control', 'action' => 'close']) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // For Nginx
        ]);
    }
}
