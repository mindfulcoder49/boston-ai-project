<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Exception;

class OpenAIService
{
    /**
     * Call OpenAI API with function calling directly via HTTP.
     *
     * @param array $functionDefinition The function schema.
     * @param string $prompt The user's prompt.
     * @param string $systemMessage The system message for context.
     * @return array The response from OpenAI API.
     * @throws Exception If the function call fails.
     */
    public function callFunction(array $functionDefinition, string $prompt, string $systemMessage): array
    {
        try {
            // Prepare the payload
            $payload = [
                "model" => "gpt-4o-mini",
                "messages" => [
                    ["role" => "system", "content" => $systemMessage],
                    ["role" => "user", "content" => $prompt]
                ],
                "tools" => [[
                    "type" => "function",
                    "function" => $functionDefinition,
                ]],
            ];

            // Send the request
            $response = $this->openaiChatCompletionsCreate($payload);

            // Log the response for debugging
            Log::info("OpenAI Response Received", ['response' => $response]);

           // Check if the model made a tool call
            $message = $response['choices'][0]['message'] ?? null;

            if (isset($message['tool_calls'])) {
                $toolCall = $message['tool_calls'][0];
                $arguments = json_decode($toolCall['function']['arguments'], true);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to decode function call arguments: " . json_last_error_msg());
            }

            return $arguments;
        } catch (Exception $e) {
            // Log and rethrow exceptions
            Log::error("OpenAI Function Call Error", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new Exception("Failed to process function call: " . $e->getMessage());
        }
    }

    /**
     * Make a direct HTTP request to OpenAI Chat Completions API.
     *
     * @param array $data The payload to send.
     * @return array The API response.
     */
    public function openaiChatCompletionsCreate(array $data): array
    {
        $client = new Client();
        $apiKey = config('services.openai.api_key');

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            Log::error("HTTP Request to OpenAI Failed", ['error' => $e->getMessage()]);
            throw new Exception("Failed to communicate with OpenAI API: " . $e->getMessage());
        }
    }
}
