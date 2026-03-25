<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup
                            {--list-targets : List available cleanup targets}
                            {--target=* : Limit cleanup to one or more configured targets}
                            {--report : Show the largest files and directories}
                            {--dry-run-before= : Preview files that would be deleted before a date}
                            {--delete-before= : Delete files modified before a date}
                            {--sample=20 : Number of files to sample in dry-run mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up old log files and downloaded datasets. Reports on the largest files and directories.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list-targets')) {
            $this->listTargets();
            return 0;
        }

        $report = (bool) $this->option('report');
        $dryRunDate = $this->option('dry-run-before');
        $deleteDate = $this->option('delete-before');
        $targets = $this->resolveTargets((array) $this->option('target'));

        if ($targets === null) {
            return 1;
        }

        $selectedModes = count(array_filter([
            $report,
            $dryRunDate !== null,
            $deleteDate !== null,
        ]));

        if ($selectedModes !== 1) {
            $this->error('Specify exactly one of --report, --dry-run-before=<YYYY-MM-DD>, or --delete-before=<YYYY-MM-DD>.');
            return 1;
        }

        if ($report) {
            $this->generateReport($targets);
        } elseif ($dryRunDate !== null) {
            $this->previewFilesBefore($dryRunDate, max((int) $this->option('sample'), 1), $targets);
        } elseif ($deleteDate !== null) {
            $this->deleteFilesBefore($deleteDate, $targets);
        } else {
            $this->error('Unknown cleanup mode.');
            return 1;
        }

        return 0;
    }

    /**
     * Generate a report of the largest files and directories.
     */
    protected function generateReport(array $targets): void
    {
        $this->info('Generating report of largest files and directories...');

        $files = [];
        $directories = [];
        $seenFilePaths = [];
        $seenDirectoryPaths = [];

        foreach ($targets as $target) {
            foreach ($target['paths'] as $path) {
                if (!File::isDirectory($path)) {
                    continue;
                }

                foreach (File::allFiles($path) as $file) {
                    $filePath = $file->getPathname();
                    if (isset($seenFilePaths[$filePath])) {
                        continue;
                    }

                    $seenFilePaths[$filePath] = true;
                    $files[] = [
                        'path' => $filePath,
                        'size' => $file->getSize(),
                    ];
                }

                foreach (File::directories($path) as $directory) {
                    if (isset($seenDirectoryPaths[$directory])) {
                        continue;
                    }

                    $seenDirectoryPaths[$directory] = true;
                    $directories[] = [
                        'path' => $directory,
                        'size' => $this->getDirectorySize($directory),
                    ];
                }
            }
        }

        $this->displayTargetSelection($targets);

        // Sort files and directories by size in descending order
        usort($files, fn ($a, $b) => $b['size'] <=> $a['size']);
        usort($directories, fn ($a, $b) => $b['size'] <=> $a['size']);

        // Get top 10 largest files and directories
        $topFiles = array_slice($files, 0, 10);
        $topDirectories = array_slice($directories, 0, 10);

        // Format size for readability
        $topFiles = array_map(fn ($file) => [$file['path'], $this->formatSize($file['size'])], $topFiles);
        $topDirectories = array_map(fn ($dir) => [$dir['path'], $this->formatSize($dir['size'])], $topDirectories);

        $this->line("\n<fg=yellow;options=bold>Top 10 Largest Files:</>");
        $this->table(['File Path', 'Size'], $topFiles);

        $this->line("\n<fg=yellow;options=bold>Top 10 Largest Directories:</>");
        $this->table(['Directory Path', 'Size'], $topDirectories);
    }

    /**
     * Delete files older than a given date.
     *
     * @param string $date
     */
    protected function deleteFilesBefore(string $date, array $targets): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('Invalid date format. Please use YYYY-MM-DD.');
            return;
        }

        $deleteTimestamp = strtotime($date);

        $this->warn("You are about to delete files modified before {$date} in the following targets:");
        $this->displayTargetSelection($targets);

        $candidateData = $this->collectCandidates($targets, $deleteTimestamp);
        $candidates = array_values($candidateData['files']);

        if (!$this->confirm('Are you sure you want to proceed?')) {
            $this->info('Cleanup cancelled.');
            return;
        }

        $this->info('Starting cleanup...');

        $deletedFilesCount = 0;
        $totalFreedSpace = 0;

        $progressBar = new ProgressBar($this->output, count($candidates));
        $progressBar->start();

        foreach ($candidates as $candidate) {
            if (File::delete($candidate['path'])) {
                $deletedFilesCount++;
                $totalFreedSpace += $candidate['size_bytes'];
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');

        $this->info("\nCleanup complete.");
        $this->line("Total files deleted: <fg=green>{$deletedFilesCount}</>");
        $this->line("Total space freed: <fg=green>{$this->formatSize($totalFreedSpace)}</>");
    }

    protected function previewFilesBefore(string $date, int $sampleSize, array $targets): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('Invalid date format. Please use YYYY-MM-DD.');
            return;
        }

        $deleteTimestamp = strtotime($date);
        $candidateData = $this->collectCandidates($targets, $deleteTimestamp);
        $candidates = array_values($candidateData['files']);
        usort($candidates, fn (array $a, array $b) => strcmp($a['last_modified'], $b['last_modified']));

        $this->info("Dry run for files modified before {$date}");
        $this->displayTargetSelection($targets);
        $this->line('Candidate file count: <fg=yellow>' . count($candidates) . '</>');
        $this->line('Estimated space to free: <fg=yellow>' . $this->formatSize($candidateData['total_size']) . '</>');

        $targetRows = array_map(
            fn (array $summary) => [
                $summary['name'],
                $summary['candidate_count'],
                $this->formatSize($summary['candidate_size']),
            ],
            $candidateData['target_summaries']
        );

        if (!empty($targetRows)) {
            $this->newLine();
            $this->line('<fg=yellow;options=bold>Target Summary:</>');
            $this->table(['Target', 'Files', 'Estimated Space'], $targetRows);
        }

        if (empty($candidates)) {
            $this->info('No files would be deleted.');
            return;
        }

        $sampleRows = array_map(
            fn (array $candidate) => [
                $candidate['last_modified'],
                $candidate['size'],
                $candidate['path'],
            ],
            array_slice($candidates, 0, $sampleSize)
        );

        $this->newLine();
        $this->line('<fg=yellow;options=bold>Oldest candidate files:</>');
        $this->table(['Last Modified', 'Size', 'Path'], $sampleRows);
    }

    protected function listTargets(): void
    {
        $targets = config('data_retention.file_targets', []);

        if (empty($targets)) {
            $this->warn('No cleanup targets are configured.');
            return;
        }

        $rows = array_map(function (array $target) {
            return [
                $target['slug'],
                $target['name'],
                !empty($target['default']) ? 'yes' : 'no',
                implode("\n", $target['paths'] ?? []),
            ];
        }, $targets);

        $this->table(['Slug', 'Name', 'Default', 'Paths'], $rows);
    }

    protected function resolveTargets(array $requestedTargets): ?array
    {
        $configuredTargets = collect(config('data_retention.file_targets', []));

        if ($configuredTargets->isEmpty()) {
            $this->error('No cleanup targets are configured.');
            return null;
        }

        $requestedTargets = collect($requestedTargets)
            ->filter(fn ($target) => is_string($target) && trim($target) !== '')
            ->map(fn (string $target) => trim($target))
            ->values();

        if ($requestedTargets->isEmpty()) {
            $targets = $configuredTargets->filter(fn (array $target) => !empty($target['default']));
            return $targets->isNotEmpty()
                ? $targets->values()->all()
                : $configuredTargets->values()->all();
        }

        $targets = [];
        $missing = [];

        foreach ($requestedTargets as $slug) {
            $target = $configuredTargets->first(fn (array $item) => ($item['slug'] ?? null) === $slug);
            if ($target) {
                $targets[] = $target;
                continue;
            }

            $missing[] = $slug;
        }

        if (!empty($missing)) {
            $this->error('Unknown cleanup target(s): ' . implode(', ', $missing));
            $this->line('Use `php artisan app:cleanup --list-targets` to see valid targets.');
            return null;
        }

        return $targets;
    }

    protected function displayTargetSelection(array $targets): void
    {
        $rows = array_map(function (array $target) {
            return [
                $target['slug'],
                $target['name'],
                implode("\n", $target['paths'] ?? []),
            ];
        }, $targets);

        $this->newLine();
        $this->line('<fg=yellow;options=bold>Selected Targets:</>');
        $this->table(['Slug', 'Name', 'Paths'], $rows);
    }

    protected function collectCandidates(array $targets, int $deleteTimestamp): array
    {
        $uniqueCandidates = [];
        $targetSummaries = [];
        $totalSize = 0;

        foreach ($targets as $target) {
            $targetCount = 0;
            $targetSize = 0;
            $excludedPaths = array_map(
                fn (string $path) => rtrim($path, DIRECTORY_SEPARATOR),
                $target['exclude_paths'] ?? []
            );

            foreach ($target['paths'] as $path) {
                if (!File::exists($path)) {
                    continue;
                }

                if (File::isFile($path)) {
                    $files = [new \SplFileInfo($path)];
                } else {
                    $files = File::allFiles($path);
                }

                foreach ($files as $file) {
                    $filePath = $file->getPathname();

                    if ($this->isExcludedPath($filePath, $excludedPaths)) {
                        continue;
                    }

                    if ($file->getMTime() >= $deleteTimestamp) {
                        continue;
                    }

                    $size = $file->getSize();

                    if (!isset($uniqueCandidates[$filePath])) {
                        $uniqueCandidates[$filePath] = [
                            'path' => $filePath,
                            'size_bytes' => $size,
                            'size' => $this->formatSize($size),
                            'last_modified' => date('Y-m-d H:i:s', $file->getMTime()),
                            'targets' => [$target['slug']],
                        ];
                        $totalSize += $size;
                    } elseif (!in_array($target['slug'], $uniqueCandidates[$filePath]['targets'], true)) {
                        $uniqueCandidates[$filePath]['targets'][] = $target['slug'];
                    }

                    $targetCount++;
                    $targetSize += $size;
                }
            }

            $targetSummaries[] = [
                'slug' => $target['slug'],
                'name' => $target['name'],
                'candidate_count' => $targetCount,
                'candidate_size' => $targetSize,
            ];
        }

        return [
            'files' => $uniqueCandidates,
            'target_summaries' => $targetSummaries,
            'total_size' => $totalSize,
        ];
    }

    protected function isExcludedPath(string $filePath, array $excludedPaths): bool
    {
        $normalizedFilePath = rtrim($filePath, DIRECTORY_SEPARATOR);

        foreach ($excludedPaths as $excludedPath) {
            if ($excludedPath === '') {
                continue;
            }

            if ($normalizedFilePath === $excludedPath) {
                return true;
            }

            if (str_starts_with($normalizedFilePath, $excludedPath . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively get the size of a directory.
     *
     * @param string $directory
     * @return int
     */
    protected function getDirectorySize(string $directory): int
    {
        $size = 0;
        $files = File::allFiles($directory);
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Format bytes into a human-readable string.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
