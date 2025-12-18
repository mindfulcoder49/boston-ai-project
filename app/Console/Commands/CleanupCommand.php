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
    protected $signature = 'app:cleanup {--report} {--delete-before=}';

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
        if ($this->option('report')) {
            $this->generateReport();
        } elseif ($date = $this->option('delete-before')) {
            $this->deleteFilesBefore($date);
        } else {
            $this->info('Please specify either --report or --delete-before=<YYYY-MM-DD>');
        }

        return 0;
    }

    /**
     * Generate a report of the largest files and directories.
     */
    protected function generateReport()
    {
        $this->info('Generating report of largest files and directories...');

        $pathsToScan = [
            storage_path('logs'),
            storage_path('app/datasets'),
        ];

        $files = [];
        $directories = [];

        foreach ($pathsToScan as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            $allFiles = File::allFiles($path);
            foreach ($allFiles as $file) {
                $files[] = [
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                ];
            }

            $allDirectories = File::directories($path);
            foreach ($allDirectories as $directory) {
                $directories[] = [
                    'path' => $directory,
                    'size' => $this->getDirectorySize($directory),
                ];
            }
        }

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
    protected function deleteFilesBefore(string $date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('Invalid date format. Please use YYYY-MM-DD.');
            return;
        }

        $deleteTimestamp = strtotime($date);
        $pathsToClean = [
            storage_path('logs'),
            storage_path('app/datasets'),
        ];

        $this->warn("You are about to delete all files modified before {$date} in the following directories:");
        foreach ($pathsToClean as $path) {
            $this->line("- {$path}");
        }

        if (!$this->confirm('Are you sure you want to proceed?')) {
            $this->info('Cleanup cancelled.');
            return;
        }

        $this->info('Starting cleanup...');

        $deletedFilesCount = 0;
        $totalFreedSpace = 0;

        foreach ($pathsToClean as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            $files = File::allFiles($path);
            $progressBar = new ProgressBar($this->output, count($files));
            $progressBar->start();

            foreach ($files as $file) {
                if ($file->getMTime() < $deleteTimestamp) {
                    $fileSize = $file->getSize();
                    if (File::delete($file->getPathname())) {
                        $deletedFilesCount++;
                        $totalFreedSpace += $fileSize;
                    }
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line(''); // for a new line after the progress bar
        }

        $this->info("\nCleanup complete.");
        $this->line("Total files deleted: <fg=green>{$deletedFilesCount}</>");
        $this->line("Total space freed: <fg=green>{$this->formatSize($totalFreedSpace)}</>");
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