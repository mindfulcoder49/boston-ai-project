<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage; // Not strictly needed if using file_put_contents with full paths

class DownloadCityDataset extends Command
{
    protected $signature = 'app:download-city-dataset';
    protected $description = 'Downloads datasets from configured cities (e.g., Boston, Cambridge)';

    public function handle()
    {
        $config = config('datasets');
        $datasets = $config['datasets'];

        if (empty($datasets)) {
            $this->info('No datasets configured. Please check config/datasets.php');
            return 0;
        }

        foreach ($datasets as $dataset) {
            $this->downloadDataset(
                $dataset['base_url'],
                $dataset['resource_id'],
                $dataset['format'],
                $dataset['name'],
                $dataset['city'],
                $dataset['url_pattern_type'],
                $dataset['pagination_type'] ?? null,
                $dataset['page_size'] ?? 1000, // Default page_size if not specified
                $dataset['order_by_field'] ?? ':id' // Default order_by_field
            );
        }

        $this->info('All configured dataset downloads attempted.');
        return 0;
    }

    protected function downloadDataset($baseUrl, $resourceId, $format, $name, $city, $urlPatternType, $paginationType = null, $pageSize = 1000, $orderByField = ':id')
    {
        $filename = $this->generateFilename($name, $format, $city);
        $destination = storage_path("app/{$filename}");

        // Ensure the destination directory exists
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                $this->error("Failed to create directory: " . $directory);
                return;
            }
        }

        if ($paginationType === 'socrata_offset' && $urlPatternType === 'extension') {
            $this->info("Attempting paginated Socrata download for: {$name} ({$city}) to {$destination}...");
            if ($this->downloadSocrataDatasetWithPagination($baseUrl, $resourceId, $format, $destination, $pageSize, $orderByField, $name)) {
                $this->info("Successfully downloaded paginated dataset: {$filename}");
            } else {
                $this->error("Failed to download paginated Socrata dataset: {$name} ({$city})");
            }
        } else {
            // Standard non-paginated download
            $url = '';
            if ($urlPatternType === 'query_param') {
                $url = "{$baseUrl}/{$resourceId}?format={$format}";
            } elseif ($urlPatternType === 'extension') {
                $url = "{$baseUrl}/{$resourceId}.{$format}";
            } else {
                $this->error("Unknown URL pattern type '{$urlPatternType}' for dataset: {$name}");
                return;
            }
            $this->info("Attempting to download dataset: {$name} ({$city}) from {$url} to {$destination}...");
            if ($this->downloadSingleFile($url, $destination)) {
                $this->info("Successfully downloaded: {$filename}");
            } else {
                $this->error("Failed to download dataset: {$name} ({$city}) from {$url}");
            }
        }
    }

    protected function downloadSocrataDatasetWithPagination($baseUrl, $resourceId, $format, $destination, $pageSize, $orderByField, $datasetName)
    {
        $offset = 0;
        $firstPage = true;

        $fileHandle = @fopen($destination, 'w');
        if ($fileHandle === false) {
            $this->error("Could not open file for writing: {$destination}");
            return false;
        }

        while (true) {
            $url = "{$baseUrl}/{$resourceId}.{$format}?\$limit={$pageSize}&\$offset={$offset}&\$order={$orderByField}";
            $this->info("Fetching page for {$datasetName}: offset {$offset}, limit {$pageSize} from {$url}");

            $pageContent = $this->fetchSocrataPageContent($url);

            if ($pageContent === false) { // cURL error
                if ($firstPage) {
                    $this->error("Failed to download initial page for {$datasetName} from {$url}.");
                    fclose($fileHandle);
                    if (file_exists($destination)) unlink($destination);
                    return false;
                }
                $this->warn("Error fetching page for {$datasetName} at offset {$offset}. Assuming end of data or transient error.");
                break; // Stop on error after the first page
            }
            
            $trimmedContent = trim($pageContent);
            if (empty($trimmedContent)) { // Truly empty response
                 $this->info("Received empty content for {$datasetName} at offset {$offset}. Assuming end of data.");
                 break;
            }

            $lines = explode("\n", $trimmedContent);

            if ($firstPage) {
                fwrite($fileHandle, $pageContent); // Write the whole content including header
                $firstPage = false;
                // Number of data rows is total lines minus 1 (for header)
                // If only header, count is 0. If header + 1 data row, count is 1.
                $numDataRows = count($lines) -1; 
                if ($numDataRows < 0) $numDataRows = 0; // handles empty file case
            } else {
                // For subsequent pages, if only header is returned, or it's empty after trim
                if (count($lines) <= 1 && !empty($trimmedContent)) { 
                    $this->info("No new data rows for {$datasetName} at offset {$offset} (only header or empty). Assuming end of data.");
                    break;
                } else if (empty($trimmedContent)) { // Should have been caught by earlier empty check, but as safeguard
                    $this->info("Received empty content for {$datasetName} at offset {$offset} on subsequent page. Assuming end of data.");
                    break;
                }
                
                array_shift($lines); // Remove header
                if (!empty($lines)) {
                    fwrite($fileHandle, "\n" . implode("\n", $lines));
                }
                $numDataRows = count($lines);
            }
            
            // If the number of data rows returned is less than page size, it's the last page
            if ($numDataRows < $pageSize) {
                $this->info("Last page fetched for {$datasetName} (received {$numDataRows} data rows, page size {$pageSize}).");
                break;
            }

            $offset += $pageSize;
            // Small delay to be polite to the API
            usleep(250000); // 0.25 seconds
        }

        fclose($fileHandle);
        return true;
    }

    protected function fetchSocrataPageContent(string $url): string|false
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout for a single page

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
                // Potentially return the content anyway if it's an error page, or handle specific codes
                return false; 
            }
            
            return $fileContents;

        } catch (\Exception $e) {
            $this->error("Exception during fetch from {$url}: " . $e->getMessage());
            if (isset($ch) && is_resource($ch)) {
                 curl_close($ch);
            }
            return false;
        }
    }
    
    /**
     * Renamed from downloadFile to downloadSingleFile to differentiate from paginated downloads.
     * This method handles non-paginated, single URL downloads.
     */
    private function downloadSingleFile(string $url, string $destination): bool
    {
        try {
            // Directory creation is now handled in downloadDataset method
            // $directory = dirname($destination);
            // ... (directory creation logic removed as it's handled before calling this)

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutes timeout

            $fileContents = curl_exec($ch);

            if (curl_errno($ch)) {
                $this->error("cURL error for {$url}: " . curl_error($ch));
                curl_close($ch);
                return false;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch); // Close curl handle after getting info

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
            // Ensure curl handle is closed if an exception occurs before explicit close
            if (isset($ch) && is_resource($ch)) {
                 curl_close($ch);
            }
            return false;
        }
    }

    protected function generateFilename($name, $format, $city)
    {
        $timestamp = now()->format('Ymd_His');
        // Sanitize city and name for directory/filename usage if necessary, though current examples are fine.
        $safeCity = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $city));
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        return "datasets/{$safeCity}/{$safeName}_{$timestamp}.{$format}";
    }
}
