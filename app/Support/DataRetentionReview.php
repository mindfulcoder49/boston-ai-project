<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataRetentionReview
{
    public function review(array $groups = [], int $sample = 5): array
    {
        $rules = collect(config('data_retention.database_rules', []));

        if (!empty($groups)) {
            $groupSet = collect($groups)
                ->filter(fn ($group) => is_string($group) && trim($group) !== '')
                ->map(fn (string $group) => trim($group))
                ->values()
                ->all();

            $rules = $rules->filter(function (array $rule) use ($groupSet) {
                return in_array($rule['group'] ?? '', $groupSet, true);
            });
        }

        return $rules
            ->map(fn (array $rule) => $this->reviewRule($rule, $sample))
            ->values()
            ->all();
    }

    private function reviewRule(array $rule, int $sample): array
    {
        $connection = $rule['connection'];
        $table = $rule['table'];
        $dateField = $rule['date_field'];
        $retentionDays = (int) $rule['retention_days'];
        $cutoff = Carbon::now()->subDays($retentionDays)->toDateTimeString();
        $sampleColumns = $rule['sample_columns'] ?? ['id', $dateField];

        try {
            $query = DB::connection($connection)->table($table)->where($dateField, '<', $cutoff);

            $candidateCount = (clone $query)->count();
            $oldestCandidate = (clone $query)->min($dateField);
            $newestCandidate = (clone $query)->max($dateField);
            $sampleRows = (clone $query)
                ->orderBy($dateField, 'asc')
                ->limit(max($sample, 1))
                ->get($sampleColumns)
                ->map(fn ($row) => (array) $row)
                ->all();

            return [
                'status' => 'ok',
                'name' => $rule['name'] ?? $table,
                'group' => $rule['group'] ?? 'default',
                'connection' => $connection,
                'table' => $table,
                'date_field' => $dateField,
                'retention_days' => $retentionDays,
                'cutoff' => $cutoff,
                'candidate_count' => $candidateCount,
                'oldest_candidate' => $oldestCandidate,
                'newest_candidate' => $newestCandidate,
                'sample_columns' => $sampleColumns,
                'sample_rows' => $sampleRows,
            ];
        } catch (\Throwable $exception) {
            Log::error('Data retention review failed.', [
                'connection' => $connection,
                'table' => $table,
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'name' => $rule['name'] ?? $table,
                'group' => $rule['group'] ?? 'default',
                'connection' => $connection,
                'table' => $table,
                'date_field' => $dateField,
                'retention_days' => $retentionDays,
                'cutoff' => $cutoff,
                'candidate_count' => null,
                'oldest_candidate' => null,
                'newest_candidate' => null,
                'sample_columns' => $sampleColumns,
                'sample_rows' => [],
                'error' => $exception->getMessage(),
            ];
        }
    }
}
