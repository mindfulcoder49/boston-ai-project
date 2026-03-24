<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CityLandingController extends Controller
{
    private const LANGUAGE_OPTIONS = [
        ['code' => 'en-US', 'label' => 'English', 'name' => 'English'],
        ['code' => 'es-MX', 'label' => 'Español', 'name' => 'Spanish'],
        ['code' => 'pt-BR', 'label' => 'Português', 'name' => 'Portuguese'],
        ['code' => 'ht-HT', 'label' => 'Kreyòl', 'name' => 'Haitian Creole'],
        ['code' => 'zh-CN', 'label' => '中文', 'name' => 'Simplified Chinese'],
        ['code' => 'vi-VN', 'label' => 'Tiếng Việt', 'name' => 'Vietnamese'],
    ];

    private const DATASET_LABELS = [
        'Crime' => 'Crime Reports',
        '311 Case' => '311 Requests',
        'Building Permit' => 'Building Permits',
        'Property Violation' => 'Property Violations',
        'Food Inspection' => 'Food Inspections',
        'Construction Off Hour' => 'After-Hours Construction Permits',
        'Car Crash' => 'Crash Reports',
    ];

    public function show(string $citySlug): Response
    {
        $cityKey = $this->resolveCityKeyFromSlug($citySlug);
        abort_unless($cityKey, 404);

        $cityConfig = config("cities.cities.{$cityKey}");
        abort_unless(is_array($cityConfig), 404);
        $dataTypes = $this->getCityDatasetLabels($cityConfig);
        $fullMapUrl = $this->resolveFullMapUrl($cityKey, $cityConfig);
        $seoTitle = $this->getCitySeoTitle($cityConfig['name'], $dataTypes);
        $seoDescription = $this->getCitySeoDescription($cityConfig['name'], $dataTypes);

        return Inertia::render('CityMapLite', [
            'city' => [
                'key' => $cityKey,
                'slug' => $citySlug,
                'name' => $cityConfig['name'],
                'latitude' => $cityConfig['latitude'],
                'longitude' => $cityConfig['longitude'],
                'defaultRadius' => $cityKey === 'everett' ? 0.35 : 0.25,
                'tagline' => $this->getCityTagline($cityConfig['name']),
                'intro' => $this->getCityIntro($cityConfig['name']),
                'fullMapUrl' => $fullMapUrl,
                'dataTypes' => $dataTypes,
                'seoTitle' => $seoTitle,
                'seoDescription' => $seoDescription,
                'overview' => $this->getCityOverview($cityConfig['name'], $dataTypes),
                'howToUse' => $this->getCityHowToUse($cityConfig['name']),
                'dataUpdateNote' => 'Data refresh schedules vary by source, so recent records may arrive at different times.',
                'relatedLinks' => $this->getRelatedLinks($fullMapUrl),
            ],
            'languageOptions' => self::LANGUAGE_OPTIONS,
        ]);
    }

    public function translateRecord(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'targetLanguage' => 'required|string|in:' . implode(',', array_column(self::LANGUAGE_OPTIONS, 'code')),
            'title' => 'required|string|max:500',
            'summary' => 'nullable|string|max:3000',
            'details' => 'required|array|min:1|max:12',
            'details.*.label' => 'required|string|max:120',
            'details.*.value' => 'required|string|max:1500',
        ]);

        if ($validated['targetLanguage'] === 'en-US') {
            return response()->json([
                'title' => $validated['title'],
                'summary' => $validated['summary'] ?? '',
                'details' => $validated['details'],
                'language' => 'en-US',
            ]);
        }

        $cacheKey = 'city_landing_translation:' . sha1(json_encode($validated));

        $translated = Cache::remember($cacheKey, now()->addDays(30), function () use ($validated) {
            return $this->requestTranslation($validated);
        });

        return response()->json($translated);
    }

    private function resolveCityKeyFromSlug(string $citySlug): ?string
    {
        $normalized = str_replace('-', '_', Str::lower($citySlug));

        if (config("cities.cities.{$normalized}")) {
            return $normalized;
        }

        return null;
    }

    private function resolveFullMapUrl(string $cityKey, array $cityConfig): string
    {
        $modelRegistry = config('data_map.models', []);
        $modelClasses = $cityConfig['models'] ?? [];
        $dataTypeKeys = [];

        foreach ($modelRegistry as $dataTypeKey => $modelClass) {
            if (in_array($modelClass, $modelClasses, true)) {
                $dataTypeKeys[] = $dataTypeKey;
            }
        }

        if (count($dataTypeKeys) === 1) {
            return route('data-map.index', ['dataType' => $dataTypeKeys[0]]);
        }

        if (!empty($dataTypeKeys)) {
            return route('data-map.combined') . '?types=' . implode(',', $dataTypeKeys);
        }

        return route('map.index', [
            'lat' => $cityConfig['latitude'],
            'lng' => $cityConfig['longitude'],
        ]);
    }

    private function getCityTagline(string $cityName): string
    {
        return "See recent public safety activity around {$cityName}.";
    }

    private function getCityIntro(string $cityName): string
    {
        return "No account needed. Tap the map, use your location, or search an address in {$cityName}.";
    }

    private function getCityDatasetLabels(array $cityConfig): array
    {
        $labels = collect($cityConfig['models'] ?? [])
            ->filter(fn ($modelClass) => method_exists($modelClass, 'getAlcivartechTypeForStyling'))
            ->map(function ($modelClass) {
                $dataType = $modelClass::getAlcivartechTypeForStyling();
                return self::DATASET_LABELS[$dataType] ?? Str::headline(Str::plural($dataType));
            })
            ->unique()
            ->values()
            ->all();

        return is_array($labels) ? $labels : [];
    }

    private function getCityOverview(string $cityName, array $dataTypes): string
    {
        $datasetPhrase = $this->joinNaturalLanguageList(
            array_map(fn ($label) => Str::lower($label), $dataTypes)
        );

        if ($datasetPhrase === '') {
            return "PublicDataWatch helps you explore recent public records in {$cityName} with a mobile-friendly map, address search, and fast record summaries.";
        }

        return "PublicDataWatch helps you explore recent {$datasetPhrase} in {$cityName} with a mobile-friendly map, address search, and fast record summaries.";
    }

    private function getCitySeoTitle(string $cityName, array $dataTypes): string
    {
        if ($dataTypes === ['Crime Reports']) {
            return "{$cityName} Crime Map and Public Safety Data | PublicDataWatch";
        }

        if ($dataTypes === ['311 Requests']) {
            return "{$cityName} 311 Map and City Service Data | PublicDataWatch";
        }

        if (count($dataTypes) === 1) {
            return "{$cityName} {$dataTypes[0]} Map | PublicDataWatch";
        }

        return "{$cityName} Public Data Map and Neighborhood Activity | PublicDataWatch";
    }

    private function getCitySeoDescription(string $cityName, array $dataTypes): string
    {
        $datasetPhrase = $this->joinNaturalLanguageList(
            array_map(fn ($label) => Str::lower($label), $dataTypes)
        );

        if ($datasetPhrase === '') {
            return "Explore recent public records in {$cityName} with an interactive map, address search, and multilingual record summaries.";
        }

        return "Explore recent {$datasetPhrase} in {$cityName} with an interactive map, address search, and multilingual record summaries.";
    }

    private function getCityHowToUse(string $cityName): string
    {
        return "Start with your current location or search an address in {$cityName}, then tap markers to read plain-language details. When you want a wider view, open the full explore map to compare more records across the city.";
    }

    private function getRelatedLinks(string $fullMapUrl): array
    {
        return [
            ['label' => 'Open full explore map', 'url' => $fullMapUrl],
            ['label' => 'Read city data news', 'url' => route('news.index')],
            ['label' => 'Learn how to use the maps', 'url' => route('help.users')],
        ];
    }

    private function joinNaturalLanguageList(array $values): string
    {
        $values = array_values(array_filter($values, fn ($value) => is_string($value) && $value !== ''));
        $count = count($values);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $values[0];
        }

        if ($count === 2) {
            return "{$values[0]} and {$values[1]}";
        }

        $last = array_pop($values);

        return implode(', ', $values) . ", and {$last}";
    }

    private function requestTranslation(array $validated): array
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            abort(500, 'OpenAI API key is not configured.');
        }

        $languageName = collect(self::LANGUAGE_OPTIONS)
            ->firstWhere('code', $validated['targetLanguage'])['name'] ?? $validated['targetLanguage'];

        $response = Http::timeout(25)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-5-mini',
                'reasoning' => ['effort' => 'low'],
                'instructions' => "Translate the provided public-safety record into {$languageName}. Preserve proper nouns, addresses, case numbers, and dates unless natural translation requires a light formatting change. Return JSON only with keys title, summary, and details. The details field must be an array of objects with string keys label and value.",
                'input' => json_encode([
                    'title' => $validated['title'],
                    'summary' => $validated['summary'] ?? '',
                    'details' => $validated['details'],
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'max_output_tokens' => 900,
            ]);

        if (!$response->successful()) {
            abort(502, 'Translation request failed.');
        }

        $payload = $response->json();
        $responseText = $payload['output_text'] ?? $this->extractResponseText($payload);
        $decoded = is_string($responseText) ? json_decode($responseText, true) : null;

        if (!is_array($decoded) || !isset($decoded['title'], $decoded['details']) || !is_array($decoded['details'])) {
            abort(502, 'Translation response was not valid JSON.');
        }

        return [
            'title' => (string) ($decoded['title'] ?? ''),
            'summary' => (string) ($decoded['summary'] ?? ''),
            'details' => collect($decoded['details'])
                ->filter(fn ($detail) => is_array($detail) && isset($detail['label'], $detail['value']))
                ->map(fn ($detail) => [
                    'label' => (string) $detail['label'],
                    'value' => (string) $detail['value'],
                ])
                ->values()
                ->all(),
            'language' => $validated['targetLanguage'],
        ];
    }

    private function extractResponseText(array $payload): ?string
    {
        foreach ($payload['output'] ?? [] as $outputItem) {
            foreach ($outputItem['content'] ?? [] as $contentItem) {
                if (($contentItem['type'] ?? null) === 'output_text' && isset($contentItem['text'])) {
                    return $contentItem['text'];
                }
            }
        }

        return null;
    }
}
