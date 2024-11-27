<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DownloadBostonDataset extends Command
{
    protected $signature = 'app:download-boston-dataset';
    protected $description = 'Downloads datasets from Boston Open Data using CKAN datastore/dump endpoint';

    public function handle()
    {
        // Load configuration
        $config = config('datasets');
        $baseUrl = 'http://data.boston.gov/datastore/dump';
        $datasets = $config['datasets'];
        $proxies = [
            'http://44.218.183.55:80', // Add more proxies as needed
        ];

        foreach ($datasets as $dataset) {
            foreach ($proxies as $proxy) {
                if ($this->fetchDataset($baseUrl, $dataset['resource_id'], $dataset['name'], $proxy)) {
                    break; // Stop trying proxies if the download is successful
                }
            }
        }

        $this->info('Datasets download attempted.');
    }

    protected function fetchDataset($baseUrl, $resourceId, $name, $proxy)
    {
        $url = "{$baseUrl}/{$resourceId}";
        $filename = $this->generateFilename($name, 'csv');
        $destination = storage_path("app/{$filename}");

        $this->info("Attempting to download dataset: {$name} using resource_id: {$resourceId} with proxy: {$proxy}...");

        try {
            $client = new Client([
                'proxy' => $proxy,
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ],
            ]);

            // Send the GET request
            $response = $client->get($url);

            if ($response->getStatusCode() !== 200) {
                $this->error("HTTP request failed with status code: " . $response->getStatusCode());
                return false;
            }

            $fileContents = $response->getBody()->getContents();

            if (empty($fileContents)) {
                $this->error("Downloaded file is empty.");
                return false;
            }

            file_put_contents($destination, $fileContents);
            $this->info("File downloaded successfully to: {$destination}");
            return true;
        } catch (RequestException $e) {
            $this->error("HTTP request error with proxy {$proxy}: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->error("Error downloading the file with proxy {$proxy}: " . $e->getMessage());
            return false;
        }
    }

    protected function generateFilename($name, $format)
    {
        $timestamp = now()->format('Ymd_His');
        return "datasets/{$name}_{$timestamp}.{$format}";
    }
}
