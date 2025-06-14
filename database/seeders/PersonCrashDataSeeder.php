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

        // Helper function to clean and check for null/empty/NaN string values
        $cleanVal = function($val) {
            if ($val === null) return null;
            $trimmedVal = trim((string)$val); // Ensure it's a string before trimming
            return ($trimmedVal === '' || strtolower($trimmedVal) === 'nan') ? null : $trimmedVal;
        };

        // --- Start of crash_datetime and crash_hour transformation ---
        $parsedDateTime = null;

        $csvCrashDatetime = $cleanVal($record['crash_datetime'] ?? null);
        $csvCrashDate = $cleanVal($record['crash_date'] ?? null);
        $csvCrashTime = $cleanVal($record['crash_time'] ?? null); // integer like 115 or HHMM
        $csvCrashTime2 = $cleanVal($record['crash_time_2'] ?? null); // HH:MM:SS

        // Priority 1: Use 'crash_datetime' field if available
        if ($csvCrashDatetime) {
            try {
                $parsedDateTime = Carbon::createFromFormat('Y/m/d H:i:sP', $csvCrashDatetime);
            } catch (\Exception $e) { /* Try next priority */ }
        }

        // Priority 2: Use 'crash_date' field
        if (!$parsedDateTime && $csvCrashDate) {
            try {
                // Attempt to parse 'crash_date' as a full datetime string first
                $parsedDateTime = Carbon::createFromFormat('Y/m/d H:i:sP', $csvCrashDate);
            } catch (\Exception $e) {
                // If parsing as full datetime fails, assume 'crash_date' is just a date part 'Y/m/d'
                $datePartCarbon = null;
                try {
                    $datePartCarbon = Carbon::createFromFormat('Y/m/d', $csvCrashDate);
                } catch (\Exception $e2) { /* 'crash_date' is not 'Y/m/d' either */ }

                if ($datePartCarbon) {
                    // Combine with 'crash_time_2' (HH:MM:SS) if available
                    if ($csvCrashTime2) {
                        $timeParts = explode(':', $csvCrashTime2);
                        if (count($timeParts) === 3 && is_numeric($timeParts[0]) && is_numeric($timeParts[1]) && is_numeric($timeParts[2])) {
                            $parsedDateTime = $datePartCarbon->copy()->setTime((int)$timeParts[0], (int)$timeParts[1], (int)$timeParts[2]);
                        }
                    }
                    // Else, combine with 'crash_time' (integer format like 115 for 01:15)
                    elseif (!$parsedDateTime && $csvCrashTime && is_numeric($csvCrashTime)) {
                        $timeInt = (int)$csvCrashTime;
                        $hours = floor($timeInt / 100); // e.g., 115 -> 1, 1230 -> 12
                        $minutes = $timeInt % 100;      // e.g., 115 -> 15, 1230 -> 30
                        if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59) {
                            $parsedDateTime = $datePartCarbon->copy()->setTime($hours, $minutes, 0); // Assume 0 seconds
                        }
                    }
                }
            }
        }
        
        $transformed['crash_datetime'] = $parsedDateTime ? $parsedDateTime->toDateTimeString() : null;

        // Derive crash_hour
        if ($parsedDateTime) {
            $transformed['crash_hour'] = $parsedDateTime->hour;
        } else {
            // If crash_datetime couldn't be formed, try to get crash_hour from its own column if it exists
            $csvCrashHour = $cleanVal($record['crash_hour'] ?? null); // Expects format like '12:00:00' or '12'
            if ($csvCrashHour !== null) {
                $timeParts = explode(':', $csvCrashHour);
                if (count($timeParts) > 0 && is_numeric($timeParts[0])) {
                    $hourVal = (int)$timeParts[0];
                    if ($hourVal >=0 && $hourVal <=23) {
                         $transformed['crash_hour'] = $hourVal;
                    } else {
                        $transformed['crash_hour'] = null; // Invalid hour value
                    }
                } else {
                    $transformed['crash_hour'] = null;
                }
            } else {
                $transformed['crash_hour'] = null;
            }
        }

        if (!$transformed['crash_datetime'] && ($csvCrashDatetime || $csvCrashDate)) {
            Log::warning("Could not form a valid crash_datetime from input for record: " . json_encode([
                'original_crash_datetime' => $record['crash_datetime'] ?? null,
                'original_crash_date' => $record['crash_date'] ?? null,
                'original_crash_time' => $record['crash_time'] ?? null,
                'original_crash_time_2' => $record['crash_time_2'] ?? null,
            ]));
        }
        // --- End of crash_datetime and crash_hour transformation ---

        foreach ($record as $key => $value) {
            $dbKey = strtolower(str_replace(' ', '_', $key));
            $currentVal = $cleanVal($value); // Use cleaned value for switch cases

            switch ($dbKey) {
                // 1. CSV columns handled by pre-processing or raw assignment in run()
                case 'objectid':        // Used for 'objectid_source' directly in run()
                case 'crash_date_text': // Used for 'crash_date_text_raw' directly in run()
                // These were used above to construct 'crash_datetime' and 'crash_hour'
                case 'crash_datetime':
                case 'crash_date':
                case 'crash_time':
                case 'crash_time_2':    // Also used for 'crash_time_2_raw' in run()
                case 'crash_hour':      // Processed above
                // Other keys to ignore if they are not meant for direct mapping
                case 'shape':
                case 'mpo_abbr':
                case 'sptroop':
                    break;

                // 2. Special parsing or mapping from CSV key to a potentially different DB key
                // Note: crash_datetime and crash_hour are handled above the loop.
                case 'x': // CSV column 'X' maps to DB column 'x_coord'
                    $transformed['x_coord'] = ($currentVal !== null && trim((string)$currentVal) !== '') ? (float)$currentVal : null;
                    break;
                case 'y': // CSV column 'Y' maps to DB column 'y_coord'
                    $transformed['y_coord'] = ($currentVal !== null && trim((string)$currentVal) !== '') ? (float)$currentVal : null;
                    break;
                case 'is_geocoded':        // CSV column 'IS_GEOCODED' maps to DB 'is_geocoded_status'
                case 'is_geocoded_status': // If CSV might also have 'IS_GEOCODED_STATUS'
                    $transformed['is_geocoded_status'] = $currentVal;
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
                    $transformed[$dbKey] = $currentVal !== null ? (int)$currentVal : null;
                    break;
                
                // Specific handling for potentially problematic integer fields
                case 'speed_limit':
                case 'speed_lim':
                case 'op_dir_sl': // Assuming op_dir_sl might also have invalid non-negative values
                    if ($currentVal !== null && is_numeric($currentVal) && (int)$currentVal >= 0) {
                        $transformed[$dbKey] = (int)$currentVal;
                    } else {
                        $transformed[$dbKey] = null; // Set to null if not a valid non-negative number
                    }
                    break;

                // Handle known integer columns that might be float in CSV due to NaNs
                case 'num_lanes':
                case 'opp_lanes':
                case 'peak_lane':
                    $transformed[$dbKey] = $currentVal !== null ? (int)$currentVal : null;
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
                    $transformed[$dbKey] = $currentVal !== null ? (float)$currentVal : null;
                    break;

                // 5. Direct mapping with Boolean type conversion
                case 'fmsca_rptbl':
                case 'fmsca_rptbl_vl':
                    $transformed[$dbKey] = ($currentVal !== null && str_contains(strtolower($currentVal), 'yes')) ? true : (($currentVal !== null) ? false : null);
                    break;
                case 'hit_run_descr':
                    if ($currentVal !== null) {
                        $lowerVal = strtolower($currentVal);
                        if (str_contains($lowerVal, 'yes')) $transformed[$dbKey] = true;
                        elseif (str_contains($lowerVal, 'no')) $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null; 
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                case 'schl_bus_reld_descr':
                    if ($currentVal !== null) {
                        $lowerVal = strtolower($currentVal);
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
                case 'haz_mat_placard_descr': 
                    if ($currentVal !== null) {
                        $lowerVal = strtolower($currentVal);
                        if ($lowerVal === 'yes') $transformed[$dbKey] = true;
                        elseif ($lowerVal === 'no') $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null; 
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                
                // 6. Default: direct mapping, ensure value is string or null
                default:
                    $transformed[$dbKey] = $currentVal !== null ? (string)$currentVal : null;
                    break;
            }
        }
        return $transformed;
    }
}