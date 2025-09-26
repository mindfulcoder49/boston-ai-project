<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'headline',
        'summary',
        'content',
        'source_model_class',
        'source_report_id',
        'status',
        'job_id',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Get the parent source model (Trend, YearlyCountComparison, etc.).
     */
    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_model_class', 'source_report_id');
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
