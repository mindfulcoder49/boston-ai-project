<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunAllDataPipelineCommand extends Command
{
    protected $signature = 'app:run-all-data-pipeline 
                            {--stages= : Comma-separated list of stage names to run (e.g., "Stage Name 1,Stage Name 2")}
                            {--boston-datasets= : Comma-separated list of Boston dataset names to download}
                            {--boston-seeders= : Comma-separated list of Boston seeder classes to run}
                            {--cambridge-datasets= : Comma-separated list of Cambridge dataset names to download (e.g., cambridge-311-service-requests)}
                            {--cambridge-seeders= : Comma-separated list of Cambridge seeder classes to run}
                            {--everett-steps= : Comma-separated list of Everett processing commands to run}
                            {--everett-seeders= : Comma-separated list of Everett seeder classes to run}
                            {--chicago-datasets= : Comma-separated list of Chicago dataset names to download}
                            {--chicago-seeders= : Comma-separated list of Chicago seeder classes to run}
                            {--san-francisco-datasets= : Comma-separated list of San Francisco dataset names to download}
                            {--san-francisco-seeders= : Comma-separated list of San Francisco seeder classes to run}
                            {--seattle-datasets= : Comma-separated list of Seattle dataset names to download}
                            {--seattle-seeders= : Comma-separated list of Seattle seeder classes to run}
                            {--montgomery-county-md-datasets= : Comma-separated list of Montgomery County MD dataset names to download}
                            {--montgomery-county-md-seeders= : Comma-separated list of Montgomery County MD seeder classes to run}
                            {--post-seeding-steps= : Comma-separated list of post-seeding commands to run}
                            {--reporting-steps= : Comma-separated list of reporting commands to run}';
    protected $description = 'Runs all or specified download, processing, and seeding commands for the data pipeline with file-based logging.';

    private string $runId;
    private string $runLogDir;
    private array $runSummary;
    private string $historyFilePath;

    public function __construct()
    {
        parent::__construct();
        $this->historyFilePath = storage_path('logs/pipeline_runs_history.json');
    }

    private function initializeRun(): void
    {
        $this->runId = Carbon::now()->format('YmdHis') . '_' . Str::uuid()->toString();
        $this->runLogDir = storage_path('logs/pipeline_runs/' . $this->runId);
        File::ensureDirectoryExists($this->runLogDir);

        $stagesOption = $this->option('stages');
        $stagesInName = $stagesOption ? ' (' . $stagesOption . ')' : '';

        $this->runSummary = [
            'run_id' => $this->runId,
            'name' => 'app:run-all-data-pipeline' . $stagesInName,
            'start_time' => Carbon::now()->toIso8601String(),
            'end_time' => null,
            'status' => 'running',
            'commands' => [],
            'summary_file_path' => $this->runLogDir . '/run_summary.json',
        ];
        $this->writeRunSummary();
    }

    private function writeRunSummary(): void
    {
        File::put($this->runLogDir . '/run_summary.json', json_encode($this->runSummary, JSON_PRETTY_PRINT));
    }

    private function updateHistoryFile(bool $isFinalUpdate = false): void
    {
        $history = [];
        if (File::exists($this->historyFilePath)) {
            $history = json_decode(File::get($this->historyFilePath), true) ?: [];
        }

        $runEntry = [
            'run_id' => $this->runSummary['run_id'],
            'name' => $this->runSummary['name'],
            'start_time' => $this->runSummary['start_time'],
            'status' => $this->runSummary['status'],
            'summary_file_path' => str_replace(storage_path(), '', $this->runSummary['summary_file_path']), // Relative path
        ];
        if ($isFinalUpdate) {
             $runEntry['end_time'] = $this->runSummary['end_time'];
        }


        // Update existing entry or add new one
        $found = false;
        foreach ($history as $key => $entry) {
            if ($entry['run_id'] === $this->runId) {
                $history[$key] = $runEntry;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $history[] = $runEntry;
        }
        
        // Keep history to a reasonable size, e.g., last 50 runs
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }


        File::put($this->historyFilePath, json_encode(array_values($history), JSON_PRETTY_PRINT));
    }


    public function handle()
    {
        $this->initializeRun();
        $this->updateHistoryFile(); // Initial entry in history

        $this->logPipelineInfo('Starting the data pipeline with file logging...');
        $overallSuccess = true;

        // Define stages and their commands with keys for filtering
        $allStages = [
            'Boston Data Acquisition' => $this->getFilteredCommands('boston-datasets', [
                'app:download-boston-dataset-via-scraper' => ['command' => 'app:download-boston-dataset-via-scraper', 'params' => ['--names' => $this->option('boston-datasets')]],
            ]),
            'Boston Data Seeding' => $this->getFilteredCommands('boston-seeders', [
                'TrashSchedulesByAddressSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'TrashSchedulesByAddressSeeder', '--force' => true]],
                'CrimeDataSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'CrimeDataSeeder', '--force' => true]],
                'ThreeOneOneSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'ThreeOneOneSeeder', '--force' => true]],
                'BuildingPermitsSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'BuildingPermitsSeeder', '--force' => true]],
                'PropertyViolationsSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'PropertyViolationsSeeder', '--force' => true]],
                'ConstructionOffHoursSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'ConstructionOffHoursSeeder', '--force' => true]],
                'FoodInspectionsSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'FoodInspectionsSeeder', '--force' => true]],
            ]),
            'Cambridge Data Acquisition' => $this->getFilteredCommands('cambridge-datasets', [
                'app:download-cambridge-logs' => ['command' => 'app:download-cambridge-logs', 'params' => []],
                'cambridge-311-service-requests' => ['command' => 'app:download-city-dataset', 'params' => ['cambridge-311-service-requests']],
                'cambridge-building-permits' => ['command' => 'app:download-city-dataset', 'params' => ['cambridge-building-permits']],
                'cambridge-sanitary-inspections' => ['command' => 'app:download-city-dataset', 'params' => ['cambridge-sanitary-inspections']],
                'cambridge-housing-code-violations' => ['command' => 'app:download-city-dataset', 'params' => ['cambridge-housing-code-violations']],
                'cambridge-crime-reports' => ['command' => 'app:download-city-dataset', 'params' => ['cambridge-crime-reports']],
                'cambridge-master-addresses-list' => ['command' => 'app:download-city-dataset', 'params' => ['cambridge-master-addresses-list']],
                'cambridge-master-intersections-list' => ['command' => 'app:download-city-dataset', 'params' => ['cambridge-master-intersections-list']],
            ]),
            'Cambridge Data Seeding' => $this->getFilteredCommands('cambridge-seeders', [
                'NativeCambridgeBuildingPermitsSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'NativeCambridgeBuildingPermitsSeeder', '--force' => true]],
                'NativeCambridgeThreeOneOneSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'NativeCambridgeThreeOneOneSeeder', '--force' => true]],
                'NativeCambridgeSanitaryInspectionsSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'NativeCambridgeSanitaryInspectionsSeeder', '--force' => true]],
                'NativeCambridgeHousingViolationsSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'NativeCambridgeHousingViolationsSeeder', '--force' => true]],
                'CambridgeAddressesSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeAddressesSeeder', '--force' => true]],
                'CambridgeIntersectionsSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeIntersectionsSeeder', '--force' => true]],
                'CambridgeCrimeDataSeederMerge' => ['command' => 'db:seed', 'params' => ['--class' => 'CambridgeCrimeDataSeederMerge', '--force' => true]],
                'CambridgePoliceLogSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'CambridgePoliceLogSeeder', '--force' => true]],
            ]),
            'Everett Data Acquisition & Processing' => $this->getFilteredCommands('everett-steps', [
                'app:download-everett-pdf-markdown' => ['command' => 'app:download-everett-pdf-markdown', 'params' => []],
                'everett:process-data' => ['command' => 'everett:process-data', 'params' => ['api' => 'places']],
                'app:generate-everett-csv' => ['command' => 'app:generate-everett-csv', 'params' => []],
            ]),
            'Everett Data Seeding' => $this->getFilteredCommands('everett-seeders', [
                'EverettCrimeDataSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'EverettCrimeDataSeeder', '--force' => true]],
            ]),
            'Chicago Data Acquisition' => $this->getFilteredCommands('chicago-datasets', [
                'chicago-crimes-2001-to-present' => ['command' => 'app:download-city-dataset', 'params' => ['chicago-crimes-2001-to-present']],
            ]),
            'Chicago Data Seeding' => $this->getFilteredCommands('chicago-seeders', [
                'ChicagoCrimeSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'ChicagoCrimeSeeder', '--force' => true]],
                'ChicagoDataPointSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'ChicagoDataPointSeeder', '--force' => true]],
            ]),
            'San Francisco Data Acquisition' => $this->getFilteredCommands('san-francisco-datasets', [
                'san_francisco-crimes' => ['command' => 'app:download-city-dataset', 'params' => ['san_francisco-crimes']],
            ]),
            'San Francisco Data Seeding' => $this->getFilteredCommands('san-francisco-seeders', [
                'SanFranciscoCrimeSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'SanFranciscoCrimeSeeder', '--force' => true]],
                'SanFranciscoDataPointSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'SanFranciscoDataPointSeeder', '--force' => true]],
            ]),
            'Seattle Data Acquisition' => $this->getFilteredCommands('seattle-datasets', [
                'seattle-crimes' => ['command' => 'app:download-city-dataset', 'params' => ['seattle-crimes']],
            ]),
            'Seattle Data Seeding' => $this->getFilteredCommands('seattle-seeders', [
                'SeattleCrimeSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'SeattleCrimeSeeder', '--force' => true]],
                'SeattleDataPointSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'SeattleDataPointSeeder', '--force' => true]],
            ]),
            'Montgomery County MD Data Acquisition' => $this->getFilteredCommands('montgomery-county-md-datasets', [
                'montgomery_county_md-crimes' => ['command' => 'app:download-city-dataset', 'params' => ['montgomery_county_md-crimes']],
            ]),
            'Montgomery County MD Data Seeding' => $this->getFilteredCommands('montgomery-county-md-seeders', [
                'MontgomeryCountyMdCrimeSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'MontgomeryCountyMdCrimeSeeder', '--force' => true]],
                'MontgomeryCountyMdDataPointSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'MontgomeryCountyMdDataPointSeeder', '--force' => true]],
            ]),
            'Post-Seeding Aggregation & Caching' => $this->getFilteredCommands('post-seeding-steps', [
                'DataPointSeeder' => ['command' => 'db:seed', 'params' => ['--class' => 'DataPointSeeder', '--force' => true]],
                'app:cache-metrics-data' => ['command' => 'app:cache-metrics-data', 'params' => []],
            ]),
            'Reporting' => $this->getFilteredCommands('reporting-steps', [
                'reports:send' => ['command' => 'reports:send', 'params' => []],
            ]),
        ];

        $stagesToRun = $allStages;
        $selectedStagesOption = $this->option('stages');

        if (!empty($selectedStagesOption)) {
            $selectedStageNames = array_map('trim', explode(',', $selectedStagesOption));
            $stagesToRun = [];
            $availableStageKeys = array_keys($allStages);

            $this->logPipelineInfo("Attempting to run specified stages: " . implode(', ', $selectedStageNames));

            foreach ($selectedStageNames as $selectedName) {
                $found = false;
                // Case-insensitive check for stage names
                foreach ($availableStageKeys as $availableKey) {
                    if (strcasecmp($selectedName, $availableKey) === 0) {
                        $stagesToRun[$availableKey] = $allStages[$availableKey];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $this->logPipelineWarn("Specified stage '{$selectedName}' not found or is invalid. Skipping. Available stages: " . implode(', ', $availableStageKeys));
                }
            }

            if (empty($stagesToRun)) {
                $this->logPipelineError("No valid stages selected from the input: '{$selectedStagesOption}'. Aborting pipeline.");
                $this->runSummary['status'] = 'failed';
                $this->runSummary['end_time'] = Carbon::now()->toIso8601String();
                $this->writeRunSummary();
                $this->updateHistoryFile(true);
                return 1; // Indicate failure
            }
            $this->logPipelineInfo("Pipeline will execute the following stages: " . implode(', ', array_keys($stagesToRun)));
        } else {
            $this->logPipelineInfo("No specific stages selected via --stages option. Running all " . count($allStages) . " stages.");
        }

        foreach ($stagesToRun as $stageName => $commands) {
            if (empty($commands)) {
                $this->logPipelineInfo("--- Skipping Stage: {$stageName} (no commands selected) ---");
                continue;
            }
            if (!$this->executePipelineStage($commands, $stageName)) {
                $overallSuccess = false; // Mark that at least one stage had issues
                $this->logPipelineWarn("Pipeline processing will continue, but stage '{$stageName}' encountered errors.");
            }
        }

        $this->line('');
        $this->runSummary['end_time'] = Carbon::now()->toIso8601String();
        if ($overallSuccess) {
            $this->logPipelineInfo('Full data pipeline completed successfully.');
            $this->runSummary['status'] = 'completed';
        } else {
            $this->logPipelineWarn('Full data pipeline completed, but one or more stages encountered errors.');
            $this->runSummary['status'] = 'failed';
        }
        
        $this->writeRunSummary();
        $this->updateHistoryFile(true); // Final update to history

        return $overallSuccess ? 0 : 1;
    }

    /**
     * Helper to filter commands based on a command-line option.
     *
     * @param string $optionName The name of the option (e.g., 'boston-seeders').
     * @param array $availableCommands The list of all available commands for the stage.
     * @return array The filtered list of commands to run.
     */
    private function getFilteredCommands(string $optionName, array $availableCommands): array
    {
        $selectedOption = $this->option($optionName);

        // Special handling for boston-datasets which is a parameter, not a command name
        if ($optionName === 'boston-datasets') {
            return empty($selectedOption) ? $availableCommands : array_values($availableCommands);
        }

        if (empty($selectedOption)) {
            return $availableCommands; // If no option is provided, run all commands for the stage
        }

        $selectedItems = array_map('trim', explode(',', $selectedOption));
        
        return array_filter($availableCommands, function ($key) use ($selectedItems) {
            return in_array($key, $selectedItems);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function prepareProcessParams(array $params): array
    {
        $processParams = [];
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $processParams[] = is_string($key) ? $key : $value;
                }
            } elseif (is_string($key) && Str::startsWith($key, '--')) {
                $processParams[] = "{$key}={$value}";
            } else {
                $processParams[] = $value;
            }
        }
        return $processParams;
    }

    private function logPipelineInfo(string $message, array $context = [])
    {
        $this->info($message);
        Log::channel('stack')->info("[PipelineRun:{$this->runId}] " . $message, $context);
    }

    private function logPipelineWarn(string $message, array $context = [])
    {
        $this->warn($message);
        Log::channel('stack')->warning("[PipelineRun:{$this->runId}] " . $message, $context);
    }

    private function logPipelineError(string $message, array $context = [])
    {
        $this->error($message);
        Log::channel('stack')->error("[PipelineRun:{$this->runId}] " . $message, $context);
    }

    /**
     * Executes a pipeline stage by running its commands.
     *
     * @param array $commands The list of commands to run in the stage.
     * @param string $stageName
     * @return bool True if all commands in the stage succeeded, false otherwise.
     */
    private function executePipelineStage(array $commands, string $stageName): bool
    {
        $this->logPipelineInfo("--- Starting Stage: {$stageName} ---");
        $stageSuccess = true;

        foreach ($commands as $item) {
            $commandName = $item['command'];
            $params = $item['params'];
            $commandStartTime = Carbon::now();
            
            $sanitizedCommandName = Str::slug($commandName);
            $commandLogFileName = "cmd_{$sanitizedCommandName}_{$commandStartTime->format('YmdHis_u')}.log"; 
            $commandLogFilePath = $this->runLogDir . '/' . $commandLogFileName;

            $currentCommandDetails = [
                'command_name' => $commandName,
                'parameters' => $params,
                'start_time' => $commandStartTime->toIso8601String(),
                'end_time' => null,
                'duration_seconds' => null,
                'status' => 'running', // Initial status
                'log_file' => $commandLogFileName,
            ];
            
            $this->runSummary['commands'][] = $currentCommandDetails;
            $currentCommandIndex = count($this->runSummary['commands']) - 1; 
            
            $this->writeRunSummary(); // Update summary with 'running' status before execution

            $this->logPipelineInfo("Running command in stage '{$stageName}': {$commandName}");

            $processCommand = array_merge([PHP_BINARY, base_path('artisan'), $commandName], $this->prepareProcessParams($params));
            $process = new Process($processCommand);
            $process->setTimeout(3600); // 1 hour timeout

            try {
                $process->mustRun(function ($type, $buffer) use ($commandLogFilePath) {
                    File::append($commandLogFilePath, $buffer); 
                    if (Process::ERR === $type) {
                        $this->output->write("<error>{$buffer}</error>"); 
                    } else {
                        $this->output->write($buffer); 
                    }
                });
                $this->runSummary['commands'][$currentCommandIndex]['status'] = 'success';
                $this->logPipelineInfo("Successfully executed: {$commandName}");
            } catch (ProcessFailedException $exception) {
                $this->logPipelineError("Command {$commandName} in stage '{$stageName}' failed.");
                File::append($commandLogFilePath, $exception->getMessage() . "\n" . $exception->getProcess()->getErrorOutput()); 
                $this->runSummary['commands'][$currentCommandIndex]['status'] = 'failed';
                $stageSuccess = false;
            } catch (Exception $e) {
                $this->logPipelineError("Command {$commandName} in stage '{$stageName}' failed with general exception: " . $e->getMessage());
                File::append($commandLogFilePath, "General Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                $this->runSummary['commands'][$currentCommandIndex]['status'] = 'failed';
                $stageSuccess = false;
            }
            
            // Finalize timing and status for the current command
            $commandEndTime = Carbon::now();
            $this->runSummary['commands'][$currentCommandIndex]['end_time'] = $commandEndTime->toIso8601String();
            $this->runSummary['commands'][$currentCommandIndex]['duration_seconds'] = $commandEndTime->diffInSeconds($commandStartTime);
            // Status is already set in try/catch blocks

            $this->writeRunSummary(); // Write summary after each command's details are finalized.

            if (!$stageSuccess) { // If this command failed
                 $this->logPipelineError("--- Stage '{$stageName}' failed due to error in command '{$commandName}'. Halting stage. ---");
                 return false; // Stop current stage and report failure
            }
        } // End of foreach ($commands as $item)

        // If the loop completes without returning false, the stage was successful
        $this->logPipelineInfo("--- Stage '{$stageName}' completed successfully. ---");
        return true;
    }
}
