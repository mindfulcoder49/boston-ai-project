<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Location;
use App\Models\User; // Import User model
use App\Jobs\SendLocationReportEmail;
use Illuminate\Support\Facades\Log; // For logging
use Illuminate\Support\Facades\Config; // To access stripe price IDs

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
    protected $description = 'Dispatch jobs to generate and send reports for locations based on user subscriptions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to dispatch report jobs...');
        $dispatchedCount = 0;

        // Get Stripe Price IDs from config
        $basicPlanPriceId = Config::get('stripe.prices.basic_plan');
        $proPlanPriceId = Config::get('stripe.prices.pro_plan');

        if (!$basicPlanPriceId || !$proPlanPriceId) {
            $this->error('Stripe Price IDs for basic or pro plan are not configured. Please check config/stripe.php and your .env file.');
            Log::error('Stripe Price IDs for basic or pro plan are not configured in DispatchLocationReports command.');
            return 1; // Indicate an error
        }

        // Get all users who have an active 'default' subscription.
        // Eager load their 'default' subscription and all their locations.
        $subscribedUsers = User::whereHas('subscriptions', function ($query) {
            $query->where('name', 'default')->whereNull('ends_at'); // Active 'default' subscription
        })->with(['subscriptions' => function ($query) {
            $query->where('name', 'default')->whereNull('ends_at'); // Ensure we only get the active default subscription
        }, 'locations'])->get();

        if ($subscribedUsers->isEmpty()) {
            $this->info('No subscribed users found with active plans that include reports.');
            return 0;
        }

        $this->info("Found {$subscribedUsers->count()} subscribed users to process.");

        foreach ($subscribedUsers as $user) {
            // Get the specific 'default' active subscription for this user
            // The eager loading helps, but we access it directly via the collection
            $activeSubscription = $user->subscriptions->firstWhere('name', 'default');

            if (!$activeSubscription) {
                Log::info("User ID: {$user->id} iterated but no active 'default' subscription found (should not happen with current query), skipping.");
                continue;
            }

            $userPlan = null;
            if ($activeSubscription->stripe_price === $basicPlanPriceId) {
                $userPlan = 'basic';
            } elseif ($activeSubscription->stripe_price === $proPlanPriceId) {
                $userPlan = 'pro';
            }

            if (!$userPlan) {
                Log::warning("User ID: {$user->id} is subscribed, but their plan (Price ID: {$activeSubscription->stripe_price}) is not recognized for reports. Skipping.");
                continue;
            }

            $this->line("Processing User ID: {$user->id} on '{$userPlan}' plan.");

            // Base query for user's locations configured for reports
            $userLocationsQuery = $user->locations()->where(function ($query) {
                $query->where('report', 'daily');
                if (now()->isSunday()) {
                    $query->orWhere('report', 'weekly');
                }
            });

            if ($userPlan === 'basic') {
                // Basic plan: Send report for only ONE configured location.
                // We'll take the first one found (e.g., oldest or by ID).
                // If a user needs to select WHICH one, that's a feature enhancement.
                $locationToSend = $userLocationsQuery->orderBy('id', 'asc')->first(); // Get the first one

                if ($locationToSend) {
                    SendLocationReportEmail::dispatch($locationToSend);
                    $this->info("-- Dispatched report for Basic User ID: {$user->id}, Location ID: {$locationToSend->id} ('{$locationToSend->address}')");
                    $dispatchedCount++;
                } else {
                    $this->comment("-- Basic User ID: {$user->id} has no locations configured for 'daily' (or 'weekly' on Sunday) reports.");
                }
            } elseif ($userPlan === 'pro') {
                // Pro plan: Send reports for ALL their configured locations.
                $locationsToSend = $userLocationsQuery->get();

                if ($locationsToSend->isNotEmpty()) {
                    foreach ($locationsToSend as $location) {
                        SendLocationReportEmail::dispatch($location);
                        $this->info("-- Dispatched report for Pro User ID: {$user->id}, Location ID: {$location->id} ('{$location->address}')");
                        $dispatchedCount++;
                    }
                } else {
                    $this->comment("-- Pro User ID: {$user->id} has no locations configured for 'daily' (or 'weekly' on Sunday) reports.");
                }
            }
        }

        $this->info("Finished dispatching. Total jobs dispatched: {$dispatchedCount}.");
        return 0;
    }
}