<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // For logging errors
use Carbon\Carbon;

class PersonCrashDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $directoryPath = storage_path('app/datasets/massachusetts/');
        $chunkSize = 250; // Adjust based on memory and performance
        $tableName = 'person_crash_data';

        if (!is_dir($directoryPath)) {
            $this->command->error("Directory not found: {$directoryPath}");
            return;
        }

        $files = glob($directoryPath . '*_Person_Level_Crash_Details.csv');
        if (empty($files)) {
            $this->command->warn("No CSV files found in directory: {$directoryPath}");
            return;
        }

        $this->command->info("Found " . count($files) . " file(s) to process.");

        foreach ($files as $csvPath) {
            $this->command->info("Processing file: {$csvPath}");

            if (!file_exists($csvPath) || !is_readable($csvPath)) {
                $this->command->error("CSV file not found or not readable: {$csvPath}");
                continue;
            }

            $fileHandle = fopen($csvPath, 'r');
            if ($fileHandle === false) {
                $this->command->error("Failed to open CSV file: {$csvPath}");
                continue;
            }

            $header = fgetcsv($fileHandle);
            if ($header === false) {
                $this->command->error("Failed to read header from CSV file: {$csvPath}");
                fclose($fileHandle);
                continue;
            }

            // Normalize header for consistency (lowercase, underscore)
            $dbColumns = array_map(function ($col) {
                if (strpos($col, "\xEF\xBB\xBF") === 0) {
                    $col = substr($col, 3);
                }
                return strtolower(str_replace(' ', '_', $col));
            }, $header);

            $dataToInsert = [];
            $rowCount = 0;
            $totalUpserted = 0;
            $skippedRowCount = 0;
            $recordsProcessedSinceLastMessage = 0;

            DB::disableQueryLog();

            while (($row = fgetcsv($fileHandle)) !== false) {
                if (count($row) !== count($dbColumns)) {
                    $skippedRowCount++;
                    $rowCount++;
                    continue;
                }

                $record = array_combine($dbColumns, $row);
                $transformedRecord = $this->transformRecord($record);

                $transformedRecord['objectid_source'] = $record['objectid'] ?? null;
                $transformedRecord['crash_date_text_raw'] = $record['crash_date_text'] ?? $record['crash_date'] ?? null;
                $transformedRecord['crash_time_2_raw'] = $record['crash_time_2'] ?? null;

                $dataToInsert[] = $transformedRecord;
                $rowCount++;
                $recordsProcessedSinceLastMessage++;

                if (count($dataToInsert) >= $chunkSize) {
                    try {
                        DB::table($tableName)->upsert(
                            $dataToInsert,
                            ['crash_numb', 'pers_numb', 'vehc_unit_numb'],
                            array_diff(array_keys($dataToInsert[0]), ['crash_numb', 'pers_numb', 'vehc_unit_numb'])
                        );
                        $totalUpserted += count($dataToInsert);
                    } catch (\Exception $e) {
                        Log::error("Error upserting chunk: " . $e->getMessage() . " - First record in failing chunk: " . json_encode($dataToInsert[0] ?? []));
                    }
                    $dataToInsert = [];

                    if ($recordsProcessedSinceLastMessage >= 5000) {
                        $this->command->line("Processed approximately {$rowCount} records. Total upserted: {$totalUpserted}.");
                        $recordsProcessedSinceLastMessage = 0;
                    }
                }
            }

            if (!empty($dataToInsert)) {
                try {
                    DB::table($tableName)->upsert(
                        $dataToInsert,
                        ['crash_numb', 'pers_numb', 'vehc_unit_numb'],
                        array_diff(array_keys($dataToInsert[0]), ['crash_numb', 'pers_numb', 'vehc_unit_numb'])
                    );
                    $totalUpserted += count($dataToInsert);
                    $this->command->info("Upserted final chunk of " . count($dataToInsert) . " records. Total: {$totalUpserted}");
                } catch (\Exception $e) {
                    Log::error("Error upserting final chunk: " . $e->getMessage() . " - First record in failing chunk: " . json_encode($dataToInsert[0] ?? []));
                }
            }

            fclose($fileHandle);
            $this->command->info("Finished processing file: {$csvPath}. Total records processed: {$rowCount}, Total records upserted: {$totalUpserted}.");
            if ($skippedRowCount > 0) {
                $this->command->warn("{$skippedRowCount} rows were skipped due to column count mismatch.");
            }
        }

        DB::enableQueryLog();
        $this->command->info("All files processed.");
    }

    private function transformRecord(array $record): array
    {
        $transformed = [];
        foreach ($record as $key => $value) {
            $dbKey = strtolower(str_replace(' ', '_', $key)); // Ensure consistent key naming
            $value = (trim($value) === '' || strtolower(trim($value)) === 'nan') ? null : trim($value);

            switch ($dbKey) {
                // 1. CSV columns handled entirely outside transformRecord (e.g., for _raw or _source fields)
                // These should be skipped here to prevent falling into default.
                case 'objectid':        // Used for 'objectid_source' directly in run()
                case 'crash_date_text': // Used for 'crash_date_text_raw' directly in run()
                case 'crash_date':
                case 'shape':
                case 'crash_time':
                case 'mpo_abbr':
                case 'sptroop':
                case 'crash_time_2':    // Used for 'crash_time_2_raw' directly in run()
                    break;

                // 2. Special parsing or mapping from CSV key to a potentially different DB key
                case 'crash_datetime':
                    $transformed[$dbKey] = $value ? Carbon::createFromFormat('Y/m/d H:i:sP', $value)->toDateTimeString() : null;
                    break;
                case 'crash_hour': // Parsed from values like '12:00:00'
                    $transformed[$dbKey] = $value ? (int)explode(':', $value)[0] : null;
                    break;
                case 'x': // CSV column 'X' maps to DB column 'x_coord'
                    $transformed['x_coord'] = ($value !== null && trim($value) !== '') ? (float)$value : null;
                    break;
                case 'y': // CSV column 'Y' maps to DB column 'y_coord'
                    $transformed['y_coord'] = ($value !== null && trim($value) !== '') ? (float)$value : null;
                    break;
                case 'is_geocoded':        // CSV column 'IS_GEOCODED' maps to DB 'is_geocoded_status'
                case 'is_geocoded_status': // If CSV might also have 'IS_GEOCODED_STATUS'
                    $transformed['is_geocoded_status'] = $value;
                    break;

                // 3. Direct mapping with Integer type conversion
                case 'year':
                case 'aadt_year':
                // case 'speed_limit': // Handled below with specific validation
                // case 'speed_lim': // Handled below with specific validation
                case 'aadt':
                case 'district_num':
                case 'numb_vehc':
                case 'numb_nonfatal_injr':
                case 'numb_fatal_injr':
                case 'vehc_unit_numb':
                case 'driver_age':
                case 'total_occpt_in_vehc':
                case 'pers_numb':
                case 'age':
                case 'statn_num':
                // case 'op_dir_sl': // Handled below if it needs similar validation, for now assume it's fine
                    $transformed[$dbKey] = $value !== null ? (int)$value : null;
                    break;
                
                // Specific handling for potentially problematic integer fields
                case 'speed_limit':
                case 'speed_lim':
                case 'op_dir_sl': // Assuming op_dir_sl might also have invalid non-negative values
                    if ($value !== null && is_numeric($value) && (int)$value >= 0) {
                        $transformed[$dbKey] = (int)$value;
                    } else {
                        $transformed[$dbKey] = null; // Set to null if not a valid non-negative number
                    }
                    break;

                // Handle known integer columns that might be float in CSV due to NaNs
                case 'num_lanes':
                case 'opp_lanes':
                case 'peak_lane':
                    $transformed[$dbKey] = $value !== null ? (int)$value : null;
                    break;

                // 4. Direct mapping with Float type conversion
                // Note: 'x_coord' and 'y_coord' cases here would handle CSVs with literal 'X_COORD'/'Y_COORD' headers.
                // If CSV has 'X'/'Y', they are handled by 'case x:'/'case y:' above.
                case 'pk_pct_sut':
                case 'av_pct_sut':
                case 'pk_pct_ct':
                case 'av_pct_ct':
                case 'lt_sidewlk':
                case 'rt_sidewlk':
                case 'shldr_lt_w':
                case 'shldr_rt_w':
                case 'surface_wd':
                case 'med_width':
                case 'shldr_ul_w':
                case 'milemarker':
                case 'x_coord': // Handles CSV 'X_COORD' if present
                case 'y_coord': // Handles CSV 'Y_COORD' if present
                case 'lat':
                case 'lon':
                    $transformed[$dbKey] = $value !== null ? (float)$value : null;
                    break;

                // 5. Direct mapping with Boolean type conversion
                case 'fmsca_rptbl':
                case 'fmsca_rptbl_vl':
                    $transformed[$dbKey] = ($value !== null && str_contains(strtolower($value), 'yes')) ? true : (($value !== null) ? false : null);
                    break;
                case 'hit_run_descr':
                    // Assuming "Yes, hit and run" or similar for true, "No hit and run" or other for false.
                    // The sample data has "No hit and run" and "Yes, hit and run".
                    // The migration has this as boolean.
                    if ($value !== null) {
                        $lowerVal = strtolower($value);
                        if (str_contains($lowerVal, 'yes')) $transformed[$dbKey] = true;
                        elseif (str_contains($lowerVal, 'no')) $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null; // Or handle as needed
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                case 'schl_bus_reld_descr':
                     // Sample data: "No, school bus not involved"
                    if ($value !== null) {
                        $lowerVal = strtolower($value);
                        if (str_contains($lowerVal, 'yes')) $transformed[$dbKey] = true;
                        elseif (str_contains($lowerVal, 'no') || $lowerVal === 'not reported') $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null;
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                case 'work_zone_reld_descr':
                case 'alc_suspd_type_descr':
                case 'drug_suspd_type_descr':
                case 'emergency_use_desc':
                case 'haz_mat_placard_descr': // "Yes", "No", "Not Applicable", "Not Reported"
                    if ($value !== null) {
                        $lowerVal = strtolower($value);
                        if ($lowerVal === 'yes') $transformed[$dbKey] = true;
                        elseif ($lowerVal === 'no') $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null; // For "Unknown", "Not Reported", "Not Applicable"
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                
                // 6. Default: direct mapping, ensure value is string or null
                default:
                    $transformed[$dbKey] = $value !== null ? (string)$value : null;
                    break;
            }
        }
        return $transformed;
    }
}