<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunAllDataPipelineCommand extends Command
{
    protected $signature = 'app:run-all-data-pipeline';
    protected $description = 'Runs all download, processing, and seeding commands for the data pipeline.';

    public function handle()
    {
        $this->info('Starting the full data pipeline...');

        $commands = [
            // Download commands
            ['command' => 'app:download-city-dataset', 'params' => []],
            ['command' => 'app:download-boston-dataset-via-scraper', 'params' => []],
            ['command' => 'app:download-cambridge-logs', 'params' => []],
            // Note: The 'app:download-everett-logs' command is assumed to exist.
            // If it doesn't, this step will fail. You may need to create this command
            // or remove this line if Everett logs are obtained manually or by another process.
            ['command' => 'app:download-everett-pdf-markdown', 'params' => []],

            // Processing commands
            ['command' => 'app:process-everett-data', 'params' => []],
            ['command' => 'app:generate-everett-csv', 'params' => []],

            // Seeding commands
            ['command' => 'db:seed', 'params' => ['--force' => true]], // Using --force for production, be cautious
            ['command' => 'db:seed', 'params' => ['--class' => 'DataPointSeeder', '--force' => true]],

            // Cache metrics data
            ['command' => 'app:cache-metrics-data', 'params' => []],

            // Send reports
            ['command' => 'reports:send', 'params' => []],
        ];

        foreach ($commands as $item) {
            $commandName = $item['command'];
            $params = $item['params'];

            $this->line('');
            $this->info("Running command: {$commandName} with params: " . json_encode($params));
            
            // Check if the command exists, except for the hypothetical one
            if ($commandName === 'app:download-everett-logs' && !$this->isCommandRegistered($commandName)) {
                $this->warn("Command '{$commandName}' is not registered. Skipping this step. Please create this command if needed for Everett logs.");
                continue;
            }
            
            $exitCode = $this->call($commandName, $params);

            if ($exitCode === 0) {
                $this->info("Successfully executed: {$commandName}");
            } else {
                $this->error("Command {$commandName} failed with exit code {$exitCode}. Stopping pipeline.");
                return $exitCode; // Stop the pipeline on failure
            }
        }

        $this->line('');
        $this->info('Full data pipeline completed successfully.');
        return 0;
    }

    /**
     * Check if an Artisan command is registered.
     *
     * @param string $name
     * @return bool
     */
    private function isCommandRegistered(string $name): bool
    {
        return array_key_exists($name, Artisan::all());
    }
}
