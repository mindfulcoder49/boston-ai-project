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

        if ($user) {
            $effectiveTierDetails = $user->getEffectiveTierDetails();
            $currentPlan = $effectiveTierDetails['tier']; // 'free', 'basic', 'pro'
        }

        return Inertia::render('Subscription', [
            'status' => $status,
            'sessionId' => $sessionId,
            'currentPlan' => $currentPlan,
            'isAuthenticated' => (bool)$user,
        ]);
    }
}