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
    private const CITY_CONTENT = [
        'boston' => [
            'searchPlaceholder' => 'Search a Boston address',
            'tagline' => 'Boston is the fullest PublicDataWatch city: crime, 311, permits, inspections, violations, and crashes in one place.',
            'intro' => 'Start with one Boston address or intersection, then widen out when you want a fuller read on neighborhood safety, city services, and development activity.',
            'overview' => 'Use the Boston page when crime alone is not enough. It combines public safety, 311, inspections, permits, violations, and crash context around one place so you can get a faster neighborhood read.',
            'howToUse' => 'Search a Boston address, tap the nearby records that matter, then open the full map when you want more layers, more area, or more time to compare.',
            'dataUpdateNote' => 'Boston combines several source schedules, so crime, 311, permits, inspections, violations, and crashes can refresh on different cadences.',
            'focusAreas' => ['Crime', '311', 'Permits', 'Inspections', 'Crashes'],
            'highlights' => [
                [
                    'title' => 'Best for',
                    'body' => 'Renters, buyers, reporters, and residents who want both public-safety and city-operations context around one block.',
                ],
                [
                    'title' => 'What makes Boston different',
                    'body' => 'Boston has the broadest multi-dataset mix on the site, so this page works as a stronger first neighborhood scan than the crime-only cities.',
                ],
                [
                    'title' => 'When to go deeper',
                    'body' => 'Open the full map after the first scan if you want to compare multiple record types across a wider section of the city.',
                ],
            ],
            'seoTitle' => 'Boston Crime, 311, Permit, Inspection, and Crash Map | PublicDataWatch',
            'seoDescription' => 'Search a Boston address and explore recent crime, 311, permits, inspections, violations, and crash context with an interactive neighborhood map.',
        ],
        'everett' => [
            'searchPlaceholder' => 'Search an Everett address',
            'tagline' => 'Check recent Everett crime around an address fast.',
            'intro' => 'This page is built for quick Everett checks: search an address, tap nearby incidents, and widen out only when you need more than the immediate area.',
            'overview' => 'Use the Everett page for a fast first pass on recent nearby crime, especially when you want to sanity-check a block before opening the larger explore map.',
            'howToUse' => 'Search an Everett address, tap incident markers to read the details, then open the full map if you want to scan a broader part of the city.',
            'dataUpdateNote' => 'Everett coverage on this page is crime-focused, so the map reflects the cadence of the Everett police source rather than a broader city-operations feed.',
            'focusAreas' => ['Crime reports', 'Address search', 'Fast block check'],
            'highlights' => [
                [
                    'title' => 'Best for',
                    'body' => 'People who want a quick Everett crime check before commuting, leasing, buying, or recommending a block to someone else.',
                ],
                [
                    'title' => 'What makes Everett different',
                    'body' => 'Everett is a tighter city scan, so this page works well as a fast mobile-first incident view instead of a bigger multi-layer dashboard.',
                ],
                [
                    'title' => 'When to go deeper',
                    'body' => 'Open the full map when you want to pan further, compare several nearby areas, or keep digging through additional records.',
                ],
            ],
            'seoTitle' => 'Everett Crime Map and Public Safety Data | PublicDataWatch',
            'seoDescription' => 'Search an Everett address and browse recent nearby crime with an interactive map and plain-language incident details.',
        ],
        'chicago' => [
            'searchPlaceholder' => 'Search a Chicago address',
            'tagline' => 'Check recent Chicago crime around an address before you commit to a block.',
            'intro' => 'Use the Chicago page for a quick first pass on recent incidents near one address, then open the full crime map when you want a wider read on the area.',
            'overview' => 'This Chicago page is built for simple block-level crime checks. It helps you start with one address and decide quickly whether you need more context.',
            'howToUse' => 'Search a Chicago address, tap nearby incidents to read the details, then widen out in the full map when you want to compare more blocks.',
            'dataUpdateNote' => 'Chicago coverage on this page is crime-focused, so what you see reflects the Chicago crime feed rather than a broader set of city datasets.',
            'focusAreas' => ['Crime reports', 'Address search', 'Block-level first pass'],
            'highlights' => [
                [
                    'title' => 'Best for',
                    'body' => 'Renters, buyers, reporters, and operators who need a fast sense of recent crime around one Chicago address.',
                ],
                [
                    'title' => 'What makes Chicago different',
                    'body' => 'Chicago is a large-city crime check first, so the page is designed to answer the address question quickly instead of forcing a full dashboard upfront.',
                ],
                [
                    'title' => 'When to go deeper',
                    'body' => 'Open the full map after the first scan if you want a broader neighborhood search or more room to compare nearby blocks.',
                ],
            ],
            'seoTitle' => 'Chicago Crime Map and Neighborhood Incident Data | PublicDataWatch',
            'seoDescription' => 'Search a Chicago address and explore recent nearby crime with an interactive map and plain-language incident summaries.',
        ],
        'san_francisco' => [
            'searchPlaceholder' => 'Search a San Francisco address',
            'tagline' => 'See recent San Francisco crime around an address without jumping straight into the full map.',
            'intro' => 'This page is the fast San Francisco starting point: search a block, read the nearby incidents, and only widen out when you need more neighborhood context.',
            'overview' => 'Use the San Francisco page when you want a quick read on recent nearby crime before switching to the broader city map.',
            'howToUse' => 'Search a San Francisco address, tap the nearby incident markers, and widen out when you want a bigger neighborhood comparison.',
            'dataUpdateNote' => 'San Francisco coverage on this page is crime-focused, so the map reflects the cadence of the city crime source rather than a broader city-operations mix.',
            'focusAreas' => ['Crime reports', 'Address search', 'Neighborhood check'],
            'highlights' => [
                [
                    'title' => 'Best for',
                    'body' => 'People evaluating a San Francisco block and wanting a faster crime snapshot before using the larger map.',
                ],
                [
                    'title' => 'What makes San Francisco different',
                    'body' => 'San Francisco works best as a quick incident scan first, especially when you already have a specific address or nearby block in mind.',
                ],
                [
                    'title' => 'When to go deeper',
                    'body' => 'Open the full map when the first screen tells you the area is worth more investigation.',
                ],
            ],
            'seoTitle' => 'San Francisco Crime Map and Public Safety Data | PublicDataWatch',
            'seoDescription' => 'Search a San Francisco address and browse recent nearby crime with an interactive map and fast incident summaries.',
        ],
        'new_york' => [
            'searchPlaceholder' => 'Search a New York address',
            'tagline' => 'See recent New York 311 requests around an address.',
            'intro' => 'This New York page is about city-service signals, not crime. Start with one address to see nearby complaints and requests before opening the broader 311 map.',
            'overview' => 'Use the New York page when you want quality-of-life and city-response context around an address, including nearby 311 request activity.',
            'howToUse' => 'Search a New York address, tap the nearby 311 records, and widen out only when you want a broader picture of service-request patterns nearby.',
            'dataUpdateNote' => 'New York coverage on this page is 311-focused, so it reflects resident request activity rather than a crime feed.',
            'matchLocalities' => ['New York', 'Manhattan', 'Brooklyn', 'Queens', 'Bronx', 'Staten Island'],
            'focusAreas' => ['311 requests', 'Address search', 'Quality-of-life signals'],
            'highlights' => [
                [
                    'title' => 'Best for',
                    'body' => 'Residents, reporters, and operators who want a fast view of complaints and city-service issues near one New York address.',
                ],
                [
                    'title' => 'What makes New York different',
                    'body' => 'This page is service-request-first, so it is better for street conditions, noise, sanitation, and local complaints than for public-safety incident review.',
                ],
                [
                    'title' => 'When to go deeper',
                    'body' => 'Open the full 311 map when you want to compare more blocks or watch the pattern across a larger area.',
                ],
            ],
            'seoTitle' => 'New York 311 Map and City Service Request Data | PublicDataWatch',
            'seoDescription' => 'Search a New York address and explore nearby 311 requests with an interactive map and quick city-service record summaries.',
        ],
        'montgomery_county_md' => [
            'searchPlaceholder' => 'Search a Montgomery County address',
            'tagline' => 'Check recent Montgomery County crime around an address across Bethesda, Rockville, Silver Spring, and nearby communities.',
            'intro' => 'This county page is designed for address-first crime checks across Montgomery County, not just one city center.',
            'overview' => 'Use the Montgomery County page when you want to check recent crime near an address across the county and its main communities.',
            'howToUse' => 'Search a Montgomery County address, read the nearby incidents, and widen out when you want a broader county-area comparison.',
            'dataUpdateNote' => 'Montgomery County coverage on this page is countywide crime data, so addresses from several local communities can be served by the same landing page.',
            'matchLocalities' => ['Bethesda', 'Rockville', 'Silver Spring', 'Gaithersburg', 'Germantown', 'Chevy Chase', 'Takoma Park', 'Potomac'],
            'focusAreas' => ['Countywide crime', 'Address search', 'Cross-community checks'],
            'highlights' => [
                [
                    'title' => 'Best for',
                    'body' => 'People checking crime around addresses in Bethesda, Rockville, Silver Spring, and other Montgomery County communities.',
                ],
                [
                    'title' => 'What makes Montgomery County different',
                    'body' => 'This page spans multiple localities under one county feed, so it works better as a regional first pass than a single-city dashboard.',
                ],
                [
                    'title' => 'When to go deeper',
                    'body' => 'Open the full map when you want to compare several nearby communities or pan farther across the county.',
                ],
            ],
            'seoTitle' => 'Montgomery County Crime Map and Neighborhood Incident Data | PublicDataWatch',
            'seoDescription' => 'Search a Montgomery County, Maryland address and explore recent nearby crime with an interactive map and quick incident details.',
        ],
        'seattle' => [
            'searchPlaceholder' => 'Search a Seattle address',
            'tagline' => 'Check recent Seattle crime around an address fast.',
            'intro' => 'Use the Seattle page for a fast first pass on nearby incidents before opening the larger crime map.',
            'overview' => 'This Seattle page is built for address-first crime checks, especially when you want a quick feel for recent nearby activity.',
            'howToUse' => 'Search a Seattle address, tap the nearby incident markers, and widen out only when you want a broader neighborhood comparison.',
            'dataUpdateNote' => 'Seattle coverage on this page is crime-focused, so it follows the Seattle crime source cadence rather than a broader city-operations feed.',
            'focusAreas' => ['Crime reports', 'Address search', 'Fast neighborhood scan'],
            'highlights' => [
                [
                    'title' => 'Best for',
                    'body' => 'Residents, renters, buyers, and reporters who need a quick Seattle crime check around one address.',
                ],
                [
                    'title' => 'What makes Seattle different',
                    'body' => 'Seattle works best as a clean first-pass incident view that answers the address question without making you learn the whole product first.',
                ],
                [
                    'title' => 'When to go deeper',
                    'body' => 'Open the full map after the first scan if the area needs more context than one address-centered view can provide.',
                ],
            ],
            'seoTitle' => 'Seattle Crime Map and Neighborhood Incident Data | PublicDataWatch',
            'seoDescription' => 'Search a Seattle address and browse recent nearby crime with an interactive map and quick incident summaries.',
        ],
    ];

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

    public function show(Request $request, string $citySlug): Response
    {
        $cityKey = $this->resolveCityKeyFromSlug($citySlug);
        abort_unless($cityKey, 404);

        $cityConfig = config("cities.cities.{$cityKey}");
        abort_unless(is_array($cityConfig), 404);
        $dataTypes = $this->getCityDatasetLabels($cityConfig);
        $fullMapUrl = $this->resolveFullMapUrl($cityKey, $cityConfig);
        $cityContent = $this->buildCityContent($cityKey, $cityConfig, $dataTypes, $fullMapUrl);

        return Inertia::render('CityMapLite', [
            'city' => [
                'key' => $cityKey,
                'slug' => $citySlug,
                'name' => $cityConfig['name'],
                'latitude' => $cityConfig['latitude'],
                'longitude' => $cityConfig['longitude'],
                'defaultRadius' => $cityKey === 'everett' ? 0.35 : 0.25,
                'tagline' => $cityContent['tagline'],
                'intro' => $cityContent['intro'],
                'fullMapUrl' => $fullMapUrl,
                'dataTypes' => $dataTypes,
                'seoTitle' => $cityContent['seoTitle'],
                'seoDescription' => $cityContent['seoDescription'],
                'overview' => $cityContent['overview'],
                'howToUse' => $cityContent['howToUse'],
                'dataUpdateNote' => $cityContent['dataUpdateNote'],
                'relatedLinks' => $cityContent['relatedLinks'],
                'searchPlaceholder' => $cityContent['searchPlaceholder'],
                'focusAreas' => $cityContent['focusAreas'],
                'highlights' => $cityContent['highlights'],
                'initialLocation' => $this->buildInitialLocation($request),
            ],
            'languageOptions' => self::LANGUAGE_OPTIONS,
            'cityRouting' => $this->buildCityRoutingMap(),
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

    private function buildCityContent(string $cityKey, array $cityConfig, array $dataTypes, string $fullMapUrl): array
    {
        $profile = self::CITY_CONTENT[$cityKey] ?? [];
        $cityName = $cityConfig['name'];

        return [
            'tagline' => $profile['tagline'] ?? $this->getCityTagline($cityName),
            'intro' => $profile['intro'] ?? $this->getCityIntro($cityName),
            'overview' => $profile['overview'] ?? $this->getCityOverview($cityName, $dataTypes),
            'howToUse' => $profile['howToUse'] ?? $this->getCityHowToUse($cityName),
            'dataUpdateNote' => $profile['dataUpdateNote'] ?? 'Data refresh schedules vary by source, so recent records may arrive at different times.',
            'seoTitle' => $profile['seoTitle'] ?? $this->getCitySeoTitle($cityName, $dataTypes),
            'seoDescription' => $profile['seoDescription'] ?? $this->getCitySeoDescription($cityName, $dataTypes),
            'searchPlaceholder' => $profile['searchPlaceholder'] ?? "Search a {$cityName} address",
            'focusAreas' => $profile['focusAreas'] ?? $this->getDefaultFocusAreas($dataTypes),
            'highlights' => $profile['highlights'] ?? $this->getDefaultHighlights($cityName, $dataTypes),
            'relatedLinks' => $this->getRelatedLinks($cityKey, $cityName, $fullMapUrl, $dataTypes, $cityConfig),
        ];
    }

    private function buildInitialLocation(Request $request): ?array
    {
        if ($request->query('lat') === null || $request->query('lng') === null) {
            return null;
        }

        return [
            'address' => $request->query('address'),
            'latitude' => (float) $request->query('lat'),
            'longitude' => (float) $request->query('lng'),
        ];
    }

    private function buildCityRoutingMap(): array
    {
        $targets = [];

        foreach (array_keys(self::CITY_CONTENT) as $cityKey) {
            $cityConfig = config("cities.cities.{$cityKey}");
            if (!is_array($cityConfig)) {
                continue;
            }

            $routeName = "city.landing.{$cityKey}";
            if (!app('router')->has($routeName)) {
                continue;
            }

            $matchLocalities = collect(array_merge(
                [$cityConfig['name'] ?? null],
                $cityConfig['serviceability']['supported_localities'] ?? [],
                self::CITY_CONTENT[$cityKey]['matchLocalities'] ?? [],
            ))
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->map(fn (string $value) => Str::lower(trim($value)))
                ->unique()
                ->values()
                ->all();

            $targets[] = [
                'key' => $cityKey,
                'name' => $cityConfig['name'] ?? Str::headline($cityKey),
                'url' => route($routeName),
                'matchLocalities' => $matchLocalities,
            ];
        }

        return $targets;
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

    private function getDefaultFocusAreas(array $dataTypes): array
    {
        if ($dataTypes === []) {
            return ['Address search', 'Interactive map', 'Nearby records'];
        }

        return array_slice($dataTypes, 0, 4);
    }

    private function getDefaultHighlights(string $cityName, array $dataTypes): array
    {
        $datasetPhrase = $this->joinNaturalLanguageList(
            array_map(fn ($label) => Str::lower($label), $dataTypes)
        );

        return [
            [
                'title' => 'Best for',
                'body' => "People who want a quick first pass on nearby public records in {$cityName}.",
            ],
            [
                'title' => 'What this page shows',
                'body' => $datasetPhrase !== ''
                    ? "This page starts with {$datasetPhrase} around one address instead of dropping you straight into the larger map."
                    : "This page starts with one address and nearby public records instead of the larger map.",
            ],
            [
                'title' => 'When to go deeper',
                'body' => 'Open the full map once the first pass tells you the area needs broader context.',
            ],
        ];
    }

    private function getRelatedLinks(string $cityKey, string $cityName, string $fullMapUrl, array $dataTypes, array $cityConfig): array
    {
        $links = [];
        $supportsCrimePreview = ($cityConfig['serviceability']['crime_address_funnel_enabled'] ?? false)
            && in_array('Crime Reports', $dataTypes, true);

        if ($supportsCrimePreview) {
            $links[] = ['label' => 'Try one-address crime preview', 'url' => route('crime-address.index')];
        }

        $fullMapLabel = match ($dataTypes) {
            ['Crime Reports'] => "Open full {$cityName} crime map",
            ['311 Requests'] => "Open full {$cityName} 311 map",
            default => "Open full {$cityName} data map",
        };

        return array_merge($links, [
            ['label' => $fullMapLabel, 'url' => $fullMapUrl],
            ['label' => 'Read city data news', 'url' => route('news.index')],
            ['label' => 'Learn how to use the maps', 'url' => route('help.users')],
        ]);
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
