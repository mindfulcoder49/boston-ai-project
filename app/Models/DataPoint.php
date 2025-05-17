<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPoint extends Model
{
    protected $table = 'data_points';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'type',
        'location',
        'crime_data_id',
        'three_one_one_case_id',
        'property_violation_id',
        'construction_off_hour_id',
        'building_permit_id',
        'food_establishment_violation_id',
        'generic_foreign_id', // Add generic_foreign_id here
    ];

    /**
     * Relationship to CrimeData model.
     */
    public function crimeData()
    {
        return $this->belongsTo(CrimeData::class, 'crime_data_id');
    }

    /**
     * Relationship to ThreeOneOneCase model.
     */
    public function threeOneOneCase()
    {
        return $this->belongsTo(ThreeOneOneCase::class, 'three_one_one_case_id');
    }

    /**
     * Relationship to PropertyViolation model.
     */
    public function propertyViolation()
    {
        return $this->belongsTo(PropertyViolation::class, 'property_violation_id');
    }

    /**
     * Relationship to ConstructionOffHour model.
     */
    public function constructionOffHour()
    {
        return $this->belongsTo(ConstructionOffHour::class, 'construction_off_hour_id');
    }

    /**
     * Relationship to BuildingPermit model.
     */
    public function buildingPermit()
    {
        return $this->belongsTo(BuildingPermit::class, 'building_permit_id');
    }

    /**
     * Relationship to FoodEstablishmentViolation model.
     */
    public function foodEstablishmentViolation()
    {
        return $this->belongsTo(FoodEstablishmentViolation::class, 'food_establishment_violation_id');
    }

    /**
     * If you need to fetch 'location' as WKB or WKT, you could add an accessor.
     * Example: Convert raw binary data to WKT for debugging:
     */
    /*
    public function getLocationAsWktAttribute()
    {
        // Convert MySQL geometry to WKT
        // This requires a raw query:
        // SELECT ST_AsText(location) as location_as_wkt FROM data_points ...
        // So you'd do something like:
        // return DB::selectOne("SELECT ST_AsText(location) as wkt FROM data_points WHERE id = ?", [$this->id])->wkt ?? null;
    }
    */
}
