<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable; // Added

class BuildingPermit extends Model
{
    use HasFactory, Mappable; // Added Mappable

    // Specify the table name if it's different from the model's pluralized name
    protected $table = 'building_permits';

    // Specify which attributes are mass assignable
    protected $fillable = [
        'permitnumber',
        'worktype',
        'permittypedescr',
        'description',
        'comments',
        'applicant',
        'declared_valuation',
        'total_fees',
        'issued_date',
        'expiration_date',
        'status',
        'occupancytype',
        'sq_feet',
        'address',
        'city',
        'state',
        'zip',
        'property_id',
        'parcel_id',
        'gpsy',
        'gpsx',
        'y_latitude',
        'x_longitude',
        'language_code',
    ];

    const SEARCHABLE_COLUMNS = [ // Added
        'permitnumber', 'worktype', 'permittypedescr', 'status', 'occupancytype',
        'address', 'city', 'state', 'zip', 'property_id', 'parcel_id', 'language_code',
    ];

    public static function getDateField(): string
    {
        return 'issued_date';
    }

    public function getDate(): string
    {
        return $this->issued_date;
    }

    public static function getExternalIdName(): string
    {
        return 'permitnumber';
    }

    public function getExternalId(): string
    {
        return $this->permitnumber;
    }

    // Mappable Trait Implementations
    // getFilterableFieldsDescription() method removed
    // getContextData() method removed
    // getSearchableColumns() method removed (trait will use SEARCHABLE_COLUMNS constant if defined, or suggestions)
    // getGptFunctionSchema() method removed
}
