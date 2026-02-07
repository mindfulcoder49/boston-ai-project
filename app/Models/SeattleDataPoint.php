<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeattleDataPoint extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'seattle_db';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seattle_data_points';

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
        'seattle_crime_id',
    ];

    /**
     * Relationship to SeattleCrime model.
     */
    public function seattleCrime()
    {
        return $this->belongsTo(SeattleCrime::class, 'seattle_crime_id');
    }
}
