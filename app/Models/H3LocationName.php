<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class H3LocationName extends Model
{
    protected $table = 'h3_location_names';

    protected $fillable = [
        'h3_index',
        'h3_resolution',
        'location_name',
        'geocoded_at',
        'raw_geocode_response',
    ];

    protected $casts = [
        'geocoded_at'          => 'datetime',
        'raw_geocode_response' => 'array',
    ];
}
