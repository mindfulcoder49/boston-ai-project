<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class DownloadBatchResults extends Command
{
    protected $signature = 'batch:download-results';
    protected $description = 'Download the results of a completed batch from OpenAI.';

    public function handle()
    {
        $filePath = 'batches/batch_info.json';

        if (!Storage::disk('local')->exists($filePath)) {
            $this->error("Batch info file does not exist: {$filePath}");
            return;
        }

        // Read the batch info file
        $batchInfo = json_decode(Storage::disk('local')->get($filePath), true);

        if (empty($batchInfo['id'])) {
            $this->error("No batch ID found in the batch info file.");
            return;
        }

        $batchId = $batchInfo['id'];

        try {
            $client = new Client();

            // Fetch batch details
            $response = $client->get("https://api.openai.com/v1/batches/{$batchId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ]);

            $batchDetails = json_decode($response->getBody(), true);

            if (empty($batchDetails['output_file_id'])) {
                $this->error("Batch results are not yet available. Status: " . $batchDetails['status']);
                return;
            }

            $outputFileId = $batchDetails['output_file_id'];

            // Download the output file
            $outputResponse = $client->get("https://api.openai.com/v1/files/{$outputFileId}/content", [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                ],
            ]);

            $outputContent = $outputResponse->getBody();

            // Save the output to a file
            $resultsPath = 'batches/batch_results.jsonl';
            Storage::disk('local')->put($resultsPath, $outputContent);

            $this->info("Batch results downloaded and saved to: {$resultsPath}");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
