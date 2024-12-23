<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TranslateModelsJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DispatchTranslationJobs extends Command
{
    protected $signature = 'translate:dispatch';
    protected $description = 'Dispatch jobs to translate untranslated fields in all tables.';

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
            'es-MX', // Spanish (Mexico)
            'zh-CN', // Chinese (Simplified, China)
            'ht-HT', // Haitian Creole (Haiti)
            'vi-VN', // Vietnamese (Vietnam)
            'pt-BR', // Portuguese (Brazil)
        ];

        foreach ($models as $modelClass) {
            try {
                Log::info("Dispatching translation jobs for {$modelClass}.");

                // Get all entries where language_code is null or en_US
                $instances = $modelClass::whereNull('language_code')
                    ->orWhere('language_code', 'en_US')
                    ->get();

                if ($instances->isEmpty()) {
                    $this->info("No untranslated entries found for {$modelClass}.");
                    continue;
                }

                $instances->each(function ($instance) use ($languageCodes, $modelClass) {
                    // Filter out instances older than 14 days
                    $entryDate = Carbon::parse($instance->getDate());
                    if ($entryDate->lt(Carbon::now()->subDays(14))) {
                        return; // Skip this instance
                    }

                    $externalId = $instance->getExternalId();
                    $externalIdName = $instance->getExternalIdName();

                    // Find existing translations for this case_enquiry_id
                    $existingTranslations = $modelClass::where($externalIdName, $externalId)
                        ->whereNotNull('language_code')
                        ->pluck('language_code')
                        ->toArray();

                    // Determine missing language codes
                    $missingLanguages = array_diff($languageCodes, $existingTranslations);

                    if (!empty($missingLanguages)) {
                        // Dispatch a job for this instance and missing languages
                        TranslateModelsJob::dispatch([$instance], $missingLanguages);
                        $this->info("Dispatched translation job for {$modelClass} with {$externalIdName} {$externalId}.");
                    }
                });

            } catch (\Exception $e) {
                Log::error("Failed to dispatch translation jobs for {$modelClass}: {$e->getMessage()}");
                $this->error("Error processing {$modelClass}: {$e->getMessage()}");
            }
        }

        $this->info('Translation jobs dispatched successfully.');
    }
}
