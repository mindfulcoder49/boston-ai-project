<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User; // If you need to check user's current subscription

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status'); // 'success' or 'cancel' or null
        $sessionId = $request->query('session_id');
        $user = $request->user();
        $currentPlan = null;

        if ($user && $user->subscribed('default')) {
            // This is a simplified check. You might need to map Stripe Price ID to your plan names.
            // For a robust solution, store the plan name or tier in your database when subscription is created/updated via webhooks.
            $subscription = $user->subscription('default');
            if ($subscription && $subscription->stripe_price === config('stripe.prices.basic_plan')) {
                $currentPlan = 'basic';
            } elseif ($subscription && $subscription->stripe_price === config('stripe.prices.pro_plan')) {
                $currentPlan = 'pro';
            }
        }

        return Inertia::render('Subscription', [
            'status' => $status,
            'sessionId' => $sessionId,
            'currentPlan' => $currentPlan,
            'isAuthenticated' => (bool)$user,
        ]);
    }
}