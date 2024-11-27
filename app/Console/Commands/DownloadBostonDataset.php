<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DownloadBostonDataset extends Command
{
    protected $signature = 'app:download-boston-dataset';
    protected $description = 'Downloads datasets from Boston Open Data';

    public function handle()
    {
        // Load the configuration from the config file
        $config = config('datasets');
        $baseUrl = $config['base_url'];
        $datasets = $config['datasets'];

        foreach ($datasets as $dataset) {
            $this->downloadDataset($baseUrl, $dataset['resource_id'], $dataset['format'], $dataset['name']);
        }

        $this->info('Datasets download attempted.');
    }

    protected function downloadDataset($baseUrl, $resourceId, $format, $name)
    {
        $url = "{$baseUrl}/{$resourceId}?format={$format}";
        $filename = $this->generateFilename($name, $format);
        $destination = storage_path("app/{$filename}");

        $this->info("Attempting to download dataset: {$name} from {$url}...");

        // Download the dataset file
        if ($this->downloadFile($url, $destination)) {
            $this->info("Downloaded {$filename}");
        } else {
            $this->error("Failed to download dataset: {$resourceId}");
        }
    }

    /**
     * Download the file from the URL.
     * 
     * @param string $url
     * @param string $destination
     * @return bool
     */
    private function downloadFile(string $url, string $destination): bool
{
    try {
        $client = new Client([
            'timeout' => 30, // Set a timeout for the request
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ],
        ]);

        // Make the request
        $response = $client->get($url);

        // Check HTTP status code
        if ($response->getStatusCode() !== 200) {
            $this->error("HTTP request failed with status code: " . $response->getStatusCode());
            return false;
        }

        // Get the file contents
        $fileContents = $response->getBody()->getContents();

        // Check if content is valid
        if (empty($fileContents)) {
            $this->error("Downloaded file is empty.");
            return false;
        }

        // Save file contents to the destination
        file_put_contents($destination, $fileContents);

        $this->info("File downloaded successfully to: {$destination}");
        return true;
    } catch (RequestException $e) {
        $this->error("HTTP request error: " . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        $this->error("Error downloading the file: " . $e->getMessage());
        return false;
    }
}
    
    

    protected function generateFilename($name, $format)
    {
        $timestamp = now()->format('Ymd_His');
        return "datasets/{$name}_{$timestamp}.{$format}";
    }
}
