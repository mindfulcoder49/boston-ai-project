<?php

namespace App\Console\Commands;

use App\Models\CambridgeMasterIntersection;
use App\Models\PersonCrashData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateIntersectionCrashReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:intersections-crashes {--distance=50 : The distance in meters to search for crashes around an intersection.} {--filters= : A JSON string of filters to apply to the crash data (e.g., \'{"year": 2023}\').}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a CSV report of Cambridge intersections and the number of crashes within a given distance.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $distance = (int) $this->option('distance');
        if ($distance <= 0) {
            $this->error('Distance must be a positive integer.');
            return Command::FAILURE;
        }

        $filtersJson = $this->option('filters');
        $filters = [];
        if ($filtersJson) {
            $filters = json_decode($filtersJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON provided for --filters option: ' . json_last_error_msg());
                return Command::FAILURE;
            }
            $this->info('Applying the following crash filters: ' . $filtersJson);
        }

        if ($this->getOutput()->isVerbose()) {
            $this->info("--- Debugging Started (Verbose Mode) ---");
            $cities = PersonCrashData::where('city_town_name', 'LIKE', '%CAMBRIDGE%')
                ->distinct()->pluck('city_town_name');
            if ($cities->isEmpty()) {
                $this->warn("Could not find any records with city_town_name like 'CAMBRIDGE'.");
            } else {
                $this->comment("Found city names like 'CAMBRIDGE': " . $cities->implode(', '));
            }
        }

        $this->info("Analyzing person_crash_data for Cambridge...");
        $query = PersonCrashData::where('city_town_name', 'CAMBRIDGE');
        $totalCambridgeRecords = (clone $query)->count();
        $geocodedCambridgeRecords = (clone $query)->whereNotNull('location')->count();

        $this->info("Total person-crash records in Cambridge: {$totalCambridgeRecords}.");
        $this->info("Geocoded records in Cambridge available for search: {$geocodedCambridgeRecords}.");

        if ($geocodedCambridgeRecords === 0) {
            $this->error("No geocoded crash data available for Cambridge. Cannot generate report.");
            return Command::FAILURE;
        }

        $this->info("Generating crash report for intersections within {$distance} meters...");

        $intersections = CambridgeMasterIntersection::all();
        $totalIntersections = $intersections->count();

        if ($totalIntersections === 0) {
            $this->error("No intersections found in the cambridge_intersections table.");
            return Command::FAILURE;
        }
        $this->info("Found {$totalIntersections} intersections to process.");

        $progressBar = $this->output->createProgressBar($totalIntersections);

        $reportData = [];
        $reportData[] = ['Intersection ID', 'Intersection Name', 'Latitude', 'Longitude', "Unique Crashes within {$distance}m"];

        foreach ($intersections as $intersection) {
            if ($this->getOutput()->isVerbose()) {
                $this->line("\nProcessing Intersection: {$intersection->intersection} ({$intersection->nodenumber})");
            }

            if (is_null($intersection->latitude) || is_null($intersection->longitude)) {
                $crashCount = 'N/A (No Lat/Lon)';
                if ($this->getOutput()->isVerbose()) {
                    $this->warn("Skipping due to missing coordinates.");
                }
            } else {
                // Swapped longitude/latitude to match location data
                $pointText = "POINT({$intersection->longitude} {$intersection->latitude})";

                // Using DB::raw for the geometry function is more reliable with parameter binding.
                $crashQuery = PersonCrashData::where('city_town_name', 'CAMBRIDGE')
                    ->whereRaw(
                        'ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?',
                        [$pointText, $distance]
                    );

                // Apply additional filters from the command option
                if (!empty($filters)) {
                    foreach ($filters as $column => $value) {
                        if (is_array($value)) {
                            $crashQuery->whereIn($column, $value);
                        } else {
                            $crashQuery->where($column, $value);
                        }
                    }
                }

                if ($this->getOutput()->isVeryVerbose()) {
                    $sql = $crashQuery->toSql();
                    $bindings = $crashQuery->getBindings();
                    // Replace placeholders with actual values for readability
                    $fullSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
                    $this->comment("SQL Query: " . $fullSql);
                }

                $crashCount = (clone $crashQuery)->distinct('crash_numb')->count('crash_numb');
                
                if ($this->getOutput()->isVerbose()) {
                    $this->comment("Found {$crashCount} unique crashes.");
                }
            }

            $reportData[] = [
                $intersection->nodenumber,
                $intersection->intersection,
                $intersection->latitude,
                $intersection->longitude,
                $crashCount,
            ];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nReport data compiled. Saving to CSV...");

        $baseFilename = 'intersection_crash_report_' . now()->format('Ymd_His');
        $csvFilename = 'reports/' . $baseFilename . '.csv';
        Storage::makeDirectory('reports');
        
        $fp = fopen(Storage::path($csvFilename), 'w');
        foreach ($reportData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $this->info("Report successfully saved to: " . storage_path('app/' . $csvFilename));

        // Create the metadata file
        $description = "A report analyzing the number of unique vehicle crashes within {$distance} meters of each intersection in Cambridge.";
        if (!empty($filters)) {
            $description .= " Filters applied: " . json_encode($filters);
        }

        $metadata = [
            'filename' => $baseFilename . '.csv',
            'name' => 'Cambridge Intersection Crash Analysis',
            'description' => $description,
            'generated_at' => now()->toIso8601String(),
            'default_filters' => [
                'Unique_Crashes_within_50m_min' => 0,
                'clusterRadius' => 0,
            ],
        ];

        $jsonFilename = 'reports/' . $baseFilename . '.json';
        Storage::put($jsonFilename, json_encode($metadata, JSON_PRETTY_PRINT));

        $this->info("Metadata file saved to: " . storage_path('app/' . $jsonFilename));

        return Command::SUCCESS;
    }
}
