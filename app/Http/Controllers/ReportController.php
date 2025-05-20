<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Auth::user()->reports()
            ->with('location:id,name,address') // Eager load location with specific columns
            ->orderBy('generated_at', 'desc')
            ->paginate(10)
            ->through(fn ($report) => [ // Transform data for the view
                'id' => $report->id,
                'title' => $report->title,
                'location_name' => $report->location ? ($report->location->name ?? $report->location->address) : 'N/A',
                'generated_at' => $report->generated_at->format('F j, Y, g:i a'),
                'view_url' => route('reports.show', $report),
                'download_url' => route('reports.download', $report),
            ]);

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
        ]);
    }

    public function show(Report $report)
    {
        Gate::authorize('view', $report); // Assumes you'll create a ReportPolicy

        return Inertia::render('Reports/Show', [
            'report' => [
                'id' => $report->id,
                'title' => $report->title,
                'content' => $report->content, // Consider using a Markdown parser in Vue
                'location_name' => $report->location ? ($report->location->name ?? $report->location->address) : 'N/A',
                'generated_at' => $report->generated_at->format('F j, Y, g:i a'),
                'download_url' => route('reports.download', $report),
            ],
        ]);
    }

    public function download(Report $report)
    {
        Gate::authorize('download', $report); // Assumes you'll create a ReportPolicy

        $filename = str_replace([' ', '/', '\\', ':', '*'], '_', $report->title) . '.md';

        $headers = [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return new StreamedResponse(function () use ($report) {
            echo $report->content;
        }, 200, $headers);
    }
}
