<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeHousingViolationData extends Model
{
    use HasFactory, Mappable;

    protected $table = 'cambridge_housing_violation_data';

    protected $fillable = [
        'record_id_external',
        'full_address',
        'parcel_number',
        'code',
        'description',
        'corrective_action',
        'correction_required_by',
        'status',
        'application_submit_date',
        'issue_date',
        'longitude',
        'latitude',
        'point_text',
    ];

    protected $casts = [
        'application_submit_date' => 'date:Y-m-d',
        'issue_date' => 'date:Y-m-d',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function getHumanName(): string
    {
        return 'Cambridge Housing Violation';
    }

    public static function getIconClass(): string
    {
        return 'property-violation-div-icon'; // Example icon
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Property Violation';
    }

    public static function getLatitudeField(): string
    {
        return 'latitude';
    }

    public static function getLongitudeField(): string
    {
        return 'longitude';
    }

    public static function getDateField(): string
    {
        return 'issue_date';
    }

    public function getDate(): ?string
    {
        return $this->issue_date ? Carbon::parse($this->issue_date)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'record_id_external';
    }

    public function getExternalId(): string
    {
        return $this->record_id_external;
    }

    public static $searchable_columns_config = [
        'record_id_external', 'full_address', 'parcel_number', 'code', 'description', 'status'
    ];
}
