<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\Mappable; // Added

class ThreeOneOneCase extends Model
{
    use Mappable; // Added

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the primary key is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the auto-incrementing primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    //add fillable
    protected $fillable = [
        'case_enquiry_id', 
        'open_dt', 
        'sla_target_dt', 
        'closed_dt', 
        'on_time', 
        'case_status', 
        'closure_reason', 
        'case_title', 
        'subject', 
        'reason', 
        'type', 
        'queue', 
        'department', 
        'submitted_photo', 
        'closed_photo', 
        'location', 
        'fire_district', 
        'pwd_district', 
        'city_council_district', 
        'police_district', 
        'neighborhood', 
        'neighborhood_services_district', 
        'ward', 
        'precinct', 
        'location_street_name', 
        'location_zipcode', 
        'latitude', 
        'longitude', 
        'source', 
        'ward_number', 
        'language_code',
    ];

    const SEARCHABLE_COLUMNS = [
        'id', 'case_enquiry_id', 'open_dt', 'sla_target_dt', 'closed_dt', 'on_time', 'case_status', 'closure_reason', 'case_title', 'subject', 'reason', 'type', 'queue', 'department', 'submitted_photo', 'closed_photo', 'location', 'fire_district', 'pwd_district', 'city_council_district', 'police_district', 'neighborhood', 'neighborhood_services_district', 'ward', 'precinct', 'location_street_name', 'location_zipcode', 'latitude', 'longitude', 'source', 'ward_number', 'language_code',
    ];
    
    //function to check case survival time
    public function getSurvivalTimeAttribute(): float
    {
        //get case open date
        $openDate = $this->open_dt;
        //get case close date
        $closeDate = $this->closed_dt;
        //check if closed date is null
        if ($closeDate == null) {
            //set to tomorrow
            $closeDate = date('Y-m-d', strtotime('+1 day'));
        }
        //calculate difference between open and close date
        $diff = abs(strtotime($closeDate) - strtotime($openDate));
        
        //return in hours
        return $diff / (60 * 60);
    }

    public static function getDateField(): string
    {
        return 'open_dt';
    }

    public function getDate(): string
    {
        return $this->open_dt;
    }

    public static function getExternalIdName(): string
    {
        return 'case_enquiry_id';
    }

    public function getExternalId(): string
    {
        return $this->case_enquiry_id;
    }

    // Mappable Trait Implementations
    // getFilterableFieldsDescription() method removed
    // getContextData() method removed
    // getSearchableColumns() method removed (trait will use SEARCHABLE_COLUMNS constant if defined, or suggestions)
    // getGptFunctionSchema() method removed
}
