<?php

namespace App\Services;

use App\Exceptions\DailyTokenLimitExceededException;
use App\Models\OpenAiDailyTokenUsage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiTokenBudgetService
{
    public const DEFAULT_DAILY_LIMIT = 2500000;
    public const DEFAULT_TOOL_CALL_MAX_COMPLETION_TOKENS = 1200;

    public function reserveForChatCompletion(array $payload, string $source = 'unknown'): array
    {
        $model = $payload['model'] ?? null;

        if (!is_string($model) || $model === '') {
            throw new RuntimeException('OpenAI model is required to reserve tokens.');
        }

        $inputTokens = $this->countInputTokens($this->buildCountPayloadFromChatCompletion($payload));
        $completionBudget = $this->extractCompletionBudget($payload);

        return $this->reserve($inputTokens, $completionBudget, $model, $source);
    }

    public function releaseReservation(?array $reservation): void
    {
        if (!$reservation) {
            return;
        }

        $usageDate = $reservation['usage_date'] ?? null;

        if (!$usageDate) {
            return;
        }

        DB::transaction(function () use ($reservation, $usageDate) {
            $usage = OpenAiDailyTokenUsage::where('usage_date', $usageDate)
                ->lockForUpdate()
                ->first();

            if (!$usage) {
                return;
            }

            $usage->input_tokens = max(0, $usage->input_tokens - (int) ($reservation['input_tokens'] ?? 0));
            $usage->reserved_completion_tokens = max(0, $usage->reserved_completion_tokens - (int) ($reservation['reserved_completion_tokens'] ?? 0));
            $usage->reserved_total_tokens = max(0, $usage->reserved_total_tokens - (int) ($reservation['reserved_total_tokens'] ?? 0));
            $usage->request_count = max(0, $usage->request_count - 1);
            $usage->save();
        });
    }

    public function formatLimitExceededMessage(DailyTokenLimitExceededException $e): string
    {
        return 'The app has hit its daily AI token cap for today. '
            . 'Please try again after midnight server time. '
            . 'Remaining tokens: ' . number_format($e->getRemainingTokens()) . '.';
    }

    private function buildCountPayloadFromChatCompletion(array $payload): array
    {
        $messages = $payload['messages'] ?? [];
        $instructions = [];
        $input = [];

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';

            if ($role === 'system') {
                $instructions[] = $this->normalizeMessageContent($content);
                continue;
            }

            $input[] = [
                'role' => $this->normalizeRole($role),
                'content' => $content,
            ];
        }

        $countPayload = [
            'model' => $payload['model'],
            'input' => count($input) === 1 && ($input[0]['role'] ?? null) === 'user'
                ? $this->normalizeMessageContent($input[0]['content'])
                : $input,
        ];

        if (!empty($instructions)) {
            $countPayload['instructions'] = implode("\n\n", array_filter($instructions));
        }

        $tools = $this->normalizeTools($payload);
        if (!empty($tools)) {
            $countPayload['tools'] = $tools;
        }

        return $countPayload;
    }

    private function countInputTokens(array $payload): int
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $client = new Client();
        $response = $client->post('https://api.openai.com/v1/responses/input_tokens', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => 60,
        ]);

        $responseBody = json_decode($response->getBody()->getContents(), true);
        $inputTokens = $responseBody['input_tokens'] ?? null;

        if (!is_int($inputTokens)) {
            Log::error('OpenAI input token counting returned an unexpected response.', [
                'payload' => $payload,
                'response' => $responseBody,
            ]);
            throw new RuntimeException('Unable to determine OpenAI input token usage.');
        }

        return $inputTokens;
    }

    private function reserve(int $inputTokens, int $completionBudget, string $model, string $source): array
    {
        $dailyLimit = (int) config('services.openai.daily_token_limit', self::DEFAULT_DAILY_LIMIT);
        $requestedTotal = $inputTokens + $completionBudget;
        $usageDate = now()->toDateString();

        return DB::transaction(function () use ($dailyLimit, $requestedTotal, $inputTokens, $completionBudget, $usageDate, $model, $source) {
            OpenAiDailyTokenUsage::firstOrCreate(
                ['usage_date' => $usageDate],
                [
                    'token_limit' => $dailyLimit,
                    'input_tokens' => 0,
                    'reserved_completion_tokens' => 0,
                    'reserved_total_tokens' => 0,
                    'request_count' => 0,
                ]
            );

            $usage = OpenAiDailyTokenUsage::where('usage_date', $usageDate)
                ->lockForUpdate()
                ->firstOrFail();

            $usage->token_limit = $dailyLimit;

            if (($usage->reserved_total_tokens + $requestedTotal) > $dailyLimit) {
                throw new DailyTokenLimitExceededException(
                    $dailyLimit,
                    (int) $usage->reserved_total_tokens,
                    $requestedTotal
                );
            }

            $usage->input_tokens += $inputTokens;
            $usage->reserved_completion_tokens += $completionBudget;
            $usage->reserved_total_tokens += $requestedTotal;
            $usage->request_count += 1;
            $usage->save();

            Log::info('Reserved OpenAI token budget.', [
                'source' => $source,
                'model' => $model,
                'usage_date' => $usageDate,
                'input_tokens' => $inputTokens,
                'completion_budget' => $completionBudget,
                'reserved_total_tokens' => $requestedTotal,
                'daily_reserved_total' => $usage->reserved_total_tokens,
                'daily_limit' => $dailyLimit,
            ]);

            return [
                'usage_date' => $usageDate,
                'model' => $model,
                'source' => $source,
                'input_tokens' => $inputTokens,
                'reserved_completion_tokens' => $completionBudget,
                'reserved_total_tokens' => $requestedTotal,
            ];
        });
    }

    private function extractCompletionBudget(array $payload): int
    {
        if (isset($payload['max_completion_tokens'])) {
            return max(0, (int) $payload['max_completion_tokens']);
        }

        if (isset($payload['max_tokens'])) {
            return max(0, (int) $payload['max_tokens']);
        }

        if (isset($payload['tools']) || isset($payload['functions'])) {
            return self::DEFAULT_TOOL_CALL_MAX_COMPLETION_TOKENS;
        }

        return 0;
    }

    private function normalizeTools(array $payload): array
    {
        $normalizedTools = [];

        foreach ($payload['tools'] ?? [] as $tool) {
            if (($tool['type'] ?? null) !== 'function') {
                continue;
            }

            $function = $tool['function'] ?? [];
            if (!isset($function['name'])) {
                continue;
            }

            $normalizedTools[] = [
                'type' => 'function',
                'name' => $function['name'],
                'description' => $function['description'] ?? '',
                'parameters' => $function['parameters'] ?? (object) [],
            ];
        }

        foreach ($payload['functions'] ?? [] as $function) {
            if (!isset($function['name'])) {
                continue;
            }

            $normalizedTools[] = [
                'type' => 'function',
                'name' => $function['name'],
                'description' => $function['description'] ?? '',
                'parameters' => $function['parameters'] ?? (object) [],
            ];
        }

        return $normalizedTools;
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'assistant', 'developer', 'user' => $role,
            default => 'user',
        };
    }

    private function normalizeMessageContent(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        return json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
