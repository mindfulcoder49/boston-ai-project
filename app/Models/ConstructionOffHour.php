<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;

class ConstructionOffHour extends Model
{
    use HasFactory, Mappable;

    protected $table = 'construction_off_hours';

    protected $fillable = [
        'app_no',
        'start_datetime',
        'stop_datetime',
        'address',
        'ward',
        'latitude',
        'longitude',
        'language_code',
    ];

    const SEARCHABLE_COLUMNS = [
        'app_no', 'address', 'ward', 'latitude', 'longitude', 'language_code',
    ];

    // Mappable Trait Implementations
    public static function getHumanName(): string
    {
        return 'Construction Off Hours';
    }

    public static function getIconClass(): string
    {
        return 'construction-off-hour-div-icon';
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Construction Off Hour';
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
        return 'start_datetime';
    }

    public function getDate(): ?string
    {
        return $this->start_datetime ? (is_string($this->start_datetime) ? $this->start_datetime : $this->start_datetime->toDateString()) : null;
    }

    public static function getExternalIdName(): string
    {
        return 'app_no';
    }

    public function getExternalId(): string
    {
        return $this->app_no;
    }
}
