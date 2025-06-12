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
use App\Models\Concerns\Mappable; // Ensure Mappable is imported


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
            ->with('user:id,name,manual_subscription_tier') // Include manual_subscription_tier
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
        
        $savedMap->load('user:id,name,manual_subscription_tier');

        $effectiveUserForTier = $savedMap->is_public ? $savedMap->user : Auth::user();
        
        if ($savedMap->is_public && !$savedMap->user) {
            Log::error("SavedMap {$savedMap->id} is public but creator user {$savedMap->user_id} not found. EffectiveUserForTier cannot be determined for tier limits.");
        }

        $mapDataSets = [];
        $allDataTypeDetails = [];
        $finalModelMapping = []; // For passing to the view

        $fullModelRegistry = $this->dataMapController->getModelMapping(); // Get key => class string map

        if ($savedMap->map_type === 'single') {
            $dataType = $savedMap->data_type;
            if (!$dataType || !isset($fullModelRegistry[$dataType])) {
                abort(404, "Data type for single map not specified or invalid.");
            }
            $modelClass = $fullModelRegistry[$dataType];
            if (!class_exists($modelClass) || !in_array(Mappable::class, class_uses_recursive($modelClass))) {
                 abort(500, "Model class for {$dataType} is not properly configured or mappable.");
            }
            $finalModelMapping[$dataType] = $modelClass;

            $query = $modelClass::query();
            $filtersToApply = $savedMap->filters;
            $this->dataMapController->applyQueryFilters($query, $modelClass, $filtersToApply, $effectiveUserForTier);
            
            $limit = $filtersToApply['limit'] ?? 1000;
            $query->limit(max(1, min((int)$limit, 100000)));
            $query->orderBy($modelClass::getDateField(), 'desc');
            
            $data = $query->get();
            $mapDataSets[$dataType] = $this->dataMapController->enrichData($data, $dataType, $modelClass);

            $filterDesc = $modelClass::getFilterableFieldsDescription();
            if (is_string($filterDesc)) {
                try { $filterDesc = json_decode($filterDesc, true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException $e) { $filterDesc = []; }
            }

            $allDataTypeDetails[$dataType] = [
                'humanName' => $modelClass::getHumanName(),
                'iconClass' => $modelClass::getIconClass(),
                'alcivartech_type_for_styling' => $modelClass::getAlcivartechTypeForStyling(),
                'latitudeField' => $modelClass::getLatitudeField(),
                'longitudeField' => $modelClass::getLongitudeField(),
                'dateField' => $modelClass::getDateField(),
                'externalIdField' => $modelClass::getExternalIdName(),
                'filterFieldsDescription' => $filterDesc,
                'searchableColumns' => $modelClass::getSearchableColumns(),
            ];

        } elseif ($savedMap->map_type === 'combined') {
            $savedFiltersByType = $savedMap->filters ?? [];
            $selectedDataTypes = $savedMap->map_settings['selected_data_types'] ?? [];

            if (empty($selectedDataTypes)) {
                Log::warning("SavedMap Combined View - map_settings.selected_data_types is empty for SavedMap ID: {$savedMap->id}.");
            }
            
            foreach ($selectedDataTypes as $dataType) {
                if (!isset($fullModelRegistry[$dataType])) {
                    Log::warning("SavedMap Combined View - DataType '{$dataType}' from selected_data_types not found in full model registry. Skipping.");
                    continue;
                }
                $modelClass = $fullModelRegistry[$dataType];
                 if (!class_exists($modelClass) || !in_array(Mappable::class, class_uses_recursive($modelClass))) {
                    Log::error("SavedMap Combined View - Model class for {$dataType} is not properly configured or mappable. Skipping.");
                    continue;
                }
                $finalModelMapping[$dataType] = $modelClass;

                $filtersToApplyForType = $savedFiltersByType[$dataType] ?? ['limit' => 1000];
                $query = $modelClass::query();
                $this->dataMapController->applyQueryFilters($query, $modelClass, $filtersToApplyForType, $effectiveUserForTier);
                
                $limit = $filtersToApplyForType['limit'] ?? 1000;
                $query->limit(max(1, min((int)$limit, 100000)));
                $query->orderBy($modelClass::getDateField(), 'desc');
                
                $data = $query->get();
                $mapDataSets[$dataType] = $this->dataMapController->enrichData($data, $dataType, $modelClass);

                $filterDesc = $modelClass::getFilterableFieldsDescription();
                if (is_string($filterDesc)) {
                     try { $filterDesc = json_decode($filterDesc, true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException $e) { $filterDesc = []; }
                }

                $allDataTypeDetails[$dataType] = [
                    'humanName' => $modelClass::getHumanName(),
                    'iconClass' => $modelClass::getIconClass(),
                    'alcivartech_type_for_styling' => $modelClass::getAlcivartechTypeForStyling(),
                    'latitudeField' => $modelClass::getLatitudeField(),
                    'longitudeField' => $modelClass::getLongitudeField(),
                    'dateField' => $modelClass::getDateField(),
                    'externalIdField' => $modelClass::getExternalIdName(),
                    'filterFieldsDescription' => $filterDesc,
                    'searchableColumns' => $modelClass::getSearchableColumns(),
                ];
            }
        }

        return Inertia::render('ViewSavedMapPage', [
            'savedMap' => $savedMap,
            'mapDataSets' => $mapDataSets, 
            'allDataTypeDetails' => $allDataTypeDetails, 
            'mapSettings' => $savedMap->map_settings,
            'isReadOnly' => true,
            'modelMapping' => $finalModelMapping, // Pass the potentially filtered modelMapping
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
