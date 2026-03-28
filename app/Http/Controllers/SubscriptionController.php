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
        $sourceContext = $request->query('source');
        $recommendedPlan = $request->query('recommended');
        $user = $request->user();
        $currentPlan = null;
        $hasCrimeAddressTrial = false;
        $hasUsedCrimeAddressTrial = false;

        if ($user) {
            $effectiveTierDetails = $user->getEffectiveTierDetails();
            $currentPlan = $effectiveTierDetails['tier']; // 'free', 'basic', 'pro'
            $hasCrimeAddressTrial = $user->hasActiveCrimeAddressTrial();
            $hasUsedCrimeAddressTrial = $user->hasUsedCrimeAddressTrial();
        }

        return Inertia::render('Subscription', [
            'status' => $status,
            'sessionId' => $sessionId,
            'currentPlan' => $currentPlan,
            'isAuthenticated' => (bool)$user,
            'sourceContext' => is_string($sourceContext) ? $sourceContext : null,
            'recommendedPlan' => in_array($recommendedPlan, ['basic', 'pro'], true) ? $recommendedPlan : null,
            'hasCrimeAddressTrial' => $hasCrimeAddressTrial,
            'hasUsedCrimeAddressTrial' => $hasUsedCrimeAddressTrial,
        ]);
    }
}
