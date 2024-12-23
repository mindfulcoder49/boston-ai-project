<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Translate a model's fillable fields into multiple languages.
     *
     * @param Model $model The model instance to translate.
     * @param array $languageCodes Array of language codes.
     * @return array The translations grouped by language code.
     */
    public function translateModel(Model $model, array $languageCodes): array
    {
        $fillable = $model->getFillable();

        // Include all fillable fields, including language_code
        $fieldsToTranslate = array_combine($fillable, array_map(fn($field) => $model->{$field} ?? null, $fillable));

        // Construct the tool definition
        $tool = [
            "type" => "function",
            "function" => [
                "name" => "store_translation",
                "description" => "Stores a translations of the model fields.",
                "parameters" => [
                    "type" => "object",
                    "properties" => array_combine(
                        $fillable,
                        array_fill(0, count($fillable), ["type" => "string"])
                    ),
                    "required" => $fillable,
                ],
            ],
        ];

        // Create system message
        $systemMessage = "You are an assistant that translates all provided non-numeric or proper noun fields of data into multiple languages using the store_translation function for a city app that helps residents of Boston. These translations will be used by older immigrants who speak different languages.";

        // Create user message
        $userMessage = "Translate the following data fields into " . json_encode($languageCodes) . " , and call the store_translation function for each translation. The output should be transformed into the target language. " . json_encode($fieldsToTranslate);

        $translations = [];

        try {
            // Call OpenAI API
            $response = $this->openAIService->callFunction(
                $tool["function"],
                $userMessage,
                $systemMessage
            );

            if (!empty($response)) {
                $translations = $response;
            }
        } catch (\Exception $e) {
            Log::error("Translation failed", ['error' => $e->getMessage()]);
        }

        return $translations;
    }
}
