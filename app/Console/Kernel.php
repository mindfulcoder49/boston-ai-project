<?php

namespace App\Console;

use App\Console\Commands\CheckIngestionDependenciesCommand;
use App\Console\Commands\DispatchDailyPipelineCommand;
use App\Console\Commands\EvaluateBackendHealthAlertsCommand;
use App\Console\Commands\RunAdminLongWorkerCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(RunAdminLongWorkerCommand::class)
            ->everyMinute()
            ->withoutOverlapping(180)
            ->runInBackground();

        $schedule->command(CheckIngestionDependenciesCommand::class)
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        $schedule->command(DispatchDailyPipelineCommand::class)
            ->dailyAt(config('backend_admin.daily_pipeline.time', '07:00'))
            ->timezone(config('backend_admin.daily_pipeline.timezone', config('app.timezone')))
            ->withoutOverlapping();

        $schedule->command(EvaluateBackendHealthAlertsCommand::class)
            ->hourlyAt(10)
            ->withoutOverlapping();

        if (config('data_retention.automation.enabled')) {
            $retentionGroups = collect(config('data_retention.automation.groups', []))
                ->filter(fn ($group) => is_string($group) && trim($group) !== '')
                ->map(fn (string $group) => trim($group))
                ->values()
                ->all();

            if (!empty($retentionGroups)) {
                $schedule->command('app:apply-data-retention', [
                    '--group' => $retentionGroups,
                    '--batch' => (int) config(
                        'data_retention.automation.batch_size',
                        config('data_retention.database_apply_batch_size', 1000)
                    ),
                    '--force' => true,
                ])
                    ->weeklyOn(
                        (int) config('data_retention.automation.day_of_week', 0),
                        config('data_retention.automation.time', '03:30')
                    )
                    ->timezone(config('data_retention.automation.timezone', config('app.timezone')))
                    ->withoutOverlapping();
            }
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
