<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class MontgomeryCountyMdCrime extends Model
{
    use HasFactory, Mappable;

    // This model points to the full dataset connection.
    // The seeder will populate a table of the same name/structure
    // in the 'montgomery_county_md_db' connection for recent data.
    protected $connection = 'montgomery_county_md_crime_db';
    protected $table = 'montgomery_county_md_crimes';
    protected $primaryKey = 'incident_id';

    public $timestamps = false;

    public static $statisticalAnalysisColumns = [
        'crimename1',
        'crimename2',
        'crimename3',
        'district',
        'agency',
        'place',
        'sector',
        'beat',
        'pra',
        'police_district_number',
        'council_districts',
        'communities'
    ];

    const SEARCHABLE_COLUMNS = [
        'incident_id', 'offence_code', 'case_number', 'date', 'start_date', 'end_date', 'nibrs_code', 'victims', 'crimename1', 'crimename2', 'crimename3', 'district', 'location', 'city', 'state', 'zip_code', 'agency', 'place', 'sector', 'beat', 'pra', 'address_number', 'street_prefix_dir', 'address_street', 'street_type', 'latitude', 'longitude', 'police_district_number', 'geolocation', 'council_districts', 'councils', 'communities', 'zip_codes', 'municipalities', 'council_districts_from_i23j_3mj8', 'service_regions', 'montgomery_county_boundary', 'council_districts_7', 'computed_region_vu5j_pcmz', 'computed_region_tx5f_5em3', 'computed_region_kbsp_ykn9', 'computed_region_d7bw_bq6x', 'computed_region_rbt8_3x7n', 'computed_region_a9cs_3ed7', 'computed_region_r648_kzwt', 'computed_region_d9ke_fpxt', 'computed_region_6vgr_duib'
    ];

    protected $fillable = [
        'incident_id',
        'offence_code',
        'case_number',
        'date',
        'start_date',
        'end_date',
        'nibrs_code',
        'victims',
        'crimename1',
        'crimename2',
        'crimename3',
        'district',
        'location',
        'city',
        'state',
        'zip_code',
        'agency',
        'place',
        'sector',
        'beat',
        'pra',
        'address_number',
        'street_prefix_dir',
        'address_street',
        'street_type',
        'latitude',
        'longitude',
        'police_district_number',
        'geolocation',
        'council_districts',
        'councils',
        'communities',
        'zip_codes',
        'municipalities',
        'council_districts_from_i23j_3mj8',
        'service_regions',
        'montgomery_county_boundary',
        'council_districts_7',
        'computed_region_vu5j_pcmz',
        'computed_region_tx5f_5em3',
        'computed_region_kbsp_ykn9',
        'computed_region_d7bw_bq6x',
        'computed_region_rbt8_3x7n',
        'computed_region_a9cs_3ed7',
        'computed_region_r648_kzwt',
        'computed_region_d9ke_fpxt',
        'computed_region_6vgr_duib'
    ];

    protected $casts = [
        'date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'victims' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'council_districts' => 'integer',
        'councils' => 'integer',
        'communities' => 'integer',
        'zip_codes' => 'integer',
        'municipalities' => 'integer',
        'council_districts_from_i23j_3mj8' => 'integer',
        'service_regions' => 'integer',
        'montgomery_county_boundary' => 'integer',
        'council_districts_7' => 'integer',
        'computed_region_vu5j_pcmz' => 'integer',
        'computed_region_tx5f_5em3' => 'integer',
        'computed_region_kbsp_ykn9' => 'integer',
        'computed_region_d7bw_bq6x' => 'integer',
        'computed_region_rbt8_3x7n' => 'integer',
        'computed_region_a9cs_3ed7' => 'integer',
        'computed_region_r648_kzwt' => 'integer',
        'computed_region_d9ke_fpxt' => 'integer',
        'computed_region_6vgr_duib' => 'integer',
    ];

    public static function getHumanName(): string
    {
        return 'Montgomery County Crime Incidents';
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
        return 'date';
    }

    public function getDate(): ?string
    {
        $dateField = 'date';
        return $this->$dateField ? Carbon::parse($this->$dateField)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'incident_id';
    }

    public function getExternalId(): string
    {
        return (string)$this->incident_id;
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Case Number',
            'mainIdentifierField' => 'case_number',
            'descriptionLabel' => 'Offense Description',
            'descriptionField' => 'crimename3',
            'additionalFields' => [
                ['label' => 'Dispatch Date', 'key' => 'date', 'format' => 'datetime'],
                ['label' => 'Occurred From', 'key' => 'start_date', 'format' => 'datetime'],
                ['label' => 'Occurred To', 'key' => 'end_date', 'format' => 'datetime'],
                ['label' => 'Location', 'key' => 'location'],
                ['label' => 'Police District', 'key' => 'district'],
                ['label' => 'Agency', 'key' => 'agency'],
                ['label' => 'Place', 'key' => 'place'],
                ['label' => 'Victims', 'key' => 'victims'],
                ['label' => 'Offense Code', 'key' => 'offence_code'],
            ],
        ];
    }

    public static function getFieldLabels(): array
    {
        return [
            'incident_id' => 'Police Incident Number, unique per incident',
            'offence_code' => 'Offense code defined by NIBRS/UCR',
            'case_number' => 'Police report number (CR Number)',
            'date' => 'Dispatch date/time when an officer was dispatched',
            'start_date' => 'Occurred from date/time',
            'end_date' => 'Occurred to date/time',
            'nibrs_code' => 'FBI NIBRS code',
            'victims' => 'Number of victims reported for the offense',
            'crimename1' => 'High level crime category (Society/Person/Property/Other)',
            'crimename2' => 'Descriptive NIBRS category',
            'crimename3' => 'Detailed offense description',
            'district' => 'Police district name (e.g., Rockville, Wheaton)',
            'location' => 'Textual block-level address or location description',
            'city' => 'City of the incident',
            'state' => 'State abbreviation',
            'zip_code' => 'ZIP code',
            'agency' => 'Assigned police department or agency',
            'place' => 'Place description (e.g., Residence, Restaurant)',
            'sector' => 'Police sector name, subset of district',
            'beat' => 'Police patrol area, subset of sector',
            'pra' => 'Police Response Area',
            'address_number' => 'House or business number',
            'street_prefix_dir' => 'Street prefix direction (N, S, E, W)',
            'address_street' => 'Street name',
            'street_type' => 'Street type (Ave, Rd, Dr)',
            'latitude' => 'Latitude coordinate for the incident',
            'longitude' => 'Longitude coordinate for the incident',
            'police_district_number' => 'Major police boundary identifier',
            'geolocation' => 'Structured geolocation object with latitude and longitude',
            'council_districts' => 'Council district id (numeric)',
            'councils' => 'Council identifier',
            'communities' => 'Community identifier',
            'zip_codes' => 'Alternate numeric zip code field',
            'municipalities' => 'Municipality identifier',
            'council_districts_from_i23j_3mj8' => 'Computed council district membership from polygon dataset',
            'service_regions' => 'Service region identifier',
            'montgomery_county_boundary' => 'Indicator for membership in county boundary polygons',
            'council_districts_7' => 'Computed membership for Council Districts 7 polygon set',
            'computed_region_vu5j_pcmz' => 'Computed region id (vu5j_pcmz)',
            'computed_region_tx5f_5em3' => 'Computed region id (tx5f_5em3)',
            'computed_region_kbsp_ykn9' => 'Computed region id (kbsp_ykn9)',
            'computed_region_d7bw_bq6x' => 'Computed region id (d7bw_bq6x)',
            'computed_region_rbt8_3x7n' => 'Computed region id (rbt8_3x7n)',
            'computed_region_a9cs_3ed7' => 'Computed region id (a9cs_3ed7)',
            'computed_region_r648_kzwt' => 'Computed region id (r648_kzwt)',
            'computed_region_d9ke_fpxt' => 'Computed region id (d9ke_fpxt)',
            'computed_region_6vgr_duib' => 'Computed region id (6vgr_duib)',
        ];
    }
}
