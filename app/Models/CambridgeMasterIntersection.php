<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeMasterIntersection extends Model
{
    use HasFactory;

    protected $table = 'cambridge_intersections';
    protected $primaryKey = 'nodenumber';

    protected $fillable = [
        'intersection',
        'intersectingstreetcount',
        'zip_code',
        'longitude',
        'latitude',
        'neighborhood',
        'election_ward',
        'election_precinct',
        'election_polling_address',
        'representation_district',
        'senate_district',
        'cad_reporting_district',
        'police_sector',
        'police_car_route',
        'police_walking_route',
        'police_neighborhood',
        'police_business_district',
        'street_sweeping_district',
        'census_tract_2010',
        'census_block_group_2010',
        'census_block_2010',
        'census_block_id_2010',
        'commercial_district',
        'census_tract_2020',
        'census_block_group_2020',
        'census_block_2020',
        'census_block_id_2020',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'intersectingstreetcount' => 'integer',
    ];

    // Accessors to map database columns to the names used in the report command
    public function getNodeNumberExternalAttribute()
    {
        return $this->nodenumber;
    }

    public function getIntersectionNameAttribute()
    {
        return $this->intersection;
    }

    public function getIntersectingStreetCountAttribute()
    {
        return $this->intersectingstreetcount;
    }

    public static function getHumanName(): string
    {
        return 'Cambridge Master Intersection';
    }

    public static function getIconClass(): string
    {
        return 'fas fa-traffic-light'; // Example icon
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Intersection';
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
        // Master intersection list might not have a primary date field
        return 'created_at';
    }

    public function getDate(): ?string
    {
        return $this->created_at ? Carbon::parse($this->created_at)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'node_number_external';
    }

    public function getExternalId(): string
    {
        return $this->node_number_external;
    }

    public static $searchable_columns_config = [
        'node_number_external', 'intersection_name', 'zip_code', 'neighborhood'
    ];
}
