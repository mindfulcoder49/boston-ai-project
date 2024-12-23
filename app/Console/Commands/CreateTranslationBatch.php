<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreateTranslationBatch extends Command
{
    protected $signature = 'batch:create-translation-batch';
    protected $description = 'Prepare batch translation requests with function calls and save to file.';

    public function handle()
    {
        $models = [
            \App\Models\ThreeOneOneCase::class,
            \App\Models\CrimeData::class,
            \App\Models\BuildingPermit::class,
            \App\Models\ConstructionOffHour::class,
            \App\Models\PropertyViolation::class,
        ];

        $languageCodes = [
            'es-MX', 'zh-CN', 'ht-HT', 'vi-VN', 'pt-BR',
        ];

        $batchRequests = [];

        foreach ($models as $modelClass) {
            $instances = $modelClass::whereNull('language_code')
                ->orWhere('language_code', 'en-US')
                ->get();

            //$limit = 0;

            foreach ($instances as $instance) {
                $entryDate = Carbon::parse($instance->getDate());

                if ($entryDate->lt(Carbon::now()->subDays(14))) {
                    continue;
                }

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
                    ->get(['language_code', 'updated_at']);

                $missingLanguages = [];

                foreach ($languageCodes as $languageCode) {
                    $translation = $existingTranslations->firstWhere('language_code', $languageCode);

                    // Check if translation is missing or outdated
                    if (!$translation || $translation->updated_at < $instance->updated_at) {
                        $missingLanguages[] = $languageCode;
                    }
                }

                foreach ($missingLanguages as $languageCode) {
                    Log::info("Adding translation request for {$modelClass} with {$externalIdName} {$externalId} to {$languageCode}.");
                    $batchRequests[] = [
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
                    ];
                }
            }
        }

        $filePath = 'batches/translation_requests_with_functions.jsonl';
        Storage::disk('local')->put($filePath, collect($batchRequests)->map(fn($r) => json_encode($r))->implode("\n"));

        $this->info("Batch file with function calls created: {$filePath}");
    }
}
