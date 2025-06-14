<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonCrashDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('person_crash_data', function (Blueprint $table) {
            $table->id(); 

            // Crash Identifiers & Basic Info
            $table->bigInteger('crash_numb')->index()->comment('Crash Number Identifier from source');
            $table->string('city_town_name')->nullable()->index(); // Keep as varchar, likely queried
            $table->dateTime('crash_datetime')->nullable()->index();
            $table->tinyInteger('crash_hour')->unsigned()->nullable()->comment('Hour of the crash (0-23), parsed from CRASH_HOUR');
            $table->string('crash_status')->nullable(); // Short, keep varchar
            $table->string('crash_severity_descr')->nullable()->index(); // Short, keep varchar
            $table->string('max_injr_svrty_cl')->nullable()->comment('Max Injury Severity Classification for the crash'); // Short, keep varchar
            $table->integer('numb_vehc')->nullable();
            $table->integer('numb_nonfatal_injr')->nullable();
            $table->integer('numb_fatal_injr')->nullable();
            $table->string('polc_agncy_type_descr')->nullable(); // Short, keep varchar
            $table->year('year')->nullable()->index();

            // Crash Characteristics
            $table->string('manr_coll_descr')->nullable(); // Short, keep varchar
            $table->text('vehc_mnvr_actn_cl')->nullable();
            $table->text('vehc_trvl_dirc_cl')->nullable(); // Changed from varchar(500)
            $table->text('vehc_seq_events_cl')->nullable();
            $table->string('ambnt_light_descr')->nullable(); // Short, keep varchar
            $table->text('weath_cond_descr')->nullable(); // Changed from varchar(500)
            $table->string('road_surf_cond_descr')->nullable(); // Short, keep varchar
            $table->text('first_hrmf_event_descr')->nullable(); // Can be descriptive
            $table->text('most_hrmfl_evt_cl')->nullable();
            $table->text('drvr_cntrb_circ_cl')->nullable();
            $table->text('vehc_config_cl')->nullable();
            
            // Location Details
            $table->text('street_numb')->nullable(); // Can be descriptive
            $table->text('rdwy')->nullable();
            $table->text('dist_dirc_from_int')->nullable();
            $table->text('near_int_rdwy')->nullable();
            $table->text('mm_rte')->nullable();
            $table->text('dist_dirc_milemarker')->nullable();
            $table->decimal('milemarker', 8, 3)->nullable();
            $table->text('exit_rte')->nullable();
            $table->text('dist_dirc_exit')->nullable();
            $table->text('exit_numb')->nullable(); // Can be descriptive like "23A"
            $table->text('dist_dirc_landmark')->nullable();
            $table->text('landmark')->nullable();
            $table->string('rdwy_jnct_type_descr')->nullable(); // Short, keep varchar
            $table->text('traf_cntrl_devc_type_descr')->nullable();
            $table->text('trafy_descr_descr')->nullable();
            $table->text('jurisdictn')->nullable();
            $table->text('first_hrmf_event_loc_descr')->nullable();
            $table->string('is_geocoded_status')->nullable(); // Short, keep varchar
            $table->text('geocoding_method_name')->nullable();
            $table->decimal('x_coord', 12, 4)->nullable();
            $table->decimal('y_coord', 12, 4)->nullable();
            $table->decimal('lat', 10, 7)->nullable()->index();
            $table->decimal('lon', 10, 7)->nullable()->index();
            $table->text('rmv_doc_ids')->nullable();
            $table->text('crash_rpt_ids')->nullable();

            // Age Summaries
            $table->string('age_drvr_yngst', 50)->nullable(); // Keep short varchar for ranges
            $table->string('age_drvr_oldest', 50)->nullable();
            $table->string('age_nonmtrst_yngst', 50)->nullable();
            $table->string('age_nonmtrst_oldest', 50)->nullable();

            // Distraction & Admin
            $table->text('drvr_distracted_cl')->nullable();
            $table->tinyInteger('district_num')->unsigned()->nullable();
            $table->string('rpa_abbr')->nullable(); // Short, keep varchar
            $table->text('vehc_emer_use_cl')->nullable();
            $table->text('vehc_towed_from_scene_cl')->nullable();
            $table->string('cnty_name')->nullable()->index(); // Keep as varchar, likely queried
            $table->text('fmsca_rptbl_cl')->nullable();
            $table->boolean('fmsca_rptbl')->nullable();
            $table->boolean('hit_run_descr')->nullable();
            $table->text('lclty_name')->nullable(); // Was mostly null, but could be longer
            $table->text('road_cntrb_descr')->nullable();
            $table->boolean('schl_bus_reld_descr')->nullable();
            $table->smallInteger('speed_limit')->unsigned()->nullable();
            $table->text('traf_cntrl_devc_func_descr')->nullable();
            $table->boolean('work_zone_reld_descr')->nullable();

            // Roadway Characteristics
            $table->integer('aadt')->unsigned()->nullable();
            $table->smallInteger('aadt_year')->unsigned()->nullable();
            $table->decimal('pk_pct_sut', 5, 3)->nullable();
            $table->decimal('av_pct_sut', 8, 3)->nullable();
            $table->decimal('pk_pct_ct', 5, 3)->nullable();
            $table->decimal('av_pct_ct', 8, 3)->nullable();
            $table->string('curb')->nullable(); // Short, keep varchar
            $table->text('truck_rte')->nullable();
            $table->decimal('lt_sidewlk', 5, 1)->nullable();
            $table->decimal('rt_sidewlk', 5, 1)->nullable();
            $table->decimal('shldr_lt_w', 5, 1)->nullable();
            $table->text('shldr_lt_t')->nullable();
            $table->decimal('surface_wd', 5, 1)->nullable();
            $table->text('surface_tp')->nullable(); // Can have '0' and descriptive text
            $table->decimal('shldr_rt_w', 5, 1)->nullable();
            $table->text('shldr_rt_t')->nullable();
            $table->tinyInteger('num_lanes')->unsigned()->nullable();
            $table->tinyInteger('opp_lanes')->unsigned()->nullable();
            $table->decimal('med_width', 5, 1)->nullable();
            $table->string('med_type')->nullable(); // Short, keep varchar
            $table->string('urban_type')->nullable(); // Short, keep varchar
            $table->text('f_class')->nullable(); // Functional Class can be descriptive
            $table->text('urban_area')->nullable();
            $table->text('fd_aid_rte')->nullable();
            $table->text('facility')->nullable();
            $table->string('operation')->nullable(); // Short, keep varchar
            $table->string('control')->nullable(); // Short, keep varchar
            $table->tinyInteger('peak_lane')->unsigned()->nullable();
            $table->smallInteger('speed_lim')->unsigned()->nullable();
            $table->text('streetname')->nullable();
            $table->text('fromstreetname')->nullable();
            $table->text('tostreetname')->nullable();
            $table->string('city')->nullable()->index(); // Keep as varchar, likely queried
            $table->string('struct_cnd')->nullable(); // Short, keep varchar
            $table->string('terrain')->nullable(); // Short, keep varchar
            $table->text('urban_loc_type')->nullable();
            $table->text('aadt_deriv')->nullable();
            $table->integer('statn_num')->nullable();
            $table->smallInteger('op_dir_sl')->unsigned()->nullable();
            $table->text('shldr_ul_t')->nullable();
            $table->decimal('shldr_ul_w', 5, 1)->nullable();
            $table->text('t_exc_type')->nullable();
            $table->text('t_exc_time')->nullable();
            $table->text('f_f_class')->nullable(); // Federal Functional Class

            // Vehicle Unit Specific
            $table->tinyInteger('vehc_unit_numb')->unsigned()->nullable()->index();
            $table->boolean('alc_suspd_type_descr')->nullable();
            $table->smallInteger('driver_age')->unsigned()->nullable();
            $table->text('drvr_cntrb_circ_descr')->nullable(); // Changed from varchar(1000)
            $table->text('driver_distracted_type_descr')->nullable();
            $table->string('drvr_lcn_state', 10)->nullable();
            $table->boolean('drug_suspd_type_descr')->nullable();
            $table->boolean('emergency_use_desc')->nullable();
            $table->boolean('fmsca_rptbl_vl')->nullable();
            $table->boolean('haz_mat_placard_descr')->nullable();
            $table->string('max_injr_svrty_vl')->nullable(); // Short, keep varchar
            $table->text('most_hrmf_event')->nullable();
            $table->smallInteger('total_occpt_in_vehc')->unsigned()->nullable();
            $table->text('vehc_manr_act_descr')->nullable();
            $table->text('vehc_confg_descr')->nullable();
            $table->text('vehc_most_dmgd_area')->nullable();
            $table->text('owner_addr_city_town')->nullable();
            $table->string('owner_addr_state', 10)->nullable();
            $table->string('vehc_reg_state', 10)->nullable();
            $table->string('vehc_reg_type_code', 20)->nullable();
            $table->text('vehc_seq_events')->nullable();
            $table->string('vehc_towed_from_scene')->nullable(); // Short, keep varchar
            $table->string('trvl_dirc_descr')->nullable(); // Short, keep varchar
            $table->text('vehicle_make_descr')->nullable();
            $table->text('vehicle_model_descr')->nullable();
            $table->string('vehicle_vin', 50)->nullable()->index();
            $table->text('driver_violation_cl')->nullable();

            // Person Specific Details
            $table->integer('pers_numb')->unsigned()->nullable();
            $table->smallInteger('age')->unsigned()->nullable();
            $table->string('ejctn_descr')->nullable(); // Short, keep varchar
            $table->string('injy_stat_descr')->nullable(); // Short, keep varchar
            $table->text('med_facly')->nullable();
            $table->text('pers_addr_city')->nullable();
            $table->string('state_prvn_code', 10)->nullable();
            $table->string('pers_type')->nullable(); // Short, keep varchar
            $table->text('prtc_sys_use_descr')->nullable();
            $table->text('sfty_equp_desc_1')->nullable();
            $table->text('sfty_equp_desc_2')->nullable();
            $table->string('sex_descr', 20)->nullable();
            $table->string('trnsd_by_descr')->nullable(); // Short, keep varchar
            
            // Non-Motorist Specific Details
            $table->text('non_mtrst_type_cl')->nullable();
            $table->text('non_mtrst_actn_cl')->nullable();
            $table->text('non_mtrst_loc_cl')->nullable();
            $table->text('non_mtrst_act_descr')->nullable();
            $table->text('non_mtrst_cond_descr')->nullable();
            $table->text('non_mtrst_loc_descr')->nullable();
            $table->text('non_mtrst_type_descr')->nullable();
            $table->text('non_mtrst_origin_dest_cl')->nullable();
            $table->text('non_mtrst_cntrb_circ_cl')->nullable();
            $table->text('non_mtrst_distracted_by_cl')->nullable();
            $table->text('non_mtrst_alc_suspd_type_cl')->nullable();
            $table->text('non_mtrst_drug_suspd_type_cl')->nullable();
            $table->text('non_mtrst_event_seq_cl')->nullable();
            $table->text('traffic_control_type_descr')->nullable();
            $table->text('non_motorist_cntrb_circ_1')->nullable();
            $table->text('non_motorist_cntrb_circ_2')->nullable();
            $table->string('non_motorist_contact_point')->nullable(); // Short, keep varchar
            $table->text('non_motorist_distracted_by_1')->nullable();
            $table->text('non_motorist_distracted_by_2')->nullable();
            $table->string('non_motorist_ejection_descr')->nullable(); // Short, keep varchar
            $table->text('non_motorist_event_sequence_1')->nullable();
            $table->text('non_motorist_event_sequence_2')->nullable();
            $table->text('non_motorist_event_sequence_3')->nullable();
            $table->text('non_motorist_event_sequence_4')->nullable();
            $table->string('non_motorist_driver_lic_state', 10)->nullable();
            $table->text('non_motorist_primary_injury')->nullable();
            $table->text('non_motorist_seating_position')->nullable();
            $table->text('non_motorist_traffic_control')->nullable();
            $table->string('non_motorist_trapped_descr')->nullable(); // Short, keep varchar
            $table->text('non_motorist_origin_dest')->nullable();
            $table->text('non_mtrst_test_type_descr')->nullable();
            $table->text('non_mtrst_test_status_descr')->nullable();
            $table->text('non_mtrst_test_result_descr')->nullable();
            
            $table->string('crash_date_text_raw')->nullable();
            $table->string('crash_time_2_raw')->nullable();
            $table->bigInteger('objectid_source')->nullable()->index()->comment('Source OBJECTID, crash-level identifier');

            $table->index(['crash_numb', 'pers_numb', 'vehc_unit_numb'], 'crash_person_vehicle_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('person_crash_data');
    }
}