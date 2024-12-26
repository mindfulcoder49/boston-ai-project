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

        $filePath = 'batches/translation_requests_with_functions.jsonl';
        Storage::disk('local')->put($filePath, ''); // Create/clear the file

        $startDate = Carbon::now()->subDays(14)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
         Log::info("Translation batch process started. Date range: {$startDate->toIso8601String()} - {$endDate->toIso8601String()}.");


        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);
            Log::info("Processing model: {$modelName}.");
            $dateField = $modelClass::getDateField();

            // Get all distinct dates within the range in reverse order (newest first)
            $dates = $modelClass::query()
                ->whereBetween($dateField, [$startDate, $endDate])
                ->distinct()
                ->pluck($dateField)
                ->map(fn($date) => Carbon::parse($date)->toDateString())
                ->unique()
                 ->sort(function ($a, $b) {
                     return ($a < $b) ? 1 : -1;
                })
                ->values();

             Log::info("Found " . count($dates) . " distinct dates to process for model {$modelName}.");


            $noTranslationCount = 0; // Counter for records with no translations needed
            foreach ($dates as $dateString) {
                $dateStart = Carbon::parse($dateString)->startOfDay();
                $dateEnd = Carbon::parse($dateString)->endOfDay();
                Log::info("Processing model {$modelName} on date: {$dateStart->toIso8601String()}.");


                // Fetch all relevant records for the day
                $records = $modelClass::where('language_code', 'en-US')
                    ->whereBetween($dateField, [$dateStart, $dateEnd])
                    ->get();
                Log::info("Found " . $records->count() . " English records to process for model {$modelName} on date {$dateString}.");

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
                     Log::info("Record {$instance->id} needs translations to: " . implode(', ', $missingLanguages) . " for {$modelName} on {$dateString}.");

                    if (empty($missingLanguages)) {
                        $noTranslationCount++;
                        Log::info("Record {$instance->id} does not need any translations, count: {$noTranslationCount} for {$modelName} on {$dateString}.");
                        if ($noTranslationCount >= 10) {
                             Log::info("Ten consecutive records found with no translations needed, skipping the rest of this model: {$modelName}.");
                             break 2; // Break out of both the records loop and date loop.
                        }
                         continue;

                    } else {
                        $noTranslationCount = 0; // Reset counter
                        foreach ($missingLanguages as $languageCode) {
                             Log::info("Adding translation request for {$modelName} with {$externalIdName} {$externalId} to {$languageCode}.");
                            $batchData .= json_encode([
                                    "custom_id" => "{$modelName}_{$instance->id}_{$languageCode}",
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

                }

                if (!empty($batchData)) {
                    Storage::disk('local')->append($filePath, $batchData);
                    Log::info("Batch data appended for {$modelName} on {$dateString}.");

                } else {
                     Log::info("No batch data to append for {$modelName} on {$dateString}.");
                }
            }
             Log::info("Finished processing model: {$modelName}.");
        }

         // Append newline at the end to respect JSONL format
        Storage::disk('local')->append($filePath, '');
        Log::info("Translation batch process finished. File created: {$filePath}");
        $this->info("Batch file with function calls created incrementally: {$filePath}");
    }
}