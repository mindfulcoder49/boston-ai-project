<?php

namespace Database\Seeders;

use App\Services\BostonThreeOneOneRowNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader; // Import League CSV Reader

class ThreeOneOneSeeder extends Seeder
{
    private const BATCH_SIZE = 500; // Define batch size for DB operations
    private BostonThreeOneOneRowNormalizer $normalizer;

    public function __construct(BostonThreeOneOneRowNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $files = $this->latestDatasetFiles();

        if (empty($files)) {
            $this->command->warn('No files found to process for Boston 311 datasets.');
            return;
        }

        sort($files);

        foreach ($files as $file) {
            $this->command->info("Processing file: " . $file);
            $this->processFile(Storage::path($file));
        }
    }

    /**
     * Keep the seed pass bounded to the newest snapshot for each Boston 311 feed.
     *
     * @return array<int, string>
     */
    private function latestDatasetFiles(): array
    {
        $availableFiles = Storage::disk('local')->files('datasets');
        $datasetNames = collect(config('boston_datasets.datasets', []))
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && str_contains($name, '311-service-requests'))
            ->unique()
            ->values();

        return $datasetNames
            ->map(function (string $datasetName) use ($availableFiles) {
                $matches = array_values(array_filter($availableFiles, function (string $file) use ($datasetName) {
                    return str_starts_with($file, "datasets/{$datasetName}_");
                }));

                if (empty($matches)) {
                    return null;
                }

                rsort($matches, SORT_STRING);

                return $matches[0];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Process the file and insert data into the database.
     *
     * @param string $filePath
     * @return void
     */
    private function processFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->command->error("File not found: $filePath");
            return;
        }

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // The header is on the first row
            $records = $csv->getRecords(); // Get an iterator for the records

            $dataBatch = [];
            $rowCount = 0;
            $processedCount = 0;

            // It's difficult to get an accurate total row count from the iterator without iterating twice
            // or loading everything into memory. We'll report progress based on processed batches.

            foreach ($records as $index => $row) {
                $rowCount++;
                try {
                    $cleanedData = $this->normalizer->normalize($row);
                    $dataBatch[] = $cleanedData;

                    if (count($dataBatch) >= self::BATCH_SIZE) {
                        $this->insertOrUpdateBatch($dataBatch);
                        $processedCount += count($dataBatch);
                        $dataBatch = []; // Reset the batch
                        $this->command->info("Processed {$processedCount} records...");
                    }
                } catch (\Exception $e) {
                    // Log errors without interrupting the seeding process
                    Log::error("Error processing row " . ($index + 1) . " in file $filePath: " . $e->getMessage() . " Data: " . json_encode($row));
                    $this->command->warn("Skipped row " . ($index + 1) . " due to error: " . $e->getMessage());
                }
            }

            // Process any remaining data in the last batch
            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
                $processedCount += count($dataBatch);
            }
            $this->command->info("Finished processing file: " . basename($filePath) . ". Total records processed: {$processedCount}. Total rows read: {$rowCount}.");

        } catch (\Exception $e) {
            $this->command->error("Failed to read or process CSV file {$filePath}: " . $e->getMessage());
        }
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        if (empty($dataBatch)) {
            return;
        }
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['service_request_id']));

        if (empty($validBatch)) {
            $this->command->warn("A batch of records was skipped as no valid 'service_request_id' was found.");
            return;
        }
        
        // Define columns to update in case of conflict
        $updateColumns = array_keys($validBatch[0]);
        $updateColumns = array_filter($updateColumns, fn($col) => $col !== 'service_request_id' && $col !== 'created_at');

        DB::table('three_one_one_cases')->upsert(
            $validBatch,
            ['service_request_id'],
            $updateColumns // Columns to update on duplicate
        );
    }
}
