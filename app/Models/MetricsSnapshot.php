<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetricsSnapshot extends Model
{
    protected $fillable = [
        'snapshot_key',
        'data',
        'last_updated_at',
        'generated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'last_updated_at' => 'datetime',
        'generated_at' => 'datetime',
    ];
}
