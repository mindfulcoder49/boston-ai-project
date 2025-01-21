<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class UploadAndExecuteBatchFile extends Command
{
    protected $signature = 'batch:upload-and-execute';
    protected $description = 'Upload a batch translation file and create a batch for execution.';

    public function handle()
    {
        $folderPath = 'batches';
        //get latest file that begins with 'translation_requests_with_functions_'
        $latestFile = collect(Storage::disk('local')->files($folderPath))
            ->filter(fn($file) => strpos($file, 'translation_requests_with_functions_') === 0)
            ->sort()
            ->last();

        $filePath = $latestFile;

        if (!Storage::disk('local')->exists($filePath)) {
            $this->error("Batch file does not exist: {$filePath}");
            return;
        }

        try {
            $client = new Client();

            // Ensure the file content is properly read
            $fileContents = fopen(Storage::disk('local')->path($filePath), 'r');

            if (!$fileContents) {
                throw new \Exception("Failed to read the batch file.");
            }

            // Step 1: Upload the file
            $uploadResponse = $client->post('https://api.openai.com/v1/files', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $fileContents,
                        'filename' => basename($filePath),
                    ],
                    ['name' => 'purpose', 'contents' => 'batch'],
                ],
            ]);

            $uploadResponseBody = json_decode($uploadResponse->getBody(), true);

            if (empty($uploadResponseBody['id'])) {
                throw new \Exception("Failed to upload batch file.");
            }

            $this->info("Batch file uploaded with file ID: " . $uploadResponseBody['id']);

            // Step 2: Create the batch
            $batchResponse = $client->post('https://api.openai.com/v1/batches', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'input_file_id' => $uploadResponseBody['id'],
                    'endpoint' => '/v1/chat/completions',
                    'completion_window' => '24h',
                    'metadata' => [
                        'description' => 'Batch translation job',
                    ],
                ],
            ]);

            $batchResponseBody = json_decode($batchResponse->getBody(), true);

            if (empty($batchResponseBody['id'])) {
                throw new \Exception("Failed to create batch.");
            }

            // Save batch information locally
            $batchInfoPath = 'batches/batch_info.json';
            Storage::disk('local')->put($batchInfoPath, json_encode($batchResponseBody, JSON_PRETTY_PRINT));

            $this->info("Batch created successfully with ID: " . $batchResponseBody['id']);
            $this->info("Batch information saved to: {$batchInfoPath}");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
