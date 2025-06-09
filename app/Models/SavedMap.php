<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SavedMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'latitude',
        'longitude',
        'zoom_level',
        'map_data_json', // Added
        'creator_display_name',
        'is_public',
        'is_approved',
        'is_featured',
        'slug',
        'view_count',
        // Add other fields if they exist and should be mass-assignable by admin
        'filters', 
        'map_settings',
        'configurable_filter_fields',
    ];

    protected $casts = [
        'filters' => 'array',
        'map_settings' => 'array',
        'configurable_filter_fields' => 'array',
        'is_public' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'latitude' => 'float', // Ensure correct casting
        'longitude' => 'float',
        'zoom_level' => 'integer',
        'view_count' => 'integer',
        'map_data_json' => 'array', // Or 'json' if you prefer objects
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
