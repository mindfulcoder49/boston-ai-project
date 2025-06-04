<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class GenerateModelMetadataCommand extends Command
{
    protected $signature = 'generate:model-metadata
                            {--models= : Comma-separated list of model names (e.g., ThreeOneOneCase,PropertyViolation)}
                            {--N=50 : Max distinct values to list for select options}
                            {--output=config/model_metadata_suggestions.php : Output file path}';

    protected $description = 'Generates metadata suggestions for Mappable models.';

    private $defaultExcludedFilterFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function handle(): int
    {
        $nThreshold = (int)$this->option('N');
        if ($nThreshold <= 0) {
            $this->warn("Invalid N value provided or default N is <=0. Resetting N to 50.");
            $nThreshold = 50; // Default value
        }
        $this->info("[CONFIG] N Threshold for distinct values: {$nThreshold}");
        $outputFilePath = base_path($this->option('output'));
        $this->info("[CONFIG] Output file path: {$outputFilePath}");

        $modelClasses = $this->getModelClasses();

        if (empty($modelClasses)) {
            $this->error('No models found to process. Ensure models exist, use the Mappable trait, and are in App\\Models, or specify them with --models.');
            return 1;
        }

        $this->info("[INFO] Processing models: " . implode(', ', array_map(fn($class) => class_basename($class), $modelClasses)));

        $allMetadata = [];

        foreach ($modelClasses as $modelClass) {
            $this->line("[MODEL] Processing model: {$modelClass}");
            try {
                $modelInstance = new $modelClass();
                $tableName = $modelInstance->getTable();
                $this->line("[MODEL] Table name: {$tableName}");
                $modelCasts = $modelInstance->getCasts();
                $this->line("[MODEL] Model casts: " . json_encode($modelCasts));
                $dateField = $modelClass::getDateField();
                $this->line("[MODEL] Date field: {$dateField}");
                $primaryKey = $modelInstance->getKeyName();
                $this->line("[MODEL] Primary key: {$primaryKey}");
                
                // Add current model's primary key to excluded fields for this model only
                $currentModelExcludedFilterFields = $this->defaultExcludedFilterFields;
                if (!in_array($primaryKey, $currentModelExcludedFilterFields)) {
                    $currentModelExcludedFilterFields[] = $primaryKey;
                }
                $this->line("[MODEL] Excluded filter fields for this model: " . json_encode($currentModelExcludedFilterFields));


                $columns = Schema::getColumnListing($tableName);
                $this->line("[SCHEMA] Columns found: " . json_encode($columns));

                $fieldsInfo = [];
                foreach ($columns as $column) {
                    $this->line("  [COLUMN] Processing column: {$tableName}.{$column}");
                    $dbType = Schema::getColumnType($tableName, $column);
                    $this->line("    [DB_INFO] DB Type: {$dbType}");
                    $appType = $this->determineAppType($column, $dbType, $modelCasts);
                    $this->line("    [APP_INFO] Determined App Type: {$appType}");
                    
                    $distinctValues = [];
                    $distinctCount = 0;

                    if (!in_array($appType, ['json']) && !Str::contains(strtolower($dbType), ['blob', 'json'])) {
                        $this->line("    [DISTINCT_VALUES] Column eligible for distinct value check.");
                        try {
                            $rawCountQueryForLog = "SELECT COUNT(DISTINCT {$column}) as count FROM \"{$tableName}\"";
                            $this->line("      [QUERY_LOG] Distinct count query (raw): {$rawCountQueryForLog}");
                            
                            // Using DB facade to get a query builder instance for logging actual SQL for the specific driver
                            $countQueryBuilder = DB::table($tableName)->selectRaw("COUNT(DISTINCT {$column}) as distinct_count_val");
                            $this->line("      [QUERY_LOG] Distinct count query (Builder SQL): " . $countQueryBuilder->toSql());
                            $this->line("      [QUERY_LOG] Distinct count query (Builder Bindings): " . json_encode($countQueryBuilder->getBindings()));

                            $countResult = $countQueryBuilder->first();
                            $distinctCount = $countResult ? (int)$countResult->distinct_count_val : 0;
                            $this->line("      [RESULT_LOG] Distinct count from DB: {$distinctCount}");

                            if ($distinctCount > 0 && $distinctCount <= $nThreshold) {
                                $this->line("      [DISTINCT_VALUES] Count {$distinctCount} is > 0 and <= N ({$nThreshold}). Fetching distinct values.");
                                $fetchQueryBuilder = DB::table($tableName)->select($column)->distinct()->orderBy($column)->limit($nThreshold);
                                $this->line("        [QUERY_LOG] Fetch distinct values SQL: " . $fetchQueryBuilder->toSql());
                                $this->line("        [QUERY_LOG] Fetch distinct values Bindings: " . json_encode($fetchQueryBuilder->getBindings()));
                                
                                $rawPluckedValues = $fetchQueryBuilder->pluck($column);
                                $this->line("        [RESULT_LOG] Raw plucked values count: " . $rawPluckedValues->count());
                                $this->line("        [RESULT_LOG] Raw plucked values (sample): " . json_encode($rawPluckedValues->take(5)->all()));

                                $distinctValues = $rawPluckedValues->filter(fn($val) => $val !== null && (string)$val !== '')
                                                                  ->map(fn($val) => (string)$val)
                                                                  ->unique() // Ensure uniqueness after string conversion and filtering
                                                                  ->values() // Re-index array
                                                                  ->toArray();
                                
                                $this->line("        [RESULT_LOG] Filtered & Mapped distinct values count: " . count($distinctValues));
                                $this->line("        [RESULT_LOG] Filtered & Mapped distinct values (sample): " . json_encode(array_slice($distinctValues, 0, 5)));

                                // The limit($nThreshold) in SQL should mean count($distinctValues) is already <= $nThreshold.
                                // This slice is a safeguard or handles cases where string conversion might change effective distinctness for PHP.
                                if (count($distinctValues) > $nThreshold) {
                                    $this->warn("      [DISTINCT_VALUES] Count of distinct values after filtering/mapping (" . count($distinctValues) . ") is > N ({$nThreshold}). Slicing.");
                                    $distinctValues = array_slice($distinctValues, 0, $nThreshold);
                                    $this->line("        [RESULT_LOG] Sliced distinct values count: " . count($distinctValues));
                                }
                            } else {
                                $this->line("      [DISTINCT_VALUES] Distinct count {$distinctCount} is 0 or > N ({$nThreshold}). Not fetching values for options list.");
                            }
                        } catch (\Exception $e) {
                            $this->warn("    [ERROR_LOG] Could not fetch distinct values for {$tableName}.{$column}: " . $e->getMessage());
                            $distinctCount = $nThreshold + 1; // Assume too many if error
                            $this->line("      [DISTINCT_VALUES] Marked distinctCount as " . ($nThreshold + 1) . " due to error.");
                        }
                    } else {
                        $this->line("    [DISTINCT_VALUES] Column type '{$appType}' (DB: '{$dbType}') is excluded from distinct value check.");
                        $distinctCount = $nThreshold + 1; // Assume too many for text/blob types
                        $this->line("      [DISTINCT_VALUES] Marked distinctCount as " . ($nThreshold + 1) . ".");
                    }

                    $isDateField = $column === $dateField;
                    $this->line("    [INFO] Is date field ('{$dateField}'): " . ($isDateField ? 'Yes' : 'No'));

                    $fieldsInfo[$column] = [
                        'name' => $column,
                        'dbType' => $dbType,
                        'appType' => $appType,
                        'distinctValues' => $distinctValues, // Populated if count <= N
                        'distinctCount' => $distinctCount,   // Actual count from DB, or N+1 if not fetched/error/excluded
                        'isDateField' => $isDateField,
                    ];
                    $this->line("    [FIELD_INFO_SUMMARY] For column '{$column}': AppType='{$appType}', DBCount='{$distinctCount}', FetchedOptions=" . count($distinctValues));
                }
                
                $this->line("[GENERATION] Generating metadata for {$modelClass}...");
                $allMetadata[$modelClass] = [
                    'filterableFieldsDescription' => $this->generateFilterableFieldsDescription($fieldsInfo, $nThreshold, $modelClass, $currentModelExcludedFilterFields),
                    'contextData' => $this->generateContextData($fieldsInfo, $modelClass, $currentModelExcludedFilterFields),
                    'searchableColumns' => $this->generateSearchableColumns($fieldsInfo, $modelClass),
                    'gptSchemaProperties' => $this->generateGptSchemaProperties($fieldsInfo, $nThreshold, $modelClass, $currentModelExcludedFilterFields),
                ];
                $this->line("[GENERATION] Finished generating metadata for {$modelClass}.");

            } catch (\Exception $e) {
                $this->error("[ERROR] Error processing model {$modelClass}: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            }
        }

        $this->line("[OUTPUT] Preparing output content...");
        $outputContent = "<?php\n\nreturn " . $this->varExport($allMetadata) . ";\n";

        File::ensureDirectoryExists(dirname($outputFilePath));
        File::put($outputFilePath, $outputContent);

        $this->info("[SUCCESS] Model metadata suggestions generated successfully at: {$outputFilePath}");
        return 0;
    }

    private function getModelClasses(): array
    {
        $modelNamesOption = $this->option('models');
        if ($modelNamesOption) {
            $modelNames = explode(',', $modelNamesOption);
            $modelClasses = [];
            foreach ($modelNames as $modelName) {
                $className = "App\\Models\\" . trim($modelName);
                if (class_exists($className)) {
                    if (method_exists($className, 'getMappableTraitUsageCheck')) { // Check for a dummy method from Mappable
                         $modelClasses[] = $className;
                    } else {
                        $this->warn("Model class $className does not appear to use the Mappable trait.");
                    }
                } else {
                    $this->warn("Model class $className not found.");
                }
            }
            return $modelClasses;
        }

        // Discover models in App\Models namespace using Mappable trait
        $discoveredClasses = [];
        $path = app_path('Models');
        if (!File::isDirectory($path)) {
            return [];
        }

        $allFiles = (new Finder())->in($path)->files()->name('*.php');
        foreach ($allFiles as $file) {
            $className = 'App\\Models\\' . $file->getBasename('.php');
            if (class_exists($className, true)) {
                try {
                    $reflection = new ReflectionClass($className);
                    if (in_array('App\\Models\\Concerns\\Mappable', $reflection->getTraitNames()) && !$reflection->isAbstract() && !$reflection->isInterface() && !$reflection->isTrait()) {
                        $discoveredClasses[] = $className;
                    }
                } catch (\ReflectionException $e) {
                    $this->warn("Could not reflect class {$className}: " . $e->getMessage());
                }
            }
        }
        return $discoveredClasses;
    }

    private function determineAppType(string $column, string $dbType, array $casts): string
    {
        if (isset($casts[$column])) {
            $castType = $casts[$column];
            // Simplified mapping from Laravel cast types
            if (in_array($castType, ['int', 'integer', 'real', 'float', 'double', 'decimal'])) return 'number';
            if (in_array($castType, ['bool', 'boolean'])) return 'boolean';
            if (in_array($castType, ['date', 'datetime', 'custom_datetime', 'timestamp'])) return 'datetime';
            if (in_array($castType, ['array', 'json', 'object', 'collection'])) return 'json';
            return 'string'; // Default for other casts
        }

        if (Str::contains($dbType, ['char', 'varchar', 'text', 'string'])) return 'string';
        if (Str::contains($dbType, ['int', 'integer', 'serial', 'bigint', 'smallint'])) return 'number';
        if (Str::contains($dbType, ['float', 'double', 'decimal', 'numeric', 'real'])) return 'number';
        if (Str::contains($dbType, ['bool'])) return 'boolean';
        if (Str::contains($dbType, ['date', 'time'])) return 'datetime'; // Includes timestamp
        if (Str::contains($dbType, ['json'])) return 'json';

        return 'string'; // Default
    }
    
    private function determineUiType(array $fieldInfo, int $nThreshold): string
    {
        $this->line("      [UI_TYPE_DECISION] For '{$fieldInfo['name']}': AppType='{$fieldInfo['appType']}', DBDistinctCount='{$fieldInfo['distinctCount']}', NThreshold='{$nThreshold}', ActualFetchedOptions=" . count($fieldInfo['distinctValues']) . ", IsDateField=" . ($fieldInfo['isDateField'] ? 'Yes':'No'));

        if ($fieldInfo['appType'] === 'boolean') {
            $this->line("        [UI_TYPE_DECISION] Determined UI Type: boolean (AppType is boolean)");
            return 'boolean';
        }
        
        // Condition for select: DB count is known, positive, and within threshold, AND we actually have more than one distinct value to show.
        if ($fieldInfo['distinctCount'] > 0 && $fieldInfo['distinctCount'] <= $nThreshold && count($fieldInfo['distinctValues']) > 1) {
            $this->line("        [UI_TYPE_DECISION] Determined UI Type: select (DistinctCount {$fieldInfo['distinctCount']} <= N {$nThreshold} AND FetchedOptions " . count($fieldInfo['distinctValues']) . " > 1)");
            return 'select';
        }
        
        if ($fieldInfo['appType'] === 'number') {
            $this->line("        [UI_TYPE_DECISION] Determined UI Type: number (AppType is number)");
            return 'number';
        }
        
        if ($fieldInfo['appType'] === 'datetime' && !$fieldInfo['isDateField']) {
            $this->line("        [UI_TYPE_DECISION] Determined UI Type: date (AppType is datetime and not the main date field)");
            return 'date';
        }
        
        $this->line("        [UI_TYPE_DECISION] Determined UI Type: text (Default)");
        return 'text';
    }

    private function generateFilterableFieldsDescription(array $fieldsInfo, int $nThreshold, string $modelClass, array $excludedFilterFields): array
    {
        $this->line("  [FILTERABLE_FIELDS] Generating for {$modelClass}");
        $items = [
            ['name' => 'search_term', 'label' => 'General Search', 'type' => 'text', 'placeholder' => 'Search across all fields...']
        ];

        foreach ($fieldsInfo as $column => $info) {
            $this->line("    [FILTERABLE_FIELDS] Processing column '{$column}' for filterable fields description");
            if (in_array($column, $excludedFilterFields) && !$info['isDateField']) {
                $this->line("      [FILTERABLE_FIELDS] Column '{$column}' is excluded and not a date field. Skipping.");
                continue;
            }
            if ($info['isDateField']) {
                $this->line("      [FILTERABLE_FIELDS] Column '{$column}' is the main date field. Skipping (handled by start/end_date).");
                continue;
            }

            $label = Str::title(str_replace('_', ' ', $column));
            $uiType = $this->determineUiType($info, $nThreshold); // Logging is inside determineUiType
            $this->line("      [FILTERABLE_FIELDS] Label: '{$label}', Determined UI Type: '{$uiType}'");
            
            if ($uiType === 'date') {
                $items[] = [
                    'name' => "{$column}_start",
                    'label' => "{$label} Start",
                    'type' => 'date',
                    'placeholder' => "Start date for {$label}"
                ];
                $items[] = [
                    'name' => "{$column}_end",
                    'label' => "{$label} End",
                    'type' => 'date',
                    'placeholder' => "End date for {$label}"
                ];
                $this->line("      [FILTERABLE_FIELDS] Added start/end date inputs for '{$column}'.");
            } elseif ($uiType === 'number') {
                $items[] = [
                    'name' => "{$column}_min",
                    'label' => "{$label} Min",
                    'type' => 'number',
                    'placeholder' => "Min value for {$label}"
                ];
                $items[] = [
                    'name' => "{$column}_max",
                    'label' => "{$label} Max",
                    'type' => 'number',
                    'placeholder' => "Max value for {$label}"
                ];
                $this->line("      [FILTERABLE_FIELDS] Added min/max number inputs for '{$column}'.");
            } else {
                $placeholder = "Enter {$label}";
                if ($uiType === 'select') $placeholder = "Select {$label}";

                $fieldDesc = ['name' => $column, 'label' => $label, 'type' => $uiType, 'placeholder' => $placeholder];

                if ($uiType === 'select' && !empty($info['distinctValues'])) {
                    $this->line("      [FILTERABLE_FIELDS] UI Type is select and has distinct values. Count: " . count($info['distinctValues']));
                    $options = array_map(fn($val) => ['value' => (string)$val, 'label' => (string)$val], $info['distinctValues']);
                    // If boolean-like values, ensure consistent labels
                    if (count($options) == 2 && 
                        ( (isset($options[0]['value']) && strtolower($options[0]['value']) === '0' && isset($options[1]['value']) && strtolower($options[1]['value']) === '1') ||
                          (isset($options[0]['value']) && strtolower($options[0]['value']) === 'false' && isset($options[1]['value']) && strtolower($options[1]['value']) === 'true') ||
                          (isset($options[0]['value']) && strtolower($options[0]['value']) === 'no' && isset($options[1]['value']) && strtolower($options[1]['value']) === 'yes') 
                        )
                    ) {
                         $this->line("        [FILTERABLE_FIELDS] Options look boolean-like. Promoting to UI type 'boolean'.");
                         $fieldDesc['type'] = 'boolean'; 
                         unset($fieldDesc['options']);
                         unset($fieldDesc['placeholder']);
                    } else {
                        $fieldDesc['options'] = $options;
                        $this->line("        [FILTERABLE_FIELDS] Added " . count($options) . " options.");
                    }
                }
                 if ($fieldDesc['type'] === 'boolean') { 
                    $this->line("      [FILTERABLE_FIELDS] UI Type is boolean. Standardizing representation.");
                    unset($fieldDesc['placeholder']);
                    unset($fieldDesc['options']);
                }
                $this->line("      [FILTERABLE_FIELDS] Final field description for '{$column}': " . json_encode($fieldDesc));
                $items[] = $fieldDesc;
            }
        }
        return $items;
    }

    private function generateContextData(array $fieldsInfo, string $modelClass, array $excludedFilterFields): string
    {
        $this->line("  [CONTEXT_DATA] Generating for {$modelClass}");
        $modelNamePlural = Str::plural($modelClass::getModelNameForHumans());
        $context = "Dataset of {$modelNamePlural}.";
        
        $sampleFilterableFields = [];
        $count = 0;
        foreach ($fieldsInfo as $column => $info) {
            if (in_array($column, $excludedFilterFields) && !$info['isDateField']) continue;
            if ($info['isDateField']) {
                 $sampleFilterableFields[] = "date (" . Str::title(str_replace('_', ' ', $column)) . ")";
                 $count++;
                 continue;
            }

            $uiType = $this->determineUiType($info, (int)$this->option('N')); // N is already int, but defensive
            if ($uiType === 'select' || $uiType === 'text' || $uiType === 'boolean') {
                 $sampleFilterableFields[] = Str::lower(Str::title(str_replace('_', ' ', $column)));
                 $count++;
            }
            if ($count >= 3) break;
        }

        if (!empty($sampleFilterableFields)) {
            $context .= " Filter by attributes like " . implode(', ', $sampleFilterableFields) . ".";
        }
        $this->line("    [CONTEXT_DATA] Generated context: {$context}");
        return $context;
    }

    private function generateSearchableColumns(array $fieldsInfo, string $modelClass): array
    {
        $searchable = [];
        // Try to use existing constant if defined
        if (defined("{$modelClass}::SEARCHABLE_COLUMNS")) {
            $searchable = $modelClass::SEARCHABLE_COLUMNS;
        } else {
            foreach ($fieldsInfo as $column => $info) {
                if ($info['appType'] === 'string' || ($info['appType'] === 'number' && !Str::contains($column, '_id'))) {
                    $searchable[] = $column;
                }
            }
        }
        return $searchable;
    }

    private function generateGptSchemaProperties(array $fieldsInfo, int $nThreshold, string $modelClass, array $excludedFilterFields): array
    {
        $this->line("  [GPT_SCHEMA] Generating for {$modelClass}");
        $dateField = $modelClass::getDateField();
        $properties = [
            'search_term' => ['type' => 'string', 'description' => 'A general search term to query across multiple text fields.'],
            'start_date' => ['type' => 'string', 'format' => 'date', 'description' => "Start date for '{$dateField}' (YYYY-MM-DD)"],
            'end_date' => ['type' => 'string', 'format' => 'date', 'description' => "End date for '{$dateField}' (YYYY-MM-DD)"],
            'limit' => ['type' => 'integer', 'description' => 'Limit the number of records. Default is 1000, max 5000.'],
        ];

        foreach ($fieldsInfo as $column => $info) {
            $this->line("    [GPT_SCHEMA] Processing column '{$column}' for GPT schema properties");
            if (in_array($column, $excludedFilterFields) && !$info['isDateField']) {
                $this->line("      [GPT_SCHEMA] Column '{$column}' is excluded and not a date field. Skipping.");
                continue;
            }
            if ($info['isDateField']) continue; // Handled by start_date/end_date

            $label = Str::title(str_replace('_', ' ', $column));
            $uiType = $this->determineUiType($info, $nThreshold);
            $description = "Filter by {$label}.";
            $gptType = 'string'; // Default GPT type

            if ($uiType === 'date') {
                $properties["{$column}_start"] = ['type' => 'string', 'format' => 'date', 'description' => "Start date for {$label} (YYYY-MM-DD)"];
                $properties["{$column}_end"] = ['type' => 'string', 'format' => 'date', 'description' => "End date for {$label} (YYYY-MM-DD)"];
                $this->line("      [GPT_SCHEMA] Added start/end date properties for '{$column}'.");
                continue; // Move to next column
            }

            switch ($uiType) {
                case 'boolean':
                    $gptType = 'boolean';
                    break;
                case 'number':
                    // Determine if integer or general number for GPT schema
                    $gptNumericType = ($info['appType'] === 'integer' || (Str::contains($info['dbType'], 'int') && !Str::contains($info['dbType'], 'interval'))) ? 'integer' : 'number';
                    $properties["{$column}_min"] = ['type' => $gptNumericType, 'description' => "Minimum value for {$label}."];
                    $properties["{$column}_max"] = ['type' => $gptNumericType, 'description' => "Maximum value for {$label}."];
                    $this->line("      [GPT_SCHEMA] Added min/max {$gptNumericType} properties for '{$column}'.");
                    continue 2; // Skip default property assignment at the end of the loop
                case 'select':
                    $gptType = 'string'; // Default for select, might be overridden to boolean
                     if (!empty($info['distinctValues'])) {
                        $options = array_map(fn($val) => (string)$val, $info['distinctValues']);
                        if (count($options) == 2 && 
                            ( (strtolower($options[0]) === '0' && strtolower($options[1]) === '1') ||
                              (strtolower($options[0]) === 'false' && strtolower($options[1]) === 'true') ||
                              (strtolower($options[0]) === 'no' && strtolower($options[1]) === 'yes') 
                            )
                        ) {
                             $gptType = 'boolean'; // Override to boolean
                             $description = "Filter by {$label} (true/false).";
                        } else {
                            $description .= ' Possible values: ' . implode(', ', $options) . '.';
                        }
                    }
                    break;
                // case 'multiselect': // If enabling multiselect UI type
                //     $properties[$column] = ['type' => 'array', 'description' => $description, 'items' => ['type' => 'string']];
                //     if (!empty($info['distinctValues'])) {
                //         $options = array_map(fn($val) => (string)$val, $info['distinctValues']);
                //         $properties[$column]['description'] .= ' Possible values: ' . implode(', ', $options) . '.';
                //     }
                //     $this->line("      [GPT_SCHEMA] Added array property for multiselect '{$column}'.");
                //     continue 2; // Skip default property assignment
                default: // text
                    $gptType = 'string';
                    break;
            }
            
            $this->line("      [GPT_SCHEMA] Label: '{$label}', UI Type: '{$uiType}', GPT Type: '{$gptType}'");
            if ($uiType === 'select' && !empty($info['distinctValues'])) {
                if ($gptType === 'boolean') { // Check if it was promoted to boolean
                    $this->line("        [GPT_SCHEMA] Options for '{$column}' determined as boolean for GPT. Description: {$description}");
                } else {
                    $this->line("        [GPT_SCHEMA] Added distinct values to GPT description for '{$column}'. Count: " . count($info['distinctValues']));
                }
            }
            
            $propertyDefinition = ['type' => $gptType, 'description' => $description];
            // No need to add format:date here as uiType 'date' is handled separately above

            $properties[$column] = $propertyDefinition;
            $this->line("      [GPT_SCHEMA] Final GPT property for '{$column}': " . json_encode($properties[$column]));
        }
        return $properties;
    }
    
    private function varExport($variable, $indentation = ''): string
    {
        if (is_array($variable)) {
            $contents = "[\n";
            foreach ($variable as $key => $value) {
                $contents .= $indentation . "    " . var_export($key, true) . " => ";
                $contents .= $this->varExport($value, $indentation . "    ") . ",\n";
            }
            $contents .= $indentation . "]";
            return $contents;
        }
        return var_export($variable, true);
    }
}
