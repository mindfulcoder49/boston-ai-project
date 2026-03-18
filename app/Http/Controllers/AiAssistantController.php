<?php
namespace App\Http\Controllers;

use App\Exceptions\DailyTokenLimitExceededException;
use App\Http\Controllers\GenericMapController; // Added for streamLocationReport
use App\Http\Controllers\ThreeOneOneCaseController; // Added for streamLocationReport
use App\Models\Report; // Added for Report model
use App\Services\OpenAiTokenBudgetService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth; // Added for Auth
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $history     = $request->input('history', []);
        $context     = $request->input('context', []);
        $model       = $request->input('model');
        $meta        = $request->input('meta', []);

        // Add the context to the beginning of the conversation history
        $history = array_merge([['role' => 'user', 'content' => 'This is the relevant information to focus on in this conversation. Any
        reference to events, incidents, occurences, data, etc, is referring to this context:'.  $context]], $history);

        // Add the user's message to the conversation history
        $history[] = ['role' => 'user', 'content' => $userMessage];

        return new StreamedResponse(function () use ($history, $userMessage, $meta) {
            $this->streamAiResponse($history, $userMessage, $meta);
        });
    }



    private function streamAiResponse(array $history, string $userMessage = '', array $meta = [])
    {
        $maxTokens = 4096;
        $model = 'gpt-5-mini';
        $client = new Client();
        $apiKey = config('services.openai.api_key');
        $tokenBudget = app(OpenAiTokenBudgetService::class);

        //prepend the context to the history
        $history = array_merge([['role' => 'user', 'content' => $this->getContext()]], $history);

        $payload = [
            'messages'              => $history,
            'max_completion_tokens' => $maxTokens,
            'model'                 => $model,
            'stream'                => true,
        ];
        $reservation = null;

        try {
            $reservation = $tokenBudget->reserveForChatCompletion($payload, 'ai_assistant_chat_stream');
            $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json'   => $payload,
                'stream' => true,
            ]);

            $body   = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $buffer .= $body->read(1024);

                while (($pos = strpos($buffer, "\n")) !== false) {
                    $chunk  = substr($buffer, 0, $pos);
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
        } catch (DailyTokenLimitExceededException $e) {
            echo $tokenBudget->formatLimitExceededMessage($e);
            ob_flush();
            flush();
        } catch (ClientException | RequestException $e) {
            $tokenBudget->releaseReservation($reservation);
            $rawBody  = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($rawBody, true);
            $errorCode = $errorData['error']['code'] ?? null;
            Log::error('OpenAI stream request failed.', [
                'status'        => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'error_code'    => $errorCode,
                'error_message' => $e->getMessage(),
                'response_body' => $rawBody,
                'model'         => $model,
                'message_count' => count($history),
            ]);
            if ($errorCode === 'context_length_exceeded') {
                echo $this->buildContextLengthMessage($meta, $errorData['error']['message'] ?? '');
            } else {
                echo 'Error communicating with AI service: ' . ($rawBody ?: $e->getMessage());
            }
            ob_flush();
            flush();
        } catch (\Exception $e) {
            $tokenBudget->releaseReservation($reservation);
            Log::error('Unexpected error in streamAiResponse: ' . $e->getMessage());
            echo 'Unexpected error: ' . $e->getMessage();
            ob_flush();
            flush();
        }
    }

    private function buildContextLengthMessage(array $meta, string $technicalError): string
    {
        $total = (int) ($meta['total'] ?? 0);

        preg_match('/configured limit of ([\d,]+) tokens/', $technicalError, $limitMatch);
        preg_match('/resulted in ([\d,]+) tokens/', $technicalError, $usedMatch);
        $limitTokens = isset($limitMatch[1]) ? (int) str_replace(',', '', $limitMatch[1]) : 0;
        $usedTokens  = isset($usedMatch[1])  ? (int) str_replace(',', '', $usedMatch[1])  : 0;

        $message = "There are too many data points in the current map view for me to analyze at once.";

        if ($limitTokens > 0 && $usedTokens > 0 && $total > 0) {
            $targetItems = max(1, (int) round($total * ($limitTokens / $usedTokens) * 0.85));
            $message .= " The {$total} items currently shown use about " . number_format($usedTokens)
                     . " tokens, but the limit is " . number_format($limitTokens)
                     . " — so you'd need roughly {$targetItems} items or fewer.";
        }

        $message .= " Try zooming into a smaller neighborhood, reducing your search radius, or narrowing the date range to bring the item count down.";

        return $message;
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
        You are a chatbot assistant embedded in an application showing people data about city operations happening near them in Boston, MA. Unless otherwise specified, all data and locations refer to Boston.
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
        $basePrompt = "You are a helpful assistant. Generate a narrative summary in markdown format for the provided city operations data. The data is for a specific city (e.g., Boston, Cambridge). If not specified in the data, assume the location is Boston, MA. ";
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
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key=$apiKey"; // Consider making model configurable
        // $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite-preview-04-17:generateContent?key=$apiKey";
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
     * Generates a news article from report data using OpenAI.
     *
     * @param string $reportTitle The title of the source report.
     * @param array|object $reportData The report data (trend summary or slimmed analysis data).
     * @param array $reportParameters Additional context about the report parameters.
     * @return array|null An array containing 'headline', 'summary', 'content', 'title', 'slug', or null on failure.
     */
    public static function defaultTrendSystemPrompt(): string
    {
        return <<<'EOT'
You are a journalist for a local news organization focused on city operations and data analysis. Your task is to write a news article based on the provided JSON data. The report title or the data itself will often indicate the city (e.g., Boston, Chicago, Cambridge). If a city is mentioned, focus the article on that city. If no city is specified anywhere, you can assume the data pertains to Boston, MA.

Your goal is to identify and tell one compelling story that is the most salient and interesting from the data. Analyze the report to find the most significant trend, anomaly, or event that stands out as the "jewel" of the story. Focus on this specific aspect and craft a narrative around it, creating a vivid and engaging article.

When identifying trends or anomalies, ensure that percentage changes or geographical patterns are contextualized with the absolute totals they represent. Avoid overemphasizing large percentage changes if the absolute numbers are small (e.g., a 200% increase from 1 to 3 incidents). Similarly, when analyzing geographical trends, focus on areas with significant absolute totals or notable deviations from regional norms. Highlight categories or regions with substantial absolute totals, even if their percentage changes or deviations are less dramatic, as they may represent a larger impact on the community.

To create an engaging narrative, follow these rules:
1. Prioritize categories or regions with the highest absolute totals or those with significant year-over-year changes or geographical deviations that reflect meaningful trends.
2. Look for patterns or correlations across multiple categories or regions that might indicate broader societal, operational, or geographical shifts.
3. Highlight anomalies that deviate sharply from historical norms or regional averages, but ensure they are statistically and contextually significant.
4. Consider the human impact of the data—how do these trends affect the community, public safety, or city operations in specific areas?
5. Avoid simply listing data points. Instead, interpret the data to uncover the "why" behind the trends and explain their implications in a way that captivates the reader.

The article should be structured in a standard news format. It must include:
1. A compelling, SEO-friendly headline that captures the essence of the story.
2. A brief, engaging summary of the key story (1-2 sentences).
3. The main article body in Markdown format, presenting the story in detail.

The tone should be objective, informative, and accessible to a general audience. Avoid listing all the data; instead, interpret the most interesting aspect and explain its significance in a way that captivates the reader.

Format your response exactly as follows — no text before HEADLINE, no text after the article body:

HEADLINE: [your headline here]

SUMMARY: [your 1-2 sentence summary here]

ARTICLE:
[full article in Markdown]
EOT;
    }

    public static function defaultHotspotSystemPrompt(): string
    {
        return <<<'EOT'
You are a journalist for a local news organization focused on city operations and data analysis. Your task is to write a news article about a geographic hotspot — a specific urban area that has been flagged across multiple independent data analysis reports as having significant statistical anomalies or trends.

The data provided includes which types of incidents contributed to this hotspot designation, the most significant anomalies detected, and the strongest statistical trends. Focus on telling a coherent, compelling story about what is happening at this location.

Rules:
1. Identify the most compelling finding across all report types and build the narrative around it.
2. Give the area human context — reference the neighborhood or area name when available.
3. Do not list every data point. Interpret and explain the significance.
4. Contextualize any numbers with meaningful comparisons when possible.
5. The tone should be objective, informative, and accessible to a general audience.
6. Avoid speculating beyond what the data supports.

Format your response exactly as follows — no text before HEADLINE, no text after the article body:

HEADLINE: [your headline here]

SUMMARY: [your 1-2 sentence summary here]

ARTICLE:
[full article in Markdown]
EOT;
    }

    /**
     * Parse a sectioned article response into headline, summary, content fields.
     * Expected format:
     *   HEADLINE: ...
     *   SUMMARY: ...
     *   ARTICLE:
     *   [markdown body]
     */
    public static function parseSectionedArticle(string $raw): array
    {
        $headline = 'Untitled Article';
        $summary  = '';
        $body     = $raw;

        if (preg_match('/^HEADLINE:\s*(.+)/m', $raw, $m)) {
            $headline = trim($m[1]);
        }
        if (preg_match('/SUMMARY:\s*([\s\S]+?)(?=\s*ARTICLE:|\z)/m', $raw, $m)) {
            $summary = trim($m[1]);
        }
        if (preg_match('/ARTICLE:\s*([\s\S]+)/m', $raw, $m)) {
            $body = trim($m[1]);
        }

        return ['headline' => $headline, 'summary' => $summary, 'content' => $body];
    }

    /**
     * Estimate the number of input tokens for a prompt without sending a generation request.
     * Uses OpenAI's /v1/responses/input_tokens endpoint.
     */
    public static function estimateInputTokens(string $model, string $systemPrompt, string $userPrompt): int
    {
        $apiKey = config('services.openai.api_key');
        $client = new \GuzzleHttp\Client();

        $response = $client->post('https://api.openai.com/v1/responses/input_tokens', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'        => $model,
                'instructions' => $systemPrompt,
                'input'        => $userPrompt,
            ],
            'timeout' => 30,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        return (int) ($body['input_tokens'] ?? 0);
    }

    public static function generateNewsArticle(string $reportTitle, $reportData, array $reportParameters = [], ?string $introPrompt = null): ?array
    {
        $apiKey = config('services.openai.api_key');
        $client = new Client();
        $tokenBudget = app(OpenAiTokenBudgetService::class);

        $systemPrompt = $introPrompt ?? static::defaultTrendSystemPrompt();

        $contextPrompt = "The analysis was generated with the following parameters. Use them to provide context in the article:\n" . json_encode($reportParameters, JSON_PRETTY_PRINT);
        $userPrompt    = "Write a news article about the following report titled '{$reportTitle}'.\n\n{$contextPrompt}\n\nHere is the data: " . json_encode($reportData);
        $payload = [
            'model'    => 'gpt-5',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ];
        $reservation = null;

        try {
            $reservation = $tokenBudget->reserveForChatCompletion($payload, 'generate_news_article');
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 120,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            $content      = trim($responseBody['choices'][0]['message']['content'] ?? '');

            if (!$content) {
                Log::warning("No content in OpenAI response for news article.", [
                    'reportTitle'  => $reportTitle,
                    'finishReason' => $responseBody['choices'][0]['finish_reason'] ?? 'unknown',
                    'response'     => $responseBody,
                ]);
                return null;
            }

            $articleData = static::parseSectionedArticle($content);
            $articleData['title'] = Str::limit($articleData['headline'], 250);
            $articleData['slug']  = Str::slug($articleData['title']);
            return $articleData;

        } catch (DailyTokenLimitExceededException $e) {
            Log::warning('Daily OpenAI token cap reached for news article generation.', [
                'reportTitle' => $reportTitle,
                'message' => $e->getMessage(),
            ]);
            return null;
        } catch (RequestException | ClientException $e) {
            $tokenBudget->releaseReservation($reservation);
            Log::error("OpenAI request failed for news article: " . $e->getMessage(), ['reportTitle' => $reportTitle]);
            if ($e->hasResponse()) {
                Log::error("OpenAI Response Body: " . $e->getResponse()->getBody()->getContents());
            }
            return null;
        } catch (\Exception $e) {
            $tokenBudget->releaseReservation($reservation);
            Log::error("Error generating news article: " . $e->getMessage(), ['reportTitle' => $reportTitle]);
            return null;
        }
    }

    /**
     * Generates a news article about a geographic hotspot hexagon using OpenAI.
     *
     * @param string $h3Index The H3 hexagon index.
     * @param string $locationName Human-readable location name (falls back to h3Index).
     * @param array $hotspotData Structured data about the hotspot findings.
     * @return array|null An array containing 'headline', 'summary', 'content', 'title', 'slug', or null on failure.
     */
    public static function generateNewsArticleFromHexagon(string $h3Index, string $locationName, array $hotspotData, ?string $introPrompt = null): ?array
    {
        $apiKey = config('services.openai.api_key');
        $client = new Client();
        $tokenBudget = app(OpenAiTokenBudgetService::class);

        $systemPrompt = $introPrompt ?? static::defaultHotspotSystemPrompt();

        $locationLabel = $locationName ?: $h3Index;
        $userPrompt    = "Write a news article about the following hotspot location: {$locationLabel} (H3 index: {$h3Index}).\n\nHotspot data:\n" . json_encode($hotspotData, JSON_PRETTY_PRINT);
        $payload = [
            'model'    => 'gpt-5',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
        ];
        $reservation = null;

        try {
            $reservation = $tokenBudget->reserveForChatCompletion($payload, 'generate_hexagon_news_article');
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 120,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);
            $content      = trim($responseBody['choices'][0]['message']['content'] ?? '');

            if (!$content) {
                Log::warning("No content in OpenAI response for hexagon article.", [
                    'h3Index'      => $h3Index,
                    'finishReason' => $responseBody['choices'][0]['finish_reason'] ?? 'unknown',
                    'response'     => $responseBody,
                ]);
                return null;
            }

            $articleData = static::parseSectionedArticle($content);
            $articleData['title'] = Str::limit($articleData['headline'], 250);
            $articleData['slug']  = Str::slug($articleData['title']);
            return $articleData;

        } catch (DailyTokenLimitExceededException $e) {
            Log::warning('Daily OpenAI token cap reached for hexagon article generation.', [
                'h3Index' => $h3Index,
                'message' => $e->getMessage(),
            ]);
            return null;
        } catch (RequestException | ClientException $e) {
            $tokenBudget->releaseReservation($reservation);
            Log::error("OpenAI request failed for hexagon article: " . $e->getMessage(), ['h3Index' => $h3Index]);
            if ($e->hasResponse()) {
                Log::error("OpenAI Response Body: " . $e->getResponse()->getBody()->getContents());
            }
            return null;
        } catch (\Exception $e) {
            $tokenBudget->releaseReservation($reservation);
            Log::error("Error generating hexagon news article: " . $e->getMessage(), ['h3Index' => $h3Index]);
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

    /**
     * Generates a JSON dictionary for a given model field by categorizing its unique values.
     *
     * @param string $modelName The name of the model (e.g., 'EverettCrimeData').
     * @param string $fieldName The name of the field to categorize (e.g., 'incident_type').
     * @param array $values The unique values from the field.
     * @return string|null The JSON string of the dictionary, or null on failure.
     */
    public static function generateDictionary(string $modelName, string $fieldName, array $values): ?string
    {
        $apiKey = config('services.gemini.api_key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key=$apiKey";
        $client = new Client();

        $systemPrompt = <<<EOT
You are a data analyst assistant. Your task is to categorize a list of values from a database field into broader, more general groups.
The user will provide a model name, a field name, and a list of unique values from that field.
You must return a single, valid JSON object. The keys of this JSON object should be the original unique values provided, and the values should be the category you assign to each.
Keep the category names concise and consistent. Do not include any text or formatting outside of this JSON object.
For example, if given values like "LARCENY, THEFT FROM MV", "LARCENY, SHOPLIFTING", your categories might be "Larceny/Theft".
EOT;

        $userPrompt = "Please categorize the following values from the '{$fieldName}' field of the '{$modelName}' model:\n\n" . json_encode($values, JSON_PRETTY_PRINT);

        $requestBody = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $systemPrompt]]],
                ['role' => 'model', 'parts' => [['text' => 'Understood. I will analyze the provided values and return a single JSON object mapping each value to a category.']]],
                ['role' => 'user', 'parts' => [['text' => $userPrompt]]],
            ],
            "generationConfig" => [
                "temperature" => 1,
                "maxOutputTokens" => 65536,
                "response_mime_type" => "application/json",
            ]
        ];

        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $requestBody,
                'timeout' => 300,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
                $jsonString = $responseBody['candidates'][0]['content']['parts'][0]['text'];
                // Basic validation before returning
                json_decode($jsonString);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $jsonString;
                }
                Log::error('Gemini returned invalid JSON for dictionary generation.', ['response' => $jsonString]);
                return null;
            }

            Log::warning('No text content found in Gemini response for dictionary generation.', ['responseBody' => $responseBody]);
            return null;

        } catch (RequestException | ClientException $e) {
            Log::error("Guzzle Exception during Gemini call for dictionary generation: " . $e->getMessage());
            if ($e->hasResponse()) {
                Log::error("Gemini Response Body: " . $e->getResponse()->getBody()->getContents());
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Error generating dictionary: " . $e->getMessage());
            return null;
        }
    }
}
