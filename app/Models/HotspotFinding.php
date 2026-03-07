<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotspotFinding extends Model
{
    protected $table = 'h3_hotspot_findings';

    protected $fillable = [
        'job_id',
        'model_class',
        'column_name',
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
}
