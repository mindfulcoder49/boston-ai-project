<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeSanitaryInspectionData extends Model
{
    use HasFactory, Mappable;

    protected $table = 'cambridge_sanitary_inspection_data';

    protected $fillable = [
        'case_number_group',
        'address',
        'parcel',
        'establishment_name',
        'code_number',
        'code_description',
        'inspector_comments',
        'case_open_date',
        'case_closed_date',
        'date_cited',
        'date_corrected',
        'code_case_status',
        'latitude',
        'longitude',
        'geocoded_column_text',
    ];

    protected $casts = [
        'case_open_date' => 'datetime',
        'case_closed_date' => 'datetime',
        'date_cited' => 'datetime',
        'date_corrected' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function getHumanName(): string
    {
        return 'Cambridge Sanitary Inspection';
    }

    public static function getIconClass(): string
    {
        return 'food-inspection-div-icon'; // Example icon for food/sanitary
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Food Inspection'; // Or 'SanitaryInspection'
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
        return 'date_cited'; // Or 'case_open_date'
    }

    public function getDate(): ?string
    {
        $dateToUse = $this->date_cited ?? $this->case_open_date;
        return $dateToUse ? Carbon::parse($dateToUse)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        // Since each row is a violation and might not have a unique ID from source
        // other than its content, we use the table's auto-incrementing ID.
        return 'id';
    }

    public function getExternalId(): string
    {
        return (string)$this->id;
    }

    public static $searchable_columns_config = [
        'case_number_group', 'address', 'establishment_name', 'code_number', 'code_description', 'code_case_status'
    ];

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Inspection ID',
            'mainIdentifierField' => 'inspection_id_external', // Verify field name
            'descriptionLabel' => 'Violation',
            'descriptionField' => 'violation_description', // Verify field name
            'additionalFields' => [
                ['label' => 'Result', 'key' => 'inspection_result'], // Verify field name
                ['label' => 'Address', 'key' => 'address'], // Verify field name
            ],
        ];
    }
}
