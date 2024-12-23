<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ThreeOneOneCase;
use App\Models\CrimeData;
use App\Models\BuildingPermit;
use App\Models\ConstructionOffHour;
use App\Models\PropertyViolation;

class TranslationSeeder extends Seeder
{
    public function run()
    {
        $resultsPath = 'batches/batch_results.jsonl';

        if (!Storage::disk('local')->exists($resultsPath)) {
            Log::error("Batch results file not found: {$resultsPath}");
            return;
        }

        $results = Storage::disk('local')->get($resultsPath);

        $models = [
            ThreeOneOneCase::class,
            CrimeData::class,
            BuildingPermit::class,
            ConstructionOffHour::class,
            PropertyViolation::class,
        ];

        foreach (explode("\n", $results) as $line) {
            if (empty($line)) {
                continue; // Skip empty lines
            }

            $result = json_decode($line, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to parse line: {$line}");
                continue;
            }

            $customId = $result['custom_id'] ?? null;
            $toolCalls = $result['response']['body']['choices'][0]['message']['tool_calls'] ?? null;

            if (!$customId || !$toolCalls) {
                Log::error("Invalid result format, missing custom_id or tool_calls: {$line}");
                continue;
            }

            preg_match('/App\\\\Models\\\\(\w+)_(\d+)_(\w+-\w+)/', $customId, $matches);

            if (count($matches) !== 4) {
                Log::error("Invalid custom_id format: {$customId}");
                continue;
            }

            $modelClass = "App\\Models\\" . $matches[1];
            $recordId = $matches[2];
            $languageCode = $matches[3];

            if (!in_array($modelClass, $models)) {
                Log::error("Unsupported model class: {$modelClass}");
                continue;
            }

            $storeTranslationCall = collect($toolCalls)->firstWhere('function.name', 'store_translation');

            if (!$storeTranslationCall || empty($storeTranslationCall['function']['arguments'])) {
                Log::error("No valid store_translation call found for custom_id: {$customId}");
                continue;
            }

            $translationData = json_decode($storeTranslationCall['function']['arguments'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to decode store_translation arguments: " . json_last_error_msg());
                continue;
            }

            try {
                // Use static methods to get external ID name and value
                $externalIdName = $modelClass::getExternalIdName();
                $externalId = $translationData[$externalIdName] ?? null;
            
                if (!$externalId) {
                    Log::error("Missing external ID for Model {$modelClass}");
                    continue;
                }

                // Parse date field
                $dateField = $modelClass::getDateField();

                if ($dateField && isset($translationData[$dateField])) {
                    $translationData[$dateField] = $this->parseDate($translationData[$dateField]);
                }

                // also parse closed_dt if it exists
                if (isset($translationData['closed_dt'])) {
                    $translationData['closed_dt'] = $this->parseDate($translationData['closed_dt']);
                }
            
                // Add the language code to the data
                $translationData['language_code'] = $languageCode;
            
                // Ensure `translationData` only includes fillable attributes
                $fillableAttributes = array_intersect_key(
                    $translationData,
                    array_flip((new $modelClass)->getFillable())
                );
            
                if (empty($fillableAttributes)) {
                    Log::error("No valid attributes for Model {$modelClass}");
                    continue;
                }
            
                // Update or insert the translation data
                $modelClass::updateOrCreate(
                    [$externalIdName => $externalId, 'language_code' => $languageCode],
                    $fillableAttributes
                );
            
                Log::info("Inserted/updated translation for Model {$modelClass}, External ID {$externalId}, Language {$languageCode}");
            } catch (\Exception $e) {
                Log::error("Failed to insert/update translation: " . $e->getMessage());
            }            
        }
    }

    private function parseDate($dateString)
    {
        $formats = [
            'Y-m-d H:i:s',        // Standard format
            'Y年m月d日 H:i:s',    // Chinese format
            'd de F de Y H:i:s',  // Portuguese format
        ];
    
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // Suppress individual format errors, continue to next format
                continue;
            }
        }
    
        // Log the failure if no format matches
        Log::error("Failed to parse date: {$dateString}");
    
        return null; // Return null if no formats match
    }
    
}
