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
    protected $signature = 'app:run-all-data-pipeline {--stages= : Comma-separated list of stage names to run (e.g., "Stage Name 1,Stage Name 2")}';
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

        $this->runSummary = [
            'run_id' => $this->runId,
            'name' => $this->signature,
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

        // Define stages and their commands
        $allStages = [
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
            if (!$this->executePipelineStage($commands, $stageName)) {
                $overallSuccess = false; // Mark that at least one stage had issues
                $this->logPipelineWarn("Pipeline processing will continue, but stage '{$stageName}' encountered errors.");
                // Optionally, you could decide to stop the entire pipeline if a stage fails:
                // $this->logPipelineError("Pipeline halted due to failure in stage: {$stageName}");
                // break; 
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
     * Execute a list of commands for a given pipeline stage.
     *
     * @param array $commands
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
    
    private function prepareProcessParams(array $params): array
    {
        $processParams = [];
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    // For boolean flags like --force, Artisan expects them to be present or not
                    // If $key is numeric, it's like ['--force'], if string, it's ['--option' => true]
                    $processParams[] = is_string($key) ? $key : $value;
                }
            } elseif (is_string($key)) {
                 // For options like --class=SeederName
                $processParams[] = "{$key}={$value}";
            } else {
                // For arguments
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
}
