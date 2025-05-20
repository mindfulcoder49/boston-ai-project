<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable; // Added

class FoodInspection extends Model
{
    use HasFactory, Mappable; // Added Mappable

    protected $table = 'food_inspections'; // Assuming it uses the same table

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

    const SEARCHABLE_COLUMNS = [ // Added
        'external_id', 'businessname', 'dbaname', 'licenseno', 'licstatus', 'licensecat',
        'result', 'viol_level', 'viol_status', 'address', 'city', 'zip', 'property_id', 'language_code',
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
        return 'resultdttm';
    }

    public function getDate(): ?string
    {
        return $this->resultdttm ? $this->resultdttm->toDateTimeString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'external_id';
    }

    public function getExternalId(): string
    {
        return (string)$this->external_id;
    }

    // Mappable Trait Implementations
    // getFilterableFieldsDescription() method removed
    // getContextData() method removed
    // getSearchableColumns() method removed (trait will use SEARCHABLE_COLUMNS constant if defined, or suggestions)
    // getGptFunctionSchema() method removed
}
