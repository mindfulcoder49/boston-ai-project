<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RunArtisanCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allow up to 2 hours for long-running pipeline commands.
     */
    public int $timeout = 7200;

    public function __construct(
        private string $command,
        private array  $parameters = []
    ) {}

    public function handle(): void
    {
        Artisan::call($this->command, $this->parameters);
    }
}
