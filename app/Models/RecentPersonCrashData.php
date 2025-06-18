<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Mappable;
use Carbon\Carbon;

class PersonCrashData extends Model
{
    use HasFactory, Mappable;

    protected $table = 'person_crash_data';

    // Note: Timestamps (created_at, updated_at) are not in the migration,
    // so Eloquent's default timestamping might need to be disabled if not desired.
    // public $timestamps = false; // Uncomment if you don't have/want created_at/updated_at

    protected $fillable = [
        'crash_numb', 'city_town_name', 'crash_datetime', 'crash_hour', 'crash_status',
        'crash_severity_descr', 'max_injr_svrty_cl', 'numb_vehc', 'numb_nonfatal_injr',
        'numb_fatal_injr', 'polc_agncy_type_descr', 'year', 'manr_coll_descr',
        'vehc_mnvr_actn_cl', 'vehc_trvl_dirc_cl', 'vehc_seq_events_cl', 'ambnt_light_descr',
        'weath_cond_descr', 'road_surf_cond_descr', 'first_hrmf_event_descr',
        'most_hrmfl_evt_cl', 'drvr_cntrb_circ_cl', 'vehc_config_cl', 'street_numb',
        'rdwy', 'dist_dirc_from_int', 'near_int_rdwy', 'mm_rte', 'dist_dirc_milemarker',
        'milemarker', 'exit_rte', 'dist_dirc_exit', 'exit_numb', 'dist_dirc_landmark',
        'landmark', 'rdwy_jnct_type_descr', 'traf_cntrl_devc_type_descr',
        'trafy_descr_descr', 'jurisdictn', 'first_hrmf_event_loc_descr',
        'is_geocoded_status', 'geocoding_method_name', 'x_coord', 'y_coord', 'lat', 'lon',
        'rmv_doc_ids', 'crash_rpt_ids', 'age_drvr_yngst', 'age_drvr_oldest',
        'age_nonmtrst_yngst', 'age_nonmtrst_oldest', 'drvr_distracted_cl', 'district_num',
        'rpa_abbr', 'vehc_emer_use_cl', 'vehc_towed_from_scene_cl', 'cnty_name',
        'fmsca_rptbl_cl', 'fmsca_rptbl', 'hit_run_descr', 'lclty_name',
        'road_cntrb_descr', 'schl_bus_reld_descr', 'speed_limit',
        'traf_cntrl_devc_func_descr', 'work_zone_reld_descr', 'aadt', 'aadt_year',
        'pk_pct_sut', 'av_pct_sut', 'pk_pct_ct', 'av_pct_ct', 'curb', 'truck_rte',
        'lt_sidewlk', 'rt_sidewlk', 'shldr_lt_w', 'shldr_lt_t', 'surface_wd',
        'surface_tp', 'shldr_rt_w', 'shldr_rt_t', 'num_lanes', 'opp_lanes',
        'med_width', 'med_type', 'urban_type', 'f_class', 'urban_area', 'fd_aid_rte',
        'facility', 'operation', 'control', 'peak_lane', 'speed_lim', 'streetname',
        'fromstreetname', 'tostreetname', 'city', 'struct_cnd', 'terrain',
        'urban_loc_type', 'aadt_deriv', 'statn_num', 'op_dir_sl', 'shldr_ul_t',
        'shldr_ul_w', 't_exc_type', 't_exc_time', 'f_f_class', 'vehc_unit_numb',
        'alc_suspd_type_descr', 'driver_age', 'drvr_cntrb_circ_descr',
        'driver_distracted_type_descr', 'drvr_lcn_state', 'drug_suspd_type_descr',
        'emergency_use_desc', 'fmsca_rptbl_vl', 'haz_mat_placard_descr',
        'max_injr_svrty_vl', 'most_hrmf_event', 'total_occpt_in_vehc',
        'vehc_manr_act_descr', 'vehc_confg_descr', 'vehc_most_dmgd_area',
        'owner_addr_city_town', 'owner_addr_state', 'vehc_reg_state',
        'vehc_reg_type_code', 'vehc_seq_events', 'vehc_towed_from_scene',
        'trvl_dirc_descr', 'vehicle_make_descr', 'vehicle_model_descr', 'vehicle_vin',
        'driver_violation_cl', 'pers_numb', 'age', 'ejctn_descr', 'injy_stat_descr',
        'med_facly', 'pers_addr_city', 'state_prvn_code', 'pers_type',
        'prtc_sys_use_descr', 'sfty_equp_desc_1', 'sfty_equp_desc_2', 'sex_descr',
        'trnsd_by_descr', 'non_mtrst_type_cl', 'non_mtrst_actn_cl',
        'non_mtrst_loc_cl', 'non_mtrst_act_descr', 'non_mtrst_cond_descr',
        'non_mtrst_loc_descr', 'non_mtrst_type_descr', 'non_mtrst_origin_dest_cl',
        'non_mtrst_cntrb_circ_cl', 'non_mtrst_distracted_by_cl',
        'non_mtrst_alc_suspd_type_cl', 'non_mtrst_drug_suspd_type_cl',
        'non_mtrst_event_seq_cl', 'traffic_control_type_descr',
        'non_motorist_cntrb_circ_1', 'non_motorist_cntrb_circ_2',
        'non_motorist_contact_point', 'non_motorist_distracted_by_1',
        'non_motorist_distracted_by_2', 'non_motorist_ejection_descr',
        'non_motorist_event_sequence_1', 'non_motorist_event_sequence_2',
        'non_motorist_event_sequence_3', 'non_motorist_event_sequence_4',
        'non_motorist_driver_lic_state', 'non_motorist_primary_injury',
        'non_motorist_seating_position', 'non_motorist_traffic_control',
        'non_motorist_trapped_descr', 'non_motorist_origin_dest',
        'non_mtrst_test_type_descr', 'non_mtrst_test_status_descr',
        'non_mtrst_test_result_descr', 'crash_date_text_raw', 'crash_time_2_raw',
        'objectid_source',
    ];

    protected $casts = [
        'crash_datetime' => 'datetime',
        'crash_hour' => 'integer', // tinyInteger unsigned
        'numb_vehc' => 'integer',
        'numb_nonfatal_injr' => 'integer',
        'numb_fatal_injr' => 'integer',
        'year' => 'integer', // year
        'milemarker' => 'decimal:3',
        'x_coord' => 'decimal:4',
        'y_coord' => 'decimal:4',
        'lat' => 'decimal:7',
        'lon' => 'decimal:7',
        'district_num' => 'integer', // tinyInteger unsigned
        'fmsca_rptbl' => 'boolean',
        'hit_run_descr' => 'boolean',
        'schl_bus_reld_descr' => 'boolean',
        'speed_limit' => 'integer', // smallInteger unsigned
        'work_zone_reld_descr' => 'boolean',
        'aadt' => 'integer', // integer unsigned
        'aadt_year' => 'integer', // smallInteger unsigned
        'pk_pct_sut' => 'decimal:3',
        'av_pct_sut' => 'decimal:3',
        'pk_pct_ct' => 'decimal:3',
        'av_pct_ct' => 'decimal:3',
        'lt_sidewlk' => 'decimal:1',
        'rt_sidewlk' => 'decimal:1',
        'shldr_lt_w' => 'decimal:1',
        'surface_wd' => 'decimal:1',
        'shldr_rt_w' => 'decimal:1',
        'num_lanes' => 'integer', // tinyInteger unsigned
        'opp_lanes' => 'integer', // tinyInteger unsigned
        'med_width' => 'decimal:1',
        'peak_lane' => 'integer', // tinyInteger unsigned
        'speed_lim' => 'integer', // smallInteger unsigned
        'statn_num' => 'integer',
        'op_dir_sl' => 'integer', // smallInteger unsigned
        'shldr_ul_w' => 'decimal:1',
        'vehc_unit_numb' => 'integer', // tinyInteger unsigned
        'alc_suspd_type_descr' => 'boolean',
        'driver_age' => 'integer', // smallInteger unsigned
        'drug_suspd_type_descr' => 'boolean',
        'emergency_use_desc' => 'boolean',
        'fmsca_rptbl_vl' => 'boolean',
        'haz_mat_placard_descr' => 'boolean',
        'total_occpt_in_vehc' => 'integer', // smallInteger unsigned
        'pers_numb' => 'integer', // integer unsigned
        'age' => 'integer', // smallInteger unsigned
        'objectid_source' => 'integer', // bigInteger
    ];

    public const SEARCHABLE_COLUMNS = [
        'crash_numb', 'city_town_name', 'crash_datetime', 'crash_hour', 'crash_status',
        'crash_severity_descr', 'max_injr_svrty_cl', 'numb_vehc', 'numb_nonfatal_injr',
        'numb_fatal_injr', 'polc_agncy_type_descr', 'year', 'manr_coll_descr',
        'vehc_mnvr_actn_cl', 'vehc_trvl_dirc_cl', 'vehc_seq_events_cl', 'ambnt_light_descr',
        'weath_cond_descr', 'road_surf_cond_descr', 'first_hrmf_event_descr',
        'most_hrmfl_evt_cl', 'drvr_cntrb_circ_cl', 'vehc_config_cl', 'street_numb',
        'rdwy', 'dist_dirc_from_int', 'near_int_rdwy', 'mm_rte', 'dist_dirc_milemarker',
        'milemarker', 'exit_rte', 'dist_dirc_exit', 'exit_numb', 'dist_dirc_landmark',
        'landmark', 'rdwy_jnct_type_descr', 'traf_cntrl_devc_type_descr',
        'trafy_descr_descr', 'jurisdictn', 'first_hrmf_event_loc_descr',
        'is_geocoded_status', 'geocoding_method_name', 'x_coord', 'y_coord', 'lat', 'lon',
        'rmv_doc_ids', 'crash_rpt_ids', 'age_drvr_yngst', 'age_drvr_oldest',
        'age_nonmtrst_yngst', 'age_nonmtrst_oldest', 'drvr_distracted_cl', 'district_num',
        'rpa_abbr', 'vehc_emer_use_cl', 'vehc_towed_from_scene_cl', 'cnty_name',
        'fmsca_rptbl_cl', 'fmsca_rptbl', 'hit_run_descr', 'lclty_name',
        'road_cntrb_descr', 'schl_bus_reld_descr', 'speed_limit',
        'traf_cntrl_devc_func_descr', 'work_zone_reld_descr', 'aadt', 'aadt_year',
        'pk_pct_sut', 'av_pct_sut', 'pk_pct_ct', 'av_pct_ct', 'curb', 'truck_rte',
        'lt_sidewlk', 'rt_sidewlk', 'shldr_lt_w', 'shldr_lt_t', 'surface_wd',
        'surface_tp', 'shldr_rt_w', 'shldr_rt_t', 'num_lanes', 'opp_lanes',
        'med_width', 'med_type', 'urban_type', 'f_class', 'urban_area', 'fd_aid_rte',
        'facility', 'operation', 'control', 'peak_lane', 'speed_lim', 'streetname',
        'fromstreetname', 'tostreetname', 'city', 'struct_cnd', 'terrain',
        'urban_loc_type', 'aadt_deriv', 'statn_num', 'op_dir_sl', 'shldr_ul_t',
        'shldr_ul_w', 't_exc_type', 't_exc_time', 'f_f_class', 'vehc_unit_numb',
        'alc_suspd_type_descr', 'driver_age', 'drvr_cntrb_circ_descr',
        'driver_distracted_type_descr', 'drvr_lcn_state', 'drug_suspd_type_descr',
        'emergency_use_desc', 'fmsca_rptbl_vl', 'haz_mat_placard_descr',
        'max_injr_svrty_vl', 'most_hrmf_event', 'total_occpt_in_vehc',
        'vehc_manr_act_descr', 'vehc_confg_descr', 'vehc_most_dmgd_area',
        'owner_addr_city_town', 'owner_addr_state', 'vehc_reg_state',
        'vehc_reg_type_code', 'vehc_seq_events', 'vehc_towed_from_scene',
        'trvl_dirc_descr', 'vehicle_make_descr', 'vehicle_model_descr', 'vehicle_vin',
        'driver_violation_cl', 'pers_numb', 'age', 'ejctn_descr', 'injy_stat_descr',
        'med_facly', 'pers_addr_city', 'state_prvn_code', 'pers_type',
        'prtc_sys_use_descr', 'sfty_equp_desc_1', 'sfty_equp_desc_2', 'sex_descr',
        'trnsd_by_descr', 'non_mtrst_type_cl', 'non_mtrst_actn_cl',
        'non_mtrst_loc_cl', 'non_mtrst_act_descr', 'non_mtrst_cond_descr',
        'non_mtrst_loc_descr', 'non_mtrst_type_descr', 'non_mtrst_origin_dest_cl',
        'non_mtrst_cntrb_circ_cl', 'non_mtrst_distracted_by_cl',
        'non_mtrst_alc_suspd_type_cl', 'non_mtrst_drug_suspd_type_cl',
        'non_mtrst_event_seq_cl', 'traffic_control_type_descr',
        'non_motorist_cntrb_circ_1', 'non_motorist_cntrb_circ_2',
        'non_motorist_contact_point', 'non_motorist_distracted_by_1',
        'non_motorist_distracted_by_2', 'non_motorist_ejection_descr',
        'non_motorist_event_sequence_1', 'non_motorist_event_sequence_2',
        'non_motorist_event_sequence_3', 'non_motorist_event_sequence_4',
        'non_motorist_driver_lic_state', 'non_motorist_primary_injury',
        'non_motorist_seating_position', 'non_motorist_traffic_control',
        'non_motorist_trapped_descr', 'non_motorist_origin_dest',
        'non_mtrst_test_type_descr', 'non_mtrst_test_status_descr',
        'non_mtrst_test_result_descr', 'crash_date_text_raw', 'crash_time_2_raw',
        'objectid_source',
    ];

    public static function getHumanName(): string
    {
        return 'Massachusetts Person-Level Crash Data';
    }

    public static function getIconClass(): string
    {
        return 'crash-div-icon'; // Example, update with your actual CSS class
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Car Crash'; // Unique type for styling
    }

    public static function getLatitudeField(): string
    {
        return 'lat';
    }

    public static function getLongitudeField(): string
    {
        return 'lon';
    }

    public static function getDateField(): string
    {
        return 'crash_datetime';
    }

    public function getDate(): ?string
    {
        return $this->crash_datetime ? Carbon::parse($this->crash_datetime)->toDateString() : null;
    }

    public static function getExternalIdName(): string
    {
        // Using crash_numb as it's a primary identifier for a crash event.
        // objectid_source is also a good candidate if it's more consistently unique or used elsewhere.
        return 'crash_numb';
    }

    public function getExternalId(): string
    {
        return (string)$this->crash_numb; // Ensure it's a string
    }

    public static function getPopupConfig(): array
    {
        return [
            'mainIdentifierLabel' => 'Crash Number',
            'mainIdentifierField' => 'crash_numb',
            'descriptionLabel' => 'Severity',
            'descriptionField' => 'crash_severity_descr',
            'additionalFields' => [
                ['label' => 'Date/Time', 'key' => 'crash_datetime', 'format' => 'datetime'],
                ['label' => 'City', 'key' => 'city_town_name'],
                ['label' => 'Manner of Collision', 'key' => 'manr_coll_descr'],
                ['label' => 'Fatalities', 'key' => 'numb_fatal_injr'],
                ['label' => 'Non-Fatal Injuries', 'key' => 'numb_nonfatal_injr'],
                ['label' => 'First Harmful Event', 'key' => 'first_hrmf_event_descr'],
                ['label' => 'Roadway', 'key' => 'rdwy'],
                ['label' => 'Street Name', 'key' => 'streetname'],
            ],
        ];
    }

    public static function getFieldLabels(): array
    {
        // This mapping is based on the provided PDF's "Display name" column for attributes.
        // It's extensive and requires careful transcription from all pages of the PDF.
        // Prioritizing fields that are likely to be displayed or used in UI.
        return [
            // From Page 1-2 (Summary area and start of Attributes)
            'crash_numb' => 'Crash Number', // Implied, not explicitly in PDF attributes list
            'city_town_name' => 'City Town Name',
            'crash_date_text' => 'Crash Date Text', // Raw
            'crash_time_2' => 'Crash Time', // Raw
            'crash_datetime' => 'Crash Date/Time', // From Page 3
            'crash_hour' => 'Crash Hour', // From Page 3
            'crash_status' => 'Crash Status', // From Page 3
            'crash_severity_descr' => 'Crash Severity', // From Page 4
            'max_injr_svrty_cl' => 'Max Injury Severity Reported', // From Page 4
            'numb_vehc' => 'Number of Vehicles', // From Page 5
            'numb_nonfatal_injr' => 'Total NonFatal Injuries', // From Page 5
            'numb_fatal_injr' => 'Total Fatal Injuries', // From Page 6
            'polc_agncy_type_descr' => 'Police Agency Type', // From Page 6
            'manr_coll_descr' => 'Manner of Collision', // From Page 7
            'vehc_mnvr_actn_cl' => 'Vehicle Actions Prior to Crash (All Vehicles)', // From Page 7
            'vehc_trvl_dirc_cl' => 'Vehicle Travel Direction (All Vehicles)', // From Page 8
            'vehc_seq_events_cl' => 'Vehicle Sequence of Events (All Vehicles)', // From Page 8
            'ambnt_light_descr' => 'Light Condition', // From Page 8
            'weath_cond_descr' => 'Weather Condition', // From Page 9
            'road_surf_cond_descr' => 'Road Surface Condition', // From Page 9
            'first_hrmf_event_descr' => 'First Harmful Event', // From Page 10
            'most_hrmfl_evt_cl' => 'Most Harmful Event (All Vehicles)', // From Page 10
            'drvr_cntrb_circ_cl' => 'Driver Contributing Circumstances (All Drivers)', // From Page 10
            'vehc_config_cl' => 'Vehicle Configuration (All Vehicles)', // From Page 10
            'street_numb' => 'Street Number', // From Page 11
            'rdwy' => 'Roadway', // From Page 11
            'dist_dirc_from_int' => 'Distance and Direction from Intersection', // From Page 11
            'near_int_rdwy' => 'Near Intersection Roadway', // From Page 11
            'mm_rte' => 'Milemarker Route', // From Page 12
            'dist_dirc_milemarker' => 'Distance and Direction from Milemarker', // From Page 12
            'milemarker' => 'Milemarker', // From Page 12
            'exit_rte' => 'Exit Route', // From Page 12
            'dist_dirc_exit' => 'Distance and Direction from Exit', // From Page 13
            'exit_numb' => 'Exit Number', // From Page 13
            'dist_dirc_landmark' => 'Distance and Direction from Landmark', // From Page 13
            'landmark' => 'Landmark', // From Page 13 (Display name simply "Landmark")
            'rdwy_jnct_type_descr' => 'Roadway Junction Type', // From Page 14
            'traf_cntrl_devc_type_descr' => 'Traffic Control Device Type', // From Page 14 (Display name, not the field TRAFY_...)
            'trafy_descr_descr' => 'Trafficway Description', // From Page 15
            'jurisdictn' => 'Jurisdiction-linked RD', // From Page 16
            'first_hrmf_event_loc_descr' => 'First Harmful Event Location', // From Page 16 (Display name)
            'non_mtrst_type_cl' => 'Vulnerable User Type (All Persons)', // From Page 17
            'non_mtrst_actn_cl' => 'Vulnerable User Action (All Persons)', // From Page 17
            'non_mtrst_loc_cl' => 'Vulnerable User Location (All Persons)', // From Page 18 (Display name)
            'is_geocoded_status' => 'Is Geocoded', // From Page 18 (Original was IS_GEOCODED)
            'geocoding_method_name' => 'Geocoding Method', // From Page 19
            'x_coord' => 'X (NAD 1983 StatePlane Massachusetts Mainland Meters)', // From Page 19
            'y_coord' => 'Y (NAD 1983 StatePlane Massachusetts Mainland Meters)', // From Page 19
            'lat' => 'Latitude', // From Page 20
            'lon' => 'Longitude', // From Page 20
            'rmv_doc_ids' => 'Document IDs', // From Page 20
            'crash_rpt_ids' => 'Crash Report IDs', // From Page 21
            'year' => 'Year', // From Page 21
            'age_drvr_yngst' => 'Age of Driver - Youngest Known', // From Page 21
            'age_drvr_oldest' => 'Age of Driver - Oldest Known', // From Page 22
            'age_nonmtrst_yngst' => 'Age of Vulnerable User - Youngest Known', // From Page 23
            'age_nonmtrst_oldest' => 'Age of Vulnerable User - Oldest Known', // From Page 23
            'drvr_distracted_cl' => 'Driver Distracted By (All Drivers)', // From Page 24
            'district_num' => 'District', // From Page 24
            'rpa_abbr' => 'RPA', // From Page 25 (RPA_ABBR is name, "RPA" is display name)
            'vehc_emer_use_cl' => 'Vehicle Emergency Use (All Vehicles)', // From Page 25
            'vehc_towed_from_scene_cl' => 'Vehicle Towed From Scene (All Vehicles)', // From Page 26
            'cnty_name' => 'County Name', // From Page 26
            'fmsca_rptbl_cl' => 'FMCSA Reportable (All Vehicles)', // From Page 26 (Display name)
            'fmsca_rptbl' => 'FMCSA Reportable (Crash)', // From Page 27
            'hit_run_descr' => 'Hit and Run', // From Page 27
            'lclty_name' => 'Locality', // From Page 28
            'road_cntrb_descr' => 'Road Contributing Circumstance', // From Page 29
            'schl_bus_reld_descr' => 'School Bus Related', // From Page 29
            'speed_limit' => 'Speed Limit', // From Page 30 (Crash level speed limit)
            'traf_cntrl_devc_func_descr' => 'Traffic Control Device Functioning', // From Page 30
            'work_zone_reld_descr' => 'Work Zone Related', // From Page 31
            'aadt' => 'AADT-linked RD', // From Page 31 (Display name. AADT is name)
            'aadt_year' => 'AADT Year-linked RD', // From Page 32
            'pk_pct_sut' => 'Peak % Single Unit Trucks-linked RD', // From Page 32
            'av_pct_sut' => 'Average Daily % Single Unit Trucks-linked RD', // From Page 33
            'pk_pct_ct' => 'Peak % Combo Trucks-linked RD', // From Page 33
            'av_pct_ct' => 'Average Daily % Combo Trucks-linked RD', // From Page 33
            'curb' => 'Curb-linked RD', // From Page 33
            'truck_rte' => 'Truck Route-linked RD', // From Page 34
            'lt_sidewlk' => 'Left Sidewalk Width-linked RD', // From Page 35
            'rt_sidewlk' => 'Right Sidewalk Width-linked RD', // From Page 35
            'shldr_lt_w' => 'Left Shoulder Width-linked RD', // From Page 35
            'shldr_lt_t' => 'Left Shoulder Type-linked RD', // From Page 36
            'surface_wd' => 'Surface Width-linked RD', // From Page 37
            'surface_tp' => 'Surface Type-linked RD', // From Page 37
            'shldr_rt_w' => 'Right Shoulder Width-linked RD', // From Page 38
            'shldr_rt_t' => 'Right Shoulder Type-linked RD', // From Page 38
            'num_lanes' => 'Number of Travel Lanes-linked RD', // From Page 38
            'opp_lanes' => 'Number of Opposing Travel Lanes-linked RD', // From Page 39
            'med_width' => 'Median Width-linked RD', // From Page 40
            'med_type' => 'Median Type-linked RD', // From Page 40
            'urban_type' => 'Urban Type-linked RD', // From Page 41
            'f_class' => 'Functional Classification-linked RD', // From Page 41
            'urban_area' => 'Urbanized Area-linked RD', // From Page 42
            'fd_aid_rte' => 'Federal Aid Route-linked RD', // Implied, not explicitly in PDF attribute list display name
            'facility' => 'Facility Type-linked RD', // From Page 42
            'operation' => 'Street Operation-linked RD', // From Page 43
            'control' => 'Access Control-linked RD', // From Page 44
            'peak_lane' => 'Number of Peak Hour Lanes-linked RD', // From Page 44
            'speed_lim' => 'Speed Limit-linked RD', // From Page 45
            'streetname' => 'Street Name-linked RD', // From Page 46
            'fromstreetname' => 'From Street Name-linked RD', // From Page 46
            'tostreetname' => 'To Street Name-linked RD', // From Page 46
            'city' => 'City-linked RD', // From Page 47
            'struct_cnd' => 'Structural Condition-linked RD', // From Page 47
            'terrain' => 'Terrain-linked RD', // From Page 48
            'urban_loc_type' => 'Urban Location Type-linked RD', // Implied, not explicitly in PDF attribute list display name
            'aadt_deriv' => 'AADT Derivation-linked RD', // From Page 48
            'statn_num' => 'AADT Station Number-linked RD', // From Page 49
            'op_dir_sl' => 'Opposing Direction Speed Limit-linked RD', // From Page 49
            'shldr_ul_t' => 'Undivided Left Shoulder Type-linked RD', // From Page 50
            'shldr_ul_w' => 'Undivided Left Shoulder Width-linked RD', // From Page 50
            't_exc_type' => 'Truck Exclusion Type-linked RD', // From Page 51
            't_exc_time' => 'Truck Exclusion Time-linked RD', // From Page 52
            'f_f_class' => 'Federal Functional Classification-linked RD', // From Page 52

            // Vehicle Unit Specific (Starts around Page 53)
            'vehc_unit_numb' => 'Vehicle Unit Number',
            'alc_suspd_type_descr' => 'Alcohol Suspected', // (for specific vehicle/driver)
            'driver_age' => 'Driver Age', // (specific driver)
            'drvr_cntrb_circ_descr' => 'Driver Contributing Circ.', // (specific driver)
            'driver_distracted_type_descr' => 'Driver Distracted', // (specific driver)
            'drvr_lcn_state' => 'Driver License State',
            'drug_suspd_type_descr' => 'Drugs Suspected', // (specific vehicle/driver)
            'emergency_use_desc' => 'Emergency Use', // (specific vehicle)
            'fmsca_rptbl_vl' => 'FMCSA Reportable (Vehicle)',
            'haz_mat_placard_descr' => 'Hazmat Placard',
            'max_injr_svrty_vl' => 'Maximum Injury Severity in Vehicle',
            'most_hrmf_event' => 'Most Harmful Event (Vehicle)', // From Page 59
            'total_occpt_in_vehc' => 'Total Occupants in Vehicle',
            'vehc_manr_act_descr' => 'Vehicle Action Prior to Crash', // (specific vehicle) From Page 59
            'vehc_confg_descr' => 'Vehicle Configuration', // (specific vehicle) From Page 60
            'vehc_most_dmgd_area' => 'Vehicle Most Damaged Area', // From Page 60
            'owner_addr_city_town' => 'Vehicle Owner City Town', // From Page 61
            'owner_addr_state' => 'Vehicle Owner State', // From Page 61
            'vehc_reg_state' => 'Vehicle Registration State', // From Page 61
            'vehc_reg_type_code' => 'Vehicle Registration Type', // From Page 61
            'vehc_seq_events' => 'Vehicle Sequence of Events', // (specific vehicle) From Page 62
            'vehc_towed_from_scene' => 'Vehicle Towed From Scene', // (specific vehicle) From Page 62
            'trvl_dirc_descr' => 'Travel Direction', // (specific vehicle) From Page 63
            'vehicle_make_descr' => 'Vehicle Make', // From Page 72
            'vehicle_model_descr' => 'Vehicle Model', // From Page 72
            'vehicle_vin' => 'VIN', // From Page 72
            'driver_violation_cl' => 'Driver Violation (All Vehicles)', // From Page 73 (though this is specific to this driver context)

            // Person Specific Details (Starts around Page 63)
            'pers_numb' => 'Person Number',
            'age' => 'Age', // (specific person)
            'ejctn_descr' => 'Ejection Description', // From Page 64
            'injy_stat_descr' => 'Injury Type', // From Page 64 (PDF uses INJY_TYPE_DESCR as name, but INJY_STAT_DESCR in CSV)
            'med_facly' => 'Medical Facility', // From Page 65
            'pers_addr_city' => 'Person Address City', // From Page 68
            'state_prvn_code' => 'Person Address State', // From Page 68
            'pers_type' => 'Person Type', // From Page 69
            'prtc_sys_use_descr' => 'Protective System Used', // From Page 69
            'sfty_equp_desc_1' => 'Safety Equipment 1', // From Page 70
            'sfty_equp_desc_2' => 'Safety Equipment 2', // (Not in PDF attribute list display name)
            'sex_descr' => 'Sex', // From Page 71
            'trnsd_by_descr' => 'Transported By', // From Page 71
            
            // Non-Motorist Person Specific Details (Many from page 65 onwards, check PDF carefully)
            'non_mtrst_act_descr' => 'Vulnerable User Action', // (specific person) From Page 65
            'non_mtrst_cond_descr' => 'Vulnerable User Condition', // From Page 66
            'non_mtrst_loc_descr' => 'Vulnerable User Location', // From Page 67
            'non_mtrst_type_descr' => 'Vulnerable User Type', // (specific person) From Page 67
            'non_mtrst_origin_dest_cl' => 'Vulnerable Users Origin Destination (All Persons)', // From Page 73
            'non_mtrst_cntrb_circ_cl' => 'Vulnerable Users Contributing Circumstance (All Persons)', // From Page 73
            'non_mtrst_distracted_by_cl' => 'Vulnerable Users Distracted By (All Persons)', // From Page 73
            'non_mtrst_alc_suspd_type_cl' => 'Vulnerable Users Alcohol Suspected Type (All Persons)', // From Page 74
            'non_mtrst_drug_suspd_type_cl' => 'Vulnerable Users Drug Suspected Type (All Persons)', // From Page 74
            'non_mtrst_event_seq_cl' => 'Vulnerable Users Sequence of Events (All Persons)', // From Page 75
            'traffic_control_type_descr' => 'Vulnerable Users Traffic Control Device Type (All Persons)', // From Page 75 (CSV Name, PDF has "Vulnerable Users Traffic Control Device Type")
            'non_motorist_cntrb_circ_1' => 'Vulnerable User Contribution 1', // From Page 76
            'non_motorist_cntrb_circ_2' => 'Vulnerable User Contribution 2', // From Page 76
            'non_motorist_contact_point' => 'Vulnerable User Contact Point', // From Page 77
            'non_motorist_distracted_by_1' => 'Vulnerable User Distracted By 1', // From Page 78
            'non_motorist_distracted_by_2' => 'Vulnerable User Distracted By 2', // From Page 78
            'non_motorist_ejection_descr' => 'Vulnerable User Ejection', // From Page 79
            'non_motorist_event_sequence_1' => 'Vulnerable User Event Sequence 1', // From Page 80
            'non_motorist_event_sequence_2' => 'Vulnerable User Event Sequence 2', // From Page 80
            'non_motorist_event_sequence_3' => 'Vulnerable User Event Sequence 3', // From Page 80
            'non_motorist_event_sequence_4' => 'Vulnerable User Event Sequence 4', // From Page 81
            'non_motorist_driver_lic_state' => 'Vulnerable User Driver License State Province', // From Page 81
            'non_motorist_primary_injury' => 'Vulnerable User Primary Injury Area', // From Page 82
            'non_motorist_seating_position' => 'Vulnerable User Seating Position', // From Page 82
            'non_motorist_traffic_control' => 'Vulnerable User Traffic Control Type', // From Page 83 (PDF display name)
            'non_motorist_trapped_descr' => 'Vulnerable User Trapped', // From Page 84
            'non_motorist_origin_dest' => 'Vulnerable User Origin Destination', // From Page 84 (PDF display name)
            'non_mtrst_test_type_descr' => 'Vulnerable User Test Type', // From Page 85
            'non_mtrst_test_status_descr' => 'Vulnerable User Test Status', // From Page 86
            'non_mtrst_test_result_descr' => 'Vulnerable User Test Result', // From Page 86
            
            // Raw/Source fields
            'crash_date_text_raw' => 'Crash Date (Raw Text)',
            'crash_time_2_raw' => 'Crash Time (Raw Text)',
            'objectid_source' => 'Source OBJECTID',
        ];
    }

}
