<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EverettCrimeData; // Changed from CrimeData
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use DateTime;
use Exception; // Added for explicit exception handling
use Illuminate\Support\Facades\File;

class EverettCrimeDataSeeder extends Seeder
{
    private const BATCH_SIZE = 500;
    private array $incidentTypeGroups = [];

    private function loadIncidentTypeGroups(): void
    {
        $path = database_path('seeders/incident_types.json');
        if (File::exists($path)) {
            $json = File::get($path);
            $this->incidentTypeGroups = json_decode($json, true);
            echo "Loaded " . count($this->incidentTypeGroups) . " incident type groups.\n";
        } else {
            echo "incident_type_groups.json not found. Seeding without incident type groups.\n";
        }
    }

    public function run()
    {
        $this->loadIncidentTypeGroups();
        $name = 'everett_police_data_combined';
        // Get all files from the datasets/everett folder in Storage
        $files = Storage::disk('local')->files('datasets/everett');

        // Filter files to only include those with the specified name in the filename
        $files = array_filter($files, function ($file) use ($name) {
            return strpos(basename($file), $name) !== false;
        });

        // Only proceed if there are any files to process
        if (!empty($files)) {
            // Sort files to get the most recent one if multiple versions exist (e.g., by name or modification time)
            // For simplicity, assuming `end()` is sufficient or only one file matches.
            $file = end($files);
            $fullPath = Storage::disk('local')->path($file);
            print_r("Processing Everett file: " . $fullPath . "\n");

            // Process the most recent file
            $this->processFile($fullPath);
        } else {
            echo "No files found to process for name: " . $name . " in datasets/everett\n";
        }
    }

    private function processFile($file)
    {
        if (!File::exists($file) || !is_readable($file)) {
            print_r("Error: File does not exist or is not readable: " . $file . "\n");
            return;
        }

        print_r("Processing Everett file: " . $file . "\n");
        $csv = Reader::createFromPath($file);
        $csv->setHeaderOffset(0); // The header is on the first row

        $dataBatch = [];
        $progress = 0;
        $startTime = microtime(true);
        $fileCount = count($csv);
        $skipped = 0;

        foreach ($csv->getRecords() as $crime) {
            $progress++;

            if (empty($crime['case_number'])) {
                print_r("Skipping record with empty case_number.\n");
                $skipped++;
                continue;
            }
            
            $latitude = $crime['incident_latitude'] ?? null;
            $longitude = $crime['incident_longitude'] ?? null;

            if (empty($latitude) || empty($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) {
                print_r("Skipping record with invalid or empty lat/long for case " . $crime['case_number'] . ": " . $latitude . ", " . $longitude . "\n");
                $skipped++;
                continue;
            }

            $occurred_on_datetime = null;
            $year = null;
            $month = null;
            $day_of_week = null;
            $hour = null;
            $incident_entry_date_parsed = null;
            $incident_time_parsed = null;

            if (!empty($crime['incident_entry_date'])) {
                $date_str = trim($crime['incident_entry_date']);
                $time_str = !empty(trim($crime['incident_time'])) ? trim($crime['incident_time']) : '00:00';

                try {
                    $dateTimeObj = null;
                    // Try m/d/Y H:i
                    if (strpos($date_str, '/') !== false && strpos($time_str, ':') !== false) {
                        $dateTimeObj = DateTime::createFromFormat('m/d/Y H:i', $date_str . ' ' . $time_str);
                    }

                    // If failed or time was default 00:00, try m/d/Y for date and parse time separately or default time
                    if (!$dateTimeObj || ($dateTimeObj && $time_str === '00:00')) {
                        $dateOnlyObj = DateTime::createFromFormat('m/d/Y', $date_str);
                        if ($dateOnlyObj) {
                            $incident_entry_date_parsed = $dateOnlyObj->format('Y-m-d');
                            // Attempt to parse time string H:i or H:i:s
                            $timeParts = date_parse_from_format('H:i', $time_str);
                            if ($timeParts['error_count'] === 0 && $timeParts['warning_count'] === 0) {
                                $dateOnlyObj->setTime($timeParts['hour'], $timeParts['minute'], $timeParts['second'] ?? 0);
                                $incident_time_parsed = $dateOnlyObj->format('H:i:s');
                            } else {
                                $dateOnlyObj->setTime(0, 0, 0); // Default to midnight if time is invalid
                                $incident_time_parsed = '00:00:00';
                            }
                            $dateTimeObj = $dateOnlyObj;
                        }
                    }
                    
                    if ($dateTimeObj instanceof DateTime) {
                        $occurred_on_datetime = $dateTimeObj->format('Y-m-d H:i:s');
                        $year = (int)$dateTimeObj->format('Y');
                        $month = (int)$dateTimeObj->format('m');
                        $day_of_week = $dateTimeObj->format('l');
                        $hour = (int)$dateTimeObj->format('H');
                        if (!$incident_entry_date_parsed) {
                            $incident_entry_date_parsed = $dateTimeObj->format('Y-m-d');
                        }
                        if (!$incident_time_parsed) {
                             $incident_time_parsed = $dateTimeObj->format('H:i:s');
                        }
                    } else {
                        throw new Exception("Invalid date/time format for: '" . $date_str . ' ' . $time_str . "'");
                    }
                } catch (Exception $e) {
                    print_r("Error parsing date/time for occurred_on_datetime: " . $e->getMessage() . ". Setting related date fields to null for case: " . $crime['case_number'] . "\n");
                }
            } else {
                 print_r("Empty incident_entry_date. Setting occurred_on_datetime fields to null for case: " . $crime['case_number'] . "\n");
            }

            // Parse incident_log_file_date
            $incident_log_file_date_parsed = null;
            if (!empty($crime['incident_log_file_date'])) {
                try {
                    $dt = DateTime::createFromFormat('m/d/Y', trim($crime['incident_log_file_date']));
                    if ($dt) {
                        $incident_log_file_date_parsed = $dt->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    print_r("Error parsing incident_log_file_date: " . $crime['incident_log_file_date'] . " for case: " . $crime['case_number'] . "\n");
                }
            }

            // Parse arrest_date
            $arrest_date_parsed = null;
            if (!empty($crime['arrest_date'])) {
                try {
                    $dt = DateTime::createFromFormat('m/d/Y', trim($crime['arrest_date']));
                    if ($dt) {
                        $arrest_date_parsed = $dt->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    print_r("Error parsing arrest_date: " . $crime['arrest_date'] . " for case: " . $crime['case_number'] . "\n");
                }
            }
            
            // Concatenate extra details for crime_details_concatenated
            // This part is similar to your original logic for 'crime_details'
            $extraDetailsText = [];
            $fieldsToConcat = [
                // Using original CSV field names as keys for $crime array
                'incident_log_file_date' => 'Incident Log File Date',
                'arrest_name' => 'Arrest Name',
                'arrest_address' => 'Arrest Address',
                'arrest_age' => 'Arrest Age',
                'arrest_date' => 'Arrest Date', // Original string format from CSV
                'arrest_charges' => 'Arrest Charges',
            ];
            foreach ($fieldsToConcat as $key => $label) {
                if (!empty($crime[$key])) {
                    $extraDetailsText[] = $label . ": " . trim($crime[$key]);
                }
            }
            $crime_details_concatenated = !empty($extraDetailsText) ? implode("\n", $extraDetailsText) : null;

            $incidentType = trim($crime['incident_type'] ?? '');
            $incidentTypeGroup = $this->incidentTypeGroups[$incidentType] ?? 'Miscellaneous';

            $dataBatch[] = [
                'case_number' => $crime['case_number'],
                'incident_log_file_date' => $incident_log_file_date_parsed,
                'incident_entry_date_parsed' => $incident_entry_date_parsed,
                'incident_time_parsed' => $incident_time_parsed,
                'occurred_on_datetime' => $occurred_on_datetime,
                'year' => $year,
                'month' => $month,
                'day_of_week' => $day_of_week,
                'hour' => $hour,
                'incident_type' => $incidentType,
                'incident_type_group' => $incidentTypeGroup,
                'incident_address' => trim($crime['incident_address'] ?? ''),
                'incident_latitude' => $latitude,
                'incident_longitude' => $longitude,
                'incident_description' => trim($crime['incident_description'] ?? ''),
                'arrest_name' => trim($crime['arrest_name'] ?? ''),
                'arrest_address' => trim($crime['arrest_address'] ?? ''),
                'arrest_age' => !empty($crime['arrest_age']) ? (int)$crime['arrest_age'] : null,
                'arrest_date_parsed' => $arrest_date_parsed,
                'arrest_charges' => trim($crime['arrest_charges'] ?? ''),
                'crime_details_concatenated' => $crime_details_concatenated,
                'source_city' => 'Everett'
            ];

            if ($progress % self::BATCH_SIZE == 0) {
                $this->insertOrUpdateBatch($dataBatch);
                $dataBatch = []; // Reset the batch

                $endTime = microtime(true);
                $timeTaken = $endTime - $startTime;
                $this->reportProgress($progress, $fileCount, $timeTaken);
                $startTime = microtime(true);
            }
        }

        if (!empty($dataBatch)) {
            $this->insertOrUpdateBatch($dataBatch);
        }
        
        print_r("Total records skipped in Everett file: " . $skipped . "\n");
        print_r("Everett file processed: " . $file . "\n");
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        DB::table((new EverettCrimeData)->getTable())->upsert($dataBatch, ['case_number'], [
            'incident_log_file_date',
            'incident_entry_date_parsed',
            'incident_time_parsed',
            'occurred_on_datetime',
            'year',
            'month',
            'day_of_week',
            'hour',
            'incident_type',
            'incident_type_group',
            'incident_address',
            'incident_latitude',
            'incident_longitude',
            'incident_description',
            'arrest_name',
            'arrest_address',
            'arrest_age',
            'arrest_date_parsed',
            'arrest_charges',
            'crime_details_concatenated',
            'source_city',
            'updated_at' // Ensure updated_at is touched on update
        ]);
    }

    private function reportProgress($progress, $fileCount, $timeTaken)
    {
        $estimatedTimePerRecord = $timeTaken / self::BATCH_SIZE;
        $estimatedTimeRemainingFile = $estimatedTimePerRecord * ($fileCount - $progress);
        
        // Clear the previous 5 lines (adjust if output changes)
        echo "\033[5A";  // Move 5 lines up
        echo "\033[K";   // Clear current line
        echo $progress . " Everett records processed.\n";
        echo "\033[K";   // Clear current line
        echo "Records remaining in this Everett file: " . ($fileCount - $progress) . ".\n";
        echo "\033[K";   // Clear current line
        echo "Time for last " . self::BATCH_SIZE . " Everett records: " . round($timeTaken, 2) . " seconds.\n";
        echo "\033[K";   // Clear current line
        echo "Estimated time remaining for this Everett file: " . $this->formatTime($estimatedTimeRemainingFile) . ".\n";
        echo "\033[K"; // Clear an potential extra line from previous output
        echo "\n"; // Ensure cursor is on a new line for next output
    }

    private function formatTime(float $timeInSeconds): string
    {
        if ($timeInSeconds < 0) $timeInSeconds = 0; // Prevent negative time display

        $hours = floor($timeInSeconds / 3600);
        $minutes = floor(($timeInSeconds % 3600) / 60);
        $seconds = $timeInSeconds % 60;

        $formattedTime = [];
        if ($hours > 0) {
            $formattedTime[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        if ($minutes > 0 || $hours > 0) { // Show minutes if hours are present or minutes > 0
            $formattedTime[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        $formattedTime[] = round($seconds, 0) . ' second' . (round($seconds, 0) != 1 ? 's' : '');

        return implode(', ', $formattedTime);
    }
}
