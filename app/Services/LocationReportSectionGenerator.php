<?php

namespace App\Services;

use App\Exceptions\DailyTokenLimitExceededException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LocationReportSectionGenerator
{
    private const PRIORITY_FIELDS = [
        'case_number',
        'incident_number',
        'service_request_id',
        'permitnumber',
        'permit_number',
        'alcivartech_date',
        'date',
        'occurred_on_datetime',
        'offense_date',
        'incident_datetime',
        'created_date',
        'closed_date',
        'incident_type',
        'primary_type',
        'complaint_type',
        'service_name',
        'category',
        'descriptor',
        'offense_description',
        'incident_description',
        'description',
        'issue_description',
        'threeoneonedescription',
        'resolution_description',
        'closure_comments',
        'additional_details',
        'comments',
        'notes',
        'crime_details_concatenated',
        'offense_sub_category',
        'arrest_charges',
        'status',
        'closure_reason',
        'location_description',
        'block',
        'address',
        'incident_address',
    ];

    private const LABEL_FIELDS = [
        'incident_type',
        'offense_description',
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
        'occurred_on_datetime',
        'offense_date',
        'incident_datetime',
        'created_date',
        'report_date_time',
        'updated_on',
    ];

    private const EXCLUDED_FIELDS = [
        'data_point_id',
        'latitude',
        'longitude',
        'location_wkt',
        'alcivartech_model',
        'alcivartech_type',
        'alcivartech_type_raw',
        'alcivartech_model_class',
        'data_point_alcivartech_date_from_dp_table',
        'year',
        'month',
        'day_of_week',
        'hour',
        'incident_log_file_date',
        'incident_entry_date_parsed',
        'incident_time_parsed',
        'source_city',
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
                if ($this->looksLikePromptEcho($content)) {
                    Log::warning('OpenAI location report section looked like a prompt echo or debug dump.', [
                        'type_context' => $typeContext,
                        'language' => $language,
                        'model' => $payload['model'],
                    ]);

                    return $this->buildFallbackSection($dataPoints);
                }

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
        return "You are writing one markdown section for a paid location report. "
            . "Write entirely in {$language}. "
            . "This section is about {$typeContext}. "
            . "Use the aggregate summary to cover the full set of records, and use the sampled records only as concrete examples. "
            . "Describe actual incidents, permits, inspections, or cases. "
            . "If description-like fields are available, include those concrete details in the representative examples instead of repeating only the category or type. "
            . "Ignore internal field names, prompt metadata, and null or missing-field commentary. "
            . "Do not mention payloads, JSON, sampled_points, or data_point_id values. "
            . "Do not include a heading because the caller already adds one. "
            . "Be factual, concise, and specific.";
    }

    private function buildUserPrompt(array $promptData): string
    {
        return "Generate one markdown report section from these data points only:\n\n"
            . json_encode($promptData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function buildPromptData(array $dataPoints): array
    {
        $maxPoints = max((int) config('services.openai.location_report_prompt_max_points', 20), 1);
        $sample = $this->selectSample($dataPoints, $maxPoints);

        return [
            'summary' => [
                'total_points' => count($dataPoints),
                'sampled_points' => count($sample),
                'omitted_points' => max(count($dataPoints) - count($sample), 0),
                'date_range' => $this->extractDateRange($dataPoints),
                'counts_by_date' => $this->extractDateCounts($dataPoints),
                'top_labels' => $this->extractTopLabels($dataPoints),
            ],
            'sample_data_points' => array_map(fn ($dataPoint) => $this->simplifyDataPoint($dataPoint), $sample),
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

        $dateCounts = $this->extractDateCounts($dataPoints);
        if (!empty($dateCounts)) {
            $lines[] = '- Counts by day: ' . implode(', ', $dateCounts) . '.';
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

        foreach ($dataPoints as $dataPoint) {
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

        foreach ($dataPoints as $dataPoint) {
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

    private function extractDateCounts(array $dataPoints): array
    {
        $counts = [];

        foreach ($dataPoints as $dataPoint) {
            $date = $this->extractComparableDate($dataPoint);
            if (!$date) {
                continue;
            }

            $dateKey = $date->toDateString();
            $counts[$dateKey] = ($counts[$dateKey] ?? 0) + 1;
        }

        if (empty($counts)) {
            return [];
        }

        krsort($counts);

        $labels = [];
        foreach (array_slice($counts, 0, 7, true) as $dateKey => $count) {
            $labels[] = "{$dateKey} ({$count})";
        }

        return $labels;
    }

    private function extractExampleLines(array $dataPoints): array
    {
        $examples = [];

        foreach (array_slice($this->sortDataPointsByDateDesc($dataPoints), 0, 5) as $dataPoint) {
            $normalized = $this->simplifyDataPoint($dataPoint);
            $parts = [];

            foreach ([
                'case_number',
                'incident_number',
                'service_request_id',
                'primary_type',
                'incident_type',
                'complaint_type',
                'category',
                'service_name',
                'descriptor',
                'incident_description',
                'description',
                'issue_description',
                'threeoneonedescription',
                'resolution_description',
                'closure_comments',
                'additional_details',
                'crime_details_concatenated',
                'address',
                'incident_address',
                'location_description',
                'occurred_on_datetime',
                'date',
                'alcivartech_date',
            ] as $field) {
                $value = $normalized[$field] ?? null;
                if (!is_string($value) || trim($value) === '') {
                    continue;
                }

                $parts[] = $value;

                if (count($parts) >= 4) {
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
        $normalized = [];

        if (is_array($dataPoint)) {
            $normalized = $dataPoint;
        } elseif (is_object($dataPoint)) {
            $normalized = get_object_vars($dataPoint);
        } else {
            return ['value' => $dataPoint];
        }

        $flattened = [];

        foreach ($normalized as $key => $value) {
            if (in_array($key, self::EXCLUDED_FIELDS, true) || str_ends_with((string) $key, '_json')) {
                continue;
            }

            if (str_ends_with((string) $key, '_data') && (is_array($value) || is_object($value))) {
                foreach ($this->flattenNestedPayload($value) as $nestedKey => $nestedValue) {
                    $flattened[$nestedKey] = $nestedValue;
                }
                continue;
            }

            $flattened[$key] = $value;
        }

        return $flattened;
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

    private function flattenNestedPayload(array|object $payload): array
    {
        $values = is_array($payload) ? $payload : get_object_vars($payload);
        $flattened = [];

        foreach ($values as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $flattened[$key] = $value;
        }

        return $flattened;
    }

    private function selectSample(array $dataPoints, int $maxPoints): array
    {
        $sorted = $this->sortDataPointsByDateDesc($dataPoints);

        if (count($sorted) <= $maxPoints) {
            return $sorted;
        }

        $groupedByDate = [];
        foreach ($sorted as $dataPoint) {
            $date = $this->extractComparableDate($dataPoint);
            $dateKey = $date?->toDateString() ?? 'unknown';
            $groupedByDate[$dateKey][] = $dataPoint;
        }

        krsort($groupedByDate);

        $sample = [];
        while (count($sample) < $maxPoints && !empty($groupedByDate)) {
            foreach (array_keys($groupedByDate) as $dateKey) {
                if (empty($groupedByDate[$dateKey])) {
                    unset($groupedByDate[$dateKey]);
                    continue;
                }

                $sample[] = array_shift($groupedByDate[$dateKey]);

                if (empty($groupedByDate[$dateKey])) {
                    unset($groupedByDate[$dateKey]);
                }

                if (count($sample) >= $maxPoints) {
                    break;
                }
            }
        }

        return $sample;
    }

    private function sortDataPointsByDateDesc(array $dataPoints): array
    {
        usort($dataPoints, function ($left, $right) {
            $leftDate = $this->extractComparableDate($left)?->getTimestamp() ?? 0;
            $rightDate = $this->extractComparableDate($right)?->getTimestamp() ?? 0;

            return $rightDate <=> $leftDate;
        });

        return $dataPoints;
    }

    private function extractComparableDate(mixed $dataPoint): ?Carbon
    {
        $normalized = $this->normalizeDataPoint($dataPoint);

        foreach (self::DATE_FIELDS as $field) {
            $value = $normalized[$field] ?? null;
            if (!is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            try {
                return Carbon::parse((string) $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function looksLikePromptEcho(string $content): bool
    {
        return preg_match('/(_json|data_point_id|sampled_points|payload status|provided records)/i', $content) === 1;
    }
}
