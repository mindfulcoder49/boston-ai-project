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
        $currentPlanDetails = null;
        $avatarUrl = null;

        if ($user) {
            /** @var User $user */
            $avatarUrl = $user->provider_avatar ?? $user->avatar; // Assuming 'avatar' is another field or provider_avatar is used

            // Determine current plan
            // Check for active or onGracePeriod subscriptions first
            $activeSubscription = $user->subscriptions()
                                    ->where(function ($query) {
                                        $query->active()->orWhere(function ($q) {
                                            $q->onGracePeriod();
                                        });
                                    })
                                    ->orderBy('created_at', 'desc') // Get the latest if multiple somehow exist
                                    ->first();

            if ($activeSubscription) {
                if ($activeSubscription->stripe_price === config('stripe.prices.basic_plan')) {
                    $currentPlanDetails = ['key' => 'basic', 'name' => 'Resident Awareness'];
                } elseif ($activeSubscription->stripe_price === config('stripe.prices.pro_plan')) {
                    $currentPlanDetails = ['key' => 'pro', 'name' => 'Pro Insights'];
                } else {
                    // Fallback for unknown subscribed plan
                    $currentPlanDetails = ['key' => 'subscribed', 'name' => 'Subscribed'];
                }
            } else {
                // Authenticated but no active paid subscription means they are on the "free" tier
                $currentPlanDetails = ['key' => 'free', 'name' => 'Registered User'];
            }
        } else {
            // Not authenticated
            $currentPlanDetails = ['key' => 'guest', 'name' => 'Guest'];
        }

        return array_merge(parent::share($request), [
            'auth' => function () use ($user, $avatarUrl, $currentPlanDetails) {
                return [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar_url' => $avatarUrl,
                        // Add other user properties you need globally
                    ] : null,
                    'currentPlan' => $currentPlanDetails,
                ];
            },
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
