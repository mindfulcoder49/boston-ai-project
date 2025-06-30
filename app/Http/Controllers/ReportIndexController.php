<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportIndexController extends Controller
{
    /**
     * Display a list of available reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $files = Storage::files('reports');

        $reports = collect($files)
            ->filter(fn ($file) => pathinfo($file, PATHINFO_EXTENSION) === 'json')
            ->map(function ($file) {
                $content = Storage::get($file);
                $data = json_decode($content, true);

                // Add a formatted date for display
                if (isset($data['generated_at'])) {
                    $data['generated_at_formatted'] = Carbon::parse($data['generated_at'])->format('F j, Y, g:i a');
                }

                // Ensure default_filters is an empty object if not present
                if (!isset($data['default_filters'])) {
                    $data['default_filters'] = (object)[];
                }

                return $data;
            })
            ->filter() // Remove any nulls from failed decodes
            ->sortByDesc('generated_at')
            ->values();

        return Inertia::render('ReportIndex', [
            'reports' => $reports,
        ]);
    }
}
