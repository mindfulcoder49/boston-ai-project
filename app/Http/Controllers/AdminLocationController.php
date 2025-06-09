<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class AdminLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $adminEmail = config('admin.email');
            if (empty($adminEmail) || !Auth::check() || Auth::user()->email !== $adminEmail) {
                Log::warning('Unauthorized access attempt to AdminLocationController.', ['user_id' => Auth::id(), 'email' => Auth::user()?->email]);
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $locations = Location::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'notes' => $location->notes,
                'language' => $location->language, // Added language
                'user_id' => $location->user_id,
                'user_name' => $location->user?->name ?? 'N/A',
                'user_email' => $location->user?->email ?? 'N/A',
                'created_at' => $location->created_at->toIso8601String(),
                'updated_at' => $location->updated_at->toIso8601String(),
            ]);
        
        $users = User::orderBy('name')->get(['id', 'name', 'email'])->map(fn($user) => [
            'id' => $user->id,
            'display_name' => "{$user->name} ({$user->email})",
        ]);


        return Inertia::render('Admin/ManageLocations', [
            'locations' => $locations,
            'usersForSelect' => $users, // For assigning/changing user_id
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'language' => ['nullable', 'string', 'max:10'], // Added language validation (e.g., 'en', 'es')
        ]);

        $location->update($validated);
        return redirect()->back()->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        try {
            $locationName = $location->name;
            $location->delete();
            return redirect()->route('admin.locations.index')->with('success', "Location '{$locationName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error("Error deleting location {$location->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete location. Check logs for details.');
        }
    }
}
