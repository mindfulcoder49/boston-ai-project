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
    ];
}