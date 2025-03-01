<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client;
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
}
