<?php

namespace App\Services;

use App\Exceptions\DailyTokenLimitExceededException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocationReportSectionGenerator
{
    private const PRIORITY_FIELDS = [
        'alcivartech_date',
        'date',
        'offense_date',
        'incident_datetime',
        'created_date',
        'primary_type',
        'description',
        'category',
        'service_name',
        'location_description',
        'block',
        'address',
    ];

    private const LABEL_FIELDS = [
        'primary_type',
        'category',
        'service_name',
        'description',
        'location_description',
        'type',
    ];

    private const DATE_FIELDS = [
        'alcivartech_date',
        'date',
        'offense_date',
        'incident_datetime',
        'created_date',
        'report_date_time',
        'updated_on',
    ];

    public function __construct(
        private readonly OpenAIService $openAIService
    ) {}

    public function generate(string $typeContext, array $dataPoints, string $language): string
    {
        if (empty($dataPoints)) {
            return 'No report generated.';
        }

        $promptData = $this->buildPromptData($dataPoints);
        $payload = [
            'model' => config('services.openai.location_report_model', 'gpt-5-mini'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($typeContext, $language),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildUserPrompt($promptData),
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

            Log::warning('OpenAI location report section returned empty content.', [
                'type_context' => $typeContext,
                'language' => $language,
                'model' => $payload['model'],
                'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
                'usage' => $response['usage'] ?? null,
                'total_points' => $promptData['summary']['total_points'] ?? count($dataPoints),
                'sampled_points' => $promptData['summary']['sampled_points'] ?? null,
            ]);

            return $this->buildFallbackSection($dataPoints);
        } catch (DailyTokenLimitExceededException $e) {
            Log::warning('Daily OpenAI token cap reached for location report section.', [
                'type_context' => $typeContext,
                'language' => $language,
                'model' => $payload['model'],
                'remaining_tokens' => $e->getRemainingTokens(),
            ]);

            return $this->buildFallbackSection($dataPoints);
        } catch (\Throwable $e) {
            Log::error('Error generating OpenAI location report section.', [
                'type_context' => $typeContext,
                'language' => $language,
                'model' => $payload['model'],
                'error' => $e->getMessage(),
            ]);

            return $this->buildFallbackSection($dataPoints);
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

    private function buildUserPrompt(array $promptData): string
    {
        return "Generate one markdown report section from these data points only:\n\n"
            . json_encode($promptData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function buildPromptData(array $dataPoints): array
    {
        $maxPoints = max((int) config('services.openai.location_report_prompt_max_points', 20), 1);
        $sample = array_slice($dataPoints, 0, $maxPoints);

        return [
            'summary' => [
                'total_points' => count($dataPoints),
                'sampled_points' => count($sample),
                'date_range' => $this->extractDateRange($dataPoints),
                'top_labels' => $this->extractTopLabels($dataPoints),
            ],
            'sample_data_points' => array_map(fn($dataPoint) => $this->simplifyDataPoint($dataPoint), $sample),
        ];
    }

    private function simplifyDataPoint(mixed $dataPoint): array
    {
        $normalized = $this->normalizeDataPoint($dataPoint);
        $maxFields = max((int) config('services.openai.location_report_max_fields_per_point', 12), 1);
        $simplified = [];

        foreach (self::PRIORITY_FIELDS as $field) {
            if (!array_key_exists($field, $normalized)) {
                continue;
            }

            $value = $this->normalizeScalarValue($normalized[$field]);
            if ($value === null) {
                continue;
            }

            $simplified[$field] = $value;

            if (count($simplified) >= $maxFields) {
                return $simplified;
            }
        }

        foreach ($normalized as $field => $value) {
            if (array_key_exists($field, $simplified)) {
                continue;
            }

            $normalizedValue = $this->normalizeScalarValue($value);
            if ($normalizedValue === null) {
                continue;
            }

            $simplified[$field] = $normalizedValue;

            if (count($simplified) >= $maxFields) {
                break;
            }
        }

        return $simplified;
    }

    private function buildFallbackSection(array $dataPoints): string
    {
        $totalPoints = count($dataPoints);
        $lines = [
            sprintf('- %d record%s matched this section.', $totalPoints, $totalPoints === 1 ? '' : 's'),
        ];

        $dateRange = $this->extractDateRange($dataPoints);
        if ($dateRange !== null) {
            $lines[] = "- Time span in source data: {$dateRange}.";
        }

        $topLabels = $this->extractTopLabels($dataPoints);
        if (!empty($topLabels)) {
            $lines[] = '- Most common labels: ' . implode(', ', $topLabels) . '.';
        }

        $examples = $this->extractExampleLines($dataPoints);
        if (!empty($examples)) {
            $lines[] = '- Examples: ' . implode('; ', $examples) . '.';
        }

        return implode("\n", $lines);
    }

    private function extractTopLabels(array $dataPoints): array
    {
        $counts = [];

        foreach (array_slice($dataPoints, 0, 100) as $dataPoint) {
            $normalized = $this->normalizeDataPoint($dataPoint);

            foreach (self::LABEL_FIELDS as $field) {
                $value = $this->normalizeScalarValue($normalized[$field] ?? null);
                if (!is_string($value) || $value === '') {
                    continue;
                }

                $counts[$value] = ($counts[$value] ?? 0) + 1;
                break;
            }
        }

        arsort($counts);

        return array_map(
            fn(string $label, int $count) => "{$label} ({$count})",
            array_keys(array_slice($counts, 0, 3, true)),
            array_values(array_slice($counts, 0, 3, true))
        );
    }

    private function extractDateRange(array $dataPoints): ?string
    {
        $dates = [];

        foreach (array_slice($dataPoints, 0, 100) as $dataPoint) {
            $normalized = $this->normalizeDataPoint($dataPoint);

            foreach (self::DATE_FIELDS as $field) {
                $value = $normalized[$field] ?? null;
                if (!is_scalar($value) || trim((string) $value) === '') {
                    continue;
                }

                try {
                    $dates[] = Carbon::parse((string) $value);
                    break;
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        if (empty($dates)) {
            return null;
        }

        usort($dates, fn(Carbon $a, Carbon $b) => $a->getTimestamp() <=> $b->getTimestamp());

        $start = $dates[0];
        $end = $dates[count($dates) - 1];

        return $start->isSameDay($end)
            ? $start->toDateString()
            : $start->toDateString() . ' to ' . $end->toDateString();
    }

    private function extractExampleLines(array $dataPoints): array
    {
        $examples = [];

        foreach (array_slice($dataPoints, 0, 5) as $dataPoint) {
            $normalized = $this->simplifyDataPoint($dataPoint);
            $parts = [];

            foreach (['primary_type', 'category', 'description', 'service_name', 'block', 'address', 'location_description', 'date', 'alcivartech_date'] as $field) {
                $value = $normalized[$field] ?? null;
                if (!is_string($value) || trim($value) === '') {
                    continue;
                }

                $parts[] = $value;

                if (count($parts) >= 3) {
                    break;
                }
            }

            if (!empty($parts)) {
                $examples[] = implode(' | ', $parts);
            }
        }

        return array_slice($examples, 0, 3);
    }

    private function normalizeDataPoint(mixed $dataPoint): array
    {
        if (is_array($dataPoint)) {
            return $dataPoint;
        }

        if (is_object($dataPoint)) {
            return get_object_vars($dataPoint);
        }

        return ['value' => $dataPoint];
    }

    private function normalizeScalarValue(mixed $value): string|int|float|bool|null
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        return Str::limit(
            $trimmed,
            max((int) config('services.openai.location_report_max_value_length', 160), 40),
            '...'
        );
    }
}
