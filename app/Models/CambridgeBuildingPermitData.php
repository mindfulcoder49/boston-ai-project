<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeBuildingPermitData extends Model
{
    use HasFactory, Mappable;

    protected $table = 'cambridge_building_permits_data';

    protected $fillable = [
        'permit_id_external',
        'address',
        'address_geocoded',
        'latitude',
        'longitude',
        'status',
        'applicant_submit_date',
        'issue_date',
        'number_of_residential_units',
        'current_property_use',
        'proposed_property_use',
        'total_cost_of_construction',
        'detailed_description_of_work',
        'gross_square_footage',
        'building_use',
        'maplot_number',
        'raw_data',
    ];

    protected $casts = [
        'applicant_submit_date' => 'datetime',
        'issue_date' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'total_cost_of_construction' => 'decimal:2',
        'gross_square_footage' => 'integer',
        'number_of_residential_units' => 'integer',
        'raw_data' => 'array',
    ];

    public static function getHumanName(): string
    {
        return 'Cambridge Building Permit';
    }

    public static function getIconClass(): string
    {
        return 'permit-div-icon'; // Example icon
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Building Permit';
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
        return 'permit_id_external';
    }

    public function getExternalId(): string
    {
        return $this->permit_id_external;
    }
    
    public static $searchable_columns_config = [
        'permit_id_external', 'address', 'status', 'current_property_use', 'proposed_property_use', 'detailed_description_of_work', 'building_use', 'maplot_number'
    ];

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Permit Number',
            'mainIdentifierField' => 'permit_num_external', // Verify field name
            'descriptionLabel' => 'Description of Work',
            'descriptionField' => 'description_of_work', // Verify field name
            'additionalFields' => [
                ['label' => 'Status', 'key' => 'status_of_permit'], // Verify field name
                ['label' => 'Address', 'key' => 'full_address'], // Verify field name
            ],
        ];
    }
}
