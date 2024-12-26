<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreateTranslationBatch extends Command
{
    protected $signature = 'batch:create-translation-batch';
    protected $description = 'Prepare batch translation requests with function calls and save to file incrementally.';

    public function handle()
    {
        $models = [
            \App\Models\ThreeOneOneCase::class,
            \App\Models\CrimeData::class,
            \App\Models\BuildingPermit::class,
            \App\Models\PropertyViolation::class,
        ];

        $languageCodes = [
            'es-MX', 'zh-CN', 'ht-HT', 'vi-VN', 'pt-BR',
        ];

        $chunkSize = 100; // Process records in chunks of 100
        $filePath = 'batches/translation_requests_with_functions.jsonl';

        // Create or clear the file before appending data
        Storage::disk('local')->put($filePath, '');

        foreach ($models as $modelClass) {
            $dateField = $modelClass::getDateField(); 

            // Filter records by date directly in the query
            $modelClass::where('language_code', 'en-US')
                ->where($dateField, '>=', Carbon::now()->subDays(14)) // Apply the 14-day date filter
                ->chunk($chunkSize, function ($instances) use ($modelClass, $languageCodes, $filePath) {
                    $batchData = '';

                    foreach ($instances as $instance) {
                        $fillableFields = $instance->getFillable();
                        $fieldsToTranslate = array_combine(
                            $fillableFields,
                            array_map(fn($field) => $instance->{$field} ?? null, $fillableFields)
                        );

                        $externalId = $instance->getExternalId();
                        $externalIdName = $instance->getExternalIdName();

                        // Find existing translations for this case_enquiry_id
                        $existingTranslations = $modelClass::where($externalIdName, $externalId)
                            ->whereNotNull('language_code')
                            ->pluck('language_code')
                            ->toArray();

                        $missingLanguages = array_diff($languageCodes, $existingTranslations);

                        foreach ($missingLanguages as $languageCode) {
                            Log::info("Adding translation request for {$modelClass} with {$externalIdName} {$externalId} to {$languageCode}.");
                            $batchData .= json_encode([
                                "custom_id" => "{$modelClass}_{$instance->id}_{$languageCode}",
                                "method" => "POST",
                                "url" => "/v1/chat/completions",
                                "body" => [
                                    "model" => "gpt-4o-mini",
                                    "messages" => [
                                        ["role" => "system", "content" => "You are an assistant that translates all provided non-numeric or proper noun fields of data into {$languageCode} using the store_translation function."],
                                        ["role" => "user", "content" => json_encode($fieldsToTranslate)],
                                    ],
                                    "tools" => [[
                                        "type" => "function",
                                        "function" => [
                                            "name" => "store_translation",
                                            "description" => "Stores translations of the model fields.",
                                            "parameters" => [
                                                "type" => "object",
                                                "properties" => array_combine(
                                                    $fillableFields,
                                                    array_fill(0, count($fillableFields), ["type" => "string"])
                                                ),
                                                "required" => $fillableFields,
                                            ],
                                        ],
                                    ]],
                                ],
                            ]);
                        }
                    }

                    // Write batch data to the file incrementally
                    if (!empty($batchData)) {
                        Storage::disk('local')->append($filePath, $batchData);
                    }
                });
        }

        // Write newline character at the end of the file
        Storage::disk('local')->append($filePath, '');

        $this->info("Batch file with function calls created incrementally: {$filePath}");
    }
}
