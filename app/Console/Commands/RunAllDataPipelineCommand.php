<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Exception; // Add this line

class RunAllDataPipelineCommand extends Command
{
    protected $signature = 'app:run-all-data-pipeline';
    protected $description = 'Runs all download, processing, and seeding commands for the data pipeline with improved robustness and granular seeder control.';

    public function handle()
    {
        $this->info('Starting the full data pipeline...');
        $overallSuccess = true;

        // Define stages and their commands
        $stages = [
            'Boston Data Acquisition' => [
                ['command' => 'app:download-boston-dataset-via-scraper', 'params' => []],
            ],
            'Boston Data Seeding' => [
                ['command' => 'db:seed', 'params' => ['--class' => 'TrashSchedulesByAddressSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CrimeDataSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'ThreeOneOneSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'BuildingPermitsSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'PropertyViolationsSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'ConstructionOffHoursSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'FoodInspectionsSeeder', '--force' => true]],
            ],
            'Cambridge Data Acquisition' => [
                ['command' => 'app:download-city-dataset', 'params' => []],
                ['command' => 'app:download-cambridge-logs', 'params' => []],
            ],
            'Cambridge Data Seeding' => [
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeBuildingPermitsSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeThreeOneOneSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeFoodInspectionSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgePropertyViolationsSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeAddressesSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeIntersectionsSeeder', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeCrimeDataSeederMerge', '--force' => true]],
                ['command' => 'db:seed', 'params' => ['--class' => 'CambridgePoliceLogSeeder', '--force' => true]],
            ],
            'Everett Data Acquisition & Processing' => [
                ['command' => 'app:download-everett-pdf-markdown', 'params' => []],
                ['command' => 'app:process-everett-data', 'params' => []],
                ['command' => 'app:generate-everett-csv', 'params' => []],
            ],
            'Everett Data Seeding' => [
                ['command' => 'db:seed', 'params' => ['--class' => 'EverettCrimeDataSeeder', '--force' => true]],
            ],
            'Post-Seeding Aggregation & Caching' => [
                ['command' => 'db:seed', 'params' => ['--class' => 'DataPointSeeder', '--force' => true]],
                ['command' => 'app:cache-metrics-data', 'params' => []],
            ],
            'Reporting' => [
                ['command' => 'reports:send', 'params' => []],
            ],
        ];

        foreach ($stages as $stageName => $commands) {
            if (!$this->executePipelineStage($commands, $stageName)) {
                $overallSuccess = false; // Mark that at least one stage had issues
            }
        }

        $this->line('');
        if ($overallSuccess) {
            $this->info('Full data pipeline completed successfully.');
        } else {
            $this->warn('Full data pipeline completed, but one or more stages encountered errors. Please check the logs.');
        }
        return $overallSuccess ? 0 : 1;
    }

    /**
     * Execute a list of commands for a given pipeline stage.
     *
     * @param array $commands
     * @param string $stageName
     * @return bool True if all commands in the stage succeeded, false otherwise.
     */
    private function executePipelineStage(array $commands, string $stageName): bool
    {
        $this->line('');
        $this->info("--- Starting Stage: {$stageName} ---");
        
        foreach ($commands as $item) {
            $commandName = $item['command'];
            $params = $item['params'];

            $this->line('');
            $this->info("Running command in stage '{$stageName}': {$commandName} with params: " . json_encode($params));
            
            try {
                // Check if the command exists before calling
                if (!$this->isCommandRegistered($commandName)) {
                    $this->error("Command '{$commandName}' is not registered. Skipping this command in stage '{$stageName}'.");
                    // Optionally, consider this a stage failure
                    // $this->error("--- Stage '{$stageName}' failed because command '{$commandName}' is not registered. ---");
                    // return false; 
                    continue; // Skip this command and continue with the next in the stage
                }

                $exitCode = $this->call($commandName, $params);
           
                if ($exitCode === 0) {
                    $this->info("Successfully executed: {$commandName}");
                } else {
                    $this->error("Command {$commandName} in stage '{$stageName}' failed with exit code {$exitCode}.");
                    $this->error("--- Stage '{$stageName}' failed. ---");
                    return false; // Mark stage as failed and stop further commands in this stage
                }
            } catch (Exception $e) { // Catching generic Exception
                $this->error("Command {$commandName} in stage '{$stageName}' failed with exception: " . $e->getMessage());
                $this->error("--- Stage '{$stageName}' failed due to an exception. ---");
                return false; // Mark stage as failed
            }
        }

        $this->info("--- Stage '{$stageName}' completed successfully. ---");
        return true;
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
