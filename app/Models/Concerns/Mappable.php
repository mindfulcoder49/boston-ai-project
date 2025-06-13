<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Log; // Added
use Illuminate\Support\Facades\Config; // Added

trait Mappable
{
    private static $modelMetadataSuggestions = null; // Added for caching

    /**
     * Helper method to load model metadata suggestions.
     */
    private static function loadModelMetadataSuggestions(): ?array // Added
    {
        if (self::$modelMetadataSuggestions === null) {
            self::$modelMetadataSuggestions = Config::get('model_metadata_suggestions', []);
        }
        return self::$modelMetadataSuggestions[static::class] ?? null;
    }

    /**
     * Get a human-readable name for the model.
     * Example: "Crime Data", "311 Cases".
     */
    abstract public static function getHumanName(): string;

    /**
     * Get the CSS class for the map icon.
     */
    abstract public static function getIconClass(): string;

    /**
     * Get the type string used for styling data points on the map (e.g., in DataMapDisplay).
     */
    abstract public static function getAlcivartechTypeForStyling(): string;

    /**
     * Get the name of the latitude field for this model.
     */
    abstract public static function getLatitudeField(): string;

    /**
     * Get the name of the longitude field for this model.
     */
    abstract public static function getLongitudeField(): string;

    /**
     * Get the name of the date field used for filtering by date ranges.
     */
    abstract public static function getDateField(): string;

    /**
     * Get the date value for a specific record, typically from the field specified by getDateField().
     */
    abstract public function getDate(): ?string;

    /**
     * Get the name of the external ID field.
     */
    abstract public static function getExternalIdName(): string;

    /**
     * Get the external ID value for a specific record.
     */
    abstract public function getExternalId(): string;

    /**
     * Get the configuration for displaying model data in a map popup.
     * Should return an array with keys like:
     * 'mainIdentifierLabel', 'mainIdentifierField',
     * 'descriptionLabel', 'descriptionField', 'additionalFields'.
     */
    abstract public static function getPopupConfig(): array;

    /**
     * Get a string describing filterable fields and their types for AI.
     * This description guides the AI in constructing filter objects.
     */
    public static function getFilterableFieldsDescription(): string // Implemented
    {
        $suggestions = self::loadModelMetadataSuggestions();
        if ($suggestions && isset($suggestions['filterableFieldsDescription'])) {
            return json_encode($suggestions['filterableFieldsDescription']);
        }
        Log::warning("Mappable Trait: No 'filterableFieldsDescription' suggestions found for model " . static::class);
        return json_encode([['name' => 'search_term', 'label' => 'General Search', 'type' => 'text', 'placeholder' => 'Search...']]); // Basic fallback
    }

    /**
     * Get context data for AI, e.g., unique values for categorical fields or general dataset information.
     */
    public static function getContextData(): string // Implemented
    {
        $suggestions = self::loadModelMetadataSuggestions();
        if ($suggestions && isset($suggestions['contextData'])) {
            return $suggestions['contextData'];
        }
        Log::warning("Mappable Trait: No 'contextData' suggestions found for model " . static::class);
        return "Dataset of " . static::getHumanName() . "."; // Use new abstract method
    }

    /**
     * Get an array of searchable/filterable column names.
     */
    public static function getSearchableColumns(): array // Implemented
    {
        $suggestions = self::loadModelMetadataSuggestions();
        if ($suggestions && isset($suggestions['searchableColumns'])) {
            return $suggestions['searchableColumns'];
        }
        // Fallback: Check for a constant in the model, then default to empty array
        if (defined(static::class . '::SEARCHABLE_COLUMNS')) {
            Log::warning("Mappable Trait: No 'searchableColumns' suggestions found for model " . static::class . ". Falling back to SEARCHABLE_COLUMNS constant.");
            return static::SEARCHABLE_COLUMNS;
        }
        Log::warning("Mappable Trait: No 'searchableColumns' suggestions or SEARCHABLE_COLUMNS constant found for model " . static::class);
        return []; // Basic fallback
    }

    /**
     * Get the GPT function schema for this model.
     */
    public static function getGptFunctionSchema(): array // Implemented
    {
        $suggestions = self::loadModelMetadataSuggestions();
        $dateField = static::getDateField(); // Assumes getDateField is always implemented in the model

        $filterProperties = [
            'search_term' => ['type' => 'string', 'description' => 'A general search term to query across multiple text fields.'],
            'start_date' => ['type' => 'string', 'format' => 'date', 'description' => "Start date for '{$dateField}' (YYYY-MM-DD)"],
            'end_date' => ['type' => 'string', 'format' => 'date', 'description' => "End date for '{$dateField}' (YYYY-MM-DD)"],
            'limit' => ['type' => 'integer', 'description' => 'Limit the number of records. Default is 1000, max 5000.'],
        ];

        if ($suggestions && isset($suggestions['gptSchemaProperties']) && is_array($suggestions['gptSchemaProperties'])) {
            // Merge suggested properties, ensuring our defaults are not overwritten if they exist in suggestions
            // but allowing suggestions to add new ones or modify non-default ones.
            foreach ($suggestions['gptSchemaProperties'] as $key => $value) {
                if (!array_key_exists($key, $filterProperties)) {
                    $filterProperties[$key] = $value;
                } elseif (is_array($value) && isset($value['description'])) { // Allow overriding description
                    $filterProperties[$key]['description'] = $value['description'];
                     if (isset($value['type'])) { // Allow overriding type too
                        $filterProperties[$key]['type'] = $value['type'];
                    }
                }
            }
        } else {
            Log::warning("Mappable Trait: No 'gptSchemaProperties' suggestions found for model " . static::class . ". Using basic schema.");
            // If no suggestions, we might try to build from filterableFieldsDescription as a complex fallback,
            // or just rely on the basic properties defined above. For now, keep it simple.
        }
        
        $contextData = static::getContextData(); // This will use the trait's implementation of getContextData

        return [
            'type' => 'function',
            'function' => [
                'name' => 'generate_data_filters',
                'description' => 'Generate filters for the data query about ' . static::getHumanName() . '. Use the properties schema for available filter fields and their types. Context: ' . $contextData, // Use new abstract method
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'filters' => [
                            'type' => 'object',
                            'description' => "Key-value pairs for filtering. Available fields and their types are defined in the properties of this 'filters' object.",
                            'properties' => $filterProperties,
                        ],
                    ],
                    'required' => ['filters'],
                ],
            ],
        ];
    }

    /**
     * Dummy method to check for Mappable trait usage.
     * Used by GenerateModelMetadataCommand.
     */
    public static function getMappableTraitUsageCheck(): bool // Added
    {
        return true;
    }
}
