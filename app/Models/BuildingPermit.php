<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;

class BuildingPermit extends Model
{
    use HasFactory, Mappable;

    protected $table = 'building_permits';

    public static $statisticalAnalysisColumns = [
        'worktype',
        'permittypedescr',
        'status',
        'occupancytype',
    ];

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

    const SEARCHABLE_COLUMNS = [
        'permitnumber', 'worktype', 'permittypedescr', 'status', 'occupancytype',
        'address', 'city', 'state', 'zip', 'property_id', 'parcel_id', 'language_code',
    ];

    // Mappable Trait Implementations
    public static function getHumanName(): string
    {
        return 'Building Permits';
    }

    public static function getIconClass(): string
    {
        return 'permit-div-icon';
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Building Permit';
    }

    public static function getLatitudeField(): string
    {
        return 'y_latitude';
    }

    public static function getLongitudeField(): string
    {
        return 'x_longitude';
    }

    public static function getDateField(): string
    {
        return 'issued_date';
    }

    public function getDate(): ?string
    {
        return $this->issued_date ? (is_string($this->issued_date) ? $this->issued_date : $this->issued_date->toDateString()) : null;
    }

    public static function getExternalIdName(): string
    {
        return 'permitnumber';
    }

    public function getExternalId(): string
    {
        return $this->permitnumber;
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Permit Number',
            'mainIdentifierField' => 'permit_number_external', // Verify field name (e.g., PERMITNUMBER)
            'descriptionLabel' => 'Work Type',
            'descriptionField' => 'work_type', // Verify field name (e.g., WORKTYPE)
            'additionalFields' => [
                ['label' => 'Status', 'key' => 'status'], // Verify field name
                ['label' => 'Address', 'key' => 'address'], // Verify field name
            ],
        ];
    }
}
