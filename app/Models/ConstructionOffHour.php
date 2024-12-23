<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstructionOffHour extends Model
{
    use HasFactory;

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

    public function getDateField(): string
    {
        return 'start_datetime';
    }
    
    public function getDate(): string
    {
        return $this->start_datetime;
    }

    public function getExternalIdName(): string
    {
        return 'app_no';
    }

    public function getExternalId(): string
    {
        return $this->app_no;
    }
}
