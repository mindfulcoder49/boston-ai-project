<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\AiAssistantController;
use Illuminate\Support\Str;

class GenerateFieldDictionary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:field-dictionary {model} {field}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a JSON dictionary file by categorizing unique values of a model field using AI.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelName = $this->argument('model');
        $field = $this->argument('field');
        $modelClass = "App\\Models\\{$modelName}";

        if (!class_exists($modelClass)) {
            $this->error("Model [{$modelClass}] not found.");
            return 1;
        }

        $modelInstance = new $modelClass();
        $tableName = $modelInstance->getTable();

        if (!DB::getSchemaBuilder()->hasColumn($tableName, $field)) {
            $this->error("Field [{$field}] not found in table [{$tableName}].");
            return 1;
        }

        $this->info("Fetching unique values for '{$field}' from '{$tableName}'...");

        $values = DB::table($tableName)->whereNotNull($field)->where($field, '!=', '')->distinct()->pluck($field);

        if ($values->isEmpty()) {
            $this->warn("No unique values found for '{$field}'. No file will be generated.");
            return 0;
        }

        $this->info("Found {$values->count()} unique values. Sending to AI for categorization...");

        $this->line(implode(', ', $values->toArray()));

        $dictionaryJson = AiAssistantController::generateDictionary($modelName, $field, $values->toArray());

        if (!$dictionaryJson) {
            $this->error("Failed to generate dictionary from AI.");
            return 1;
        }

        // Validate and analyze JSON
        $dictionary = json_decode($dictionaryJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("AI returned invalid JSON. Please try again.");
            $this->line($dictionaryJson);
            return 1;
        }

        $this->info("Analyzing generated dictionary...");

        $originalValues = $values->all();
        $returnedKeys = array_keys($dictionary);

        $missingKeys = array_diff($originalValues, $returnedKeys);
        $extraKeys = array_diff($returnedKeys, $originalValues);

        if (count($missingKeys) > 0) {
            $this->warn("The AI response was missing some of the original values as keys:");
            $this->line(implode(', ', $missingKeys));
        }

        if (count($extraKeys) > 0) {
            $this->warn("The AI response included extra keys that were not in the original values:");
            $this->line(implode(', ', $extraKeys));
        }

        if (count($missingKeys) === 0 && count($extraKeys) === 0) {
            $this->info("Consistency check passed: All original values are present as keys in the response.");
        }

        $categories = array_unique(array_values($dictionary));
        $this->info("Generated " . count($categories) . " unique categories.");
        $this->line("Categories: " . implode(', ', $categories));

        $fileName = Str::snake(Str::plural(str_replace('_', ' ', $field))) . '.json';
        $path = database_path('seeders/' . $fileName);

        // Use the validated and decoded dictionary, then re-encode with pretty print for readability
        File::put($path, json_encode($dictionary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Successfully generated dictionary and saved to '{$path}'.");

        return 0;
    }
}
