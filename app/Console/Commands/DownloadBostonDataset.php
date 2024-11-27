<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DownloadBostonDataset extends Command
{
    protected $signature = 'app:download-boston-dataset';
    protected $description = 'Downloads datasets from Boston Open Data using CKAN Data API';

    public function handle()
    {
        // Load the configuration from the config file
        $config = config('datasets');
        $baseUrl = $config['base_url'];
        $datasets = $config['datasets'];

        foreach ($datasets as $dataset) {
            $this->downloadDataset($baseUrl, $dataset['resource_id'], $dataset['name']);
        }

        $this->info('Datasets download attempted.');
    }

    protected function downloadDataset($baseUrl, $resourceId, $name)
    {
        $filename = $this->generateFilename($name, 'json'); // Save as JSON
        $destination = storage_path("app/{$filename}");

        $this->info("Attempting to download dataset: {$name} using resource_id: {$resourceId}...");

        // Download the dataset file
        if ($this->fetchDataset($resourceId, $destination)) {
            $this->info("Downloaded {$filename}");
        } else {
            $this->error("Failed to download dataset: {$resourceId}");
        }
    }

    /**
     * Fetch dataset using CKAN Data API.
     * 
     * @param string $resourceId
     * @param string $destination
     * @return bool
     */
    private function fetchDataset(string $resourceId, string $destination): bool
    {
        $apiUrl = "https://data.boston.gov/api/3/action/datastore_search?resource_id={$resourceId}&limit=1000"; // Adjust limit as needed

        try {
            $client = new Client([
                'timeout' => 30, // Set a timeout for the request
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ],
            ]);

            // Make the request
            $response = $client->get($apiUrl);

            // Check HTTP status code
            if ($response->getStatusCode() !== 200) {
                $this->error("HTTP request failed with status code: " . $response->getStatusCode());
                return false;
            }

            // Parse response body
            $data = json_decode($response->getBody(), true);

            // Check if the dataset contains records
            if (!isset($data['result']['records']) || empty($data['result']['records'])) {
                $this->error("No records found for resource_id: {$resourceId}");
                return false;
            }

            // Save the records as JSON
            file_put_contents($destination, json_encode($data['result']['records'], JSON_PRETTY_PRINT));

            $this->info("Dataset successfully fetched and stored at: {$destination}");
            return true;
        } catch (RequestException $e) {
            $this->error("HTTP request error: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->error("Error fetching the dataset: " . $e->getMessage());
            return false;
        }
    }

    protected function generateFilename($name, $format)
    {
        $timestamp = now()->format('Ymd_His');
        return "datasets/{$name}_{$timestamp}.{$format}";
    }
}
