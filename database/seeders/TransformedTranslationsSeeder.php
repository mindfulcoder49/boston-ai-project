<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ThreeOneOneCase;
use App\Models\CrimeData;
use App\Models\BuildingPermit;
use App\Models\ConstructionOffHour;
use App\Models\PropertyViolation;

class TransformedTranslationsSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
        $filePath = 'batches/transformed_translations.jsonl';

        if (!Storage::disk('local')->exists($filePath)) {
            Log::error("Transformed translations file not found: {$filePath}");
            return;
        }

        $lines = explode("\n", Storage::disk('local')->get($filePath));
        $batchData = [];
        $progress = 0;

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $record = json_decode($line, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to parse line: {$line}");
                continue;
            }

            $modelClass = $this->getModelClass($record);
            if (!$modelClass) {
                Log::error("Unknown model for record: " . json_encode($record));
                continue;
            }

            if (isset($record['occurred_on_date'])) {
                $record['occurred_on_date'] = $this->formatDate($record['occurred_on_date']);
            }

            $batchData[$modelClass][] = $record;

            if (count($batchData[$modelClass]) >= self::BATCH_SIZE) {
                $this->insertBatch($modelClass, $batchData[$modelClass]);
                $batchData[$modelClass] = [];
                $progress += self::BATCH_SIZE;
                echo "Processed {$progress} records...\n"; // Replaced $this->info with echo
            }
        }

        // Insert remaining records
        foreach ($batchData as $modelClass => $data) {
            if (!empty($data)) {
                $this->insertBatch($modelClass, $data);
            }
        }

        echo "All records processed and inserted.\n"; // Replaced $this->info with echo
    }

    private function insertBatch(string $modelClass, array $dataBatch): void
    {
        try {
            $tableName = (new $modelClass)->getTable();
            $uniqueKeys = [$modelClass::getExternalIdName(), 'language_code'];

            DB::table($tableName)->upsert(
                $dataBatch,
                $uniqueKeys,
                array_keys($dataBatch[0]) // Update all columns except unique keys
            );

            Log::info("Batch inserted successfully for {$modelClass}.", ['count' => count($dataBatch)]);
        } catch (\Exception $e) {
            Log::error("Failed batch insert for {$modelClass}: " . $e->getMessage());
        }
    }

    private function getModelClass(array $record): ?string
    {
        if (isset($record['case_enquiry_id'])) {
            return ThreeOneOneCase::class;
        } elseif (isset($record['incident_number'])) {
            return CrimeData::class;
        } elseif (isset($record['permitnumber'])) {
            return BuildingPermit::class;
        } elseif (isset($record['app_no'])) {
            return ConstructionOffHour::class;
        } elseif (isset($record['case_no'])) {
            return PropertyViolation::class;
        }

        return null; // Unknown model
    }

    private function formatDate(string $date): ?string
    {
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::error("Invalid date format: {$date}");
            return null;
        }
    }

}
