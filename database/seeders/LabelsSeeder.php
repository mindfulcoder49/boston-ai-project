<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\Label;
use App\Services\OpenAIService;

class LabelTranslationSeeder extends Seeder
{
    protected $aiService;

    public function __construct()
    {
        $this->aiService = app(OpenAIService::class); // Inject your AI service
    }

    public function run()
    {
        Log::info('Starting LabelTranslationSeeder...');

        try {
            // Fetch all English labels
            $englishLabels = Label::where('language_code', 'en')->get();

            if ($englishLabels->isEmpty()) {
                Log::warning('No English labels found to translate.');
                return;
            }

            Log::info('Fetched English labels', ['count' => $englishLabels->count()]);

            // Prepare the AI function definition
            $functionDefinition = [
                "name" => "store_translated_labels",
                "description" => "Translates a set of labels into the specified language.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "labels" => [
                            "type" => "array",
                            "items" => [
                                "type" => "object",
                                "properties" => [
                                    "code_name" => ["type" => "string", "description" => "The label's code name."],
                                    "text" => ["type" => "string", "description" => "The label's text in English."],
                                    "language_code" => ["type" => "string", "description" => "The target language code for translation."],
                                ],
                                "required" => ["code_name", "text", "language_code"]
                            ]
                        ]
                    ],
                    "required" => ["labels"]
                ]
            ];

            // Iterate over chunks of 10 labels for translation
            foreach ($englishLabels->chunk(10) as $chunk) {
                $labelsToTranslate = $chunk->map(function ($label) {
                    return [
                        "code_name" => $label->code_name,
                        "text" => $label->text,
                        "language_code" => 'es', // Example: translating to Spanish
                    ];
                })->toArray();

                // Prompt the AI
                $prompt = "Translate these English labels into Spanish (language code 'es'):\n" . json_encode($labelsToTranslate, JSON_PRETTY_PRINT);

                $systemMessage = "You are a translation assistant responsible for translating application labels into different languages.";

                $translatedLabels = $this->aiService->callFunction($functionDefinition, $prompt, $systemMessage);

                // Insert translated labels into the database
                foreach ($translatedLabels['labels'] as $translatedLabel) {
                    Label::create([
                        'code_name' => $translatedLabel['code_name'],
                        'text' => $translatedLabel['text'],
                        'language_code' => $translatedLabel['language_code'],
                    ]);

                    Log::info("Inserted translated label", ['code_name' => $translatedLabel['code_name'], 'language_code' => $translatedLabel['language_code']]);
                }
            }

            Log::info('LabelTranslationSeeder completed.');
        } catch (\Exception $e) {
            Log::error('Failed to seed translated labels', ['exception' => $e]);
        }
    }
}
