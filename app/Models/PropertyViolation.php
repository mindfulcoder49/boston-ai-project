<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable; // Added

class PropertyViolation extends Model
{
    use HasFactory, Mappable; // Added Mappable

    protected $table = 'property_violations';

    protected $fillable = [
        'case_no',
        'ap_case_defn_key',
        'status_dttm',
        'status',
        'code',
        'value',
        'description',
        'violation_stno',
        'violation_sthigh',
        'violation_street',
        'violation_suffix',
        'violation_city',
        'violation_state',
        'violation_zip',
        'ward',
        'contact_addr1',
        'contact_addr2',
        'contact_city',
        'contact_state',
        'contact_zip',
        'sam_id',
        'latitude',
        'longitude',
        'location',
        'language_code',
    ];

    const SEARCHABLE_COLUMNS = [ // Added
        'case_no', 'status', 'code', 'description', 'violation_stno', 'violation_street',
        'violation_zip', 'ward', 'contact_city', 'contact_state', 'sam_id', 'latitude', 'longitude', 'language_code',
    ];

    public static function getDateField(): string
    {
        return 'status_dttm';
    }

    public function getDate(): string
    {
        if ($this->status_dttm === null) {
            return '';
        }
        return $this->status_dttm;
    }

    public static function getExternalIdName(): string
    {
        return 'case_no';
    }

    public function getExternalId(): string
    {
        return $this->case_no;
    }

    // Mappable Trait Implementations
    // getFilterableFieldsDescription() method removed
    // getContextData() method removed
    // getSearchableColumns() method removed (trait will use SEARCHABLE_COLUMNS constant if defined, or suggestions)
    // getGptFunctionSchema() method removed
}