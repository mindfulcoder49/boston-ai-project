<?php

namespace App\Http\Controllers;

use App\Models\SavedMap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\DataMapController; // For getModelClass, enrichData, getMinDateForEffectiveUser
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;


class SavedMapController extends Controller
{
    protected DataMapController $dataMapController;

    public function __construct(DataMapController $dataMapController)
    {
        $this->dataMapController = $dataMapController;
    }

    /**
     * Display a listing of the user's saved maps.
     */
    public function index()
    {
        $user = Auth::user();
        if ($user) {
            $userSavedMaps = SavedMap::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $userSavedMaps = collect();
        }
        
        $publicMapsQuery = SavedMap::where('is_public', true)
            ->where('is_approved', true) 
            ->with('user:id,name') 
            ->orderBy('created_at', 'desc');
        
        $publicMaps = $publicMapsQuery->get();

        $publicMaps->each(function ($map) {
            // Creator display name logic is now handled by an accessor or directly in Vue.
            // We ensure 'creator_display_name' is part of the $map object.
            if ($map->user) {
                $tierDetails = $map->user->getEffectiveTierDetails(); 
                Log::info("Public Map {$map->id} - Creator User ID: {$map->user_id}, Tier Details: " . json_encode($tierDetails));
                $map->creator_tier_name = $tierDetails['tier'] ?? 'Unknown'; 
                $tierDisplayMap = [
                    'pro' => 'Pro User',
                    'basic' => 'Basic User',
                    'free' => 'Free Tier User',
                    'guest' => 'Guest',
                ];
                $map->creator_tier_display_name = $tierDisplayMap[strtolower($map->creator_tier_name)] ?? ucfirst($map->creator_tier_name);
            } else {
                $map->creator_tier_display_name = 'Unknown User';
            }
            // The map object itself will have 'creator_display_name' if set,
            // and 'user.name' if the user relationship is loaded.
            // Vue component will decide: map.creator_display_name || map.user?.name
        });


        return Inertia::render('UserSavedMapsPage', [
            'userSavedMaps' => $userSavedMaps,
            'publicSavedMaps' => $publicMaps, // creator_display_name is directly on the map object
        ]);
    }

    /**
     * Store a newly created saved map in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'creator_display_name' => 'nullable|string|max:100', // Added validation
            'map_type' => 'required|string|in:single,combined',
            'data_type' => 'nullable|string', 
            'filters' => 'required|array',
            'map_settings' => 'nullable|array',
            'is_public' => 'sometimes|boolean',
            'configurable_filter_fields' => 'nullable|array', // Added for configurable filters
            // 'is_approved' is not set here by user, defaults to false in DB
        ]);

        if ($validated['map_type'] === 'single' && empty($validated['data_type'])) {
            return back()->withErrors(['data_type' => 'Data type is required for single maps.'])->withInput();
        }

        // Ensure configurable_filter_fields is at least an empty array/object if null
        $validated['configurable_filter_fields'] = $validated['configurable_filter_fields'] ?? ($validated['map_type'] === 'combined' ? (object)[] : []);


        $savedMap = Auth::user()->savedMaps()->create($validated);

        return redirect()->route('saved-maps.index')->with('success', 'Map saved successfully!');
    }

    /**
     * Display the specified saved map.
     * This method prepares data for viewing a saved map in a read-only state.
     */
    public function view(SavedMap $savedMap)
    {
        // Authorization check
        $user = Auth::user();
        $isOwner = $user && $user->id === $savedMap->user_id;
        // Define admin email directly for this check as per specific requirement context
        $adminEmail = 'alex.g.alcivar49@gmail.com';
        $isAdminViewingPublicMap = $user && $user->email === $adminEmail && $savedMap->is_public;
        $isPublicAndApproved = $savedMap->is_public && $savedMap->is_approved;

        if (!($isOwner || $isAdminViewingPublicMap || $isPublicAndApproved)) {
            abort(403, 'You are not authorized to view this map.');
        }
        
        // Ensure 'creator_display_name' and 'configurable_filter_fields' are available
        $savedMap->load('user:id,name');
        // configurable_filter_fields should be automatically cast to array by Laravel if defined in $casts


        $effectiveUserForTier = $savedMap->is_public ? $savedMap->user : Auth::user();
        
        // This check is important if a map is public but its creator user record is somehow missing.
        if ($savedMap->is_public && !$savedMap->user) {
            Log::error("SavedMap {$savedMap->id} is public but creator user {$savedMap->user_id} not found. EffectiveUserForTier cannot be determined for tier limits.");
            // If admin is viewing, they might still want to see the map structure, but data limits might be guest-level.
            // If a regular user is viewing, this is a problem.
            // For simplicity, if creator is missing for a public map, data tier might default or be restricted.
            // The current logic will make $effectiveUserForTier null here.
            // The applyQueryFilters method in DataMapController needs to handle a null $effectiveUserForTier gracefully (e.g., apply guest limits).
            // For now, we'll proceed, but this is an edge case to be aware of for data tiering.
        }
        
        // If it's a private map, $effectiveUserForTier is Auth::user(). If Auth::user() is null (guest),
        // the initial authorization check ($isOwner) would have already failed.
        // So, $effectiveUserForTier should generally be non-null here if authorization passed for private maps.


        $mapDataSets = [];
        $allDataTypeDetails = [];

        if ($savedMap->map_type === 'single') {
            $dataType = $savedMap->data_type;
            if (!$dataType) {
                abort(404, "Data type for single map not specified.");
            }
            $modelClass = $this->dataMapController->getModelClassForDataType($dataType);
            
            $query = $modelClass::query();
            $filtersToApply = $savedMap->filters;

            // Apply filters using the centralized method from DataMapController
            // The applyQueryFilters method now handles the tierMinDate internally.
            $this->dataMapController->applyQueryFilters($query, $modelClass, $filtersToApply, $effectiveUserForTier);
            
            $limit = $filtersToApply['limit'] ?? 1000;
            $query->limit(max(1, min((int)$limit, 100000)));
            $query->orderBy($modelClass::getDateField(), 'desc');

            Log::info("SavedMap Single View - Query SQL for {$dataType}: " . $query->toSql());
            Log::info("SavedMap Single View - Query bindings for {$dataType}: " . json_encode($query->getBindings()));
            $data = $query->get();
            $mapDataSets[$dataType] = $this->dataMapController->enrichData($data, $dataType);

            $filterDesc = $modelClass::getFilterableFieldsDescription();
            if (is_string($filterDesc)) {
                $filterDesc = json_decode($filterDesc, true);
            }

            $allDataTypeDetails[$dataType] = [
                'dateField' => $modelClass::getDateField(),
                'externalIdField' => $modelClass::getExternalIdName(),
                'filterFieldsDescription' => $filterDesc, // Use decoded value
                'modelNameForHumans' => $modelClass::getModelNameForHumans(),
                'searchableColumns' => $modelClass::getSearchableColumns(), // Added searchableColumns
            ];

        } elseif ($savedMap->map_type === 'combined') {
            $savedFiltersByType = $savedMap->filters; 
            
            foreach ($this->dataMapController->getModelMapping() as $dataType => $modelClassString) { 
                // Skip if no filters for this type in saved map OR if the dataType is not part of the saved map's filters
                if (!isset($savedFiltersByType[$dataType])) {
                    // If you want to display all types from modelMapping regardless of saved filters,
                    // then initialize $filtersToApply = [] for types not in $savedFiltersByType.
                    // For now, only processing types that have filters in the saved map.
                    continue;
                }

                $modelClass = $this->dataMapController->getModelClassForDataType($dataType);
                $query = $modelClass::query();
                $filtersToApplyForType = $savedFiltersByType[$dataType];

                // Apply filters using the centralized method
                $this->dataMapController->applyQueryFilters($query, $modelClass, $filtersToApplyForType, $effectiveUserForTier);
                
                $limit = $filtersToApplyForType['limit'] ?? 1000;
                $query->limit(max(1, min((int)$limit, 100000)));
                $query->orderBy($modelClass::getDateField(), 'desc');
                
                Log::info("SavedMap Combined View - Query SQL for {$dataType}: " . $query->toSql());
                Log::info("SavedMap Combined View - Query bindings for {$dataType}: " . json_encode($query->getBindings()));
                $data = $query->get();
                $mapDataSets[$dataType] = $this->dataMapController->enrichData($data, $dataType);

                $filterDesc = $modelClass::getFilterableFieldsDescription();
                if (is_string($filterDesc)) {
                    $filterDesc = json_decode($filterDesc, true);
                }

                $allDataTypeDetails[$dataType] = [
                    'dateField' => $modelClass::getDateField(),
                    'externalIdField' => $modelClass::getExternalIdName(),
                    'filterFieldsDescription' => $filterDesc, // Use decoded value
                    'modelNameForHumans' => $modelClass::getModelNameForHumans(),
                    'searchableColumns' => $modelClass::getSearchableColumns(), // Added searchableColumns
                ];
            }
        }

        return Inertia::render('ViewSavedMapPage', [
            'savedMap' => $savedMap, // Now includes creator_display_name and configurable_filter_fields
            'mapDataSets' => $mapDataSets, 
            'allDataTypeDetails' => $allDataTypeDetails, 
            'mapSettings' => $savedMap->map_settings, // Center, zoom, selected layers
            'isReadOnly' => true,
            'modelMapping' => $this->dataMapController->getModelMapping(), // Add this line
        ]);
    }


    /**
     * Update the specified saved map in storage.
     */
    public function update(Request $request, SavedMap $savedMap)
    {
        if (Auth::id() !== $savedMap->user_id) { abort(403); }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'creator_display_name' => 'sometimes|nullable|string|max:100', // Allow updating display name
            'is_public' => 'sometimes|boolean',
            'configurable_filter_fields' => 'sometimes|nullable|array', // Allow updating configurable filters
            // 'filters' => 'sometimes|required|array', // If allowing filter updates
            // 'map_settings' => 'sometimes|nullable|array', // If allowing map settings updates
        ]);
        
        if (isset($validated['configurable_filter_fields'])) {
             $validated['configurable_filter_fields'] = $validated['configurable_filter_fields'] ?? ($savedMap->map_type === 'combined' ? (object)[] : []);
        }

        // If the map is being made private, it should also be unapproved and unfeatured.
        // Admin approval is for public maps. If user makes it private, admin approval is moot.
        if (isset($validated['is_public']) && $validated['is_public'] === false) {
            $validated['is_approved'] = false;
            $validated['is_featured'] = false;
        }

        $savedMap->update($validated);
        return redirect()->route('saved-maps.index')->with('success', 'Map updated successfully!');
    }

    /**
     * Remove the specified saved map from storage.
     */
    public function destroy(SavedMap $savedMap)
    {
        if (Auth::id() !== $savedMap->user_id) { abort(403); }

        $savedMap->delete();
        return redirect()->route('saved-maps.index')->with('success', 'Map deleted successfully!');
    }
}
