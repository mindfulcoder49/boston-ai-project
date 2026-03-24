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

    public function show(string $citySlug): Response
    {
        $cityKey = $this->resolveCityKeyFromSlug($citySlug);
        abort_unless($cityKey, 404);

        $cityConfig = config("cities.cities.{$cityKey}");
        abort_unless(is_array($cityConfig), 404);

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
                'fullMapUrl' => $this->resolveFullMapUrl($cityKey, $cityConfig),
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
