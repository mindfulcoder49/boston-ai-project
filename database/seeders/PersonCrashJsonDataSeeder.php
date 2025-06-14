<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage; // For progress file
use Carbon\Carbon;

class PersonCrashJsonDataSeeder extends Seeder
{
    private const PROGRESS_DIR = 'job_progress';
    private const PROGRESS_FILE = self::PROGRESS_DIR . '/PersonCrashJsonDataSeeder_progress.json';

    private function getProgress(): array
    {
        if (!Storage::exists(self::PROGRESS_FILE)) {
            return ['processed_files' => []];
        }
        $content = Storage::get(self::PROGRESS_FILE);
        $progress = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($progress['processed_files'])) {
            return ['processed_files' => []];
        }
        return $progress;
    }

    private function saveProgress(array $progress)
    {
        if (!Storage::exists(self::PROGRESS_DIR)) {
            Storage::makeDirectory(self::PROGRESS_DIR);
        }
        Storage::put(self::PROGRESS_FILE, json_encode($progress, JSON_PRETTY_PRINT));
    }

    private function markFileAsProcessed(string $filePath, array &$progress)
    {
        if (!in_array($filePath, $progress['processed_files'])) {
            $progress['processed_files'][] = $filePath;
            $progress['last_updated'] = now()->toIso8601String();
            $this->saveProgress($progress);
        }
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $basePath = storage_path('app/datasets/massachusetts/geojson');
        $years = ['2022', '2023', '2024', '2025']; // Or scan directories if preferred
        $chunkSize = 250;
        $tableName = 'person_crash_data';
        $overallTotalUpserted = 0;
        $overallTotalProcessed = 0;
        $overallSkippedFilesDueToError = 0;
        $overallSkippedFilesDueToCompletion = 0;

        // Ensure directory for progress file exists
        $storageProgressDir = storage_path('app/' . self::PROGRESS_DIR);
        if (!File::isDirectory($storageProgressDir)) {
            File::makeDirectory($storageProgressDir, 0775, true, true);
        }
        $seederProgress = $this->getProgress();

        DB::disableQueryLog();

        $allFilesToProcess = [];
        foreach ($years as $year) {
            $yearPath = $basePath . '/' . $year;
            if (!File::isDirectory($yearPath)) {
                $this->command->warn("Directory not found for year {$year}: {$yearPath}");
                continue;
            }
            $jsonFiles = File::glob($yearPath . '/crash_data_*.geojson');
            if (!empty($jsonFiles)) {
                $allFilesToProcess = array_merge($allFilesToProcess, $jsonFiles);
            } else {
                 $this->command->info("No GeoJSON files found for year {$year} in {$yearPath}.");
            }
        }
        
        if (empty($allFilesToProcess)) {
            $this->command->info("No GeoJSON files found to process in any year directory.");
            DB::enableQueryLog();
            return;
        }

        foreach ($allFilesToProcess as $jsonFilePath) {
            if (in_array($jsonFilePath, $seederProgress['processed_files'])) {
                $this->command->line("Skipping already processed file: {$jsonFilePath}");
                $overallSkippedFilesDueToCompletion++;
                continue;
            }

            $this->command->line("Processing GeoJSON file: {$jsonFilePath}");
            
            // Check if file is readable before attempting to get contents
            if (!File::isReadable($jsonFilePath)) {
                $this->command->error("File is not readable: {$jsonFilePath}. Skipping.");
                Log::error("File not readable during seeding: {$jsonFilePath}");
                $overallSkippedFilesDueToError++;
                continue;
            }
            
            $fileContents = File::get($jsonFilePath); // This can consume memory for large files
            $data = json_decode($fileContents, true);
            unset($fileContents); // Free memory

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->command->error("Error decoding JSON from file {$jsonFilePath}: " . json_last_error_msg());
                Log::error("JSON decode error for file {$jsonFilePath}: " . json_last_error_msg());
                $overallSkippedFilesDueToError++;
                continue;
            }

            if (!isset($data['features']) || !is_array($data['features'])) {
                $this->command->warn("No 'features' array found or it's not an array in {$jsonFilePath}. Marking as processed to avoid re-attempts on bad file.");
                Log::warning("No 'features' array in {$jsonFilePath}");
                $this->markFileAsProcessed($jsonFilePath, $seederProgress); // Mark bad file as processed
                $overallSkippedFilesDueToError++;
                continue;
            }

            $dataToInsert = [];
            $fileRowCount = 0;
            $fileUpsertedCount = 0;
            $recordsProcessedSinceLastMessage = 0;

            foreach ($data['features'] as $feature) {
                if (!isset($feature['properties']) || !is_array($feature['properties'])) {
                    Log::warning("Skipping feature in {$jsonFilePath} due to missing or invalid 'properties'. Feature ID: " . ($feature['id'] ?? 'N/A'));
                    continue;
                }

                $rawProperties = $feature['properties'];
                $record = [];
                foreach ($rawProperties as $key => $value) {
                    $record[strtolower($key)] = $value; // Normalize keys to lowercase
                }
                
                $transformedRecord = $this->transformRecord($record);
                
                $transformedRecord['objectid_source'] = $record['objectid'] ?? null;
                $transformedRecord['crash_date_text_raw'] = $record['crash_date_text'] ?? null;
                $transformedRecord['crash_time_2_raw'] = $record['crash_time_2'] ?? null;

                $dataToInsert[] = $transformedRecord;
                $fileRowCount++;
                $overallTotalProcessed++;
                $recordsProcessedSinceLastMessage++;

                if (count($dataToInsert) >= $chunkSize) {
                    try {
                        DB::table($tableName)->upsert(
                            $dataToInsert,
                            ['crash_numb', 'pers_numb', 'vehc_unit_numb'],
                            array_diff(array_keys($dataToInsert[0]), ['crash_numb', 'pers_numb', 'vehc_unit_numb'])
                        );
                        $fileUpsertedCount += count($dataToInsert);
                    } catch (\Exception $e) {
                        Log::error("Error upserting chunk from {$jsonFilePath}: " . $e->getMessage() . " - First record: " . json_encode($dataToInsert[0] ?? []));
                        // Decide if we should stop the whole seeder or just this file
                        // For now, we log and continue with the next chunk/file.
                    }
                    $dataToInsert = [];

                    if ($recordsProcessedSinceLastMessage >= 5000) {
                        $this->command->line("Processed approximately {$fileRowCount} records from {$jsonFilePath}. Total upserted from this file: {$fileUpsertedCount}.");
                        $recordsProcessedSinceLastMessage = 0;
                    }
                }
            }
            unset($data); // Free memory from decoded JSON

            if (!empty($dataToInsert)) {
                try {
                    DB::table($tableName)->upsert(
                        $dataToInsert,
                        ['crash_numb', 'pers_numb', 'vehc_unit_numb'],
                        array_diff(array_keys($dataToInsert[0]), ['crash_numb', 'pers_numb', 'vehc_unit_numb'])
                    );
                    $fileUpsertedCount += count($dataToInsert);
                } catch (\Exception $e) {
                    Log::error("Error upserting final chunk from {$jsonFilePath}: " . $e->getMessage() . " - First record: " . json_encode($dataToInsert[0] ?? []));
                }
            }
            $this->command->info("Finished processing {$jsonFilePath}. Records processed: {$fileRowCount}, Records upserted: {$fileUpsertedCount}.");
            $this->markFileAsProcessed($jsonFilePath, $seederProgress);
            $overallTotalUpserted += $fileUpsertedCount;
        }

        DB::enableQueryLog();
        $this->command->info("GeoJSON Seeding completed. Total records processed: {$overallTotalProcessed}, Total records upserted: {$overallTotalUpserted}.");
        if ($overallSkippedFilesDueToCompletion > 0) {
            $this->command->info("{$overallSkippedFilesDueToCompletion} files were skipped as they were already processed.");
        }
        if ($overallSkippedFilesDueToError > 0) {
            $this->command->warn("{$overallSkippedFilesDueToError} files were skipped due to errors (e.g., unreadable, bad JSON, no features).");
        }
        
        // Check if all discoverable files are processed, then clear progress
        $allFilesNowProcessed = true;
        $currentProgress = $this->getProgress(); // Re-fetch
        $recheckFiles = [];
         foreach ($years as $year) {
            $yearPath = $basePath . '/' . $year;
            if (File::isDirectory($yearPath)) {
                $jsonFiles = File::glob($yearPath . '/crash_data_*.geojson');
                if (!empty($jsonFiles)) $recheckFiles = array_merge($recheckFiles, $jsonFiles);
            }
        }
        foreach($recheckFiles as $filePath) {
            if (!in_array($filePath, $currentProgress['processed_files'])) {
                $allFilesNowProcessed = false;
                break;
            }
        }

        if ($allFilesNowProcessed && !empty($recheckFiles)) { // ensure there were files to process
            $this->command->info('All discoverable GeoJSON files have been processed. Clearing seeder progress file.');
            Storage::delete(self::PROGRESS_FILE);
        } else if (empty($recheckFiles)) {
             $this->command->info('No files were found to process. Seeder progress not cleared.');
        } else {
            $this->command->info('Not all discoverable files are processed yet. Seeder progress retained.');
        }
    }

    private function transformRecord(array $record): array
    {
        $transformed = [];
        foreach ($record as $key => $value) { // $key is already lowercased
            $dbKey = $key;

            // Standardize null-like values from JSON properties
            if (is_string($value)) {
                $trimmedValue = trim($value);
                if ($trimmedValue === '' || strtolower($trimmedValue) === 'null' || strtolower($trimmedValue) === 'nan') {
                    $value = null;
                } else {
                    $value = $trimmedValue;
                }
            }

            switch ($dbKey) {
                case 'objectid':        // Handled for 'objectid_source' directly in run()
                case 'crash_date_text': // Handled for 'crash_date_text_raw' directly in run()
                case 'crash_time_2':    // Handled for 'crash_time_2_raw' directly in run()
                    break;

                case 'crash_datetime': // Unix timestamp in milliseconds from GeoJSON
                    $transformed[$dbKey] = $value !== null ? Carbon::createFromTimestampMs($value)->toDateTimeString() : null;
                    break;
                case 'crash_hour': // e.g., "07:00AM to 07:59AM"
                    if ($value !== null) {
                        try {
                            $timePart = explode(' to ', (string)$value)[0]; // Take "07:00AM"
                            $transformed[$dbKey] = Carbon::parse($timePart)->hour; // Get 24-hour format hour
                        } catch (\Exception $e) {
                            $transformed[$dbKey] = null;
                            Log::debug("Could not parse crash_hour '{$value}': " . $e->getMessage());
                        }
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;

                case 'x': // GeoJSON properties.X (projected coordinate)
                    $transformed['x_coord'] = ($value !== null && is_numeric($value)) ? (float)$value : null;
                    break;
                case 'y': // GeoJSON properties.Y (projected coordinate)
                    $transformed['y_coord'] = ($value !== null && is_numeric($value)) ? (float)$value : null;
                    break;
                
                case 'is_geocoded': // GeoJSON "Yes", "No"
                    $transformed['is_geocoded_status'] = $value; // Assuming DB column 'is_geocoded_status' is string
                    break;

                // Integer types
                case 'year':
                case 'numb_vehc':
                case 'numb_nonfatal_injr':
                case 'numb_fatal_injr':
                case 'vehc_unit_numb':
                case 'driver_age':
                case 'total_occpt_in_vehc':
                case 'pers_numb':
                case 'age':
                case 'statn_num':
                case 'district_num':
                case 'aadt': // Often string in source, e.g., "1154"
                case 'num_lanes':
                case 'opp_lanes':
                case 'peak_lane':
                    $transformed[$dbKey] = ($value !== null && is_numeric($value)) ? (int)$value : null;
                    break;
                
                case 'speed_limit':
                case 'speed_lim':
                case 'op_dir_sl':
                    if ($value !== null && is_numeric($value) && (int)$value >= 0) {
                        $transformed[$dbKey] = (int)$value;
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;

                // Float types
                case 'lat': // GeoJSON properties.LAT
                case 'lon': // GeoJSON properties.LON
                case 'pk_pct_sut':
                case 'av_pct_sut':
                case 'pk_pct_ct':
                case 'av_pct_ct':
                case 'lt_sidewlk': // e.g. "4"
                case 'rt_sidewlk': // e.g. "5"
                case 'shldr_lt_w':
                case 'shldr_rt_w':
                case 'surface_wd':
                case 'med_width':
                case 'shldr_ul_w':
                case 'milemarker':
                    $transformed[$dbKey] = ($value !== null && is_numeric($value)) ? (float)$value : null;
                    break;

                // Boolean-like string to Boolean
                case 'fmsca_rptbl': // e.g., "No, not federally reportable"
                case 'fmsca_rptbl_vl':
                    $transformed[$dbKey] = ($value !== null && stripos((string)$value, 'yes') !== false) ? true : (($value !== null) ? false : null);
                    break;
                case 'hit_run_descr': // e.g., "Yes, hit and run", "No hit and run"
                    if ($value !== null) {
                        $lowerVal = strtolower((string)$value);
                        if (str_contains($lowerVal, 'yes')) $transformed[$dbKey] = true;
                        elseif (str_contains($lowerVal, 'no')) $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null;
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                case 'schl_bus_reld_descr': // e.g., "No, school bus not involved"
                    if ($value !== null) {
                        $lowerVal = strtolower((string)$value);
                        if (str_contains($lowerVal, 'yes')) $transformed[$dbKey] = true;
                        elseif (str_contains($lowerVal, 'no') || $lowerVal === 'not reported') $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null;
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                case 'work_zone_reld_descr':    // e.g., "No"
                case 'alc_suspd_type_descr':  // e.g., "Not reported", "No"
                case 'drug_suspd_type_descr': // e.g., "Not reported", "No"
                case 'emergency_use_desc':    // e.g., "No"
                case 'haz_mat_placard_descr': // e.g., "Not reported"
                    if ($value !== null) {
                        $lowerVal = strtolower((string)$value);
                        if ($lowerVal === 'yes') $transformed[$dbKey] = true;
                        elseif ($lowerVal === 'no') $transformed[$dbKey] = false;
                        else $transformed[$dbKey] = null; // "Not reported", "Unknown" etc. become null
                    } else {
                        $transformed[$dbKey] = null;
                    }
                    break;
                
                default: // Preserve type for numerics/booleans from JSON, otherwise string
                    if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                        $transformed[$dbKey] = $value;
                    } else {
                        $transformed[$dbKey] = (string)$value; // Default to string if not handled
                    }
                    break;
            }
        }
        return $transformed;
    }
}
