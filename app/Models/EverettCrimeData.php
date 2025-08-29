<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable; // Added

class EverettCrimeData extends Model
{
    use HasFactory, Mappable; // Added Mappable

    protected $table = 'everett_crime_data';

    public static $statisticalAnalysisColumns = [
        'incident_type',
    ];

    protected $fillable = [
        'case_number',
        'incident_log_file_date',
        'incident_entry_date_parsed',
        'incident_time_parsed',
        'occurred_on_datetime',
        'year',
        'month',
        'day_of_week',
        'hour',
        'incident_type',
        'incident_address',
        'incident_latitude',
        'incident_longitude',
        'incident_description',
        'arrest_name',
        'arrest_address',
        'arrest_age',
        'arrest_date_parsed',
        'arrest_charges',
        'crime_details_concatenated',
        'source_city',
    ];

    protected $casts = [
        'incident_log_file_date' => 'date:Y-m-d',
        'incident_entry_date_parsed' => 'date:Y-m-d',
        'arrest_date_parsed' => 'date:Y-m-d',
        'occurred_on_datetime' => 'datetime:Y-m-d H:i:s',
        'incident_latitude' => 'decimal:7',
        'incident_longitude' => 'decimal:7',
        'year' => 'integer',
        'month' => 'integer',
        'hour' => 'integer',
        'arrest_age' => 'integer',
    ];

    /**
     * Define which columns are searchable.
     */
    public const SEARCHABLE_COLUMNS = [
        'case_number',
        'incident_type',
        'incident_address',
        'incident_description',
        'arrest_name',
        'arrest_charges',
        'crime_details_concatenated',
    ];

    /**
     * Get the name of the date field used for filtering by date ranges.
     */
    public static function getDateField(): string
    {
        return 'occurred_on_datetime';
    }

    /**
     * Get the date value for a specific record.
     */
    public function getDate(): ?string
    {
        return $this->occurred_on_datetime ? $this->occurred_on_datetime->toDateString() : null;
    }

    /**
     * Get the name of the external ID field.
     */
    public static function getExternalIdName(): string
    {
        return 'case_number';
    }

    /**
     * Get the external ID value for a specific record.
     */
    public function getExternalId(): string
    {
        return $this->case_number;
    }

    // Mappable Trait Implementations
    public static function getHumanName(): string
    {
        return 'Everett Crime';
    }

    public static function getIconClass(): string
    {
        return 'crime-div-icon'; // Shares icon style
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Crime'; // Shares styling type
    }

    public static function getLatitudeField(): string
    {
        return 'incident_latitude';
    }

    public static function getLongitudeField(): string
    {
        return 'incident_longitude';
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Incident Number',
            'mainIdentifierField' => 'case_number',
            'descriptionLabel' => 'Description',
            'descriptionField' => 'incident_description',
            'additionalFields' => [
                ['label' => 'Location', 'key' => 'incident_address'],
                ['label' => 'Type', 'key' => 'incident_type'],
            ],
        ];
    }
}
