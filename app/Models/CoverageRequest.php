<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverageRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'requested_address',
        'normalized_address',
        'latitude',
        'longitude',
        'source_page',
        'status',
        'notes',
        'request_count',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'request_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
