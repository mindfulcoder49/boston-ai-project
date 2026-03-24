<?php

namespace App\Console\Commands;

use Database\Seeders\NewYork311Seeder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class SeedNewYork311Command extends Command
{
    protected $signature = 'app:seed-new-york-311
                            {--resume-historical : Skip rows newer than the oldest loaded historical day and re-upsert that day onward.}';

    protected $description = 'Run the New York 311 seeder, optionally resuming a historical import with a one-day overlap.';

    public function handle(): int
    {
        $seeder = $this->laravel->make(NewYork311Seeder::class)
            ->setContainer($this->laravel)
            ->setCommand($this);

        Model::unguarded(function () use ($seeder) {
            $seeder->__invoke([
                'resumeHistorical' => (bool) $this->option('resume-historical'),
            ]);
        });

        return self::SUCCESS;
    }
}
