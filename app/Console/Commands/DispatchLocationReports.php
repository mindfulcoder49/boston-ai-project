<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Location;
use App\Models\User; // Import User model
use App\Jobs\SendLocationReportEmail;
use App\Services\ReportAccessService;
use Illuminate\Support\Facades\Log; // For logging
// Config is still used by User::getEffectiveTierDetails indirectly, so no need to remove its use here if other parts of the app rely on it.
// use Illuminate\Support\Facades\Config; 

class DispatchLocationReports extends Command
{
    public function __construct(
        private readonly ReportAccessService $reportAccessService
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send 
                            {--user-id= : Send reports for a specific user ID}
                            {--location-id= : Send a report for a specific location ID}
                            {--force : Force sending reports regardless of schedule (daily/weekly)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to generate and send reports for locations based on user subscriptions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to dispatch report jobs...');
        $dispatchedCount = 0;
        $userId = $this->option('user-id');
        $locationId = $this->option('location-id');
        $force = $this->option('force');

        if ($locationId) {
            $location = Location::find($locationId);
            if ($location) {
                $this->info("Dispatching for specific location ID: {$locationId}");
                SendLocationReportEmail::dispatch($location);
                $dispatchedCount++;
            } else {
                $this->error("Location with ID {$locationId} not found.");
            }
            $this->info("Finished dispatching. Total jobs dispatched: {$dispatchedCount}.");
            return 0;
        }

        $query = $userId ? User::where('id', $userId) : User::whereHas('locations');
        $usersWithLocations = $query->with('locations')->get();

        if ($usersWithLocations->isEmpty()) {
            $this->info('No users with locations found to process for reports.');
            return 0;
        }

        $this->info("Found {$usersWithLocations->count()} users with locations to process.");

        foreach ($usersWithLocations as $user) {
            $access = $this->reportAccessService->resolveRecurringReportAccess($user);

            if (!$access['eligible']) {
                Log::info("User ID: {$user->id} is not eligible for recurring reports. Skipping.");
                continue;
            }

            $this->line("Processing User ID: {$user->id} in '{$access['mode']}' recurring-report mode.");

            $configuredLocations = $this->reportAccessService->filterEligibleLocations($user->locations, $access, $force);

            if ($configuredLocations->isEmpty()) {
                $this->comment("-- User ID: {$user->id} has no locations configured for today's reports" . ($force ? ' (even with --force)' : '.'));
                continue;
            }

            foreach ($configuredLocations as $location) {
                SendLocationReportEmail::dispatch($location);
                $this->info("-- Dispatched report for User ID: {$user->id}, Location ID: {$location->id} ('{$location->address}')");
                $dispatchedCount++;
            }
        }

        $this->info("Finished dispatching. Total jobs dispatched: {$dispatchedCount}.");
        return 0;
    }
}
