<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConstructionOffHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ConstructionOffHoursSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    private $addressCache = [];
    private $suffixMap = [
        'av' => 'ave',
        'bl' => 'blvd',
        'pk' => 'park',
        'pw' => 'pkwy',
        'wy' => 'way',
        'te' => 'ter',
        'pz' => 'plz',
        'hw' => 'hwy',
    ];

    public function run()
    {
        $name = 'construction-off-hours';

        Log::info('Starting ConstructionOffHoursSeeder.');

        // Load all addresses into memory for matching
        $this->addressCache = $this->loadAddressCache();

        // Get files with the specified name in the filename
        $files = Storage::disk('local')->files('datasets');
        $files = array_filter($files, fn($file) => strpos($file, $name) !== false);

        if (!empty($files)) {
            $file = end($files); // Get the most recent file
            Log::info("Found " . count($files) . " files. Processing the most recent file: {$file}");
            $this->processFile(Storage::path($file));
        } else {
            Log::warning("No files found matching the name: '{$name}'.");
        }

        Log::info('ConstructionOffHoursSeeder completed.');
    }

    private function loadAddressCache()
    {
        Log::info("Loading addresses from 'trash_schedules_by_address' table...");
        $addresses = DB::table('trash_schedules_by_address')
            ->select('full_address', 'y_coord as latitude', 'x_coord as longitude')
            ->get();

        Log::info("Loaded " . $addresses->count() . " addresses into memory.");

        return $addresses->mapWithKeys(function ($item) {
            return [strtolower($item->full_address) => $item];
        })->toArray();
    }

    private function processFile($file)
    {
        try {
            $csv = Reader::createFromPath($file);
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $dataBatch = [];
            $progress = 0;
            $skipped = 0;

            foreach ($records as $offHour) {
                $progress++;

                // Log progress at intervals
                if ($progress % 100 === 0) {
                    Log::info("Progress: {$progress} records processed.");
                }

                // Validate record
                if (empty($offHour['app_no']) || empty($offHour['start_datetime']) || empty($offHour['address'])) {
                    $skipped++;
                    Log::warning("Skipping invalid record at row {$progress}: " . json_encode($offHour));
                    continue;
                }

                // Extract and normalize the base address
                $baseAddress = $this->normalizeAddress($offHour['address']);

                // Find the best matching address
                $bestMatch = $this->findMatch($baseAddress);

                // Fallback: Try substring after hyphen if no match found
                if (!$bestMatch && str_contains($baseAddress, '-')) {
                    $afterHyphen = $this->getSubstringAfterHyphen($baseAddress);
                    Log::info("No match found for normalized address: '{$baseAddress}'. Trying after hyphen: '{$afterHyphen}'");
                    $bestMatch = $this->findMatch($afterHyphen);

                    if ($bestMatch) {
                        Log::info("Fallback match for row {$progress}: '{$baseAddress}' -> '{$afterHyphen}' -> '{$bestMatch->full_address}'");
                    }
                }

                if (!$bestMatch) {
                    $skipped++;
                    Log::warning("No match found for normalized address: '{$baseAddress}' (original: '{$offHour['address']}')");
                    continue;
                }

                // Log matched address details
                Log::info("Matched address for row {$progress}: '{$offHour['address']}' -> '{$bestMatch->full_address}'");

                $dataBatch[] = [
                    'app_no' => $offHour['app_no'],
                    'start_datetime' => $offHour['start_datetime'],
                    'stop_datetime' => $offHour['stop_datetime'],
                    'address' => $offHour['address'],
                    'ward' => $offHour['ward'],
                    'latitude' => $bestMatch->latitude,
                    'longitude' => $bestMatch->longitude,
                ];

                if ($progress % self::BATCH_SIZE == 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    Log::info("Inserted batch of " . self::BATCH_SIZE . " records. Progress: {$progress}");
                }
            }

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
                Log::info("Inserted final batch of " . count($dataBatch) . " records.");
            }

            Log::info("Finished processing file: {$file}. Total processed: {$progress}, Skipped: {$skipped}");
        } catch (\Exception $e) {
            Log::error("Error processing file: {$file}. Exception: " . $e->getMessage());
        }
    }

    private function normalizeAddress($address)
    {
        $parts = explode(',', $address, 2);
        $baseAddress = strtolower(trim($parts[0]));

        // Replace suffixes using the suffix map
        foreach ($this->suffixMap as $short => $full) {
            $baseAddress = preg_replace('/\b' . $short . '\b/', $full, $baseAddress);
        }

        return $baseAddress;
    }

    private function getSubstringAfterHyphen($address)
    {
        $parts = explode('-', $address, 2);
        return isset($parts[1]) ? trim($parts[1]) : $address;
    }

    private function findMatch($normalizedAddress)
    {
        return $this->addressCache[$normalizedAddress] ?? null;
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        try {
            DB::table((new ConstructionOffHour)->getTable())->upsert($dataBatch, ['app_no'], [
                'start_datetime', 'stop_datetime', 'address', 'ward', 'latitude', 'longitude',
            ]);
            Log::info("Batch upsert completed with " . count($dataBatch) . " records.");
        } catch (\Exception $e) {
            Log::error("Error during batch upsert: " . $e->getMessage());
        }
    }
}