<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ThreeOneOneCase;
use App\Models\CrimeData;
use App\Models\BuildingPermit;
use App\Models\ConstructionOffHour;
use App\Models\PropertyViolation;

class TransformTranslationsCommand extends Command
{
    protected $signature = 'translations:transform';
    protected $description = 'Transform translation data by merging with original database records';

    private const OUTPUT_FILE = 'batches/transformed_translations.jsonl';
    private const CHUNK_SIZE = 500;

    private const FIELD_MAPPINGS = [
        ThreeOneOneCase::class => [
            'originalFields' => [
                'case_enquiry_id', 'open_dt', 'sla_target_dt', 'closed_dt', 'on_time',
                'latitude', 'longitude', 'location', 'city_council_district', 
                'neighborhood', 'ward', 'precinct', 'location_street_name', 
                'location_zipcode', 'closed_photo', 'submitted_photo'
            ],
            'translatedFields' => [
                'case_title', 'subject', 'reason', 'type', 'queue', 
                'department', 'case_status', 'closure_reason', 'source'
            ],
        ],
        CrimeData::class => [
            'originalFields' => [
                'incident_number', 'offense_code', 'year', 'month', 'day_of_week',
                'hour', 'shooting', 'lat', 'long', 'occurred_on_date', 'district',
                'reporting_area', 'location', 'offense_code_group'
            ],
            'translatedFields' => [
                'offense_description', 'ucr_part', 'street'
            ],
        ],        
        BuildingPermit::class => [
            'originalFields' => [
                'permitnumber', 'sq_feet', 'gpsy', 'gpsx', 'y_latitude', 
                'x_longitude', 'issued_date', 'expiration_date', 'occupancytype',
                'declared_valuation', 'total_fees', 'state', 'zip', 'property_id', 'parcel_id'
            ],
            'translatedFields' => [
                'description', 'comments', 'status', 'address', 'city', 'worktype', 'permittypedescr'
            ],
        ],
        PropertyViolation::class => [
            'originalFields' => [
                'case_no', 'ap_case_defn_key', 'status_dttm', 'latitude', 'longitude',
                'violation_stno', 'violation_sthigh', 'violation_street', 'violation_suffix',
                'violation_city', 'violation_state', 'violation_zip', 'ward', 'contact_addr1',
                'contact_addr2', 'contact_city', 'contact_state', 'contact_zip', 'sam_id',
                'code', 'value'
            ],
            'translatedFields' => [
                'description', 'status', 'location'
            ],
        ],        
    ];
    
    

    public function handle()
    {
        $translationFilePath = 'batches/batch_results.jsonl';

        if (!Storage::disk('local')->exists($translationFilePath)) {
            Log::error("Translation file not found: {$translationFilePath}");
            return;
        }

        $startTime = now();
        $translationsByModel = $this->organizeTranslations($translationFilePath);

        // Ensure the output file is empty before starting
        Storage::disk('local')->put(self::OUTPUT_FILE, '');

        $totalProcessed = 0;

        foreach (self::FIELD_MAPPINGS as $modelClass => $fields) {
            $totalProcessed += $this->processModel($modelClass, $fields, $translationsByModel[$modelClass] ?? []);
        }

        $duration = now()->diffInSeconds($startTime);
        $this->info("Processing completed in {$duration} seconds. Total records processed: {$totalProcessed}");
    }

    private function organizeTranslations(string $filePath): array
{
    $translationsByModel = [];
    $lines = explode("\n", Storage::disk('local')->get($filePath));

    foreach ($lines as $line) {
        if (empty($line)) continue;

        $result = json_decode($line, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($result['custom_id'])) {
            Log::debug("Invalid or missing custom_id in translation line: {$line}");
            continue;
        }

        // Parse the tool call for the external ID
        $toolCalls = $result['response']['body']['choices'][0]['message']['tool_calls'] ?? null;
        if (!$toolCalls) {
            Log::debug("No tool calls found for custom_id: {$result['custom_id']}");
            continue;
        }

        $storeTranslationCall = collect($toolCalls)->firstWhere('function.name', 'store_translation');
        if (!$storeTranslationCall || empty($storeTranslationCall['function']['arguments'])) {
            Log::debug("No valid store_translation call found for custom_id: {$result['custom_id']}");
            continue;
        }

        $translationData = json_decode($storeTranslationCall['function']['arguments'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::debug("Failed to decode translation arguments for custom_id: {$result['custom_id']}");
            continue;
        }

        // Extract the model class and external ID
        if (preg_match('/App\\\\Models\\\\(\w+)_\d+_(\w+-\w+)/', $result['custom_id'], $matches)) {
            [$fullId, $modelName, $languageCode] = $matches;
            $modelClass = "App\\Models\\{$modelName}";

            $externalIdName = $modelClass::getExternalIdName();
            $externalId = $translationData[$externalIdName] ?? null;

            if (!$externalId) {
                Log::debug("External ID not found for custom_id: {$result['custom_id']}");
                continue;
            }

            // Organize translations by model, external ID, and language
            if (!isset($translationsByModel[$modelClass])) {
                $translationsByModel[$modelClass] = [];
            }

            if (!isset($translationsByModel[$modelClass][$externalId])) {
                $translationsByModel[$modelClass][$externalId] = [];
            }

            $translationsByModel[$modelClass][$externalId][$languageCode] = $result;
        } else {
            Log::debug("Failed to parse model and language code from custom_id: {$result['custom_id']}");
        }
    }

    return $translationsByModel;
}


    private function processModel(string $modelClass, array $fields, array $translations): int
    {
        $this->info("Processing {$modelClass}...");
        
        $externalIdName = $modelClass::getExternalIdName();
        $dateField = $modelClass::getDateField();
        $days = 14;

        $startDate = now()->subDays($days)->startOfDay();
        $query = $modelClass::whereDate($dateField, '>=', $startDate)->where('language_code', 'en-US');

        $totalProcessed = 0;

        $query->chunk(self::CHUNK_SIZE, function ($records) use ($fields, $translations, $externalIdName, &$totalProcessed, $modelClass) {
            $batchData = [];

            foreach ($records as $record) {
                $externalId = $record->{$externalIdName};

                if (!isset($translations[$externalId])) {
                    Log::debug("No translation found for external ID: {$externalId}");
                    continue;
                }

                foreach ($translations[$externalId] as $languageCode => $translationResult) {
                    $toolCalls = $translationResult['response']['body']['choices'][0]['message']['tool_calls'] ?? null;
                    if (!$toolCalls) {
                        Log::debug("No tool calls in translation for external ID: {$externalId}, Language: {$languageCode}");
                        continue;
                    }

                    $storeTranslationCall = collect($toolCalls)->firstWhere('function.name', 'store_translation');
                    if (!$storeTranslationCall || empty($storeTranslationCall['function']['arguments'])) {
                        Log::debug("No store_translation call or arguments for external ID: {$externalId}, Language: {$languageCode}");
                        continue;
                    }

                    $translationData = json_decode($storeTranslationCall['function']['arguments'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::debug("Failed to decode translation arguments for external ID: {$externalId}, Language: {$languageCode}");
                        continue;
                    }

                    $transformedRecord = $this->createTransformedRecord($record, $translationData, $fields, $languageCode);
                    if ($transformedRecord) {
                        Log::debug("Transformed record created for external ID: {$externalId}, Language: {$languageCode}", $transformedRecord);
                        $batchData[] = json_encode($transformedRecord);
                        $totalProcessed++;
                    }
                }
            }

            if (!empty($batchData)) {
                Storage::disk('local')->append(self::OUTPUT_FILE, implode("\n", $batchData));
            }

            $this->info("Processed " . count($records) . " records for {$modelClass}. Total so far: {$totalProcessed}");
        });

        return $totalProcessed;
    }


    private function createTransformedRecord($originalRecord, array $translationData, array $fields, string $languageCode): ?array
    {
        $transformedRecord = [];

        foreach ($fields['originalFields'] as $field) {
            $transformedRecord[$field] = $originalRecord->{$field} ?? null;
        }

        foreach ($fields['translatedFields'] as $field) {
            $transformedRecord[$field] = $translationData[$field] ?? "";
        }

        $transformedRecord['language_code'] = $languageCode;

        return $transformedRecord;
    }
}