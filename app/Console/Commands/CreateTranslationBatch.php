<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

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

        $filePath = 'batches/translation_requests_with_functions.jsonl';
        Storage::disk('local')->put($filePath, ''); // Create/clear the file

        $startDate = Carbon::now()->subDays(14)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        foreach ($models as $modelClass) {
            $dateField = $modelClass::getDateField();

            // Get all distinct dates within the range
            $dates = $modelClass::query()
                ->whereBetween($dateField, [$startDate, $endDate])
                ->distinct()
                ->pluck($dateField)
                ->map(fn($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->sort()
                ->values();

           foreach ($dates as $dateString) {
                $dateStart = Carbon::parse($dateString)->startOfDay();
                $dateEnd = Carbon::parse($dateString)->endOfDay();
                 
                 // Fetch all relevant records for the day
                $records = $modelClass::where('language_code', 'en-US')
                    ->whereBetween($dateField, [$dateStart, $dateEnd])
                    ->get();

                $batchData = '';
               
                foreach ($records as $instance) {
                    $fillableFields = $instance->getFillable();
                    $fieldsToTranslate = array_combine(
                        $fillableFields,
                        array_map(fn($field) => $instance->{$field} ?? null, $fillableFields)
                    );
                     $externalId = $instance->getExternalId();
                    $externalIdName = $instance->getExternalIdName();

                     // Fetch existing translations for all languages in one query for this instance.
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
                        ]) . "\n"; // Add newline for JSONL format.
                    }
                }

                if (!empty($batchData)) {
                    Storage::disk('local')->append($filePath, $batchData);
                }
            }
        }

         // Append newline at the end to respect JSONL format
        Storage::disk('local')->append($filePath, '');
        $this->info("Batch file with function calls created incrementally: {$filePath}");
    }
}