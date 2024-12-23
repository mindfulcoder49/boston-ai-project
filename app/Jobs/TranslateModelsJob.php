<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Log;

class TranslateModelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $models;
    protected array $languageCodes;

    public function __construct(array $models, array $languageCodes)
    {
        $this->models = $models;
        $this->languageCodes = $languageCodes;
    }

    public function handle(TranslationService $translationService)
    {
        foreach ($this->models as $model) {
            try {
                Log::info("Translating model: " . get_class($model));
                $translations = $translationService->translateModel($model, $this->languageCodes);

                
                foreach ($translations as $languageCode => $fields) {
                    $translatedModel = $model->replicate();
                    $translatedModel->fill($fields);
                    $translatedModel->language_code = $languageCode;
                    $translatedModel->save();
                }

                //add en_US to the language_code column of the original model
                $model->language_code = 'en-US';
                $model->save();

                Log::info("Translations for model: " . json_encode($translations));

            } catch (\Exception $e) {
                Log::error("Translation failed for model: {$e->getMessage()}");
            }
        }
    }
}
