<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class CambridgeMasterAddress extends Model
{
    use HasFactory, Mappable;

    protected $table = 'cambridge_master_addresses';

    protected $fillable = [
        'address_id_external',
        'full_addr',
        'street_number',
        'stname',
        'building_id',
        'maplot',
        'latitude',
        'longitude',
        'type',
        'zip_code',
        'neighborhood',
        'election_ward',
        'election_precinct',
        'raw_data',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'raw_data' => 'array',
    ];

    public static function getHumanName(): string
    {
        return 'Cambridge Master Address';
    }

    public static function getIconClass(): string
    {
        return 'fas fa-map-marker-alt'; // Example icon
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Address';
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
        // Master address list might not have a primary date field for time-based filtering
        // Returning 'created_at' as a fallback.
        return 'created_at';
    }

    public function getDate(): ?string
    {
        return $this->created_at ? Carbon::parse($this->created_at)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        return 'address_id_external';
    }

    public function getExternalId(): string
    {
        return $this->address_id_external;
    }

    public static $searchable_columns_config = [
        'address_id_external', 'full_addr', 'stname', 'maplot', 'zip_code', 'neighborhood'
    ];
}
