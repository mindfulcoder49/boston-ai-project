<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage; // Not strictly needed if using file_put_contents with full paths
use Illuminate\Support\Facades\DB;

class DownloadCityDataset extends Command
{
    protected $signature = 'app:download-city-dataset {dataset? : The name of a specific dataset to download.} {--resume-from= : The path to a partial CSV file to resume downloading.}';
    protected $description = 'Downloads datasets from configured cities (e.g., Boston, Cambridge), with resume support.';

    public function handle()
    {
        $resumeFile = $this->option('resume-from');

        if ($resumeFile) {
            return $this->handleResume($resumeFile);
        }

        return $this->handleNewDownload();
    }

    protected function handleNewDownload()
    {
        $datasetName = $this->argument('dataset');
        $config = config('datasets');
        $allDatasets = $config['datasets'];

        if (empty($allDatasets)) {
            $this->info('No datasets configured. Please check config/datasets.php');
            return 0;
        }

        $datasetsToDownload = $allDatasets;
        if ($datasetName) {
            $this->info("Looking for dataset '{$datasetName}' to download.");
            $datasetsToDownload = array_filter($allDatasets, function ($dataset) use ($datasetName) {
                return $dataset['name'] === $datasetName;
            });

            if (empty($datasetsToDownload)) {
                $this->error("Dataset '{$datasetName}' not found in configuration.");
                return 1;
            }
        }

        foreach ($datasetsToDownload as $dataset) {
            $whereClause = null;

            // Handle incremental downloads
            if (($dataset['download_type'] ?? 'full') === 'incremental') {
                $this->info("Processing incremental download for '{$dataset['name']}'.");
                $modelClass = $dataset['model'] ?? null;
                $dateField = $dataset['date_field'] ?? null;

                if (!$modelClass || !$dateField) {
                    $this->warn("Skipping incremental download for '{$dataset['name']}' due to missing 'model' or 'date_field' config.");
                    continue;
                }

                if (!class_exists($modelClass)) {
                    $this->warn("Model class '{$modelClass}' not found for dataset '{$dataset['name']}'. Performing a full download.");
                } else {
                    $model = new $modelClass();
                    $connection = $model->getConnectionName() ?? config('database.default');
                    
                    try {
                        $latestDate = DB::connection($connection)->table($model->getTable())->max($dateField);

                        if ($latestDate) {
                            // Socrata uses ISO 8601 format. Assuming the DB stores a compatible format.
                            $formattedDate = date('Y-m-d\TH:i:s', strtotime($latestDate));
                            $whereClause = "{$dateField} > '{$formattedDate}'";
                            $this->info("Found latest date: {$latestDate}. Resuming download with condition: {$whereClause}");
                        } else {
                            $this->info("No existing data found for '{$dataset['name']}'. Performing a full download.");
                        }
                    } catch (\Exception $e) {
                        $this->error("Could not query database for latest date on '{$dataset['name']}': " . $e->getMessage());
                        $this->info("Performing a full download instead.");
                    }
                }
            }

            $this->downloadDataset(
                $dataset['base_url'],
                $dataset['resource_id'],
                $dataset['format'],
                $dataset['name'],
                $dataset['city'],
                $dataset['url_pattern_type'],
                $dataset['pagination_type'] ?? null,
                $dataset['page_size'] ?? 1000, // Default page_size if not specified
                $dataset['order_by_field'] ?? ':id', // Default order_by_field
                $dataset['order_by_direction'] ?? 'ASC', // Default order_by_direction
                null, // destination
                0, // startOffset
                null, // startYear
                $whereClause // whereClause for incremental
            );
        }

        $this->info('All configured dataset downloads attempted.');
        return 0;
    }

    protected function handleResume(string $resumeFile)
    {
        $this->info("Attempting to resume download for: {$resumeFile}");

        if (!file_exists($resumeFile)) {
            $this->error("Resume file not found: {$resumeFile}");
            return 1;
        }

        // Extract dataset name from filename like '.../chicago-crimes-2001-to-present_20250831_191130.csv'
        preg_match('/([a-zA-Z0-9-]+)_\d{8}_\d{6}\.csv$/', basename($resumeFile), $matches);
        if (empty($matches[1])) {
            $this->error("Could not parse dataset name from filename. Expected format: 'name_YYYYMMDD_HHMMSS.csv'");
            return 1;
        }
        $datasetName = $matches[1];

        $config = config('datasets');
        $dataset = collect($config['datasets'])->firstWhere('name', $datasetName);

        if (!$dataset) {
            $this->error("No configuration found for dataset '{$datasetName}'.");
            return 1;
        }

        $this->info("Found configuration for '{$datasetName}'. Calculating resume offset...");

        $lineCount = 0;
        if (file_exists($resumeFile)) {
            $handle = fopen($resumeFile, 'r');
            if (!$handle) {
                $this->error("Could not open file to count lines: {$resumeFile}");
                return 1;
            }
            while (fgets($handle) !== false) {
                $lineCount++;
            }
            fclose($handle);
        }

        // Offset is the number of data rows already downloaded.
        // If there's a header, the number of data rows is line count - 1.
        // If the file is empty or has only a header, we start from 0.
        $startOffset = ($lineCount > 1) ? $lineCount - 1 : 0;
        $startYear = null;

        // For year-by-year, we need to figure out which year we were on.
        if (($dataset['pagination_type'] ?? null) === 'socrata_by_year') {
            $lastLine = '';
            if ($lineCount > 1) {
                $handle = fopen($resumeFile, 'r');
                // Go to the end of the file minus a buffer to get the last line
                fseek($handle, -4096, SEEK_END);
                $lastLine = trim(stream_get_contents($handle));
                $lastLines = explode("\n", $lastLine);
                $lastLine = end($lastLines);
                fclose($handle);
            }

            if ($lastLine) {
                $headerHandle = fopen($resumeFile, 'r');
                $header = fgetcsv($headerHandle);
                fclose($headerHandle);
                $lastRecord = str_getcsv($lastLine);
                $yearFieldIndex = array_search($dataset['year_field'], $header);
                if ($yearFieldIndex !== false && isset($lastRecord[$yearFieldIndex])) {
                    $startYear = (int)$lastRecord[$yearFieldIndex];
                    $this->info("Last record found was in year {$startYear}. Resuming from this year.");
                }
            }
        }


        if ($startOffset <= 0) {
            $this->info("File is new or contains no data rows. Starting download from the beginning.");
        } else {
            $this->info("Resuming from offset: {$startOffset} ({$lineCount} lines in file).");
        }

        // Call downloadDataset with the resume parameters
        $this->downloadDataset(
            $dataset['base_url'],
            $dataset['resource_id'],
            $dataset['format'],
            $dataset['name'],
            $dataset['city'],
            $dataset['url_pattern_type'],
            $dataset['pagination_type'] ?? null,
            $dataset['page_size'] ?? 1000,
            $dataset['order_by_field'] ?? ':id',
            $dataset['order_by_direction'] ?? 'ASC',
            $resumeFile, // Pass the destination file path
            $startOffset, // Pass the starting offset
            $startYear // Pass the starting year for year-based downloads
        );

        return 0;
    }

    protected function downloadDataset($baseUrl, $resourceId, $format, $name, $city, $urlPatternType, $paginationType = null, $pageSize = 1000, $orderByField = ':id', $orderByDirection = 'ASC', $destination = null, $startOffset = 0, $startYear = null, ?string $incrementalWhere = null)
    {
        // If no destination is provided (new download), generate a new filename.
        if ($destination === null) {
            $filename = $this->generateFilename($name, $format, $city);
            $destination = storage_path("app/{$filename}");
        } else {
            // When resuming, the filename is the full path to the existing file.
            $filename = basename($destination);
        }

        // Ensure the destination directory exists
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                $this->error("Failed to create directory: " . $directory);
                return;
            }
        }

        if ($paginationType === 'socrata_by_year') {
            $this->info("Attempting year-by-year Socrata download for: {$name} ({$city}) to {$destination}...");
            $config = collect(config('datasets.datasets'))->firstWhere('name', $name);
            if ($this->downloadSocrataByYear($config, $destination, $startOffset, $startYear, $incrementalWhere)) {
                $this->info("Successfully completed year-by-year download: {$filename}");
            } else {
                $this->error("Failed year-by-year Socrata download: {$name} ({$city})");
            }
        } elseif ($paginationType === 'socrata_offset' && $urlPatternType === 'extension') {
            $this->info("Attempting paginated Socrata download for: {$name} ({$city}) to {$destination}...");
            if ($this->downloadSocrataDatasetWithPagination($baseUrl, $resourceId, $format, $destination, $pageSize, $orderByField, $orderByDirection, $name, $startOffset, $incrementalWhere)) {
                $this->info("Successfully downloaded paginated dataset: {$filename}");
            } else {
                $this->error("Failed to download paginated Socrata dataset: {$name} ({$city})");
            }
        } else {
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

    protected function downloadSocrataByYear(array $config, string $destination, int $startOffset = 0, ?int $resumeYear = null, ?string $incrementalWhere = null)
    {
        $loopStartYear = $resumeYear ?? $config['start_year'];
        $endYear = $config['end_year'] ?? now()->year;
        $yearField = $config['year_field'];

        for ($year = $loopStartYear; $year <= $endYear; $year++) {
            $this->info("--- Starting download for year: {$year} ---");
            $whereClause = "{$yearField}={$year}";
            if ($incrementalWhere) {
                $whereClause .= " AND ({$incrementalWhere})";
            }

            // If resuming, the offset only applies to the first year of the resumed loop.
            $currentYearOffset = ($year === $loopStartYear) ? $startOffset : 0;

            $success = $this->downloadSocrataDatasetWithPagination(
                $config['base_url'],
                $config['resource_id'],
                $config['format'],
                $destination,
                $config['page_size'],
                $config['order_by_field'],
                $config['order_by_direction'],
                $config['name'] . " (Year {$year})",
                $currentYearOffset,
                $whereClause,
                $year === (int)$config['start_year'] // isFirstYearOfTotalDownload
            );

            if (!$success) {
                $this->error("Download failed for year {$year}. Stopping process. You can resume later.");
                return false;
            }
        }

        return true;
    }

    protected function downloadSocrataDatasetWithPagination($baseUrl, $resourceId, $format, $destination, $pageSize, $orderByField, $orderByDirection, $datasetName, $startOffset = 0, ?string $whereClause = null, bool $isFirstYearOfTotalDownload = true)
    {
        $offset = $startOffset;
        
        // Determine if the file is truly new or if we are just starting a new year in an existing file.
        $isNewFile = !file_exists($destination) || filesize($destination) === 0;
        $fileMode = $isNewFile ? 'w' : 'a';

        $fileHandle = @fopen($destination, $fileMode);
        if ($fileHandle === false) {
            $this->error("Could not open file for writing: {$destination}");
            return false;
        }

        $firstPage = true;

        while (true) {
            // Build URL with optional $where clause
            $url = "{$baseUrl}/{$resourceId}.json?\$limit={$pageSize}&\$offset={$offset}&\$order=" . urlencode("{$orderByField} {$orderByDirection}");
            if ($whereClause) {
                $url .= "&\$where=" . urlencode($whereClause);
            }
            $this->info("Fetching page for {$datasetName}: offset {$offset}, limit {$pageSize}");

            $pageContent = $this->fetchSocrataPageContent($url);

            if ($pageContent === false) {
                $this->warn("Error fetching page for {$datasetName} at offset {$offset}. Download stopped. You can resume later.");
                break;
            }

            $data = json_decode($pageContent, true);

            if (empty($data)) {
                $this->info("Received no data for {$datasetName} at offset {$offset}. Assuming end of data.");
                break;
            }

            // Only write header if it's a brand new file on the very first page.
            // For year-by-year, this ensures the header is only written once at the beginning.
            if ($isNewFile && $firstPage) {
                $headers = array_keys($data[0]);
                fputcsv($fileHandle, $headers);
            }

            foreach ($data as $row) {
                foreach ($row as $key => &$value) {
                    if (is_array($value) && isset($value['type']) && $value['type'] === 'Point') {
                        $value = "({$value['coordinates'][1]}, {$value['coordinates'][0]})";
                    } elseif (is_array($value)) {
                        $value = json_encode($value);
                    }
                }
                fputcsv($fileHandle, $row);
            }

            $firstPage = false;
            $numDataRows = count($data);

            if ($numDataRows < $pageSize) {
                $this->info("Last page fetched for {$datasetName} (received {$numDataRows} rows, page size {$pageSize}).");
                break;
            }

            $offset += $pageSize;
            usleep(250000); // 0.25 seconds
        }

        fclose($fileHandle);
        return true;
    }

    protected function fetchSocrataPageContent(string $url): string|false
    {
        $maxRetries = 3;
        $retryDelay = 5; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 180); // 3 minutes timeout for a single page

                $fileContents = curl_exec($ch);
                $curlError = curl_error($ch);
                $curlErrno = curl_errno($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($curlErrno !== 0) {
                    // Retry on timeout or connection errors
                    if (in_array($curlErrno, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT, CURLE_RECV_ERROR])) {
                        $this->warn("cURL error for {$url}: {$curlError}. Retrying in {$retryDelay}s... (Attempt {$attempt}/{$maxRetries})");
                        sleep($retryDelay);
                        continue;
                    }
                    $this->error("cURL error for {$url}: " . $curlError);
                    return false;
                }

                if ($httpCode >= 500) { // Retry on server errors (5xx)
                    $this->warn("HTTP request for {$url} failed with status code: {$httpCode}. Retrying in {$retryDelay}s... (Attempt {$attempt}/{$maxRetries})");
                    sleep($retryDelay);
                    continue;
                }

                if ($httpCode !== 200) {
                    $this->error("HTTP request for {$url} failed with status code: {$httpCode}");
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

        $this->error("Failed to fetch {$url} after {$maxRetries} attempts.");
        return false;
    }
    
    /**
     * Renamed from downloadFile to downloadSingleFile to differentiate from paginated downloads.
     */
    private function downloadSingleFile(string $url, string $destination): bool
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
            return false;
        }
    }

    protected function generateFilename($name, $format, $city)
    {
        $timestamp = now()->format('Ymd_His');
        $safeCity = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $city));
        $safeName = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        return "datasets/{$safeCity}/{$safeName}_{$timestamp}.{$format}";
    }
}
