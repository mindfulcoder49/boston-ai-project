<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File; // For directory operations

class DownloadBostonDatasetViaScraper extends Command
{
    protected $signature = 'app:download-boston-dataset-via-scraper';
    protected $description = 'Downloads datasets from Boston Open Data using an intermediary scraper service.';

    public function handle()
    {
        $config = config('boston_datasets');
        if (!$config || !isset($config['base_url']) || !isset($config['datasets'])) {
            $this->error('Boston datasets configuration is missing or invalid. Please check config/boston_datasets.php');
            return 1;
        }
        
        $baseUrl = $config['base_url'];
        $datasets = $config['datasets'];

        if (empty($datasets)) {
            $this->info('No Boston datasets configured in config/boston_datasets.php');
            return 0;
        }

        foreach ($datasets as $dataset) {
            if (!isset($dataset['resource_id']) || !isset($dataset['format']) || !isset($dataset['name'])) {
                $this->warn("Skipping invalid dataset entry: " . json_encode($dataset));
                continue;
            }
            $this->downloadDataset($baseUrl, $dataset['resource_id'], $dataset['format'], $dataset['name']);
        }

        $this->info('Boston datasets download via scraper attempted.');
        return 0;
    }

    protected function downloadDataset($baseUrl, $resourceId, $format, $name)
    {
        $datasetUrl = "{$baseUrl}/{$resourceId}?format={$format}";
        $filename = $this->generateFilename($name, $format); 
        $destination = storage_path("app/{$filename}");

        $this->info("Attempting to download Boston dataset via scraper: {$name} from {$datasetUrl} to {$destination}...");

        $baseDatasetDir = storage_path("app/datasets");
        if (!File::isDirectory($baseDatasetDir)) {
            if (!File::makeDirectory($baseDatasetDir, 0775, true, true) && !File::isDirectory($baseDatasetDir)) {
                $this->error("Failed to create base directory: " . $baseDatasetDir);
                return;
            }
        }
        
        $directory = dirname($destination);
        if (!File::isDirectory($directory)) {
            if (!File::makeDirectory($directory, 0775, true, true) && !File::isDirectory($directory)) {
                $this->error("Failed to create directory: " . $directory);
                return;
            }
        }

        if ($this->downloadFileUsingScraper($datasetUrl, $destination)) {
            $this->info("Downloaded Boston dataset via scraper: {$filename}");
        } else {
            $this->error("Failed to download Boston dataset via scraper: {$name} (Resource ID: {$resourceId}) from {$datasetUrl}");
        }
    }

    private function downloadFileUsingScraper(string $datasetUrl, string $destination): bool
    {
        $scraperConfig = config('services.scraper_service');
        if (!$scraperConfig || empty($scraperConfig['base_url'])) {
            $this->error('Scraper service configuration is missing or invalid (base_url).');
            return false;
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
                return false;
            }

            $fileContents = $response->body();

            if (empty($fileContents)) {
                $this->error("Downloaded file from scraper for {$datasetUrl} is empty.");
                return false;
            }

            if (File::put($destination, $fileContents) === false) {
                $this->error("Failed to write file to {$destination}. Check permissions and path.");
                return false;
            }
            
            return true;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->error("HTTP Request Exception while contacting scraper for {$datasetUrl}: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->error("Error downloading file via scraper from {$datasetUrl}: " . $e->getMessage());
            return false;
        }
    }
    
    protected function generateFilename($name, $format)
    {
        $timestamp = now()->format('Ymd_His');
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        return "datasets/{$safeName}_{$timestamp}.{$format}";
    }
}
