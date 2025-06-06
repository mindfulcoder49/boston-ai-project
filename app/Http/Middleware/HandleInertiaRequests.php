<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Ensure User model is imported
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentPlanData = null;

        if ($user) {
            /** @var User $user */
            $effectiveTierDetails = $user->getEffectiveTierDetails();
            
            $uiName = 'Registered User'; // Default for authenticated user if tier is 'free'
            switch ($effectiveTierDetails['tier']) {
                case 'free':
                    $uiName = 'Registered User';
                    break;
                case 'basic':
                    $uiName = 'Resident Awareness';
                    break;
                case 'pro':
                    $uiName = 'Pro Insights';
                    break;
                // Add other cases if new tiers are introduced and require different UI names
            }

            $currentPlanData = [
                'tier' => $effectiveTierDetails['tier'], // 'free', 'basic', 'pro'
                'name' => $uiName, // Consistent name for UI components like PageTemplate/DataVisibilityBanner
                'displayName' => $effectiveTierDetails['planName'], // Full descriptive plan name
                'source' => $effectiveTierDetails['source'], // 'default', 'manual', 'stripe'
            ];
        } else {
            // For guest users
            $currentPlanData = [
                'tier' => 'guest',
                'name' => 'Guest',
                'displayName' => 'Guest',
                'source' => 'none',
            ];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                // Laravel Breeze/Jetstream typically shares the user object automatically.
                // If you need to customize it or ensure it's always present:
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->provider_avatar, // Ensure this line is present and correct
                    // Add other user properties you need globally
                ] : null,
                'currentPlan' => $currentPlanData,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'status_error' => fn () => $request->session()->get('status_error'),
                // You can add other flash message types here if needed
            ],
            // If you have global translations managed via PHP and want to share them:
            // 'translations' => function () {
            //     return [
            //         'LabelsByLanguageCode' => \App\Services\TranslationService::getAllLabels(), // Example
            //     ];
            // },
        ]);
    }
}
