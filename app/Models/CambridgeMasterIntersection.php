<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeMasterIntersection extends Model
{
    use HasFactory, Mappable;

    protected $table = 'cambridge_master_intersections';

    protected $fillable = [
        'node_number_external',
        'intersection_name',
        'intersecting_street_count',
        'zip_code',
        'longitude',
        'latitude',
        'neighborhood',
        'raw_data',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'intersecting_street_count' => 'integer',
        'raw_data' => 'array',
    ];

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
