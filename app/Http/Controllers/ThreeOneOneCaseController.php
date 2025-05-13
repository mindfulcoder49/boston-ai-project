<?php

namespace App\Http\Controllers;

use App\Models\ThreeOneOneCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ThreeOneOneCaseController extends Controller
{
    /**
     * Display a listing of the cases with associated predictions.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $searchTerm = $request->get('searchTerm', '');
        // Log::debug("doing a search for $searchTerm");
        $cases = ThreeOneOneCase::where(function($query) use ($searchTerm) {
                foreach (ThreeOneOneCase::SEARCHABLE_COLUMNS as $column) {
                    $query->orWhere($column, 'LIKE', "%{$searchTerm}%");
                }
            })
            // only include cases with predictions
            ->whereHas('predictions', function($query) {
                $query->where('prediction_date', '>', '2021-01-01');
            })
            ->orderBy('open_dt', 'desc')
            ->take(50)
            ->get();

        return Inertia::render('ThreeOneOneCaseList', [
            'cases' => $cases,
            'search' => $searchTerm
        ]);
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
                // It's possible an empty array is a valid "not found" response from Open311 for a specific ID.
                // Let the frontend decide how to interpret an empty array.
                // If the API guarantees an array with one item or an error, this could be a 404.
                // For now, return the empty data.
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
        ]);

        $caseEnquiryIds = $validated['case_enquiry_ids'];

        if (empty($caseEnquiryIds)) {
            return response()->json(['error' => 'No case enquiry IDs provided.'], 400);
        }
        // deduplicate the case enquiry IDs
        $caseEnquiryIds = array_unique($caseEnquiryIds);

        // The Boston API expects a comma-separated string for multiple service_request_id
        $serviceRequestIdsString = implode(',', $caseEnquiryIds);

        $apiKey = Config::get('services.bostongov.api_key');
        $baseUrl = Config::get('services.bostongov.base_url', 'https://311.boston.gov/open311/v2');

        if (!$apiKey) {
            Log::error('Boston 311 API key not configured.');
            return response()->json(['error' => 'Service configuration error.'], 500);
        }

        // Construct the URL as per Open311 spec for multiple requests
        // e.g., https://311.boston.gov/open311/v2/requests.json?api_key=YOUR_API_KEY&service_request_id=ID1,ID2,ID3
        $apiUrl = "{$baseUrl}/requests.json?api_key={$apiKey}&service_request_id={$serviceRequestIdsString}";

        try {
            $response = Http::timeout(30)->get($apiUrl); // Increased timeout for potentially larger response

            if ($response->failed()) {
                Log::error("Boston 311 API request failed for multiple case IDs. Status: " . $response->status() . " Body: " . $response->body());
                return response()->json(['error' => 'Failed to fetch data from Boston 311 API for multiple cases.', 'details' => $response->json() ?: $response->body()], $response->status());
            }

            $data = $response->json();

            // According to Open311, the response should be an array of service_requests.
            // If $data is null or not an array, it might indicate an issue or an empty valid response.
            if (!is_array($data)) {
                 Log::warning("Boston 311 API returned non-array data for multiple case IDs. Body: " . $response->body());
                 // Depending on API behavior, an empty array might be valid if no cases are found or all are invalid.
                 // If an error is expected, this might need adjustment.
                 // For now, return what we got, which might be an empty array if $data was null and json() produced it.
            }
            
            return response()->json(['data' => $data]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Boston 311 API connection error for multiple case IDs: " . $e->getMessage());
            return response()->json(['error' => 'Could not connect to Boston 311 API.'], 503);
        } catch (\Exception $e) {
            Log::error("Error fetching live data for multiple case IDs: " . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred while fetching live data for multiple cases.'], 500);
        }
    }
}
