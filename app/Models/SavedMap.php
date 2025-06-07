<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'creator_display_name', // Added
        'name',
        'description',
        'map_type',
        'data_type',
        'filters',
        'map_settings',
        'is_public',
        'is_approved', // Added
        'is_featured', // Added
    ];

    protected $casts = [
        'filters' => 'array',
        'map_settings' => 'array',
        'is_public' => 'boolean',
        'is_approved' => 'boolean', // Added
        'is_featured' => 'boolean', // Added
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
