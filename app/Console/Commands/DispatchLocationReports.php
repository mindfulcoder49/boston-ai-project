<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Location;
use App\Jobs\SendLocationReportEmail;

class DispatchLocationReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to generate and send reports for locations with daily reports enabled.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Fetch locations with 'daily' report setting.  Using ::where() with a query is much faster.
        $locations = Location::where('report', 'daily')->get();

        foreach ($locations as $location) {
            SendLocationReportEmail::dispatch($location);
        }

        $this->info('Dispatched report jobs for all locations with daily reports enabled.');
    }
}