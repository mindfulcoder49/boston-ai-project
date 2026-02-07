<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class SeattleCrime extends Model
{
    use HasFactory, Mappable;

    // This model points to the full dataset connection.
    // The seeder will populate a table of the same name/structure
    // in the 'seattle_db' connection for recent data.
    protected $connection = 'seattle_crime_db';
    protected $table = 'seattle_crimes';
    protected $primaryKey = 'offense_id';

    public $timestamps = false;

    public static $statisticalAnalysisColumns = [
        'offense_category',
        'offense_sub_category',
        'nibrs_crime_against_category',
        'nibrs_group_a_b',
        'precinct',
        'neighborhood',
        'beat',
        'nibrs_offense_code',
        'reporting_area'
    ];

    const SEARCHABLE_COLUMNS = [
        'report_number', 'report_date_time', 'offense_id', 'offense_date', 'nibrs_group_a_b', 'nibrs_crime_against_category', 'offense_sub_category', 'shooting_type_group', 'block_address', 'latitude', 'longitude', 'beat', 'precinct', 'sector', 'neighborhood', 'reporting_area', 'offense_category', 'nibrs_offense_code_description', 'nibrs_offense_code'
    ];

    protected $fillable = [
        'report_number',
        'report_date_time',
        'offense_id',
        'offense_date',
        'nibrs_group_a_b',
        'nibrs_crime_against_category',
        'offense_sub_category',
        'shooting_type_group',
        'block_address',
        'latitude',
        'longitude',
        'beat',
        'precinct',
        'sector',
        'neighborhood',
        'reporting_area',
        'offense_category',
        'nibrs_offense_code_description',
        'nibrs_offense_code'
    ];

    protected $casts = [
        'report_date_time' => 'datetime',
        'offense_date' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public static function getHumanName(): string
    {
        return 'Seattle Crime Reports (NIBRS Offenses)';
    }

    public static function getIconClass(): string
    {
        return 'crime-div-icon';
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Crime';
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
        return 'offense_date';
    }

    public function getDate(): ?string
    {
        $dateField = 'offense_date';
        return $this->$dateField ? Carbon::parse($this->$dateField)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'offense_id';
    }

    public function getExternalId(): string
    {
        return (string)$this->offense_id;
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Report Number',
            'mainIdentifierField' => 'report_number',
            'descriptionLabel' => 'Offense Sub Category',
            'descriptionField' => 'offense_sub_category',
            'additionalFields' => [
                ['label' => 'Offense Date', 'key' => 'offense_date', 'format' => 'datetime'],
                ['label' => 'Reported At', 'key' => 'report_date_time', 'format' => 'datetime'],
                ['label' => 'Category', 'key' => 'offense_category'],
                ['label' => 'NIBRS Code', 'key' => 'nibrs_offense_code'],
                ['label' => 'NIBRS Description', 'key' => 'nibrs_offense_code_description'],
                ['label' => 'Block', 'key' => 'block_address'],
                ['label' => 'Precinct', 'key' => 'precinct'],
                ['label' => 'Neighborhood', 'key' => 'neighborhood'],
                ['label' => 'Beat', 'key' => 'beat'],
            ],
        ];
    }

    public static function getFieldLabels(): array
    {
        return [
            'report_number' => 'Primary report identifier. One report can include multiple offense records.',
            'report_date_time' => 'Date and time the offense(s) was reported. May differ from offense occurrence date.',
            'offense_id' => 'Distinct identifier for this offense record. Unique per offense.',
            'offense_date' => 'Date and time of offense start. If null, Event Start Date is implied.',
            'nibrs_group_a_b' => 'NIBRS group classification, typically A or B.',
            'nibrs_crime_against_category' => 'High level crime against category used for reporting (for example PROPERTY, SOCIETY).',
            'offense_sub_category' => 'Detailed offense subcategory derived from NIBRS offense codes.',
            'shooting_type_group' => 'Indicates shots fired type when applicable. Often not present for non shooting events.',
            'block_address' => 'Blurred block-level address for the offense location.',
            'latitude' => 'Latitude of offense location blurred to block. Use decimal(10,7). -1.0 or similar denotes unavailable.',
            'longitude' => 'Longitude of offense location blurred to block. Use decimal(10,7). -1.0 or similar denotes unavailable.',
            'beat' => 'Designated police beat where the offense occurred (for example E2, M1).',
            'precinct' => 'Named police precinct (for example East, West, North).',
            'sector' => 'Police sector code associated with the offense location.',
            'neighborhood' => 'MCPP neighborhood name or dash when not available.',
            'reporting_area' => 'Geographic reporting area identifier from Mark43.',
            'offense_category' => 'Broad offense category grouping used for reporting (for example PROPERTY CRIME, VIOLENT).',
            'nibrs_offense_code_description' => 'Human readable description of the NIBRS offense code.',
            'nibrs_offense_code' => 'Reported NIBRS offense code used for classification and grouping.',
        ];
    }
}
