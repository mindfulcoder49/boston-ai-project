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
            return response()->json(['data' => $cachedData]);
        }

        $apiKey = Config::get('services.bostongov.api_key');
        $baseUrl = Config::get('services.bostongov.base_url', 'https://311.boston.gov/open311/v2');

        if (!$apiKey) {
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
                Cache::put($cacheKey, 'no_data', now()->addMinutes(10)); // Cache "no data" marker for 10 minutes
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

        if (!$apiKey) {
            Log::error('Boston 311 API key not configured.');
            return response()->json(['error' => 'Service configuration error.'], 500);
        }

        $allCaseData = [];
        $missingCaseIds = [];

        // Check cache for each ID
        foreach ($caseEnquiryIds as $caseEnquiryId) {
            $cacheKey = "case_details_{$caseEnquiryId}";
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                if ($cachedData === 'no_data') {
                    $allCaseData[] = []; // Add empty data for cases with "no data" cached
                    continue;
                }
                $allCaseData[] = $cachedData;
            } else {
                $missingCaseIds[] = $caseEnquiryId;
            }
        }

        //log stats on misses and hits
        Log::info("Cache hits: " . (count($caseEnquiryIds) - count($missingCaseIds)) . ", Cache misses: " . count($missingCaseIds));

        // Fetch data for missing IDs
        if (!empty($missingCaseIds)) {
            $chunkedCaseEnquiryIds = array_chunk($missingCaseIds, 50); // API limit of 50 per request

            foreach ($chunkedCaseEnquiryIds as $index => $chunk) {
                $serviceRequestIdsString = implode(',', $chunk);
                $apiUrl = "{$baseUrl}/requests.json?api_key={$apiKey}&service_request_id={$serviceRequestIdsString}";

                try {
                    $response = Http::timeout(30)->get($apiUrl);

                    if ($response->failed()) {
                        Log::error("Boston 311 API request failed for chunk of case IDs. Status: " . $response->status() . " Body: " . $response->body());
                        return response()->json(['error' => 'Failed to fetch data from Boston 311 API for a batch of cases.', 'details' => $response->json() ?: $response->body()], $response->status());
                    }

                    $data = $response->json();

                    if (is_array($data)) {
                        $returnedCaseIds = [];
                        foreach ($data as $case) {
                            $caseId = $case['service_request_id'] ?? null;
                            if ($caseId) {
                                $cacheKey = "case_details_{$caseId}";
                                Cache::put($cacheKey, $case, now()->addMinutes(10)); // Cache for 10 minutes
                                $allCaseData[] = $case;
                                $returnedCaseIds[] = $caseId;
                            }
                        }

                        // Identify missing case IDs and cache "no data" for them
                        $missingFromResponse = array_diff($chunk, $returnedCaseIds);
                        foreach ($missingFromResponse as $missingCaseId) {
                            $cacheKey = "case_details_{$missingCaseId}";
                            Cache::put($cacheKey, 'no_data', now()->addMinutes(10)); // Cache "no data" marker for 10 minutes
                            $allCaseData[] = []; // Add empty data for cases with "no data"
                        }
                    } else {
                        foreach ($chunk as $caseId) {
                            $cacheKey = "case_details_{$caseId}";
                            Cache::put($cacheKey, 'no_data', now()->addMinutes(10)); // Cache "no data" marker for 10 minutes
                            $allCaseData[] = []; // Add empty data for cases with "no data"
                        }
                    }

                    // Rate limit: sleep for 1 second if there are more chunks to process
                    if ($index < count($chunkedCaseEnquiryIds) - 1) {
                        sleep(1);
                    }

                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Log::error("Boston 311 API connection error for chunk of case IDs: " . $e->getMessage());
                    return response()->json(['error' => 'Could not connect to Boston 311 API.'], 503);
                } catch (\Exception $e) {
                    Log::error("Error fetching live data for chunk of case IDs: " . $e->getMessage());
                    return response()->json(['error' => 'An unexpected error occurred while fetching live data for a batch of cases.'], 500);
                }
            }
        }

        return response()->json(['data' => $allCaseData]);
    }
}
