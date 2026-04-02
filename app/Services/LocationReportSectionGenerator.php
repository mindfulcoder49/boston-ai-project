<?php

namespace App\Services;

use App\Exceptions\DailyTokenLimitExceededException;
use Illuminate\Support\Facades\Log;

class LocationReportSectionGenerator
{
    public function __construct(
        private readonly OpenAIService $openAIService
    ) {}

    public function generate(string $typeContext, array $dataPoints, string $language): string
    {
        if (empty($dataPoints)) {
            return 'No report generated.';
        }

        $payload = [
            'model' => config('services.openai.location_report_model', 'gpt-5-mini'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($typeContext, $language),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildUserPrompt($dataPoints),
                ],
            ],
            'max_completion_tokens' => (int) config('services.openai.location_report_max_completion_tokens', 800),
        ];

        try {
            $response = $this->openAIService->openaiChatCompletionsCreate($payload);
            $content = $response['choices'][0]['message']['content'] ?? null;

            if (is_string($content) && trim($content) !== '') {
                return trim($content);
            }

            Log::warning('No text content found in OpenAI response for location report section.', [
                'type_context' => $typeContext,
                'language' => $language,
                'model' => $payload['model'],
                'response' => $response,
            ]);

            return 'No report generated.';
        } catch (DailyTokenLimitExceededException $e) {
            Log::warning('Daily OpenAI token cap reached for location report section.', [
                'type_context' => $typeContext,
                'language' => $language,
                'model' => $payload['model'],
                'remaining_tokens' => $e->getRemainingTokens(),
            ]);

            return "Error generating report section for {$typeContext}.";
        } catch (\Throwable $e) {
            Log::error('Error generating OpenAI location report section.', [
                'type_context' => $typeContext,
                'language' => $language,
                'model' => $payload['model'],
                'error' => $e->getMessage(),
            ]);

            return "Error generating report section for {$typeContext}.";
        }
    }

    private function buildSystemPrompt(string $typeContext, string $language): string
    {
        return "You are a helpful assistant. Generate a narrative summary in markdown format for the provided city operations data. "
            . "The data is for a specific city (for example Boston or Cambridge). If the city is not specified in the data, assume Boston, MA. "
            . "The report must be entirely in {$language}. "
            . "This section is specifically about {$typeContext}. "
            . "Focus only on the data points provided in this request. "
            . "Summarize the incidents factually, without speculation. "
            . "Do not include disclaimers, introductions, or conclusions for this section. "
            . "Keep the section brief and direct.";
    }

    private function buildUserPrompt(array $dataPoints): string
    {
        return "Generate one markdown report section from these data points only:\n\n"
            . json_encode($dataPoints, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
