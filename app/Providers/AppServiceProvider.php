<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia; // Import Inertia
use App\Models\SavedMap; // Import SavedMap model
use Illuminate\Support\Facades\Auth; // If needed for user-specific shared data

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'errors' => function () {
                return session('errors') ? session('errors')->getBag('default')->getMessages() : (object) [];
            },
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                ];
            },
            'auth' => function () {
                $user = Auth::user();
                if ($user) {
                    $user->loadMissing('roles', 'permissions', 'subscriptions'); // Ensure subscriptions are loaded
                    $currentPlan = $user->getEffectiveTierDetails(); // Uses your existing method
                    return [
                        'user' => $user->toArray() + ['current_plan_details' => $currentPlan], // Include plan details
                        'currentPlan' => $currentPlan, // Keep this for compatibility if used directly
                    ];
                }
                return ['user' => null, 'currentPlan' => ['name' => 'Guest', 'tier' => 'guest']];
            },
            'csrf_token' => csrf_token(),
            'featuredMaps' => function () { // Add this closure
                return SavedMap::where('is_public', true)
                               ->where('is_approved', true)
                               ->where('is_featured', true)
                               ->with('user:id,name') // Eager load user for display
                               ->orderBy('updated_at', 'desc') // Or some other ordering for featured maps
                               ->take(5) // Limit the number of featured maps
                               ->get()
                               ->map(function ($map) {
                                   // Ensure tier display name is available if needed by banner directly
                                   if ($map->user) {
                                       $tierDetails = $map->user->getEffectiveTierDetails();
                                       $tierDisplayMap = [
                                           'pro' => 'Pro User',
                                           'basic' => 'Basic User',
                                           'free' => 'Free Tier User',
                                       ];
                                       $map->creator_tier_display_name = $tierDisplayMap[strtolower($tierDetails['tier'] ?? '')] ?? ucfirst($tierDetails['tier'] ?? 'User');
                                   } else {
                                       $map->creator_tier_display_name = 'User';
                                   }
                                   return $map;
                               });
            },
        ]);
    }
}
