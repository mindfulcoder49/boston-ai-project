<?php

// database/seeders/CrimeDataSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CrimeData;
use App\Models\TrashScheduleByAddress; // Assuming this model exists and maps to trash_schedules_by_address
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CrimeDataSeeder extends Seeder
{
    private const BATCH_SIZE = 500;
    private const STREET_ABBREVIATIONS_BOSTON = [
        // Common Boston street suffixes and abbreviations
        'STREET' => 'ST', 'AVENUE' => 'AVE', 'ROAD' => 'RD', 'DRIVE' => 'DR',
        'PLACE' => 'PL', 'COURT' => 'CT', 'LANE' => 'LN', 'BOULEVARD' => 'BLVD',
        'PARKWAY' => 'PKWY', 'SQUARE' => 'SQ', 'TERRACE' => 'TER', 'HIGHWAY' => 'HWY',
        'CIRCLE' => 'CIR', 'ALLEY' => 'ALY', 'EXPRESSWAY' => 'EXPY', 'FREEWAY' => 'FWY',
        'JUNCTION' => 'JCT', 'POINT' => 'PT', 'TRAIL' => 'TRL', 'TURNPIKE' => 'TPKE',
        'WAY' => 'WY', 'CENTER' => 'CTR',
        // Specific Boston names if needed, similar to Cambridge
        'MOUNT' => 'MT', 'SAINT' => 'ST',
    ];

    private array $bostonAddressCache = [];
    private array $offenseCodeGroups = [];

    private function normalizeBostonStreetName(string $streetName): string
    {
        $processedName = strtoupper(trim($streetName));
        $processedName = preg_replace('/\s+/', ' ', $processedName);
        $processedName = preg_replace('/^THE\s+/', '', $processedName);

        foreach (self::STREET_ABBREVIATIONS_BOSTON as $search => $replace) {
            $processedName = preg_replace('/\b' . preg_quote($search, '/') . '\b/i', $replace, $processedName);
        }
        return rtrim(trim($processedName), '.');
    }

    private function parseBostonAddressString(string $addressString): ?array
    {
        $addressString = trim($addressString);
        // Regex to capture number (optional) and street part.
        // Handles cases like "123 MAIN ST", "MAIN ST", "123A MAIN ST"
        // Does not explicitly handle "BLOCK" like Cambridge, adjust if Boston data has it.
        if (preg_match('/^(\d+[A-Z]?)\s+(.*)$/i', $addressString, $matches) || // Number then Street
            preg_match('/^(.*?)(\s+\d+[A-Z]?)$/i', $addressString, $matches_reverse) // Street then Number (less common for input but good for parsing full_address)
           ) {
            if (!empty($matches)) {
                $numberPart = trim($matches[1]);
                $rawStreetNamePart = trim($matches[2]);
            } elseif (!empty($matches_reverse)) { // If street name came first
                $rawStreetNamePart = trim($matches_reverse[1]);
                $numberPart = trim($matches_reverse[2]);
            } else { // Fallback for street name only
                 $rawStreetNamePart = $addressString;
                 $numberPart = null;
            }

            $numericStreetNumber = $numberPart ? intval($numberPart) : null;
            $normalizedStreetName = $this->normalizeBostonStreetName($rawStreetNamePart);
            
            if (!empty($normalizedStreetName)) {
                $result = ['name' => $normalizedStreetName];
                if ($numericStreetNumber !== null) {
                    $result['number'] = $numericStreetNumber;
                    $result['original_number_part'] = $numberPart;
                }
                return $result;
            }
        } elseif (!empty($addressString)) { // No number, just street name
            $normalizedStreetName = $this->normalizeBostonStreetName($addressString);
            if (!empty($normalizedStreetName)) {
                return ['name' => $normalizedStreetName, 'number' => null];
            }
        }
        return null;
    }

    private function loadBostonAddressData(): void
    {
        $this->command->info("Loading Boston address data from trash schedules into cache...");
        // Use the model if it exists, otherwise DB::table()
        $trashAddresses = DB::table((new TrashScheduleByAddress)->getTable())
            ->select('full_address', 'x_coord', 'y_coord')
            ->whereNotNull('full_address')
            ->whereNotNull('x_coord')
            ->whereNotNull('y_coord')
            ->where('x_coord', '!=', 0) // Filter out (0,0) coordinates often used as placeholders
            ->where('y_coord', '!=', 0)
            ->get();

        foreach ($trashAddresses as $addr) {
            if (empty($addr->full_address) || empty($addr->x_coord) || empty($addr->y_coord)) {
                continue;
            }
            
            $parsed = $this->parseBostonAddressString($addr->full_address);
            if ($parsed && !empty($parsed['name'])) {
                $normalizedStreet = strtolower($parsed['name']); // Already normalized by parseBostonAddressString
                $number = $parsed['number'] ?? 0; // Default to 0 if no number, helps sorting

                $this->bostonAddressCache[$normalizedStreet][] = [
                    'number' => (int)$number,
                    'latitude' => (float)$addr->y_coord, // y_coord is typically latitude
                    'longitude' => (float)$addr->x_coord, // x_coord is typically longitude
                ];
            }
        }

        // Sort addresses by street number for efficient searching
        foreach ($this->bostonAddressCache as $streetName => $addressList) {
            usort($this->bostonAddressCache[$streetName], function ($a, $b) {
                return $a['number'] <=> $b['number'];
            });
        }
        $this->command->info("Finished loading " . count($trashAddresses) . " Boston addresses into cache, grouped by " . count($this->bostonAddressCache) . " unique street names.");
    }

    private function loadOffenseCodeGroups(): void
    {
        $this->command->info("Loading offense code groups...");
        $path = database_path('seeders/offense_code_groups.json');
        if (!File::exists($path)) {
            $this->command->error("offense_code_groups.json not found!");
            return;
        }
        $json = File::get($path);
        $this->offenseCodeGroups = json_decode($json, true);
        $this->command->info("Loaded " . count($this->offenseCodeGroups) . " offense code groups.");
    }

    public function run()
    {
        $this->command->info("Starting Boston Crime Data Seeder...");
        $this->loadBostonAddressData(); // Load cache once
        $this->loadOffenseCodeGroups();

        $name = 'crime-incident-reports';
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

    private function processFile($file)
    {
        $this->command->info("Processing file: " . $file);
        $csv = Reader::createFromPath($file);
        $csv->setHeaderOffset(0); // The header is on the first row

        $records = $csv->getRecords();

        $dataBatch = [];
        $progress = 0;
        $startTime = microtime(true);
        $fileCount = count(file($file));
        $skipped = 0;

        foreach ($records as $crime) {
            $progress++;

            $lat = trim($crime['Lat'] ?? '');
            $long = trim($crime['Long'] ?? '');
            $attemptedFallback = false;

            if (empty($lat) || empty($long) || !is_numeric($lat) || !is_numeric($long) || (float)$lat == 0 || (float)$long == 0) {
                $attemptedFallback = true;
                $rawStreetFromCrime = trim($crime['STREET'] ?? '');

                if (!empty($rawStreetFromCrime)) {
                    $parsedCrimeStreet = $this->parseBostonAddressString($rawStreetFromCrime);

                    if ($parsedCrimeStreet && !empty($parsedCrimeStreet['name'])) {
                        $normalizedCrimeStreetName = strtolower($parsedCrimeStreet['name']); // Name already normalized

                        if (isset($this->bostonAddressCache[$normalizedCrimeStreetName])) {
                            $cachedAddressesOnStreet = $this->bostonAddressCache[$normalizedCrimeStreetName];
                            if (!empty($cachedAddressesOnStreet)) {
                                $foundMatchInCache = false;
                                if (isset($parsedCrimeStreet['number']) && is_numeric($parsedCrimeStreet['number'])) {
                                    $crimeStreetNum = (int)$parsedCrimeStreet['number'];
                                    $closestMatch = null;
                                    $minDifference = PHP_INT_MAX;

                                    foreach ($cachedAddressesOnStreet as $cachedAddr) {
                                        $difference = abs($crimeStreetNum - $cachedAddr['number']);
                                        if ($difference < $minDifference) {
                                            $minDifference = $difference;
                                            $closestMatch = $cachedAddr;
                                        } elseif ($difference === $minDifference && $closestMatch && $cachedAddr['number'] < $closestMatch['number']) {
                                            $closestMatch = $cachedAddr; // Prefer lower number in case of tie
                                        }
                                    }
                                    if ($closestMatch) {
                                        $lat = (string)$closestMatch['latitude'];
                                        $long = (string)$closestMatch['longitude'];
                                        $foundMatchInCache = true;
                                        $this->command->comment("Geocoded INCIDENT_NUMBER {$crime['INCIDENT_NUMBER']} ('{$rawStreetFromCrime}') using closest number match from cache: {$lat},{$long}");
                                    }
                                }

                                if (!$foundMatchInCache) { // Fallback to first address on street if no number or no exact match
                                    $firstAddress = $cachedAddressesOnStreet[0];
                                    $lat = (string)$firstAddress['latitude'];
                                    $long = (string)$firstAddress['longitude'];
                                    $this->command->comment("Geocoded INCIDENT_NUMBER {$crime['INCIDENT_NUMBER']} ('{$rawStreetFromCrime}') using first address on street from cache: {$lat},{$long}");
                                }
                            } else {
                                 $this->command->comment("Street '{$normalizedCrimeStreetName}' for INCIDENT_NUMBER {$crime['INCIDENT_NUMBER']} found in cache keys, but no addresses listed.");
                            }
                        } else {
                             $this->command->comment("Street '{$normalizedCrimeStreetName}' for INCIDENT_NUMBER {$crime['INCIDENT_NUMBER']} ('{$rawStreetFromCrime}') not in address cache.");
                        }
                    } else {
                        $this->command->comment("Could not parse STREET '{$rawStreetFromCrime}' for INCIDENT_NUMBER {$crime['INCIDENT_NUMBER']}.");
                    }
                } else {
                    $this->command->comment("Empty STREET field for INCIDENT_NUMBER {$crime['INCIDENT_NUMBER']} with missing coords.");
                }
            }


            // Final check for Lat/Long before inserting
            if (empty(trim($lat)) || empty(trim($long)) || !is_numeric($lat) || !is_numeric($long) || (float)$lat == 0 || (float)$long == 0) {
                $reason = "invalid/missing lat/long";
                if ($attemptedFallback) {
                    $reason .= " (fallback failed or N/A)";
                }
                $this->command->warn("Skipping record {$crime['INCIDENT_NUMBER']} due to {$reason}. STREET: '{$crime['STREET']}'. Original Lat/Long: '{$crime['Lat']}'/'{$crime['Long']}'.");
                $skipped++;
                continue;
            }

            $occurred_on_date = $this->formatDate($crime['OCCURRED_ON_DATE']);

            $offenseCode = trim($crime['OFFENSE_CODE'] ?? '');
            $offenseCodeGroup = $this->offenseCodeGroups[$offenseCode] ?? $crime['OFFENSE_CODE_GROUP'] ?? 'Miscellaneous';

            $dataBatch[] = [
                'incident_number' => $crime['INCIDENT_NUMBER'],
                'offense_code' => $offenseCode,
                'offense_code_group' => $offenseCodeGroup,
                'offense_description' => $crime['OFFENSE_DESCRIPTION'],
                'district' => $crime['DISTRICT'],
                'reporting_area' => $crime['REPORTING_AREA'],
                'shooting' => ($crime['SHOOTING'] ?? '0') == '1' || ($crime['SHOOTING'] ?? 'N') == 'Y', // Handle '0'/'1' and 'N'/'Y'
                'occurred_on_date' => $occurred_on_date,
                'year' => $crime['YEAR'],
                'month' => $crime['MONTH'],
                'day_of_week' => trim($crime['DAY_OF_WEEK']),
                'hour' => $crime['HOUR'],
                'ucr_part' => $crime['UCR_PART'],
                'street' => $crime['STREET'],
                'lat' => (float)$lat,
                'long' => (float)$long,
                'location' => $crime['Location'], // Keep original Location field
                'language_code' => 'en-US', // Default language code
                'source_city' => 'Boston'
            ];

            if ($progress % self::BATCH_SIZE == 0) {
                $this->insertOrUpdateBatch($dataBatch);
                $dataBatch = []; // Reset the batch

                // Progress reporting
                $endTime = microtime(true);
                $timeTaken = $endTime - $startTime;
                $this->reportProgress($progress, $fileCount, $timeTaken);
                $startTime = microtime(true);
            }
        }

        // Process any remaining data
        if (!empty($dataBatch)) {
            $this->insertOrUpdateBatch($dataBatch);
        }

        $this->command->info("File processed: " . basename($file) . ". Skipped records: {$skipped}");
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
            'source_city'
        ]);
    }

    private function reportProgress($progress, $fileCount, $timeTaken)
    {
        $estimatedTimePerRecord = $timeTaken / self::BATCH_SIZE;
        $estimatedTimeRemainingFile = $estimatedTimePerRecord * ($fileCount - $progress);
        
        // Using $this->command->getOutput()->write() for more control if needed,
        // but $this->command->info() or comment() should be fine for simple lines.
        // For multi-line dynamic updates, direct output manipulation is better.
        // For now, let's simplify to standard command outputs.
        
        $this->command->info(
            sprintf(
                "%d records processed. Records remaining: %d. Time for last %d: %.2fs. Est. time remaining: %s.",
                $progress,
                ($fileCount - $progress),
                self::BATCH_SIZE,
                $timeTaken,
                $this->formatTime($estimatedTimeRemainingFile)
            )
        );
    }

    private function formatTime(float $timeInSeconds): string
    {
        $hours = floor($timeInSeconds / 3600);
        $minutes = floor(($timeInSeconds % 3600) / 60);
        $seconds = $timeInSeconds % 60;

        $formattedTime = [];
        if ($hours > 0) {
            $formattedTime[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        if ($minutes > 0 || $hours > 0) {
            $formattedTime[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        $formattedTime[] = round($seconds, 2) . ' second' . ($seconds != 1 ? 's' : '');

        return implode(', ', $formattedTime);
    }

    private function formatDate($date)
    {
        if (empty($date)) return null;
        // Strip timezone offset like +00 or -04:00
        $date = preg_replace('/\s*([+-]\d{2}(:\d{2})?|Z)$/', '', trim($date));

        try {
            // Try parsing common datetime format first
            $carbonDate = \Carbon\Carbon::parse($date);
            return $carbonDate->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Fallback for other potential date formats if Carbon::parse fails
            // Example: "m/d/Y H:i:s" or "Y-m-d H:i:s.u"
            try {
                // Attempt specific formats if known issues exist
                $carbonDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s.u', $date);
                 return $carbonDate->format('Y-m-d H:i:s');
            } catch (\Exception $e2) {
                 $this->command->warn("Could not parse date: '{$date}'. Error: " . $e->getMessage());
                return null;
            }
        }
    }
}
