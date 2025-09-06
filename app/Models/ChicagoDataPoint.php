<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChicagoDataPoint extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'chicago_db';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chicago_data_points';

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
        'chicago_crime_id',
    ];

    /**
     * Relationship to ChicagoCrime model.
     */
    public function chicagoCrime()
    {
        return $this->belongsTo(ChicagoCrime::class, 'chicago_crime_id');
    }
}
