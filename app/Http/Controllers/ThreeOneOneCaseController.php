<?php

namespace App\Http\Controllers;

use App\Models\ThreeOneOneCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

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
}
