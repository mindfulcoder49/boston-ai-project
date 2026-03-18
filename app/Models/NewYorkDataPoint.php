<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewYorkDataPoint extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'new_york_db';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'new_york_data_points';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'location',
        'generic_foreign_id',
        'alcivartech_date',
        'new_york_311_id',
    ];

    /**
     * Relationship to NewYork311 model.
     */
    public function newYork311()
    {
        return $this->belongsTo(NewYork311::class, 'new_york_311_id');
    }
}
