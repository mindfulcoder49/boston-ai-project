<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;

class FoodInspection extends Model
{
    use HasFactory, Mappable;

    protected $table = 'food_inspections';

    protected $fillable = [
        'external_id', // Assuming this is the primary external ID, was licenseno in placeholder
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

    const SEARCHABLE_COLUMNS = [
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

    // Mappable Trait Implementations
    public static function getHumanName(): string
    {
        return 'Food Inspections';
    }

    public static function getIconClass(): string
    {
        return 'food-inspection-div-icon';
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Food Inspection';
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
        return 'resultdttm';
    }

    public function getDate(): ?string
    {
        return $this->resultdttm ? $this->resultdttm->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'external_id'; // Corrected from placeholder 'licenseno'
    }

    public function getExternalId(): string
    {
        return (string)$this->external_id; // Corrected from placeholder 'licenseno'
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'License No',
            'mainIdentifierField' => 'licenseno',
            'descriptionLabel' => 'Violation',
            'descriptionField' => 'violdesc',
            'additionalFields' => [
                ['label' => 'Business Name', 'key' => 'businessname'],
                ['label' => 'Result', 'key' => 'result'],
                ['label' => 'Violation History', 'key' => 'violation_summary']
            ],
        ];
    }
}
