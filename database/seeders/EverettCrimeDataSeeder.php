<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CrimeData;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use DateTime; // Added for date parsing

class EverettCrimeDataSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
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
            echo "Processing Everett file: " . $file . "\n";

            // Process the most recent file
            $this->processFile(Storage::path($file));
        } else {
            echo "No files found to process for name: " . $name . " in datasets/everett\n";
        }
    }

    private function processFile($file)
    {
        print_r("Processing Everett file: " . $file . "\n");
        $csv = Reader::createFromPath($file);
        $csv->setHeaderOffset(0); // The header is on the first row

        // $records = $csv->getRecords(); // Get records inside the loop to potentially save memory

        $dataBatch = [];
        $progress = 0;
        $startTime = microtime(true);
        $fileCount = count($csv); // More efficient way to count records with league/csv
        $skipped = 0;

        foreach ($csv->getRecords() as $crime) {
            $progress++;

            if (empty($crime['case_number'])) {
                print_r("Skipping record with empty case_number.\n");
                $skipped++;
                continue;
            }
            
            if (!is_numeric($crime['incident_latitude']) || !is_numeric($crime['incident_longitude']) || empty($crime['incident_latitude']) || empty($crime['incident_longitude'])) {
                print_r("Skipping record with invalid or empty lat/long for case " . $crime['case_number'] . ": " . $crime['incident_latitude'] . ", " . $crime['incident_longitude'] . "\n");
                $skipped++;
                continue;
            }

            $occurred_on_datetime_str = null;
            $year = null;
            $month = null;
            $day_of_week = null;
            $hour = null;

            if (!empty($crime['incident_entry_date'])) {
                $date_str = trim($crime['incident_entry_date']);
                // Default to midnight if time is missing or empty
                $time_str = !empty(trim($crime['incident_time'])) ? trim($crime['incident_time']) : '00:00';

                try {
                    // Attempt to parse MM/DD/YYYY HH:MM
                    $dateTimeObj = DateTime::createFromFormat('m/d/Y H:i', $date_str . ' ' . $time_str);
                    if ($dateTimeObj === false) {
                         // Attempt to parse MM/DD/YYYY (if time parsing failed or time was just '00:00')
                        $dateTimeObj = DateTime::createFromFormat('m/d/Y', $date_str);
                         if ($dateTimeObj === false) {
                            // If primary date format fails, try YYYY-MM-DD as a fallback if applicable, or just throw.
                            // For now, stick to the m/d/Y format as per CSV example.
                            throw new \Exception("Invalid date/time format for: '" . $date_str . ' ' . $time_str . "'");
                         }
                         // If only date parsed, set time to midnight
                         $dateTimeObj->setTime(0, 0, 0);
                    }
                    $occurred_on_datetime_str = $dateTimeObj->format('Y-m-d H:i:s');
                    $year = (int)$dateTimeObj->format('Y');
                    $month = (int)$dateTimeObj->format('m');
                    $day_of_week = $dateTimeObj->format('l'); // Full textual representation (e.g., Sunday)
                    $hour = (int)$dateTimeObj->format('H');
                } catch (\Exception $e) {
                    print_r("Error parsing date/time: " . $e->getMessage() . ". Setting date fields to null for case: " . $crime['case_number'] . "\n");
                }
            } else {
                 print_r("Empty incident_entry_date. Setting date fields to null for case: " . $crime['case_number'] . "\n");
            }

            $incidentType = trim($crime['incident_type'] ?? '');
            $incidentDetails = trim($crime['incident_description'] ?? '');
            $fullOffenseDescription = null;

            if (!empty($incidentType) && !empty($incidentDetails)) {
                $fullOffenseDescription = $incidentType . ' - ' . $incidentDetails;
            } elseif (!empty($incidentType)) {
                $fullOffenseDescription = $incidentType;
            } elseif (!empty($incidentDetails)) {
                $fullOffenseDescription = $incidentDetails;
            }

            $streetAddress = trim($crime['incident_address'] ?? '');

            $extraDetails = [];
            $fieldsToConcat = [
                'incident_log_file_date' => 'Incident Log File Date',
                'arrest_name' => 'Arrest Name',
                'arrest_address' => 'Arrest Address',
                'arrest_age' => 'Arrest Age',
                'arrest_date' => 'Arrest Date',
                'arrest_charges' => 'Arrest Charges',
            ];

            foreach ($fieldsToConcat as $key => $label) {
                if (!empty($crime[$key])) {
                    $extraDetails[] = $label . ": " . trim($crime[$key]);
                }
            }
            $crimeDetails = !empty($extraDetails) ? implode("\n", $extraDetails) : null;

            $dataBatch[] = [
                'incident_number' => $crime['case_number'],
                'offense_code' => null, // Not available in Everett data
                'offense_code_group' => null, // Not available
                'offense_description' => $fullOffenseDescription,
                'district' => null, // Not available
                'reporting_area' => null, // Not available
                'shooting' => false, // Assuming false as not specified
                'occurred_on_date' => $occurred_on_datetime_str,
                'year' => $year,
                'month' => $month,
                'day_of_week' => $day_of_week,
                'hour' => $hour,
                'ucr_part' => null, // Not available
                'street' => !empty($streetAddress) ? $streetAddress : null,
                'lat' => $crime['incident_latitude'],
                'long' => $crime['incident_longitude'],
                'location' => '(' . $crime['incident_latitude'] . ', ' . $crime['incident_longitude'] . ')',
                'language_code' => 'en-US', // Default language code
                'crime_details' => $crimeDetails,
                // Assuming crime_start_time and crime_end_time are not available in this CSV
                // and will remain null or be handled by a different process if needed.
                'crime_start_time' => $occurred_on_datetime_str, // Or null if not applicable as start time
                'crime_end_time' => null, // Or derive if possible, otherwise null
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
        DB::table((new CrimeData)->getTable())->upsert($dataBatch, ['incident_number'], [
            'offense_code',
            'offense_code_group',
            'offense_description',
            'district',
            'reporting_area',
            'shooting',
            'occurred_on_date',
            'year',
            'month',
            'day_of_week',
            'hour',
            'ucr_part',
            'street',
            'lat',
            'long',
            'location',
            'language_code',
            'crime_details',
            'crime_start_time',
            'crime_end_time'
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
