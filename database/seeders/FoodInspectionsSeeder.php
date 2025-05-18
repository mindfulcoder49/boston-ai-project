<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FoodInspection;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FoodInspectionsSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
        $name = 'food-inspections'; // Assuming CSV filename contains this
        $files = Storage::disk('local')->files('datasets');

        $files = array_filter($files, function ($file) use ($name) {
            return strpos(strtolower(basename($file)), $name) !== false;
        });

        if (!empty($files)) {
            // Sort files by modification time to get the most recent one if multiple exist
            usort($files, function ($a, $b) {
                return Storage::disk('local')->lastModified($b) <=> Storage::disk('local')->lastModified($a);
            });
            $file = $files[0]; // Get the most recent file
            $this->command->info("Processing file: " . $file);
            $this->processFile(Storage::path($file));
        } else {
            $this->command->warn("No files found to process for name: " . $name);
        }
    }

    private function processFile($filePath)
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setEscape('');

            $records = $csv->getRecords();
            $dataBatch = [];
            $progress = 0;

            foreach ($records as $record) {
                $progress++;

                // Convert empty strings to null
                foreach ($record as $key => $value) {
                    if ($value === '') {
                        $record[$key] = null;
                    }
                }

                // Parse location into latitude and longitude
                $latitude = null;
                $longitude = null;
                if (!empty($record['location'])) {
                    $locationParts = explode(',', str_replace(['(', ')'], '', $record['location']));
                    if (count($locationParts) === 2) {
                        $latitude = trim($locationParts[0]);
                        $longitude = trim($locationParts[1]);
                    }
                }
                
                $dataBatch[] = [
                    'external_id' => $record['_id'],
                    'businessname' => $record['businessname'],
                    'dbaname' => $record['dbaname'],
                    'legalowner' => $record['legalowner'],
                    'namelast' => $record['namelast'],
                    'namefirst' => $record['namefirst'],
                    'licenseno' => $record['licenseno'],
                    'issdttm' => $this->formatDate($record['issdttm']),
                    'expdttm' => $this->formatDate($record['expdttm']),
                    'licstatus' => $record['licstatus'],
                    'licensecat' => $record['licensecat'],
                    'descript' => $record['descript'],
                    'result' => $record['result'],
                    'resultdttm' => $this->formatDate($record['resultdttm']),
                    'violation' => $record['violation'],
                    'viol_level' => $record['viol_level'],
                    'violdesc' => $record['violdesc'],
                    'violdttm' => $this->formatDate($record['violdttm']),
                    'viol_status' => $record['viol_status'],
                    'status_date' => $this->formatDate($record['status_date']),
                    'comments' => $record['comments'],
                    'address' => $record['address'],
                    'city' => $record['city'],
                    'state' => $record['state'],
                    'zip' => $record['zip'],
                    'property_id' => $record['property_id'],
                    'latitude' => $this->parseFloat($latitude),
                    'longitude' => $this->parseFloat($longitude),
                    'language_code' => 'en-US', // Assuming English
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($progress % self::BATCH_SIZE == 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = []; // Reset the batch
                    $this->command->info("Processed {$progress} records...");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }

            $this->command->info("File processed successfully: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing file: " . basename($filePath) . " - " . $e->getMessage());
        }
    }

    private function formatDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        try {
            // Attempt to parse with various common formats including timezone
            return Carbon::parse($dateString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // If parsing fails, log or handle as needed, return null or original
            $this->command->warn("Could not parse date: {$dateString}");
            return null;
        }
    }

    private function parseFloat($value)
    {
        return is_numeric($value) ? (float)$value : null;
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        if (empty($dataBatch)) {
            return;
        }
        
        $columnsToUpdate = array_keys($dataBatch[0]);
        // Remove 'external_id' and 'created_at' from columns to update on duplicate
        $columnsToUpdate = array_filter($columnsToUpdate, function ($column) {
            return !in_array($column, ['external_id', 'created_at']);
        });


        DB::table((new FoodInspection)->getTable())->upsert(
            $dataBatch,
            ['external_id'], // Unique key for identifying records
            array_values($columnsToUpdate) // Columns to update on duplicate
        );
    }
}
