<?php

namespace App\Console\Commands;

use App\Support\AdminLongWorkerHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class RunAdminLongWorkerCommand extends Command
{
    protected $signature = 'app:run-admin-long-worker';

    protected $description = 'Runs the scheduled backend queue worker and records worker heartbeat evidence.';

    public function handle(): int
    {
        $connection = (string) config('backend_admin.queue_worker.connection', config('queue.default'));
        $queue = collect(explode(',', (string) config('backend_admin.queue_worker.queues', 'admin-long,default')))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->implode(',');
        $timeout = (int) config('backend_admin.queue_worker.timeout', 7200);
        $tries = (int) config('backend_admin.queue_worker.tries', 1);

        $parameters = [
            'connection' => $connection,
            '--stop-when-empty' => true,
            '--queue' => $queue,
            '--timeout' => $timeout,
            '--tries' => $tries,
        ];

        AdminLongWorkerHeartbeat::record('queue:work', 'started', [
            'connection' => $connection,
            'queue' => $queue,
            'timeout' => $timeout,
            'tries' => $tries,
        ]);

        try {
            $exitCode = Artisan::call('queue:work', $parameters);

            AdminLongWorkerHeartbeat::record('queue:work', $exitCode === 0 ? 'completed' : 'failed', [
                'connection' => $connection,
                'queue' => $queue,
                'timeout' => $timeout,
                'tries' => $tries,
                'exit_code' => $exitCode,
            ]);

            return $exitCode;
        } catch (Throwable $throwable) {
            AdminLongWorkerHeartbeat::record('queue:work', 'failed', [
                'connection' => $connection,
                'queue' => $queue,
                'timeout' => $timeout,
                'tries' => $tries,
                'message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
