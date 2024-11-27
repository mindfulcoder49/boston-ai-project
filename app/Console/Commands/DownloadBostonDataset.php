<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DownloadBostonDataset extends Command
{
    protected $signature = 'app:download-boston-dataset';
    protected $description = 'Downloads datasets from Boston Open Data using CKAN API';

    public function handle()
    {
        // Load configuration
        $config = config('datasets');
        $baseUrl = 'http://data.boston.gov/api/3/action/datastore_search';
        $datasets = $config['datasets'];
        $apiKey = config('services.bostongov.api_key');

        foreach ($datasets as $dataset) {
            $this->fetchDataset($baseUrl, $dataset['resource_id'], $dataset['name'], $apiKey);
        }

        $this->info('Datasets download attempted.');
    }

    protected function fetchDataset($baseUrl, $resourceId, $name, $apiKey)
    {
        $url = $baseUrl;
        $filename = $this->generateFilename($name, 'json');
        $destination = storage_path("app/{$filename}");
        $proxy = 'http://44.218.183.55:80';

        $this->info("Attempting to download dataset: {$name} using resource_id: {$resourceId}...");

        try {
            $client = new Client([
                'proxy' => $proxy,
                'timeout' => 30,
                'headers' => [
                    'Authorization' => $apiKey,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ],
            ]);

            // Prepare the POST data payload
            $postData = [
                'json' => [
                    'resource_id' => $resourceId,
                    'limit' => 1000,
                ],
            ];

            // Send the request
            $response = $client->post($url, $postData);

            if ($response->getStatusCode() !== 200) {
                $this->error("HTTP request failed with status code: " . $response->getStatusCode());
                return;
            }

            $fileContents = $response->getBody()->getContents();

            if (empty($fileContents)) {
                $this->error("Downloaded file is empty.");
                return;
            }

            file_put_contents($destination, $fileContents);
            $this->info("File downloaded successfully to: {$destination}");
        } catch (RequestException $e) {
            $this->error("HTTP request error: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->error("Error downloading the file: " . $e->getMessage());
        }
    }

    protected function generateFilename($name, $format)
    {
        $timestamp = now()->format('Ymd_His');
        return "datasets/{$name}_{$timestamp}.{$format}";
    }
}
