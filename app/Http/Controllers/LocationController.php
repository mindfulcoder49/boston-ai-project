<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendLocationReportEmail;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get locations for the authenticated user
        $locations = Auth::user()->locations;
        return response()->json($locations, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'nullable|string',
            'report' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $currentLocationCount = $user->locations()->count();
        $maxLocations = 1; // Default for free users

        if ($user->subscribed('default')) {
            $subscription = $user->subscription('default');
            if ($subscription && $subscription->stripe_price === config('stripe.prices.basic_plan')) {
                $maxLocations = 3;
            } elseif ($subscription && $subscription->stripe_price === config('stripe.prices.pro_plan')) {
                $maxLocations = 10;
            }
        }
        // If user is authenticated but not subscribed to basic or pro, they are on the free tier (maxLocations remains 1)

        if ($currentLocationCount >= $maxLocations) {
            return response()->json(['error' => 'Maximum number of locations reached for your current plan.'], 403);
        }

        // Associate the location with the authenticated user
        $location = $user->locations()->create($validated);

        return response()->json($location, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        // Ensure the location belongs to the authenticated user
        if ($location->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($location, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        // Ensure the location belongs to the authenticated user
        if ($location->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'address' => 'nullable|string',
            'report' => 'nullable|string',
            'language' => 'nullable|string',
        ]);

        $location->update($validated);

        return response()->json($location, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        // Ensure the location belongs to the authenticated user
        if ($location->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $location->delete();

        return response()->json(null, 204);
    }

    // New method to dispatch a single location report email
    public function dispatchLocationReportEmail(Location $location)
    {
        // Dispatch a job to send the location report email
        SendLocationReportEmail::dispatch($location);

        return response()->json(['message' => 'Location report email dispatched'], 200);
    }
}
