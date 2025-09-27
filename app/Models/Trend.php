<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trend extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trends';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model_class',
        'column_name',
        'job_id',
        'h3_resolution',
        'p_value_anomaly',
        'p_value_trend',
        'analysis_weeks_trend',
        'analysis_weeks_anomaly',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'analysis_weeks_trend' => 'array',
    ];

    /**
     * Get the human-readable name for the model.
     *
     * @return string
     */
    public static function getHumanName(): string
    {
        return 'Statistical Trend Analysis';
    }

    /**
     * Get the instance of the source model.
     * Note: This is a dynamic relationship and doesn't represent a standard foreign key.
     */
    public function sourceModel()
    {
        if (class_exists($this->model_class)) {
            // This doesn't return a relationship object, but an instance of the source model class.
            // This is a conceptual link rather than a DB-level one.
            return new $this->model_class;
        }
        return null;
    }
}
