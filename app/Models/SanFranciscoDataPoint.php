<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanFranciscoDataPoint extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'san_francisco_db';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'san_francisco_data_points';

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
        'san_francisco_crime_id',
    ];

    /**
     * Relationship to SanFranciscoCrime model.
     */
    public function sanFranciscoCrime()
    {
        return $this->belongsTo(SanFranciscoCrime::class, 'san_francisco_crime_id');
    }
}
