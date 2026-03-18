<?php

namespace App\Support;

class AdminPipelineConfig
{
    public static function getConfig(): array
    {
        $config = config('admin_pipeline', []);

        if (!empty($config)) {
            return $config;
        }

        $path = config_path('admin_pipeline.php');

        return is_file($path) ? require $path : [];
    }

    public static function getCitySections(): array
    {
        $config = static::getConfig();
        $cities = $config['cities'] ?? [];
        $ordered = [];

        foreach ($config['city_order'] ?? array_keys($cities) as $cityKey) {
            if (!isset($cities[$cityKey])) {
                continue;
            }

            $city = $cities[$cityKey];
            $ordered[] = [
                'key' => $cityKey,
                'label' => $city['label'],
                'description' => $city['description'],
                'acquisition' => static::formatUiSection($city['acquisition'] ?? null),
                'seeding' => static::formatUiSection($city['seeding'] ?? null),
            ];
        }

        return $ordered;
    }

    public static function getGeneralSections(): array
    {
        return array_values(array_filter(array_map(
            fn (array $section) => static::formatUiSection($section),
            static::getConfig()['general_sections'] ?? []
        )));
    }

    public static function getStageNames(): array
    {
        $stages = [];

        foreach (static::getCitySections() as $city) {
            foreach (['acquisition', 'seeding'] as $type) {
                if (!empty($city[$type]['stage'])) {
                    $stages[] = $city[$type]['stage'];
                }
            }
        }

        foreach (static::getGeneralSections() as $section) {
            if (!empty($section['stage'])) {
                $stages[] = $section['stage'];
            }
        }

        return $stages;
    }

    public static function getOptionDefinitions(): array
    {
        $options = [];

        foreach (static::getConfig()['cities'] ?? [] as $city) {
            foreach (['acquisition', 'seeding'] as $type) {
                if (!empty($city[$type]['option'])) {
                    $options[] = [
                        'name' => $city[$type]['option'],
                        'description' => $city[$type]['option_description'] ?? "Comma-separated list of {$city['label']} {$type} items to run",
                    ];
                }
            }
        }

        foreach (static::getConfig()['general_sections'] ?? [] as $section) {
            $options[] = [
                'name' => $section['option'],
                'description' => $section['option_description'] ?? "Comma-separated list of {$section['label']} to run",
            ];
        }

        return $options;
    }

    public static function getStageCommandMap(callable $optionResolver): array
    {
        $stageMap = [];

        foreach (static::getConfig()['cities'] ?? [] as $city) {
            foreach (['acquisition', 'seeding'] as $type) {
                $section = $city[$type] ?? null;
                if (!$section) {
                    continue;
                }

                $stageMap[$section['stage']] = static::buildStageCommands($section, $optionResolver);
            }
        }

        foreach (static::getConfig()['general_sections'] ?? [] as $section) {
            $stageMap[$section['stage']] = static::buildStageCommands($section, $optionResolver);
        }

        return $stageMap;
    }

    private static function formatUiSection(?array $section): ?array
    {
        if (!$section) {
            return null;
        }

        return [
            'label' => $section['label'],
            'parameter' => $section['parameter'],
            'stage' => $section['stage'],
            'items' => array_map(fn (array $item) => $item['name'], static::resolveItems($section)),
        ];
    }

    private static function buildStageCommands(array $section, callable $optionResolver): array
    {
        if (($section['mode'] ?? null) === 'batch_download') {
            return [
                $section['command'] => [
                    'command' => $section['command'],
                    'params' => [
                        $section['command_parameter'] => $optionResolver($section['option']),
                    ],
                ],
            ];
        }

        $selectedItems = static::parseSelectedItems($optionResolver($section['option']));
        $availableCommands = [];

        foreach (static::resolveItems($section) as $item) {
            $availableCommands[$item['name']] = [
                'command' => $item['command'],
                'params' => $item['params'] ?? [],
            ];
        }

        if (empty($selectedItems)) {
            return $availableCommands;
        }

        return array_filter(
            $availableCommands,
            fn (string $key) => in_array($key, $selectedItems, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private static function resolveItems(array $section): array
    {
        $items = $section['items'] ?? [];

        if (($section['source'] ?? null) === 'boston_datasets') {
            foreach (config('boston_datasets.datasets', []) as $dataset) {
                $items[] = [
                    'name' => $dataset['name'],
                    'command' => 'app:download-boston-dataset-via-scraper',
                    'params' => ['--names' => $dataset['name']],
                ];
            }
        }

        if (($section['source'] ?? null) === 'datasets') {
            foreach (config('datasets.datasets', []) as $dataset) {
                if (($dataset['city'] ?? null) !== ($section['city'] ?? null)) {
                    continue;
                }

                $items[] = [
                    'name' => $dataset['name'],
                    'command' => 'app:download-city-dataset',
                    'params' => [$dataset['name']],
                ];
            }
        }

        foreach ($section['extra_items'] ?? [] as $item) {
            $items[] = $item;
        }

        return $items;
    }

    private static function parseSelectedItems(?string $selectedOption): array
    {
        if (!$selectedOption) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $selectedOption))));
    }
}
