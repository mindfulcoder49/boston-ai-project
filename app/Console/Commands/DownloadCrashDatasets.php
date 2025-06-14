<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File; // For directory operations
use Illuminate\Support\Facades\Storage; // For progress file
use Carbon\Carbon;

class DownloadCrashDatasets extends Command
{
    protected $signature = 'app:download-massdot-crash-data';
    protected $description = 'Downloads MassDOT crash data for specified years (2022-2025) in GeoJSON format with recovery.';

    private const BASE_URL_TEMPLATE = 'https://gis.crashdata.dot.mass.gov/arcgis/rest/services/MassDOT/MASSDOT_ODP_OPEN_{YEAR}/FeatureServer/2';
    private const YEARS_TO_FETCH = [2022, 2023, 2024, 2025];
    private const PAGE_SIZE = 2000; // Records per request
    private const PROGRESS_DIR = 'job_progress';
    private const PROGRESS_FILE = self::PROGRESS_DIR . '/DownloadCrashDatasets_progress.json';

    public function __construct()
    {
        parent::__construct();
    }

    private function getProgress(): array
    {
        if (!Storage::exists(self::PROGRESS_FILE)) {
            return [];
        }
        $content = Storage::get(self::PROGRESS_FILE);
        $progress = json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE ? $progress : [];
    }

    private function saveProgress(array $progress)
    {
        if (!Storage::exists(self::PROGRESS_DIR)) {
            Storage::makeDirectory(self::PROGRESS_DIR);
        }
        Storage::put(self::PROGRESS_FILE, json_encode($progress, JSON_PRETTY_PRINT));
    }

    public function handle()
    {
        $this->info('Starting MassDOT crash dataset download process...');
        $overallProgress = $this->getProgress();

        foreach (self::YEARS_TO_FETCH as $year) {
            $yearKey = "year_{$year}";
            if (isset($overallProgress[$yearKey]) && ($overallProgress[$yearKey]['status'] ?? '') === 'completed') {
                $this->info("Year {$year} already marked as completed. Skipping.");
                continue;
            }
            $this->downloadYearData($year, self::BASE_URL_TEMPLATE, $overallProgress);
        }

        $this->info('All MassDOT crash dataset downloads attempted.');
        // Clean up progress file if all years are completed, or leave it for inspection
        $allCompleted = true;
        $currentProgress = $this->getProgress(); // Re-fetch progress
        foreach (self::YEARS_TO_FETCH as $year) {
            if (($currentProgress["year_{$year}"]['status'] ?? '') !== 'completed') {
                $allCompleted = false;
                break;
            }
        }
        if ($allCompleted) {
            $this->info('All years successfully downloaded and finalized. Clearing progress file.');
            Storage::delete(self::PROGRESS_FILE);
        }
        return 0;
    }

    protected function downloadYearData(int $year, string $baseUrlTemplate, array &$overallProgress)
    {
        $this->info("Processing download for year {$year}...");
        $serviceUrl = str_replace('{YEAR}', (string)$year, $baseUrlTemplate);
        $yearKey = "year_{$year}";

        $currentOffset = 0;
        $featuresWrittenThisYear = 0;
        $destinationPath = '';
        $isResuming = false;

        if (isset($overallProgress[$yearKey]) && !empty($overallProgress[$yearKey]['filepath'])) {
            $progressData = $overallProgress[$yearKey];
            $currentOffset = $progressData['offset'] ?? 0;
            $featuresWrittenThisYear = $progressData['features_written_in_file'] ?? 0;
            $destinationPath = $progressData['filepath'];
            $isResuming = true;
            $this->info("Resuming download for year {$year} from offset {$currentOffset}. Features already written: {$featuresWrittenThisYear}. File: {$destinationPath}");
        } else {
            $destinationDir = storage_path("app/datasets/massachusetts/geojson/{$year}");
            if (!File::isDirectory($destinationDir)) {
                File::makeDirectory($destinationDir, 0775, true, true);
            }
            $timestamp = now()->format('Ymd_His');
            $filename = "crash_data_{$year}_{$timestamp}.geojson";
            $destinationPath = "{$destinationDir}/{$filename}";
            $this->info("Starting fresh download for year {$year} to {$destinationPath}");
        }
        
        // Ensure directory for progress file exists
        $storageProgressDir = storage_path('app/' . self::PROGRESS_DIR);
        if (!File::isDirectory($storageProgressDir)) {
            File::makeDirectory($storageProgressDir, 0775, true, true);
        }


        $fileHandle = @fopen($destinationPath, $isResuming ? 'a' : 'w'); // 'a' for append if resuming
        if (!$fileHandle) {
            $this->error("Failed to open file for writing: {$destinationPath}");
            // Update progress to reflect failure to open file if needed, or just error out
            $overallProgress[$yearKey]['status'] = 'error_opening_file';
            $this->saveProgress($overallProgress);
            return;
        }

        if (!$isResuming) {
            fwrite($fileHandle, '{"type":"FeatureCollection","crs":{"type":"name","properties":{"name":"urn:ogc:def:crs:OGC:1.3:CRS84"}},"features":[');
            $featuresWrittenThisYear = 0; // Reset for fresh start
        }
        
        // Initial progress save for new downloads or if resuming (to confirm file is openable)
        $overallProgress[$yearKey] = [
            'filepath' => $destinationPath,
            'offset' => $currentOffset,
            'features_written_in_file' => $featuresWrittenThisYear,
            'status' => 'in_progress',
            'last_updated' => now()->toIso8601String(),
        ];
        $this->saveProgress($overallProgress);

        $totalFeaturesDownloadedThisSession = 0;

        while (true) {
            $queryParams = http_build_query([
                'where' => '1=1',
                'outFields' => '*',
                'f' => 'geojson',
                'resultOffset' => $currentOffset,
                'resultRecordCount' => self::PAGE_SIZE,
                'orderByFields' => 'OBJECTID ASC'
            ]);
            $url = "{$serviceUrl}/query?{$queryParams}";

            $this->line("Fetching from: {$url}");
            $responseJson = $this->fetchArcGisPageContent($url);

            if ($responseJson === false) {
                $this->error("Failed to fetch data for year {$year} at offset {$currentOffset}. Stopping for this year. Progress saved.");
                $overallProgress[$yearKey]['status'] = 'error_fetching';
                $overallProgress[$yearKey]['last_error_offset'] = $currentOffset;
                $this->saveProgress($overallProgress);
                fclose($fileHandle);
                return;
            }

            $data = json_decode($responseJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("JSON decode error for year {$year} at offset {$currentOffset}: " . json_last_error_msg() . ". Stopping for this year. Progress saved.");
                $overallProgress[$yearKey]['status'] = 'error_json_decode';
                $this->saveProgress($overallProgress);
                fclose($fileHandle);
                return;
            }

            $apiFeatures = $data['features'] ?? [];
            $numApiFeatures = count($apiFeatures);
            $totalFeaturesDownloadedThisSession += $numApiFeatures;

            if ($numApiFeatures > 0) {
                foreach ($apiFeatures as $feature) {
                    if ($featuresWrittenThisYear > 0) { // If any feature is already in the file (either from resume or previous in this batch)
                        fwrite($fileHandle, ',');
                    }
                    fwrite($fileHandle, json_encode($feature));
                    $featuresWrittenThisYear++;
                }
            }
            
            $currentOffset += $numApiFeatures; // ArcGIS offset is based on records processed by the server

            // Update progress after each successful batch write
            $overallProgress[$yearKey]['offset'] = $currentOffset;
            $overallProgress[$yearKey]['features_written_in_file'] = $featuresWrittenThisYear;
            $overallProgress[$yearKey]['last_updated'] = now()->toIso8601String();
            $this->saveProgress($overallProgress);

            $this->info("Fetched {$numApiFeatures} features for year {$year}. Total written to file this year: {$featuresWrittenThisYear}. Next offset: {$currentOffset}");

            $exceededTransferLimit = $data['properties']['exceededTransferLimit'] ?? ($data['exceededTransferLimit'] ?? false);

            if ($numApiFeatures < self::PAGE_SIZE || ($exceededTransferLimit === false && $numApiFeatures > 0)) {
                 if ($exceededTransferLimit === true && $numApiFeatures === self::PAGE_SIZE) {
                    // Continue if exceededTransferLimit is true and we got a full page
                 } else {
                    $this->info("Last page fetched for year {$year} (or no transfer limit indication).");
                    break;
                 }
            }
            
            if ($numApiFeatures === 0) {
                $this->info("No more features found for year {$year} at offset {$currentOffset}.");
                break;
            }
            usleep(500000);
        }

        fwrite($fileHandle, ']}'); // Close GeoJSON features array and main object
        fclose($fileHandle);

        $overallProgress[$yearKey]['status'] = 'completed';
        $overallProgress[$yearKey]['final_feature_count'] = $featuresWrittenThisYear;
        $overallProgress[$yearKey]['completed_at'] = now()->toIso8601String();
        $this->saveProgress($overallProgress);

        $this->info("Successfully downloaded and finalized data for year {$year} to {$destinationPath}. Total features: {$featuresWrittenThisYear}");
    }

    protected function fetchArcGisPageContent(string $url): string|false
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Boston AI Project Downloader/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout for a single page request
            // ArcGIS services sometimes are slow, ensure SSL verification is robust
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);


            $responseContents = curl_exec($ch);

            if (curl_errno($ch)) {
                $this->error("cURL error for {$url}: " . curl_error($ch));
                curl_close($ch);
                return false;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                $this->error("HTTP request for {$url} failed with status code: {$httpCode}. Response: " . substr($responseContents, 0, 500));
                return false;
            }
            
            return $responseContents;

        } catch (\Exception $e) {
            $this->error("Exception during fetch from {$url}: " . $e->getMessage());
            if (isset($ch) && is_resource($ch)) {
                 curl_close($ch);
            }
            return false;
        }
    }
}
