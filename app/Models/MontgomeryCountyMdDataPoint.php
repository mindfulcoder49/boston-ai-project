<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MontgomeryCountyMdDataPoint extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'montgomery_county_md_db';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'montgomery_county_md_data_points';

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
        'montgomery_county_md_crime_id',
    ];

    /**
     * Relationship to MontgomeryCountyMdCrime model.
     */
    public function montgomeryCountyMdCrime()
    {
        return $this->belongsTo(MontgomeryCountyMdCrime::class, 'montgomery_county_md_crime_id');
    }
}
