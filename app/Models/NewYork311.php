<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class NewYork311 extends Model
{
    use HasFactory, Mappable;

    // This model points to the full dataset connection.
    // The seeder will populate a table of the same name/structure
    // in the 'new_york_db' connection for recent data.
    protected $connection = 'new_york_311_db';
    protected $table = 'new_york_311s';
    protected $primaryKey = 'unique_key';

    public $timestamps = false;

    public static $statisticalAnalysisColumns = [
        'complaint_type',
        'descriptor',
        'agency',
        'borough',
        'community_board',
        'council_district',
        'police_precinct',
        'status',
        'open_data_channel_type',
        'city_council_districts',
        'community_districts'
    ];

    const SEARCHABLE_COLUMNS = [
        'unique_key', 'created_date', 'closed_date', 'agency', 'agency_name', 'complaint_type', 'descriptor', 'additional_details', 'location_type', 'incident_zip', 'incident_address', 'street_name', 'cross_street_1', 'cross_street_2', 'intersection_street_1', 'intersection_street_2', 'address_type', 'city', 'landmark', 'facility_type', 'status', 'due_date', 'resolution_description', 'resolution_action_updated_date', 'community_board', 'council_district', 'police_precinct', 'bbl', 'borough', 'x_coordinate_state_plane', 'y_coordinate_state_plane', 'open_data_channel_type', 'park_facility_name', 'park_borough', 'vehicle_type', 'taxi_company_borough', 'taxi_pick_up_location', 'bridge_highway_name', 'bridge_highway_direction', 'road_ramp', 'bridge_highway_segment', 'latitude', 'longitude', 'location', 'community_districts', 'borough_boundaries', 'police_precincts', 'city_council_districts'
    ];

    protected $fillable = [
        'unique_key',
        'created_date',
        'closed_date',
        'agency',
        'agency_name',
        'complaint_type',
        'descriptor',
        'additional_details',
        'location_type',
        'incident_zip',
        'incident_address',
        'street_name',
        'cross_street_1',
        'cross_street_2',
        'intersection_street_1',
        'intersection_street_2',
        'address_type',
        'city',
        'landmark',
        'facility_type',
        'status',
        'due_date',
        'resolution_description',
        'resolution_action_updated_date',
        'community_board',
        'council_district',
        'police_precinct',
        'bbl',
        'borough',
        'x_coordinate_state_plane',
        'y_coordinate_state_plane',
        'open_data_channel_type',
        'park_facility_name',
        'park_borough',
        'vehicle_type',
        'taxi_company_borough',
        'taxi_pick_up_location',
        'bridge_highway_name',
        'bridge_highway_direction',
        'road_ramp',
        'bridge_highway_segment',
        'latitude',
        'longitude',
        'location',
        'community_districts',
        'borough_boundaries',
        'police_precincts',
        'city_council_districts'
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'closed_date' => 'datetime',
        'due_date' => 'datetime',
        'resolution_action_updated_date' => 'datetime',
        'x_coordinate_state_plane' => 'float',
        'y_coordinate_state_plane' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'community_districts' => 'integer',
        'borough_boundaries' => 'integer',
        'police_precincts' => 'integer',
        'city_council_districts' => 'integer',
    ];

    public static function getHumanName(): string
    {
        return 'New York 311 Service Requests';
    }

    public static function getIconClass(): string
    {
        return 'case-div-icon no-photo';
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return '311 Case';
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
        return 'created_date';
    }

    public function getDate(): ?string
    {
        $dateField = 'created_date';
        return $this->$dateField ? Carbon::parse($this->$dateField)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'unique_key';
    }

    public function getExternalId(): string
    {
        return (string)$this->unique_key;
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Service Request Unique Key',
            'mainIdentifierField' => 'unique_key',
            'descriptionLabel' => 'Problem / Complaint Type',
            'descriptionField' => 'complaint_type',
            'additionalFields' => [
                ['label' => 'Created Date', 'key' => 'created_date', 'format' => 'datetime'],
                ['label' => 'Closed Date', 'key' => 'closed_date', 'format' => 'datetime'],
                ['label' => 'Agency', 'key' => 'agency_name'],
                ['label' => 'Status', 'key' => 'status'],
                ['label' => 'Resolution', 'key' => 'resolution_description'],
                ['label' => 'Borough', 'key' => 'borough'],
                ['label' => 'Incident Address', 'key' => 'incident_address'],
            ],
        ];
    }

    public static function getFieldLabels(): array
    {
        return [
            'unique_key' => 'Unique identifier of a Service Request (SR)',
            'created_date' => 'Date and time the service request was created',
            'closed_date' => 'Date and time the service request was closed by the responding agency',
            'agency' => 'Acronym of the responding City Government Agency',
            'agency_name' => 'Full name of the responding City Government Agency',
            'complaint_type' => 'Top-level topic or category of the incident or condition',
            'descriptor' => 'Second-level detail associated with the Problem / Complaint Type',
            'additional_details' => 'Third level of detail for the problem; free-text and not always populated',
            'location_type' => 'Type of location used in the address information',
            'incident_zip' => 'Incident location zip code from geo validation',
            'incident_address' => 'House number or address provided by submitter',
            'street_name' => 'Street name of the incident address',
            'cross_street_1' => 'First cross street based on geo validated incident location',
            'cross_street_2' => 'Second cross street based on geo validated incident location',
            'intersection_street_1' => 'First intersecting street if address is an intersection',
            'intersection_street_2' => 'Second intersecting street if address is an intersection',
            'address_type' => 'Type of incident location information, for example INTERSECTION or BLOCKFACE',
            'city' => 'City of the incident location as provided by geovalidation',
            'landmark' => 'Name of the landmark if the incident location is identified as one',
            'facility_type' => 'Type of city facility associated with the service request, if available',
            'status' => 'Current status of the service request',
            'due_date' => 'Date when the responding agency is expected to update the SR based on SLA',
            'resolution_description' => 'Narrative describing the last action taken or next steps',
            'resolution_action_updated_date' => 'Date when the responding agency last updated the service request action',
            'community_board' => 'Community Board name/identifier from geovalidation',
            'council_district' => 'City Council district where the service request is located',
            'police_precinct' => 'NYPD precinct for the incident location',
            'bbl' => 'Borough, Block and Lot parcel identifier provided by geovalidation',
            'borough' => 'Borough name confirmed by geovalidation',
            'x_coordinate_state_plane' => 'State Plane X coordinate of the incident location (projected); used for internal spatial calculations',
            'y_coordinate_state_plane' => 'State Plane Y coordinate of the incident location (projected); used for internal spatial calculations',
            'open_data_channel_type' => 'How the service request was submitted to 311 (Phone, Online, Mobile, Other, Unknown)',
            'park_facility_name' => 'Parks Department facility name if incident is in a park facility',
            'park_borough' => 'Borough of the park facility if applicable',
            'vehicle_type' => 'Type of vehicle when the incident relates to taxis or TLC vehicles',
            'taxi_company_borough' => 'Borough of the taxi company if the SR identifies a taxi',
            'taxi_pick_up_location' => 'Pick up location for taxi-related complaints',
            'bridge_highway_name' => 'Name of the bridge or highway if applicable',
            'bridge_highway_direction' => 'Direction on the bridge or highway where the issue took place',
            'road_ramp' => 'Differentiates whether the incident was on the road or a ramp for bridge/highway reports',
            'bridge_highway_segment' => 'Additional information on the section of the bridge or highway',
            'latitude' => 'Geographic latitude of the incident location',
            'longitude' => 'Geographic longitude of the incident location',
            'location' => 'Geo point combining latitude and longitude for spatial queries',
            'community_districts' => 'Computed region id for Community Districts where the point is located (for choropleths)',
            'borough_boundaries' => 'Computed region id for Borough Boundaries where the point is located',
            'police_precincts' => 'Computed region id for Police Precincts where the point is located',
            'city_council_districts' => 'Computed region id for City Council Districts where the point is located',
        ];
    }
}
