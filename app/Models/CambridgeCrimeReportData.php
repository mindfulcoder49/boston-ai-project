<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeCrimeReportData extends Model
{
    use HasFactory, Mappable;

    protected $table = 'cambridge_crime_reports_data';

    protected $fillable = [
        'file_number_external',
        'date_of_report',
        'crime_datetime_raw',
        'crime_start_time',
        'crime_end_time',
        'crime',
        'reporting_area',
        'neighborhood',
        'location_address',
        'latitude',
        'longitude',
        'crime_details', // Added
    ];

    protected $casts = [
        'date_of_report' => 'datetime',
        'crime_start_time' => 'datetime',
        'crime_end_time' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function getHumanName(): string
    {
        return 'Cambridge Crime Report';
    }

    public static function getIconClass(): string
    {
        return 'crime-div-icon'; // Example icon for crime
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
        // Use crime_start_time if available, otherwise date_of_report
        return 'crime_start_time'; 
    }

    public function getDate(): ?string
    {
        $dateToUse = $this->crime_start_time ?? $this->date_of_report;
        return $dateToUse ? Carbon::parse($dateToUse)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'file_number_external';
    }

    public function getExternalId(): string
    {
        return $this->file_number_external;
    }

    public static $searchable_columns_config = [
        'file_number_external', 'crime', 'reporting_area', 'neighborhood', 'location_address'
    ];
}
