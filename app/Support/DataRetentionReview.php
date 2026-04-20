<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataRetentionReview
{
    public function review(array $groups = [], array $rules = [], int $sample = 5): array
    {
        return $this->resolveRules($groups, $rules)
            ->map(fn (array $rule) => $this->reviewRule($rule, $sample))
            ->values()
            ->all();
    }

    public function apply(array $groups = [], array $rules = [], ?int $batchSize = null): array
    {
        $effectiveBatchSize = max(
            $batchSize ?? (int) config('data_retention.database_apply_batch_size', 1000),
            1
        );

        return $this->resolveRules($groups, $rules)
            ->map(function (array $rule) use ($effectiveBatchSize) {
                $review = $this->reviewRule($rule, 0);

                if (($review['status'] ?? 'ok') !== 'ok') {
                    return array_merge($review, [
                        'batch_size' => $effectiveBatchSize,
                        'deleted_count' => 0,
                        'batches' => 0,
                        'remaining_candidate_count' => $review['candidate_count'],
                        'approx_deleted_bytes' => null,
                    ]);
                }

                return $this->applyRule($rule, $review, $effectiveBatchSize);
            })
            ->values()
            ->all();
    }

    private function resolveRules(array $groups, array $rules)
    {
        $groupSet = $this->normalizeFilters($groups);
        $ruleSet = $this->normalizeFilters($rules);

        return collect(config('data_retention.database_rules', []))
            ->map(fn (array $rule) => $this->normalizeRule($rule))
            ->filter(function (array $rule) use ($groupSet, $ruleSet) {
                if (!empty($groupSet) && !in_array($rule['group'], $groupSet, true)) {
                    return false;
                }

                if (!empty($ruleSet) && !in_array($rule['slug'], $ruleSet, true)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    private function normalizeFilters(array $filters): array
    {
        return collect($filters)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->values()
            ->all();
    }

    private function normalizeRule(array $rule): array
    {
        $connection = (string) ($rule['connection'] ?? config('database.default', 'mysql'));
        $table = (string) ($rule['table'] ?? '');
        $dateField = (string) ($rule['date_field'] ?? 'created_at');

        return [
            'slug' => (string) ($rule['slug'] ?? $table),
            'name' => (string) ($rule['name'] ?? $table),
            'group' => (string) ($rule['group'] ?? 'default'),
            'connection' => $connection,
            'table' => $table,
            'date_field' => $dateField,
            'retention_days' => (int) ($rule['retention_days'] ?? 0),
            'delete_key' => (string) ($rule['delete_key'] ?? 'id'),
            'sample_columns' => $this->normalizeSampleColumns($rule['sample_columns'] ?? ['id', $dateField]),
            'notes' => $rule['notes'] ?? null,
        ];
    }

    private function normalizeSampleColumns(array $sampleColumns): array
    {
        return collect($sampleColumns)
            ->filter(fn ($column) => is_string($column) && trim($column) !== '')
            ->map(fn (string $column) => trim($column))
            ->unique()
            ->values()
            ->all();
    }

    private function reviewRule(array $rule, int $sample): array
    {
        $connectionName = $rule['connection'];
        $table = $rule['table'];
        $dateField = $rule['date_field'];
        $retentionDays = (int) $rule['retention_days'];
        $cutoff = Carbon::now()->subDays($retentionDays)->toDateTimeString();
        $sampleColumns = $rule['sample_columns'];

        try {
            $connection = DB::connection($connectionName);
            $driver = $connection->getDriverName();
            $query = $this->candidateQuery($connection, $table, $dateField, $cutoff);

            $candidateCount = (clone $query)->count();
            $totalRows = $connection->table($table)->count();
            $oldestCandidate = $candidateCount > 0 ? (clone $query)->min($dateField) : null;
            $newestCandidate = $candidateCount > 0 ? (clone $query)->max($dateField) : null;
            $sampleRows = [];

            if ($candidateCount > 0 && $sample > 0) {
                $sampleRows = (clone $query)
                    ->orderBy($dateField, 'asc')
                    ->limit(max($sample, 1))
                    ->get($sampleColumns)
                    ->map(fn ($row) => (array) $row)
                    ->all();
            }

            $approxTableBytes = $this->approximateTableBytes($connection, $table);
            $approxCandidateBytes = $this->estimateBytes($approxTableBytes, $totalRows, $candidateCount);

            return [
                'status' => 'ok',
                'slug' => $rule['slug'],
                'name' => $rule['name'],
                'group' => $rule['group'],
                'connection' => $connectionName,
                'driver' => $driver,
                'table' => $table,
                'date_field' => $dateField,
                'delete_key' => $rule['delete_key'],
                'retention_days' => $retentionDays,
                'cutoff' => $cutoff,
                'total_rows' => $totalRows,
                'candidate_count' => $candidateCount,
                'candidate_pct' => $totalRows > 0
                    ? round(($candidateCount / $totalRows) * 100, 2)
                    : null,
                'oldest_candidate' => $oldestCandidate,
                'newest_candidate' => $newestCandidate,
                'approx_table_bytes' => $approxTableBytes,
                'approx_candidate_bytes' => $approxCandidateBytes,
                'sample_columns' => $sampleColumns,
                'sample_rows' => $sampleRows,
                'notes' => $rule['notes'],
            ];
        } catch (\Throwable $exception) {
            Log::error('Data retention review failed.', [
                'connection' => $connectionName,
                'table' => $table,
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'slug' => $rule['slug'],
                'name' => $rule['name'],
                'group' => $rule['group'],
                'connection' => $connectionName,
                'driver' => null,
                'table' => $table,
                'date_field' => $dateField,
                'delete_key' => $rule['delete_key'],
                'retention_days' => $retentionDays,
                'cutoff' => $cutoff,
                'total_rows' => null,
                'candidate_count' => null,
                'candidate_pct' => null,
                'oldest_candidate' => null,
                'newest_candidate' => null,
                'approx_table_bytes' => null,
                'approx_candidate_bytes' => null,
                'sample_columns' => $sampleColumns,
                'sample_rows' => [],
                'notes' => $rule['notes'],
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function applyRule(array $rule, array $review, int $batchSize): array
    {
        $connectionName = $rule['connection'];
        $table = $rule['table'];
        $dateField = $rule['date_field'];
        $deleteKey = $rule['delete_key'];
        $cutoff = $review['cutoff'];

        $deletedCount = 0;
        $batches = 0;

        try {
            $connection = DB::connection($connectionName);

            while (true) {
                $deleteIds = $this->candidateQuery($connection, $table, $dateField, $cutoff)
                    ->orderBy($deleteKey)
                    ->limit($batchSize)
                    ->pluck($deleteKey)
                    ->all();

                if (empty($deleteIds)) {
                    break;
                }

                $deletedCount += $connection->table($table)
                    ->whereIn($deleteKey, $deleteIds)
                    ->delete();

                $batches++;
            }

            $remainingCandidateCount = $this->candidateQuery($connection, $table, $dateField, $cutoff)->count();

            return array_merge($review, [
                'status' => 'ok',
                'batch_size' => $batchSize,
                'deleted_count' => $deletedCount,
                'batches' => $batches,
                'remaining_candidate_count' => $remainingCandidateCount,
                'approx_deleted_bytes' => $this->estimateBytes(
                    $review['approx_table_bytes'],
                    $review['total_rows'],
                    $deletedCount
                ),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Data retention apply failed.', [
                'connection' => $connectionName,
                'table' => $table,
                'delete_key' => $deleteKey,
                'cutoff' => $cutoff,
                'deleted_count' => $deletedCount,
                'batches' => $batches,
                'error' => $exception->getMessage(),
            ]);

            return array_merge($review, [
                'status' => 'error',
                'batch_size' => $batchSize,
                'deleted_count' => $deletedCount,
                'batches' => $batches,
                'remaining_candidate_count' => null,
                'approx_deleted_bytes' => $this->estimateBytes(
                    $review['approx_table_bytes'],
                    $review['total_rows'],
                    $deletedCount
                ),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function candidateQuery(Connection $connection, string $table, string $dateField, string $cutoff)
    {
        return $connection->table($table)
            ->whereNotNull($dateField)
            ->where($dateField, '<', $cutoff);
    }

    private function approximateTableBytes(Connection $connection, string $table): ?int
    {
        if (!in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return null;
        }

        try {
            $stats = $connection->table('information_schema.tables')
                ->selectRaw('COALESCE(data_length, 0) + COALESCE(index_length, 0) as approx_table_bytes')
                ->where('table_schema', $connection->getDatabaseName())
                ->where('table_name', $table)
                ->first();

            if ($stats === null || !isset($stats->approx_table_bytes)) {
                return null;
            }

            return (int) $stats->approx_table_bytes;
        } catch (\Throwable $exception) {
            Log::warning('Could not load table size for data retention review.', [
                'connection' => $connection->getName(),
                'table' => $table,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function estimateBytes(?int $tableBytes, ?int $totalRows, ?int $rowCount): ?int
    {
        if ($tableBytes === null || $totalRows === null || $rowCount === null) {
            return null;
        }

        if ($tableBytes <= 0 || $totalRows <= 0 || $rowCount <= 0) {
            return 0;
        }

        return (int) round(($tableBytes / $totalRows) * $rowCount);
    }
}
