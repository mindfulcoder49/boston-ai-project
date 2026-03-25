<?php

namespace App\Jobs;

use App\Support\AdminLongWorkerHeartbeat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class RunArtisanCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allow up to 2 hours for long-running pipeline commands.
     */
    public int $timeout = 7200;
    public int $tries = 1;

    public function __construct(
        private string $command,
        private array  $parameters = []
    ) {
        $this->onQueue(config('backend_admin.long_running_queue', 'admin-long'));
    }

    public function handle(): void
    {
        AdminLongWorkerHeartbeat::record($this->command, 'started', [
            'parameters' => $this->parameters,
        ]);

        try {
            $exitCode = Artisan::call($this->command, $this->parameters);

            AdminLongWorkerHeartbeat::record($this->command, 'completed', [
                'parameters' => $this->parameters,
                'exit_code' => $exitCode,
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException("Queued command {$this->command} exited with code {$exitCode}.");
            }
        } catch (Throwable $throwable) {
            AdminLongWorkerHeartbeat::record($this->command, 'failed', [
                'parameters' => $this->parameters,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    public function failed(Throwable $exception): void
    {
        AdminLongWorkerHeartbeat::record($this->command, 'failed', [
            'parameters' => $this->parameters,
            'message' => $exception->getMessage(),
        ]);
    }
}
