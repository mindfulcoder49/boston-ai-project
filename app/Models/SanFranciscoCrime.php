<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class SanFranciscoCrime extends Model
{
    use HasFactory, Mappable;

    // This model points to the full dataset connection.
    // The seeder will populate a table of the same name/structure
    // in the 'san_francisco_db' connection for recent data.
    protected $connection = 'san_francisco_crime_db';
    protected $table = 'san_francisco_crimes';
    protected $primaryKey = 'row_id';

    public $timestamps = false;

    public static $statisticalAnalysisColumns = [
        'incident_category',
        'incident_subcategory',
        'incident_code',
        'resolution',
        'police_district',
        'analysis_neighborhood',
        'supervisor_district',
        'incident_year'
    ];

    const SEARCHABLE_COLUMNS = [
        'row_id', 'incident_datetime', 'incident_date', 'incident_time', 'incident_year', 'incident_day_of_week', 'report_datetime', 'incident_id', 'incident_number', 'cad_number', 'report_type_code', 'report_type_description', 'filed_online', 'incident_code', 'incident_category', 'incident_subcategory', 'incident_description', 'resolution', 'intersection', 'cnn', 'police_district', 'analysis_neighborhood', 'supervisor_district', 'supervisor_district_2012', 'latitude', 'longitude', 'point', 'data_as_of', 'data_loaded_at', 'neighborhoods', 'esncag_boundary_file', 'central_market_tenderloin_boundary_polygon_updated', 'civic_center_harm_reduction_project_boundary', 'hsoc_zones_as_of_2018_06_05', 'invest_in_neighborhoods_iin_areas', 'current_supervisor_districts', 'current_police_districts'
    ];

    protected $fillable = [
        'row_id',
        'incident_datetime',
        'incident_date',
        'incident_time',
        'incident_year',
        'incident_day_of_week',
        'report_datetime',
        'incident_id',
        'incident_number',
        'cad_number',
        'report_type_code',
        'report_type_description',
        'filed_online',
        'incident_code',
        'incident_category',
        'incident_subcategory',
        'incident_description',
        'resolution',
        'intersection',
        'cnn',
        'police_district',
        'analysis_neighborhood',
        'supervisor_district',
        'supervisor_district_2012',
        'latitude',
        'longitude',
        'point',
        'data_as_of',
        'data_loaded_at',
        'neighborhoods',
        'esncag_boundary_file',
        'central_market_tenderloin_boundary_polygon_updated',
        'civic_center_harm_reduction_project_boundary',
        'hsoc_zones_as_of_2018_06_05',
        'invest_in_neighborhoods_iin_areas',
        'current_supervisor_districts',
        'current_police_districts'
    ];

    protected $casts = [
        'incident_datetime' => 'datetime',
        'incident_year' => 'integer',
        'report_datetime' => 'datetime',
        'filed_online' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'data_as_of' => 'datetime',
        'data_loaded_at' => 'datetime',
        'neighborhoods' => 'integer',
        'esncag_boundary_file' => 'integer',
        'central_market_tenderloin_boundary_polygon_updated' => 'integer',
        'civic_center_harm_reduction_project_boundary' => 'integer',
        'hsoc_zones_as_of_2018_06_05' => 'integer',
        'invest_in_neighborhoods_iin_areas' => 'integer',
        'current_supervisor_districts' => 'integer',
        'current_police_districts' => 'integer',
    ];

    public static function getHumanName(): string
    {
        return 'San Francisco Police Department Incidents';
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
        return 'incident_datetime';
    }

    public function getDate(): ?string
    {
        $dateField = 'incident_datetime';
        return $this->$dateField ? Carbon::parse($this->$dateField)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'row_id';
    }

    public function getExternalId(): string
    {
        return (string)$this->row_id;
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Incident Number',
            'mainIdentifierField' => 'incident_number',
            'descriptionLabel' => 'Incident Category',
            'descriptionField' => 'incident_category',
            'additionalFields' => [
                ['label' => 'Date', 'key' => 'incident_datetime', 'format' => 'datetime'],
                ['label' => 'Description', 'key' => 'incident_description', 'format' => 'null'],
                ['label' => 'Category', 'key' => 'incident_category', 'format' => 'null'],
                ['label' => 'Intersection', 'key' => 'intersection', 'format' => 'null'],
                ['label' => 'Resolution', 'key' => 'resolution', 'format' => 'null'],
                ['label' => 'Police District', 'key' => 'police_district', 'format' => 'null'],
                ['label' => 'Filed Online', 'key' => 'filed_online', 'format' => 'boolean'],
            ],
        ];
    }

    public static function getFieldLabels(): array
    {
        return [
            'row_id' => 'Unique row identifier for the dataset (Socrata row id)',
            'incident_datetime' => 'Date and time when the incident occurred (best primary date for filtering)',
            'incident_date' => 'Date when the incident occurred (calendar date only)',
            'incident_time' => 'Time of day the incident occurred (text, e.g., 17:55)',
            'incident_year' => 'Year of the incident (convenience field useful for filtering)',
            'incident_day_of_week' => 'Day of week when the incident occurred (e.g., Monday)',
            'report_datetime' => 'Timestamp when the report was filed (may differ from incident time)',
            'incident_id' => 'System-generated identifier for incident reports (unique per report)',
            'incident_number' => 'Number issued on the report; used to reference cases and documents (Case Number)',
            'cad_number' => 'Computer Aided Dispatch number (may be null for online-filed reports)',
            'report_type_code' => 'System code for the report type',
            'report_type_description' => 'Human readable report type (Initial, Supplement, Coplogic, etc.)',
            'filed_online' => 'True if the report was filed via Coplogic (online)',
            'incident_code' => 'System code describing the specific incident type',
            'incident_category' => 'High-level category mapped to the incident code (e.g., Assault, Larceny)',
            'incident_subcategory' => 'Subcategory used for reporting and statistics',
            'incident_description' => 'Text description that corresponds with the incident code',
            'resolution' => 'Resolution of the incident at time of report (e.g., Open or Active, Cite or Arrest Adult)',
            'intersection' => 'Nearest intersection (two or more street names separated by a backslash)',
            'cnn' => 'Unique identifier of the intersection (Centerline Node Network id)',
            'police_district' => 'Police district where the incident occurred (entered by officer)',
            'analysis_neighborhood' => 'Neighborhood assigned for analysis (may be based on intersection)',
            'supervisor_district' => 'Current Supervisor District number (1-11)',
            'supervisor_district_2012' => 'Previous supervisor district (2012-2022) assigned for reference',
            'latitude' => 'Latitude in WGS84 (EPSG:4326)',
            'longitude' => 'Longitude in WGS84 (EPSG:4326)',
            'point' => 'Geolocation point (GeoJSON/OGC WKT compatible)',
            'data_as_of' => 'Dataset as-of timestamp (no description provided by portal)',
            'data_loaded_at' => 'Timestamp when this dataset file was uploaded to the portal',
            'neighborhoods' => 'Polygon id from the Neighborhoods dataset containing the point (used for choropleths)',
            'esncag_boundary_file' => 'Boundary file id (no description provided)',
            'central_market_tenderloin_boundary_polygon_updated' => 'Boundary polygon indicator for Central Market/Tenderloin (no description)',
            'civic_center_harm_reduction_project_boundary' => 'Boundary indicator for Civic Center Harm Reduction Project',
            'hsoc_zones_as_of_2018_06_05' => 'HSOC zone id (no detailed description provided)',
            'invest_in_neighborhoods_iin_areas' => 'IIN area id (no detailed description provided)',
            'current_supervisor_districts' => 'Computed id for current supervisor districts (spatial join)',
            'current_police_districts' => 'Computed id for current police districts (spatial join)',
        ];
    }
}
