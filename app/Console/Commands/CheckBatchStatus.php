<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class CheckBatchStatus extends Command
{
    protected $signature = 'batch:check-status';
    protected $description = 'Check the status of the batch uploaded to OpenAI.';

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

            // Send request to OpenAI API to get batch status
            $response = $client->get("https://api.openai.com/v1/batches/{$batchId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);

            if (isset($responseBody['status'])) {
                $this->info("Batch Status: " . $responseBody['status']);
                $this->info("Details: " . json_encode($responseBody, JSON_PRETTY_PRINT));
            } else {
                $this->error("Unable to fetch batch status. Response: " . $response->getBody());
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
