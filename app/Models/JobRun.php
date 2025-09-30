<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JobRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'job_class',
        'status',
        'related_model_type',
        'related_model_id',
        'payload',
        'output',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function relatedModel(): MorphTo
    {
        return $this->morphTo();
    }

    public function getJobClassNameAttribute()
    {
        return class_basename($this->job_class);
    }
}
