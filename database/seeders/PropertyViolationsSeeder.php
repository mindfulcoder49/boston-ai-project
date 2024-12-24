<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyViolation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;


class PropertyViolationsSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
        $name = 'property-violations';
        // Get all files from the datasets folder in Storage
        $files = Storage::disk('local')->files('datasets');

        // Filter files to only include those with the specified name in the filename
        $files = array_filter($files, function ($file) use ($name) {
            return strpos($file, $name) !== false;
        });

        // Only proceed if there are any files to process
        if (!empty($files)) {
            // Get the most recent file
            $file = end($files);
            echo "Processing file: " . $file . "\n";

            // Process the most recent file
            $this->processFile(Storage::path($file));
        } else {
            echo "No files found to process for name: " . $name . "\n";
        }
    }

    private function processFile($filePath)
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // The header is on the first row
             //set the escape character to null
             $csv->setEscape('');

            $records = $csv->getRecords();

            $dataBatch = [];
            $progress = 0;
            
            foreach ($records as $violation) {
                $progress++;
                 //echo $case_no, description etc, on one line
                echo "Case No: " . $violation['case_no'] . " Description: " . $violation['description'] . "\n";

                // Check and format datetime fields
                $statusDttm = $this->formatDate($violation['status_dttm']);

                //convert empty strings to null
                foreach ($violation as $key => $value) {
                    if ($value === '') {
                        $violation[$key] = null;
                    }
                }

                // Add data to batch array
                $dataBatch[] = [
                    'case_no' => $violation['case_no'],
                    'ap_case_defn_key' => $violation['ap_case_defn_key'],
                    'status_dttm' => $statusDttm,
                    'status' => $violation['status'],
                    'code' => $violation['code'],
                    'value' => $violation['value'],
                    'description' => $violation['description'],
                    'violation_stno' => $violation['violation_stno'],
                    'violation_sthigh' => $violation['violation_sthigh'],
                    'violation_street' => $violation['violation_street'],
                    'violation_suffix' => $violation['violation_suffix'],
                    'violation_city' => $violation['violation_city'],
                    'violation_state' => $violation['violation_state'],
                    'violation_zip' => $violation['violation_zip'],
                    'ward' => $violation['ward'],
                    'contact_addr1' => $violation['contact_addr1'],
                    'contact_addr2' => $violation['contact_addr2'],
                    'contact_city' => $violation['contact_city'],
                    'contact_state' => $violation['contact_state'],
                    'contact_zip' => $violation['contact_zip'],
                    'sam_id' => $violation['sam_id'],
                    'latitude' => $violation['latitude'],
                    'longitude' => $violation['longitude'],
                    'location' => $violation['location'],
                    'language_code' => 'en-US',
                ];
                
                if ($progress % self::BATCH_SIZE == 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = []; // Reset the batch
                }
            }

            // Process any remaining data
            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }

            echo "File processed: " . basename($filePath) . "\n";
        } catch (\Exception $e) {
            echo "Error processing file: " . basename($filePath) . " - " . $e->getMessage() . "\n";
        }
    }

    private function formatDate($date)
    {
           if (empty($date)) {
            // Set to null or a default value that makes sense for your application
            return null;
        }

         if ( $date == '1970-01-01 00:00:00') {
            echo "Date is 1970-01-01 00:00:00\n";
            return null;
        }
        
        //if date is before epoch time, set to null
        if (strtotime($date) < 0) {
            echo "Date is before epoch time\n";
            return null;
        }
        
         // Ensure date is in the correct format
        $formattedDate = date('Y-m-d H:i:s', strtotime($date));

        return $formattedDate;
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        DB::table((new PropertyViolation)->getTable())->upsert($dataBatch, ['case_no'], [
            'ap_case_defn_key', 'status_dttm', 'status', 'code', 'value', 'description',
            'violation_stno', 'violation_sthigh', 'violation_street', 'violation_suffix',
            'violation_city', 'violation_state', 'violation_zip', 'ward', 'contact_addr1',
            'contact_addr2', 'contact_city', 'contact_state', 'contact_zip', 'sam_id',
             'latitude', 'longitude', 'location', 'language_code'
        ]);
    }
}