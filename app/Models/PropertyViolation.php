<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;

class PropertyViolation extends Model
{
    use HasFactory, Mappable;

    protected $table = 'property_violations';

    protected $fillable = [
        'case_no', // Assuming this is the primary external ID, was ticket_number in placeholder
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

    const SEARCHABLE_COLUMNS = [
        'case_no', 'status', 'code', 'description', 'violation_stno', 'violation_street',
        'violation_zip', 'ward', 'contact_city', 'contact_state', 'sam_id', 'latitude', 'longitude', 'language_code',
    ];

    // Mappable Trait Implementations
    public static function getHumanName(): string
    {
        return 'Property Violations';
    }

    public static function getIconClass(): string
    {
        return 'property-violation-div-icon';
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
        return 'status_dttm';
    }

    public function getDate(): ?string
    {
        return $this->status_dttm ? (is_string($this->status_dttm) ? $this->status_dttm : $this->status_dttm->toDateString()) : null;
    }

    public static function getExternalIdName(): string
    {
        return 'case_no'; // Corrected from placeholder 'ticket_number' to match fillable/schema
    }

    public function getExternalId(): string
    {
        return $this->case_no; // Corrected from placeholder 'ticket_number'
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Ticket Number',
            'mainIdentifierField' => 'ticket_number', // Verify field name
            'descriptionLabel' => 'Description',
            'descriptionField' => 'description', // Verify field name
            'additionalFields' => [
                ['label' => 'Status', 'key' => 'status'], // Verify field name
                ['label' => 'Address', 'key' => 'address'], // Verify field name
            ],
        ];
    }
}