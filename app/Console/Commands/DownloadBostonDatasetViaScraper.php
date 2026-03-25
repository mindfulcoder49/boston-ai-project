<?php

namespace App\Console\Commands;

use App\Support\OperationalSummaryLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File; // For directory operations

class DownloadBostonDatasetViaScraper extends Command
{
    protected $signature = 'app:download-boston-dataset-via-scraper {--names= : Comma-separated list of dataset names to download (e.g., "crime-incident-reports,building-permits")}';
    protected $description = 'Downloads specific or all datasets from Boston Open Data using an intermediary scraper service.';

    public function handle(): int
    {
        $config = config('boston_datasets');
        if (!$config || !isset($config['base_url']) || !isset($config['datasets'])) {
            $this->error('Boston datasets configuration is missing or invalid. Please check config/boston_datasets.php');
            return 1;
        }
        
        $baseUrl = $config['base_url'];
        $allDatasets = $config['datasets'];
        $selectedNames = $this->option('names');

        $datasetsToDownload = $allDatasets;

        if (!empty($selectedNames)) {
            $namesArray = array_map('trim', explode(',', $selectedNames));
            $datasetsToDownload = array_filter($allDatasets, function ($dataset) use ($namesArray) {
                return isset($dataset['name']) && in_array($dataset['name'], $namesArray);
            });

            if (count($datasetsToDownload) !== count($namesArray)) {
                $foundNames = array_column($datasetsToDownload, 'name');
                $notFoundNames = array_diff($namesArray, $foundNames);
                $this->warn('Could not find the following specified datasets in config: ' . implode(', ', $notFoundNames));
            }
        }

        if (empty($datasetsToDownload)) {
            $this->info('No Boston datasets to download based on the provided names or configuration.');
            return 0;
        }

        $this->info('Found ' . count($datasetsToDownload) . ' dataset(s) to download.');
        OperationalSummaryLogger::emit($this, $this->getName(), 'start', [
            'dataset_count' => count($datasetsToDownload),
        ]);

        $results = [
            'attempted' => 0,
            'downloaded' => 0,
            'failed' => 0,
            'bytes_written' => 0,
        ];

        foreach ($datasetsToDownload as $dataset) {
            if (!isset($dataset['resource_id']) || !isset($dataset['format']) || !isset($dataset['name'])) {
                $this->warn("Skipping invalid dataset entry: " . json_encode($dataset));
                continue;
            }
            $results['attempted']++;
            $downloadResult = $this->downloadDataset($baseUrl, $dataset['resource_id'], $dataset['format'], $dataset['name']);
            if ($downloadResult['success']) {
                $results['downloaded']++;
                $results['bytes_written'] += $downloadResult['bytes_written'];
            } else {
                $results['failed']++;
            }
        }

        $this->info('Boston datasets download via scraper attempted for selected datasets.');
        OperationalSummaryLogger::emit($this, $this->getName(), 'complete', $results, $results['failed'] > 0 ? 'warning' : 'info');
        return $results['failed'] > 0 ? 1 : 0;
    }

    protected function downloadDataset($baseUrl, $resourceId, $format, $name): array
    {
        $datasetUrl = "{$baseUrl}/{$resourceId}?format={$format}";
        $filename = $this->generateFilename($name, $format); 
        $destination = storage_path("app/{$filename}");

        $this->info("Attempting to download Boston dataset via scraper: {$name} from {$datasetUrl} to {$destination}...");

        $baseDatasetDir = storage_path("app/datasets");
        if (!File::isDirectory($baseDatasetDir)) {
            if (!File::makeDirectory($baseDatasetDir, 0775, true, true) && !File::isDirectory($baseDatasetDir)) {
                $this->error("Failed to create base directory: " . $baseDatasetDir);
                return ['success' => false, 'bytes_written' => 0, 'message' => 'Failed to create base dataset directory'];
            }
        }
        
        $directory = dirname($destination);
        if (!File::isDirectory($directory)) {
            if (!File::makeDirectory($directory, 0775, true, true) && !File::isDirectory($directory)) {
                $this->error("Failed to create directory: " . $directory);
                return ['success' => false, 'bytes_written' => 0, 'message' => 'Failed to create dataset directory'];
            }
        }

        $downloadResult = $this->downloadFileUsingScraper($datasetUrl, $destination);

        if ($downloadResult['success']) {
            $this->info("Downloaded Boston dataset via scraper: {$filename}");
            OperationalSummaryLogger::emit($this, $this->getName(), 'dataset_complete', [
                'dataset' => $name,
                'output_file' => $destination,
                'bytes_written' => $downloadResult['bytes_written'],
            ]);
            return [
                'success' => true,
                'bytes_written' => $downloadResult['bytes_written'],
                'output_file' => $destination,
            ];
        } else {
            $this->error("Failed to download Boston dataset via scraper: {$name} (Resource ID: {$resourceId}) from {$datasetUrl}");
            OperationalSummaryLogger::emit($this, $this->getName(), 'dataset_failed', [
                'dataset' => $name,
                'output_file' => $destination,
                'message' => $downloadResult['message'],
            ], 'error');
            return [
                'success' => false,
                'bytes_written' => 0,
                'output_file' => $destination,
                'message' => $downloadResult['message'],
            ];
        }
    }

    private function downloadFileUsingScraper(string $datasetUrl, string $destination): array
    {
        $scraperConfig = config('services.scraper_service');
        if (!$scraperConfig || empty($scraperConfig['base_url'])) {
            $this->error('Scraper service configuration is missing or invalid (base_url).');
            return ['success' => false, 'bytes_written' => 0, 'message' => 'Scraper configuration missing'];
        }

        $scraperEndpoint = rtrim($scraperConfig['base_url'], '/') . '/scrape_url';
        
        $headers = [
            'X-User-Id' => $scraperConfig['user_id'],
            'X-User-Name' => $scraperConfig['user_name'],
            'X-User-Role' => $scraperConfig['user_role'],
        ];
        
        $payload = [
            'url' => $datasetUrl,
            'wait' => (int)$scraperConfig['wait_seconds'],
            'url_type' => 'csv'
        ];

        try {
            $response = Http::withHeaders($headers)
                            ->timeout(600) // Increased timeout for potentially long scraping/download
                            ->post($scraperEndpoint, $payload);

            if (!$response->successful()) {
                $this->error("Scraper service request for {$datasetUrl} failed. Status: {$response->status()}. Body: " . $response->body());
                return ['success' => false, 'bytes_written' => 0, 'message' => 'Scraper request failed with status ' . $response->status()];
            }

            $fileContents = $response->body();

            if (empty($fileContents)) {
                $this->error("Downloaded file from scraper for {$datasetUrl} is empty.");
                return ['success' => false, 'bytes_written' => 0, 'message' => 'Downloaded file is empty'];
            }

            if (File::put($destination, $fileContents) === false) {
                $this->error("Failed to write file to {$destination}. Check permissions and path.");
                return ['success' => false, 'bytes_written' => 0, 'message' => 'Failed to write downloaded file'];
            }
            
            return ['success' => true, 'bytes_written' => strlen($fileContents), 'message' => null];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->error("HTTP Request Exception while contacting scraper for {$datasetUrl}: " . $e->getMessage());
            return ['success' => false, 'bytes_written' => 0, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->error("Error downloading file via scraper from {$datasetUrl}: " . $e->getMessage());
            return ['success' => false, 'bytes_written' => 0, 'message' => $e->getMessage()];
        }
    }
    
    protected function generateFilename($name, $format)
    {
        $timestamp = now()->format('Ymd_His');
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        return "datasets/{$safeName}_{$timestamp}.{$format}";
    }
}
