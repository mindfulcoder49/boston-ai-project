<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class ChicagoCrime extends Model
{
    use HasFactory, Mappable;

    // This model points to the full dataset connection.
    // The seeder will populate a table of the same name/structure
    // in the 'chicago_db' connection for recent data.
    protected $connection = 'chicago_crime_db';
    protected $table = 'chicago_crimes';

    public $timestamps = false;

    public static $statisticalAnalysisColumns = [
        'primary_type',
        'location_description',
        'arrest',
        'domestic',
        'ward',
        'community_area',
    ];

    const SEARCHABLE_COLUMNS = [
        'id', 'case_number', 'date', 'block', 'iucr', 'primary_type', 'description',
        'location_description', 'arrest', 'domestic', 'beat', 'district', 'ward',
        'community_area', 'fbi_code', 'x_coordinate', 'y_coordinate', 'year',
        'updated_on', 'latitude', 'longitude', 'location'
    ];

    protected $fillable = [
        'id', 'case_number', 'date', 'block', 'iucr', 'primary_type', 'description',
        'location_description', 'arrest', 'domestic', 'beat', 'district', 'ward',
        'community_area', 'fbi_code', 'x_coordinate', 'y_coordinate', 'year',
        'updated_on', 'latitude', 'longitude', 'location'
    ];

    protected $casts = [
        'date' => 'datetime',
        'updated_on' => 'datetime',
        'arrest' => 'boolean',
        'domestic' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'x_coordinate' => 'float',
        'y_coordinate' => 'float',
        'year' => 'integer',
        'ward' => 'integer',
        'beat' => 'integer',
        'district' => 'integer',
    ];

    public static function getHumanName(): string
    {
        return 'Chicago Crime';
    }

    public static function getIconClass(): string
    {
        return 'crime-div-icon'; // Reuse existing icon class
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
        return 'date';
    }

    public function getDate(): ?string
    {
        return $this->date ? Carbon::parse($this->date)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'id';
    }

    public function getExternalId(): string
    {
        return (string)$this->id;
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Case Number',
            'mainIdentifierField' => 'case_number',
            'descriptionLabel' => 'Primary Type',
            'descriptionField' => 'primary_type',
            'additionalFields' => [
                ['label' => 'Date', 'key' => 'date', 'format' => 'datetime'],
                ['label' => 'Description', 'key' => 'description'],
                ['label' => 'Block', 'key' => 'block'],
                ['label' => 'Arrest', 'key' => 'arrest', 'format' => 'boolean'],
                ['label' => 'Domestic', 'key' => 'domestic', 'format' => 'boolean'],
            ],
        ];
    }

    public static function getFieldLabels(): array
    {
        return [
            'id' => 'ID',
            'case_number' => 'Case Number',
            'date' => 'Date',
            'block' => 'Block',
            'iucr' => 'IUCR',
            'primary_type' => 'Primary Type',
            'description' => 'Description',
            'location_description' => 'Location Description',
            'arrest' => 'Arrest',
            'domestic' => 'Domestic',
            'beat' => 'Beat',
            'district' => 'District',
            'ward' => 'Ward',
            'community_area' => 'Community Area',
            'fbi_code' => 'FBI Code',
            'x_coordinate' => 'X Coordinate',
            'y_coordinate' => 'Y Coordinate',
            'year' => 'Year',
            'updated_on' => 'Updated On',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
        ];
    }
}
