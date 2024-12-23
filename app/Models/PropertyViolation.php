<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyViolation extends Model
{
    use HasFactory;

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
}