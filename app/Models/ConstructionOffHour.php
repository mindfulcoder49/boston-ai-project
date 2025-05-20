<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable; // Added

class ConstructionOffHour extends Model
{
    use HasFactory, Mappable; // Added Mappable

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

    const SEARCHABLE_COLUMNS = [ // Added
        'app_no', 'address', 'ward', 'latitude', 'longitude', 'language_code',
    ];

    public static function getDateField(): string
    {
        return 'start_datetime';
    }

    public function getDate(): string
    {
        return $this->start_datetime;
    }

    public static function getExternalIdName(): string
    {
        return 'app_no';
    }

    public function getExternalId(): string
    {
        return $this->app_no;
    }

    // Mappable Trait Implementations
}
