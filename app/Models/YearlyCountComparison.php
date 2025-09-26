<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearlyCountComparison extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'yearly_count_comparisons';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model_class',
        'group_by_col',
        'baseline_year',
        'job_id',
    ];

    /**
     * Get the human-readable name for the model.
     *
     * @return string
     */
    public static function getHumanName(): string
    {
        return 'Yearly Count Comparison';
    }
}
