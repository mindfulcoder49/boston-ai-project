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
use Symfony\Component\Console\Input\InputOption;
use App\Support\AdminPipelineConfig;
use App\Support\OperationalSummaryLogger;
use App\Support\PipelineRunSummary;
use App\Support\PipelineRunStore;

class RunAllDataPipelineCommand extends Command
{
    protected $signature = 'app:run-all-data-pipeline
                            {--stages= : Comma-separated list of stage names to run (e.g., "Stage Name 1,Stage Name 2")}';
    protected $description = 'Runs all or specified download, processing, and seeding commands for the data pipeline with file-based logging.';

    private string $runId;
    private string $runLogDir;
    private array $runSummary;
    private string $historyFilePath;

    public function __construct()
    {
        parent::__construct();
        $this->historyFilePath = (string) config('backend_admin.pipeline_runs.history_path', storage_path('logs/pipeline_runs_history.json'));

        foreach (AdminPipelineConfig::getOptionDefinitions() as $option) {
            $this->getDefinition()->addOption(new InputOption(
                $option['name'],
                null,
                InputOption::VALUE_OPTIONAL,
                $option['description']
            ));
        }
    }

    private function initializeRun(): void
    {
        $this->runId = Carbon::now()->format('YmdHis') . '_' . Str::uuid()->toString();
        $this->runLogDir = app(PipelineRunStore::class)->runDirectory($this->runId);
        File::ensureDirectoryExists($this->runLogDir);

        $stagesOption = $this->option('stages');
        $stagesInName = $stagesOption ? ' (' . $stagesOption . ')' : '';

        $this->runSummary = [
            'summary_version' => 2,
            'run_id' => $this->runId,
            'name' => 'app:run-all-data-pipeline' . $stagesInName,
            'start_time' => Carbon::now()->toIso8601String(),
            'end_time' => null,
            'status' => 'running',
            'commands' => [],
            'stages' => [],
            'summary_file_path' => $this->runLogDir . '/run_summary.json',
        ];
        $this->writeRunSummary();
    }

    private function writeRunSummary(): void
    {
        $this->runSummary = PipelineRunSummary::enrich($this->runSummary);
        File::put($this->runLogDir . '/run_summary.json', json_encode($this->runSummary, JSON_PRETTY_PRINT));
    }

    private function updateHistoryFile(bool $isFinalUpdate = false): void
    {
        $history = [];
        if (File::exists($this->historyFilePath)) {
            $history = json_decode(File::get($this->historyFilePath), true) ?: [];
        }

        $runEntry = PipelineRunSummary::historyEntry($this->runSummary);
        if ($isFinalUpdate) {
             $runEntry['end_time'] = $this->runSummary['end_time'];
        } else {
             unset($runEntry['end_time']);
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

        $allStages = AdminPipelineConfig::getStageCommandMap(fn (string $option) => $this->option($option));

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
        $stageStartTime = Carbon::now();

        $this->runSummary['stages'][] = [
            'stage_name' => $stageName,
            'start_time' => $stageStartTime->toIso8601String(),
            'end_time' => null,
            'duration_seconds' => null,
            'status' => 'running',
        ];
        $stageIndex = count($this->runSummary['stages']) - 1;
        $this->writeRunSummary();

        foreach ($commands as $item) {
            $commandName = $item['command'];
            $params = $item['params'];
            $commandStartTime = Carbon::now();
            
            $sanitizedCommandName = Str::slug($commandName);
            $commandLogFileName = "cmd_{$sanitizedCommandName}_{$commandStartTime->format('YmdHis_u')}.log"; 
            $commandLogFilePath = $this->runLogDir . '/' . $commandLogFileName;

            $currentCommandDetails = [
                'command_name' => $commandName,
                'stage_name' => $stageName,
                'parameters' => $params,
                'start_time' => $commandStartTime->toIso8601String(),
                'end_time' => null,
                'duration_seconds' => null,
                'status' => 'running', // Initial status
                'log_file' => $commandLogFileName,
                'failure_excerpt' => null,
                'summary_events' => [],
                'latest_summary_event' => null,
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
                $this->runSummary['commands'][$currentCommandIndex]['failure_excerpt'] = $this->buildFailureExcerpt(
                    $exception->getProcess()->getErrorOutput(),
                    $exception->getProcess()->getOutput(),
                    $exception->getMessage()
                );
                $stageSuccess = false;
            } catch (Exception $e) {
                $this->logPipelineError("Command {$commandName} in stage '{$stageName}' failed with general exception: " . $e->getMessage());
                File::append($commandLogFilePath, "General Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                $this->runSummary['commands'][$currentCommandIndex]['status'] = 'failed';
                $this->runSummary['commands'][$currentCommandIndex]['failure_excerpt'] = Str::limit(trim($e->getMessage()), 240);
                $stageSuccess = false;
            }
            
            // Finalize timing and status for the current command
            $commandEndTime = Carbon::now();
            $this->runSummary['commands'][$currentCommandIndex]['end_time'] = $commandEndTime->toIso8601String();
            $this->runSummary['commands'][$currentCommandIndex]['duration_seconds'] = $commandEndTime->diffInSeconds($commandStartTime);
            $this->runSummary['commands'][$currentCommandIndex]['summary_events'] = OperationalSummaryLogger::extractFromFile($commandLogFilePath);
            $this->runSummary['commands'][$currentCommandIndex]['latest_summary_event'] =
                !empty($this->runSummary['commands'][$currentCommandIndex]['summary_events'])
                    ? end($this->runSummary['commands'][$currentCommandIndex]['summary_events'])
                    : null;
            // Status is already set in try/catch blocks

            $this->writeRunSummary(); // Write summary after each command's details are finalized.

            if (!$stageSuccess) { // If this command failed
                 $this->finalizeStage($stageIndex, 'failed', $stageStartTime);
                 $this->logPipelineError("--- Stage '{$stageName}' failed due to error in command '{$commandName}'. Halting stage. ---");
                 return false; // Stop current stage and report failure
            }
        } // End of foreach ($commands as $item)

        // If the loop completes without returning false, the stage was successful
        $this->finalizeStage($stageIndex, 'success', $stageStartTime);
        $this->logPipelineInfo("--- Stage '{$stageName}' completed successfully. ---");
        return true;
    }

    private function finalizeStage(int $stageIndex, string $status, Carbon $stageStartTime): void
    {
        $stageEndTime = Carbon::now();
        $this->runSummary['stages'][$stageIndex]['status'] = $status;
        $this->runSummary['stages'][$stageIndex]['end_time'] = $stageEndTime->toIso8601String();
        $this->runSummary['stages'][$stageIndex]['duration_seconds'] = $stageEndTime->diffInSeconds($stageStartTime);
        $this->writeRunSummary();
    }

    private function buildFailureExcerpt(string ...$segments): ?string
    {
        $combined = trim(implode("\n", array_filter(array_map(function (string $segment) {
            return trim(preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $segment));
        }, $segments))));

        if ($combined === '') {
            return null;
        }

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R+/', $combined) ?: []),
            fn (string $line) => $line !== ''
        ));

        $keywords = ['error', 'exception', 'failed', 'undefined', 'not found'];
        foreach (array_reverse($lines) as $line) {
            $lower = Str::lower($line);
            foreach ($keywords as $keyword) {
                if (Str::contains($lower, $keyword)) {
                    return Str::limit($line, 240);
                }
            }
        }

        return Str::limit(end($lines) ?: $combined, 240);
    }
}
