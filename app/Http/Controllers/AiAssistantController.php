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
use Illuminate\Support\Facades\Auth; // Added for Auth
use App\Models\Report; // Added for Report model
use Illuminate\Support\Str;

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
        $history = array_merge([['role' => 'user', 'content' => 'This is the relevant information to focus on in this conversation. Any
        reference to events, incidents, occurences, data, etc, is referring to this context:'.  $context]], $history);

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
                'timeout' => 120, // Increase timeout to 120 seconds
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
     * Generates a news article from report data using Gemini.
     *
     * @param string $reportTitle The title of the source report.
     * @param array|object $reportData The raw data from the report.
     * @param array $reportParameters Additional context about the report parameters.
     * @return array|null An array containing 'headline', 'summary', and 'content', or null on failure.
     */
    public static function generateNewsArticle(string $reportTitle, $reportData, array $reportParameters = []): ?array
    {
        $apiKey = config('services.gemini.api_key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey";
        $client = new Client();

        $systemPrompt = <<<EOT
You are a journalist for a local news organization focused on city operations and data analysis. Your task is to write a news article based on the provided JSON data.

The article should be structured in a standard news format. It must include:
1. A compelling, SEO-friendly `headline`.
2. A brief, engaging `summary` of the key findings (1-2 sentences).
3. The main `content` of the article in Markdown format.

The tone should be objective, informative, and accessible to a general audience. Analyze the data, identify the most significant trends, comparisons, or anomalies, and present them clearly. Do not just list the data; interpret it and explain its significance.

The JSON response MUST be a single, valid JSON object with three keys: "headline", "summary", and "content". Do not include any other text or formatting outside of this JSON object.
EOT;

        $contextPrompt = "The analysis was generated with the following parameters. Use them to provide context in the article:\n" . json_encode($reportParameters, JSON_PRETTY_PRINT);
        $userPrompt = "Write a news article about the following report titled '{$reportTitle}'.\n\n{$contextPrompt}\n\nHere is the data: " . json_encode($reportData);

        $requestBody = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $systemPrompt]]],
                ['role' => 'model', 'parts' => [['text' => 'Understood. I will act as a journalist and provide a news article in the specified JSON format.']]],
                ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 65536,
                "response_mime_type" => "application/json",
            ]
        ];

        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $requestBody,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            $articleData = null;
            $jsonString = null;

            if (isset($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
                $jsonString = $responseBody['candidates'][0]['content']['parts'][0]['text'];
                $articleData = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($articleData['headline'], $articleData['summary'], $articleData['content'])) {
                    $articleData['title'] = Str::limit($articleData['headline'], 250);
                    $articleData['slug'] = Str::slug($articleData['title']);
                    return $articleData;
                }
            }

            // Handle cases where JSON is invalid or generation was stopped
            if (isset($responseBody['candidates'][0]['finishReason'])) {
                $finishReason = $responseBody['candidates'][0]['finishReason'];
                Log::warning("Gemini generation for news article finished with reason: {$finishReason}", [
                    'finishReason' => $finishReason,
                    'reportTitle' => $reportTitle,
                    'usageMetadata' => $responseBody['usageMetadata'] ?? 'N/A',
                ]);

                if ($finishReason === 'MAX_TOKENS' && $jsonString) {
                    Log::warning("AI response was truncated. Attempting to salvage partial content.", [
                        'reportTitle' => $reportTitle,
                        'maxOutputTokens' => $requestBody['generationConfig']['maxOutputTokens'],
                        'promptTokenCount' => $responseBody['usageMetadata']['promptTokenCount'] ?? 'N/A',
                    ]);

                    // Attempt to salvage what we can from the truncated JSON
                    $salvagedData = [
                        'headline' => 'Headline Missing (Truncated)',
                        'summary' => 'Summary Missing (Truncated)',
                        'content' => $jsonString, // Store the raw truncated string
                    ];

                    // Try to extract headline and summary with regex
                    if (preg_match('/"headline":\s*"(.*?)"/s', $jsonString, $matches)) {
                        $salvagedData['headline'] = $matches[1];
                    }
                    if (preg_match('/"summary":\s*"(.*?)"/s', $jsonString, $matches)) {
                        $salvagedData['summary'] = $matches[1];
                    }

                    $salvagedData['title'] = Str::limit($salvagedData['headline'], 250);
                    $salvagedData['slug'] = Str::slug($salvagedData['title']);
                    return $salvagedData;
                }

                // For other finish reasons, we fail.
                return null;

            } elseif (isset($responseBody['promptFeedback']['blockReason'])) {
                Log::warning("Gemini content generation blocked for news article.", [
                    'reason' => $responseBody['promptFeedback']['blockReason'],
                    'reportTitle' => $reportTitle,
                ]);
                return null;
            }

            // If we are here, JSON was invalid for a reason other than MAX_TOKENS, or text was missing.
            Log::error('Failed to decode JSON from Gemini or JSON is missing required keys.', ['response' => $jsonString]);
            return null;

        } catch (RequestException | ClientException $e) {
            Log::error("Guzzle Exception during Gemini call for news article: " . $e->getMessage(), ['reportTitle' => $reportTitle]);
            if ($e->hasResponse()) {
                Log::error("Gemini Response Body for news article: " . $e->getResponse()->getBody()->getContents());
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Error generating news article: " . $e->getMessage(), ['reportTitle' => $reportTitle]);
            return null;
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

        $fullReportContent = ""; // Accumulator for the full report

        return new StreamedResponse(function () use ($centralLatitude, $centralLongitude, $address, $radius, $language, $maxDaysIndividualReports, &$fullReportContent) {
            try {
                // --- 0. Prepend Location Details ---
                $locationDetailsHeader = "## Location Report Details\n\n";
                $locationDetailsHeader .= "- **Address:** {$address}\n";
                $locationDetailsHeader .= "- **Coordinates:** Latitude {$centralLatitude}, Longitude {$centralLongitude}\n";
                $locationDetailsHeader .= "- **Radius:** {$radius} miles\n";
                $locationDetailsHeader .= "- **Report Language:** {$language}\n";
                $locationDetailsHeader .= "- **Report Generated:** " . Carbon::now()->locale($language)->isoFormat('LLLL') . "\n\n";
                $locationDetailsHeader .= "---\n\n";

                echo "data: " . json_encode(['type' => 'markdown', 'content' => $locationDetailsHeader]) . "\n\n";
                ob_flush();
                flush();
                $fullReportContent .= $locationDetailsHeader;

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
                    $noDataMessage = 'No map data found for the specified location and radius.';
                    echo "data: " . json_encode(['type' => 'status', 'message' => $noDataMessage]) . "\n\n";
                    ob_flush();
                    flush();
                    $fullReportContent .= $noDataMessage . "\n";
                    // Still save this attempt if user is logged in
                    if (Auth::check()) {
                        Report::create([
                            'user_id' => Auth::id(),
                            'location_id' => null,
                            'title' => "On-Demand Report for {$address} - " . Carbon::now()->format('Y-m-d H:i'),
                            'content' => $fullReportContent,
                            'generated_at' => Carbon::now(),
                        ]);
                    }
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
                    $currentDateSectionContent = "";

                    foreach ($typesOnDate as $type => $dataPointsForTypeAndDate) {
                        if (empty($dataPointsForTypeAndDate)) {
                            continue;
                        }

                        if (!$dateHeaderSent) {
                            $dateHeaderMarkdown = "\n### " . $displayDate . "\n";
                            echo "data: " . json_encode(['type' => 'markdown', 'content' => $dateHeaderMarkdown]) . "\n\n";
                            ob_flush();
                            flush();
                            $currentDateSectionContent .= $dateHeaderMarkdown;
                            $dateHeaderSent = true;
                        }
                        
                        $typeHeaderMarkdown = "#### $type\n";
                        echo "data: " . json_encode(['type' => 'markdown', 'content' => $typeHeaderMarkdown]) . "\n\n";
                        ob_flush();
                        flush();
                        $currentDateSectionContent .= $typeHeaderMarkdown;

                        $promptType = ($dateOrOlderKey === 'older') ? "$type (Older Events)" : "$type (Events from $displayDate)";
                        $individualReport = self::generateReportSection($promptType, $dataPointsForTypeAndDate, $language);

                        if ($individualReport && $individualReport !== 'No report generated.' && !str_starts_with($individualReport, "Error generating report section for") && $individualReport !== 'Report content generation was blocked due to safety settings.') {
                            $reportChunk = $individualReport . "\n\n";
                            echo "data: " . json_encode(['type' => 'markdown', 'content' => $reportChunk]) . "\n\n";
                            ob_flush();
                            flush();
                            $currentDateSectionContent .= $reportChunk;
                            $reportGenerated = true;
                        } else if ($individualReport === 'No report generated.') {
                             $statusMsg = "No specific details to report for $type on $displayDate.";
                             echo "data: " . json_encode(['type' => 'status', 'message' => $statusMsg]) . "\n\n";
                             ob_flush();
                             flush();
                             $currentDateSectionContent .= "*".$statusMsg."*\n\n";
                        } else { // Error message or safety block
                             $errorMsg = $individualReport; // Contains the error or safety message
                             echo "data: " . json_encode(['type' => 'error', 'message' => $errorMsg]) . "\n\n";
                             ob_flush();
                             flush();
                             $currentDateSectionContent .= "**".$errorMsg."**\n\n";
                        }
                    }
                     if ($dateHeaderSent) { // Add a separator between dates if a date section was processed
                        $separatorMarkdown = "\n---\n\n";
                        echo "data: " . json_encode(['type' => 'markdown', 'content' => $separatorMarkdown]) . "\n\n";
                        ob_flush();
                        flush();
                        $currentDateSectionContent .= $separatorMarkdown;
                     }
                     $fullReportContent .= $currentDateSectionContent; // Append the whole date section
                }

                if (!$reportGenerated && empty($groupedDataByDateAndType)) { // This case is handled earlier by mapData check
                     // $msg = 'No relevant data points found to generate a report.';
                     // echo "data: " . json_encode(['type' => 'status', 'message' => $msg]) . "\n\n";
                     // ob_flush();
                     // flush();
                     // $fullReportContent .= "*".$msg."*\n";
                } else if (!$reportGenerated) {
                    $msg = 'Finished processing. No specific report sections were generated based on the available data.';
                    echo "data: " . json_encode(['type' => 'status', 'message' => $msg]) . "\n\n";
                    ob_flush();
                    flush();
                    $fullReportContent .= "*".$msg."*\n";
                }

                // Save the full report if user is authenticated
                if (Auth::check() && !empty($fullReportContent)) {
                    Report::create([
                        'user_id' => Auth::id(),
                        'location_id' => null, // On-demand reports are not tied to a saved location
                        'title' => "On-Demand Report for {$address} - " . Carbon::now()->format('Y-m-d H:i'),
                        'content' => $fullReportContent,
                        'generated_at' => Carbon::now(),
                    ]);
                    Log::info("Streamed on-demand report saved for user: " . Auth::id() . " for address: {$address}");
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
