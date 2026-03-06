<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotspotFinding extends Model
{
    protected $table = 'h3_hotspot_findings';

    protected $fillable = [
        'trend_id',
        'h3_index',
        'h3_resolution',
        'anomaly_count',
        'trend_count',
        'top_anomalies',
        'top_trends',
    ];

    protected $casts = [
        'top_anomalies' => 'array',
        'top_trends'    => 'array',
    ];

    public function trend()
    {
        return $this->belongsTo(Trend::class);
    }
}
