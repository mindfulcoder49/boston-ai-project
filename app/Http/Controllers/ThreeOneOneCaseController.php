<?php

namespace App\Http\Controllers;

use App\Models\ThreeOneOneCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class ThreeOneOneCaseController extends Controller
{
    public function getMultiple(Request $request)
    {
        if ($request->has('service_request_ids') && !$request->has('case_enquiry_ids')) {
            $request->merge([
                'case_enquiry_ids' => $request->input('service_request_ids'),
            ]);
        }

        return $this->getMultipleLiveCaseDetails($request);
    }

    public function indexnofilter(Request $request)
    {
        $searchTerm = $request->get('searchTerm', '');
        // Log::debug("doing a search for $searchTerm");
        $cases = ThreeOneOneCase::where(function($query) use ($searchTerm) {
                foreach (ThreeOneOneCase::SEARCHABLE_COLUMNS as $column) {
                    $query->orWhere($column, 'LIKE', "%{$searchTerm}%");
                }
            })
            ->take(4000)
            ->get();

        return Inertia::render('ThreeOneOneProject', [
            'cases' => $cases,
            'search' => $searchTerm
        ]);
    }

    /**
     * Fetch live case details from Boston 311 API.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $caseEnquiryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLiveCaseDetails(Request $request, $caseEnquiryId)
    {
        $cacheKey = "case_details_{$caseEnquiryId}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            if ($cachedData === 'no_data') {
                return response()->json(['data' => []]);
            }
            return response()->json(['data' => $cachedData]);
        }

        if (!$this->isLegacyOpen311Id($caseEnquiryId)) {
            $localCase = $this->getLocalCasesByIds([(string) $caseEnquiryId])[(string) $caseEnquiryId] ?? null;
            if ($localCase) {
                Cache::put($cacheKey, $localCase, now()->addMinutes(10));
                return response()->json(['data' => $localCase]);
            }

            Cache::put($cacheKey, 'no_data', now()->addMinutes(10));
            return response()->json(['data' => []]);
        }

        $apiKey = Config::get('services.bostongov.api_key');
        $baseUrl = Config::get('services.bostongov.base_url', 'https://311.boston.gov/open311/v2');

        if (!$apiKey) {
            $localCase = $this->getLocalCasesByIds([(string) $caseEnquiryId])[(string) $caseEnquiryId] ?? null;
            if ($localCase) {
                Cache::put($cacheKey, $localCase, now()->addMinutes(10));
                return response()->json(['data' => $localCase]);
            }

            Log::error('Boston 311 API key not configured.');
            return response()->json(['error' => 'Service configuration error.'], 500);
        }

        $apiUrl = "{$baseUrl}/requests/{$caseEnquiryId}.json?api_key={$apiKey}";

        try {
            $response = Http::timeout(15)->get($apiUrl);

            if ($response->failed()) {
                Log::error("Boston 311 API request failed for case ID {$caseEnquiryId}. Status: " . $response->status() . " Body: " . $response->body());
                return response()->json(['error' => 'Failed to fetch data from Boston 311 API.', 'details' => $response->json() ?: $response->body()], $response->status());
            }

            $data = $response->json();

            if (empty($data) || !is_array($data)) {
                Log::warning("Boston 311 API returned empty or invalid data for case ID {$caseEnquiryId}. Body: " . $response->body());
                $localCase = $this->getLocalCasesByIds([(string) $caseEnquiryId])[(string) $caseEnquiryId] ?? null;
                if ($localCase) {
                    Cache::put($cacheKey, $localCase, now()->addMinutes(10));
                    return response()->json(['data' => $localCase]);
                }

                Cache::put($cacheKey, 'no_data', now()->addMinutes(10));
                return response()->json(['data' => []]);
            }

            if (is_array($data)) {
                Cache::put($cacheKey, $data, now()->addMinutes(10)); // Cache for 10 minutes
            }

            return response()->json(['data' => $data]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Boston 311 API connection error for case ID {$caseEnquiryId}: " . $e->getMessage());
            return response()->json(['error' => 'Could not connect to Boston 311 API.'], 503);
        } catch (\Exception $e) {
            Log::error("Error fetching live case data for case ID {$caseEnquiryId}: " . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred while fetching live data.'], 500);
        }
    }

    /**
     * Fetch live case details for multiple cases from Boston 311 API.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMultipleLiveCaseDetails(Request $request)
    {
        $validated = $request->validate([
            'case_enquiry_ids' => 'required|array',
            'case_enquiry_ids.*' => 'string', // Ensure IDs are strings
        ]);

        $caseEnquiryIds = $validated['case_enquiry_ids'];

        if (empty($caseEnquiryIds)) {
            return response()->json(['data' => []]); // Return empty data if no IDs provided
        }

        $caseEnquiryIds = array_values(array_unique($caseEnquiryIds)); // Deduplicate and reindex
        $apiKey = Config::get('services.bostongov.api_key');
        $baseUrl = Config::get('services.bostongov.base_url', 'https://311.boston.gov/open311/v2');

        if (!$apiKey && !empty(array_filter($caseEnquiryIds, fn ($id) => $this->isLegacyOpen311Id($id)))) {
            Log::error('Boston 311 API key not configured.');
            return response()->json(['error' => 'Service configuration error.'], 500);
        }

        $resultMap = $this->getLocalCasesByIds($caseEnquiryIds);
        $allCaseData = [];
        $missingLegacyCaseIds = [];

        // Check cache for each ID
        foreach ($caseEnquiryIds as $caseEnquiryId) {
            if (!$this->isLegacyOpen311Id($caseEnquiryId)) {
                continue;
            }

            $cacheKey = "case_details_{$caseEnquiryId}";
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                if ($cachedData !== 'no_data') {
                    $resultMap[$caseEnquiryId] = $this->mergeCasePayloads($resultMap[$caseEnquiryId] ?? null, $cachedData);
                }
            } else {
                $missingLegacyCaseIds[] = $caseEnquiryId;
            }
        }

        Log::info("Cache hits: " . (count($caseEnquiryIds) - count($missingLegacyCaseIds)) . ", Cache misses: " . count($missingLegacyCaseIds));

        if (!empty($missingLegacyCaseIds)) {
            $chunkedCaseEnquiryIds = array_chunk($missingLegacyCaseIds, 50); // API limit of 50 per request

            foreach ($chunkedCaseEnquiryIds as $index => $chunk) {
                $serviceRequestIdsString = implode(',', $chunk);
                $apiUrl = "{$baseUrl}/requests.json?api_key={$apiKey}&service_request_id={$serviceRequestIdsString}";

                try {
                    $response = Http::timeout(30)->get($apiUrl);

                    if ($response->failed()) {
                        Log::error("Boston 311 API request failed for chunk of case IDs. Status: " . $response->status() . " Body: " . $response->body());
                        continue;
                    }

                    $data = $response->json();

                    if (is_array($data)) {
                        $returnedCaseIds = [];
                        foreach ($data as $case) {
                            $caseId = $case['service_request_id'] ?? null;
                            if ($caseId) {
                                $cacheKey = "case_details_{$caseId}";
                                Cache::put($cacheKey, $case, now()->addMinutes(10)); // Cache for 10 minutes
                                $resultMap[$caseId] = $this->mergeCasePayloads($resultMap[$caseId] ?? null, $case);
                                $returnedCaseIds[] = $caseId;
                            }
                        }

                        $missingFromResponse = array_diff($chunk, $returnedCaseIds);
                        foreach ($missingFromResponse as $missingCaseId) {
                            $cacheKey = "case_details_{$missingCaseId}";
                            if (!isset($resultMap[$missingCaseId])) {
                                Cache::put($cacheKey, 'no_data', now()->addMinutes(10));
                            }
                        }
                    }

                    if ($index < count($chunkedCaseEnquiryIds) - 1) {
                        sleep(1);
                    }

                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Log::error("Boston 311 API connection error for chunk of case IDs: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error fetching live data for chunk of case IDs: " . $e->getMessage());
                }
            }
        }

        foreach ($caseEnquiryIds as $caseEnquiryId) {
            $allCaseData[] = $resultMap[$caseEnquiryId] ?? [];
        }

        return response()->json(['data' => $allCaseData]);
    }

    protected function getLocalCasesByIds(array $caseIds): array
    {
        $stringIds = array_values(array_unique(array_map('strval', $caseIds)));
        $numericIds = collect($stringIds)
            ->filter(fn ($caseId) => $this->isLegacyOpen311Id($caseId))
            ->map(fn ($caseId) => (int) $caseId)
            ->values()
            ->all();

        $query = ThreeOneOneCase::query()->whereIn('service_request_id', $stringIds);

        if (!empty($numericIds)) {
            $query->orWhereIn('case_enquiry_id', $numericIds);
        }

        return $query->get()
            ->mapWithKeys(function (ThreeOneOneCase $case) {
                $key = (string) ($case->service_request_id ?: $case->case_enquiry_id);
                return [$key => (object) $case->toArray()];
            })
            ->all();
    }

    protected function mergeCasePayloads(mixed $localCase, mixed $liveCase): object
    {
        return (object) array_merge(
            (array) ($localCase instanceof \stdClass ? $localCase : (array) $localCase),
            (array) ($liveCase instanceof \stdClass ? $liveCase : (array) $liveCase),
        );
    }

    protected function isLegacyOpen311Id(mixed $caseId): bool
    {
        return preg_match('/^\d+$/', (string) $caseId) === 1;
    }
}
