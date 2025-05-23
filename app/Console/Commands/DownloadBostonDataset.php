<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
// Illuminate\Support\Facades\Storage; // Not strictly needed if using file_put_contents with full paths

class DownloadBostonDataset extends Command
{
    protected $signature = 'app:download-boston-dataset';
    protected $description = 'Downloads datasets specifically from Boston Open Data using boston_datasets.php config';

    public function handle()
    {
        // Load the configuration from the boston_datasets.php config file
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

        $this->info('Boston datasets download attempted.');
        return 0;
    }

    protected function downloadDataset($baseUrl, $resourceId, $format, $name)
    {
        $url = "{$baseUrl}/{$resourceId}?format={$format}";
        // Original filename generation, without city subfolder
        $filename = $this->generateFilename($name, $format); 
        $destination = storage_path("app/{$filename}");

        $this->info("Attempting to download Boston dataset: {$name} from {$url} to {$destination}...");

        // Ensure the base 'datasets' directory exists
        $baseDatasetDir = storage_path("app/datasets");
        if (!is_dir($baseDatasetDir)) {
            if (!mkdir($baseDatasetDir, 0775, true) && !is_dir($baseDatasetDir)) {
                $this->error("Failed to create base directory: " . $baseDatasetDir);
                return;
            }
        }
        // Ensure the specific destination directory exists (in case $filename includes subdirectories, though original doesn't)
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                $this->error("Failed to create directory: " . $directory);
                return;
            }
        }


        if ($this->downloadFile($url, $destination)) {
            $this->info("Downloaded Boston dataset: {$filename}");
        } else {
            $this->error("Failed to download Boston dataset: {$name} (Resource ID: {$resourceId}) from {$url}");
        }
    }

    private function downloadFile(string $url, string $destination): bool
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 600); 
    
            $fileContents = curl_exec($ch);
    
            if (curl_errno($ch)) {
                $this->error("cURL error for {$url}: " . curl_error($ch));
                curl_close($ch);
                return false;
            }
    
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch); 
    
            if ($httpCode !== 200) {
                $this->error("HTTP request for {$url} failed with status code: {$httpCode}");
                return false;
            }
    
            if (empty($fileContents)) {
                $this->error("Downloaded file from {$url} is empty.");
                return false;
            }
    
            if (file_put_contents($destination, $fileContents) === false) {
                $this->error("Failed to write file to {$destination}. Check permissions and path.");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("Error downloading file from {$url}: " . $e->getMessage());
            if (isset($ch) && is_resource($ch)) {
                 curl_close($ch);
            }
            return false;
        }
    }
    
    protected function generateFilename($name, $format)
    {
        $timestamp = now()->format('Ymd_His');
        // Original path: datasets/name_timestamp.format
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        return "datasets/{$safeName}_{$timestamp}.{$format}";
    }
}
