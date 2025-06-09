<?php

namespace App\Http\Controllers;

use App\Models\SavedMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminMapController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $adminEmail = config('admin.email');
            if (empty($adminEmail) || !Auth::check() || Auth::user()->email !== $adminEmail) {
                Log::warning('Unauthorized access attempt to AdminMapController.', ['user_id' => Auth::id(), 'email' => Auth::user()?->email]);
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $maps = SavedMap::with('user:id,name,email')
            ->orderBy('is_featured', 'desc')
            ->orderBy('is_approved', 'asc')
            ->orderBy('is_public', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(20) // Paginate for better performance
            ->through(fn ($map) => [ // Transform data for frontend
                'id' => $map->id,
                'name' => $map->name,
                'description' => $map->description,
                'user_name' => $map->user?->name ?? 'N/A',
                'user_email' => $map->user?->email ?? 'N/A',
                'creator_display_name' => $map->creator_display_name,
                'is_public' => $map->is_public,
                'is_approved' => $map->is_approved,
                'is_featured' => $map->is_featured,
                'updated_at' => $map->updated_at->toIso8601String(),
                'created_at' => $map->created_at->toIso8601String(),
                'view_url' => route('saved-maps.view', $map->slug ?: $map->id),
                'filters' => $map->filters,
                'map_settings' => $map->map_settings,
                'configurable_filter_fields' => $map->configurable_filter_fields,
                'latitude' => $map->latitude,
                'longitude' => $map->longitude,
                'zoom_level' => $map->zoom_level,
                'slug' => $map->slug,
                'view_count' => $map->view_count,
            ]);

        return Inertia::render('Admin/ManageMaps', [
            'maps' => $maps,
        ]);
    }

    public function approve(SavedMap $savedMap)
    {
        if (!$savedMap->is_public) {
            return redirect()->back()->with('error', 'This map is not marked as public by the user. Admin should set it to public first if approval is desired.');
        }
        $savedMap->update(['is_approved' => true]);
        return redirect()->back()->with('success', 'Map approved successfully.');
    }

    public function unapprove(SavedMap $savedMap)
    {
        // When unapproving, also unfeature it.
        $savedMap->update(['is_approved' => false, 'is_featured' => false]);
        return redirect()->back()->with('success', 'Map unapproved and unfeatured successfully.');
    }

    public function feature(SavedMap $savedMap)
    {
        if (!$savedMap->is_public || !$savedMap->is_approved) {
            return redirect()->back()->with('error', 'Only public and approved maps can be featured.');
        }
        $savedMap->update(['is_featured' => true]);
        return redirect()->back()->with('success', 'Map featured successfully.');
    }

    public function unfeature(SavedMap $savedMap)
    {
        $savedMap->update(['is_featured' => false]);
        return redirect()->back()->with('success', 'Map unfeatured successfully.');
    }

    public function update(Request $request, SavedMap $savedMap)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'], // Increased max length
            'creator_display_name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['required', 'boolean'],
            'is_approved' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'zoom_level' => ['required', 'integer', 'min:1', 'max:22'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('saved_maps')->ignore($savedMap->id)],
            'view_count' => ['required', 'integer', 'min:0'],
             // Assuming these are JSON fields or will be cast by the model
            'filters' => ['nullable', 'json'],
            'map_settings' => ['nullable', 'json'],
            'configurable_filter_fields' => ['nullable', 'json'],
        ]);

        // Logic constraints:
        if (!$validated['is_public']) {
            $validated['is_approved'] = false;
            $validated['is_featured'] = false;
        }
        if (!$validated['is_approved']) {
            $validated['is_featured'] = false;
        }

        // Handle JSON fields: decode if they are strings, or ensure they are arrays/objects
        // The model's $casts should handle this if 'json' type is used,
        // but if they come as strings from the form, ensure they are decoded for the update.
        foreach (['filters', 'map_settings', 'configurable_filter_fields'] as $jsonField) { // Removed 'map_data_json' from this loop
            if (isset($validated[$jsonField]) && is_string($validated[$jsonField])) {
                $decoded = json_decode($validated[$jsonField], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $validated[$jsonField] = $decoded;
                } else {
                    // Handle invalid JSON, perhaps return an error
                    // For now, we assume valid JSON string or null from textarea
                }
            }
        }
        
        // If slug is empty or not provided, generate one (optional, based on your needs)
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name'] . '-' . $savedMap->id); // Ensure uniqueness
        }


        $savedMap->update($validated);
        return redirect()->back()->with('success', 'Map updated successfully.');
    }

    public function destroy(SavedMap $savedMap)
    {
        try {
            $mapName = $savedMap->name;
            $savedMap->delete();
            return redirect()->route('admin.maps.index')->with('success', "Map '{$mapName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error("Error deleting map {$savedMap->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete map. Check logs for details.');
        }
    }
}
