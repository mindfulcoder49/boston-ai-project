<?php

namespace App\Console;

use App\Console\Commands\CheckIngestionDependenciesCommand;
use App\Console\Commands\DispatchDailyPipelineCommand;
use App\Console\Commands\EvaluateBackendHealthAlertsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $queue = config('backend_admin.long_running_queue', 'admin-long');
        $timeout = (int) config('backend_admin.queue_worker.timeout', 7200);
        $tries = (int) config('backend_admin.queue_worker.tries', 1);

        $schedule->command("queue:work --stop-when-empty --queue={$queue} --timeout={$timeout} --tries={$tries}")
            ->everyMinute()
            ->withoutOverlapping(180);

        $schedule->command(CheckIngestionDependenciesCommand::class)
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        $schedule->command(DispatchDailyPipelineCommand::class)
            ->dailyAt(config('backend_admin.daily_pipeline.time', '02:15'))
            ->timezone(config('backend_admin.daily_pipeline.timezone', config('app.timezone')))
            ->withoutOverlapping();

        $schedule->command(EvaluateBackendHealthAlertsCommand::class)
            ->hourlyAt(10)
            ->withoutOverlapping();
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
