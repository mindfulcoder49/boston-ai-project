<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodEstablishmentViolation extends Model
{
    use HasFactory;

    protected $table = 'food_establishment_violations';

    protected $fillable = [
        'external_id',
        'businessname',
        'dbaname',
        'legalowner',
        'namelast',
        'namefirst',
        'licenseno',
        'issdttm',
        'expdttm',
        'licstatus',
        'licensecat',
        'descript',
        'result',
        'resultdttm',
        'violation',
        'viol_level',
        'violdesc',
        'violdttm',
        'viol_status',
        'status_date',
        'comments',
        'address',
        'city',
        'state',
        'zip',
        'property_id',
        'latitude',
        'longitude',
        'language_code',
    ];

    protected $casts = [
        'issdttm' => 'datetime',
        'expdttm' => 'datetime',
        'resultdttm' => 'datetime',
        'violdttm' => 'datetime',
        'status_date' => 'datetime',
    ];

    public static function getDateField(): string
    {
        return 'violdttm'; // Or 'issdttm' or 'resultdttm' depending on primary relevance
    }

    public function getDate(): ?string
    {
        return $this->violdttm ? $this->violdttm->toDateTimeString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'external_id';
    }

    public function getExternalId(): string
    {
        return (string)$this->external_id;
    }
}
