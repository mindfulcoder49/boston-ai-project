<?php

namespace App\Support;

use Carbon\Carbon;

class PipelineRunSummary
{
    public static function enrich(array $runSummary): array
    {
        $runSummary['summary_version'] = $runSummary['summary_version'] ?? 2;
        $runSummary['commands'] = array_values($runSummary['commands'] ?? []);
        $runSummary['stages'] = array_values($runSummary['stages'] ?? []);

        foreach ($runSummary['commands'] as $index => $command) {
            $runSummary['commands'][$index]['stage_name'] = $command['stage_name'] ?? null;
            $runSummary['commands'][$index]['failure_excerpt'] = $command['failure_excerpt'] ?? null;
        }

        $runSummary['command_counts'] = self::summarizeCounts($runSummary['commands']);
        $runSummary['stages'] = self::enrichStages($runSummary['stages'], $runSummary['commands']);
        $runSummary['stage_counts'] = self::summarizeCounts($runSummary['stages']);
        $runSummary['failed_commands_count'] = $runSummary['command_counts']['failed'];
        $runSummary['first_failed_command'] = self::firstFailedCommand($runSummary['commands']);
        $runSummary['core_freshness'] = self::coreFreshness($runSummary['commands']);

        return $runSummary;
    }

    public static function historyEntry(array $runSummary): array
    {
        $runSummary = self::enrich($runSummary);

        return [
            'run_id' => $runSummary['run_id'] ?? null,
            'name' => $runSummary['name'] ?? null,
            'start_time' => $runSummary['start_time'] ?? null,
            'end_time' => $runSummary['end_time'] ?? null,
            'status' => $runSummary['status'] ?? 'unknown',
            'summary_file_path' => str_replace(storage_path(), '', $runSummary['summary_file_path'] ?? ''),
            'command_counts' => $runSummary['command_counts'],
            'stage_counts' => $runSummary['stage_counts'],
            'failed_commands_count' => $runSummary['failed_commands_count'],
            'first_failed_command' => $runSummary['first_failed_command'],
            'core_freshness' => $runSummary['core_freshness'],
        ];
    }

    public static function freshness(array $runSummary, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        if (($runSummary['status'] ?? null) === 'running') {
            return [
                'status' => 'running',
                'label' => 'Running',
                'age_hours' => 0,
                'age_human' => 'In progress',
            ];
        }

        $referenceTime = $runSummary['end_time'] ?? $runSummary['start_time'] ?? null;
        if (!$referenceTime) {
            return [
                'status' => 'unknown',
                'label' => 'Unknown',
                'age_hours' => null,
                'age_human' => 'Unknown age',
            ];
        }

        $timestamp = Carbon::parse($referenceTime);
        $ageHours = $timestamp->diffInHours($now);

        if ($ageHours < 24) {
            $status = 'fresh';
            $label = 'Fresh';
        } elseif ($ageHours < 48) {
            $status = 'aging';
            $label = 'Aging';
        } else {
            $status = 'stale';
            $label = 'Stale';
        }

        return [
            'status' => $status,
            'label' => $label,
            'age_hours' => $ageHours,
            'age_human' => $timestamp->diffForHumans($now),
        ];
    }

    private static function summarizeCounts(array $items): array
    {
        $counts = [
            'total' => count($items),
            'success' => 0,
            'failed' => 0,
            'running' => 0,
            'other' => 0,
        ];

        foreach ($items as $item) {
            $status = $item['status'] ?? 'other';

            if (isset($counts[$status])) {
                $counts[$status]++;
                continue;
            }

            if ($status === 'completed') {
                $counts['success']++;
                continue;
            }

            $counts['other']++;
        }

        return $counts;
    }

    private static function enrichStages(array $stages, array $commands): array
    {
        foreach ($stages as $index => $stage) {
            $stageName = $stage['stage_name'] ?? null;
            $stageCommands = array_values(array_filter(
                $commands,
                fn (array $command) => ($command['stage_name'] ?? null) === $stageName
            ));

            $counts = self::summarizeCounts($stageCommands);
            $stages[$index]['command_count'] = $counts['total'];
            $stages[$index]['success_count'] = $counts['success'];
            $stages[$index]['failed_count'] = $counts['failed'];

            if (
                ($stages[$index]['duration_seconds'] ?? null) === null
                && !empty($stage['start_time'])
                && !empty($stage['end_time'])
            ) {
                $stages[$index]['duration_seconds'] = Carbon::parse($stage['end_time'])
                    ->diffInSeconds(Carbon::parse($stage['start_time']));
            }
        }

        return $stages;
    }

    private static function firstFailedCommand(array $commands): ?array
    {
        foreach ($commands as $command) {
            if (($command['status'] ?? null) !== 'failed') {
                continue;
            }

            return [
                'command_name' => $command['command_name'] ?? null,
                'stage_name' => $command['stage_name'] ?? null,
                'parameters' => $command['parameters'] ?? [],
                'log_file' => $command['log_file'] ?? null,
                'duration_seconds' => $command['duration_seconds'] ?? null,
                'failure_excerpt' => $command['failure_excerpt'] ?? null,
            ];
        }

        return null;
    }

    private static function coreFreshness(array $commands): array
    {
        $components = [
            'data_point_seeder' => self::coreComponent(
                $commands,
                fn (array $command) => ($command['command_name'] ?? null) === 'db:seed'
                    && (($command['parameters']['--class'] ?? null) === 'DataPointSeeder'),
                'DataPointSeeder'
            ),
            'metrics_cache' => self::coreComponent(
                $commands,
                fn (array $command) => ($command['command_name'] ?? null) === 'app:cache-metrics-data',
                'Metrics Cache'
            ),
            'reports_send' => self::coreComponent(
                $commands,
                fn (array $command) => ($command['command_name'] ?? null) === 'reports:send',
                'Reports Dispatch'
            ),
        ];

        $seenStatuses = array_values(array_filter(
            array_map(fn (array $component) => $component['status'], $components),
            fn (string $status) => $status !== 'not_run'
        ));

        if (empty($seenStatuses)) {
            $status = 'not_applicable';
            $label = 'Not evaluated';
        } elseif (in_array('failed', $seenStatuses, true)) {
            $status = 'failed';
            $label = 'Core freshness failed';
        } elseif (in_array('running', $seenStatuses, true)) {
            $status = 'pending';
            $label = 'Core freshness pending';
        } elseif (count($seenStatuses) === count($components) && count(array_unique($seenStatuses)) === 1 && $seenStatuses[0] === 'success') {
            $status = 'preserved';
            $label = 'Core freshness preserved';
        } else {
            $status = 'partial';
            $label = 'Core freshness partial';
        }

        return [
            'status' => $status,
            'label' => $label,
            'components' => $components,
        ];
    }

    private static function coreComponent(array $commands, callable $matcher, string $label): array
    {
        $matched = null;

        foreach ($commands as $command) {
            if ($matcher($command)) {
                $matched = $command;
            }
        }

        if (!$matched) {
            return [
                'label' => $label,
                'status' => 'not_run',
                'command_name' => null,
                'stage_name' => null,
                'log_file' => null,
            ];
        }

        return [
            'label' => $label,
            'status' => $matched['status'] ?? 'unknown',
            'command_name' => $matched['command_name'] ?? null,
            'stage_name' => $matched['stage_name'] ?? null,
            'log_file' => $matched['log_file'] ?? null,
        ];
    }
}
