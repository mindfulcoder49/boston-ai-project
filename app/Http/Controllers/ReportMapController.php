<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class ReportMapController extends Controller
{
    /**
     * Display a map of the data from a generated CSV report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $filename
     * @return \Inertia\Response
     */
    public function show(Request $request, string $filename)
    {
        Log::info('ReportMapController@show called with filename: ' . $filename);
        // Security: Prevent directory traversal attacks.
        $safeFilename = basename($filename);
        $filePath = 'reports/' . $safeFilename;

        if (!Storage::exists($filePath)) {
            abort(404, 'Report file not found.');
        }

        // Read and parse the CSV file.
        $csvContent = Storage::get($filePath);
        $lines = explode(PHP_EOL, trim($csvContent));
        
        // Sanitize header by replacing spaces with underscores
        $header = array_map(function($h) {
            return str_replace(' ', '_', $h);
        }, str_getcsv(array_shift($lines)));
        
        $data = collect($lines)->map(function ($line) use ($header) {
            if (empty(trim($line))) {
                return null;
            }
            $row = str_getcsv($line);
            // Ensure the row has the same number of columns as the header
            if (count($row) === count($header)) {
                return (object) array_combine($header, $row);
            }
            return null;
        })->filter()->values();

        // Determine the crash count column header dynamically (now with underscores)
        $crashCountHeader = collect($header)->first(fn($h) => Str::contains($h, 'Crashes_within'));

        // Define filter names based on the dynamic header
        $minFilterName = $crashCountHeader ? $crashCountHeader . '_min' : 'min_crashes';
        $maxFilterName = $crashCountHeader ? $crashCountHeader . '_max' : 'max_crashes';

        // Create a "virtual" data type configuration for the frontend.
        $dataType = 'intersection_crash_report';
        $config = [
            'humanName' => 'Intersection Crash Report',
            'iconClass' => 'fas fa-car-crash', // Fallback icon class
            'alcivartech_type_for_styling' => 'Reported Intersection',
            'latitudeField' => 'Latitude',
            'longitudeField' => 'Longitude',
            'dateField' => null, // No date field in this report
            'externalIdField' => 'Intersection_ID', // Sanitized
            'searchableColumns' => ['Intersection_ID', 'Intersection_Name'], // Sanitized
            'filterFieldsDescription' => json_encode([ // Ensure this is a JSON string
                [
                    'name' => 'Intersection_Name', // Sanitized
                    'label' => 'Intersection Name',
                    'type' => 'text',
                    'placeholder' => 'e.g., Massachusetts Ave & Main St'
                ],
                [
                    'name' => $minFilterName,
                    'label' => 'Min ' . ($crashCountHeader ? str_replace('_', ' ', $crashCountHeader) : 'Crashes'),
                    'type' => 'number',
                ],
                [
                    'name' => $maxFilterName,
                    'label' => 'Max ' . ($crashCountHeader ? str_replace('_', ' ', $crashCountHeader) : 'Crashes'),
                    'type' => 'number',
                ],
            ]),
            'dynamicIcon' => [
                'enabled' => true,
                'textField' => $crashCountHeader, // The field containing the number to display
                'className' => 'report-value-icon' // A CSS class for styling
            ]
        ];

        // Enrich the data with the format expected by the frontend.
        $enrichedData = $data->map(function ($point) use ($dataType, $config, $crashCountHeader) {
            $point->alcivartech_model = $dataType;
            $point->alcivartech_type = $config['alcivartech_type_for_styling'];
            $point->latitude = $point->{$config['latitudeField']} ?? null;
            $point->longitude = $point->{$config['longitudeField']} ?? null;
            $point->alcivartech_date = null; // No date
            
            // Ensure the crash count field is numeric for filtering
            if ($crashCountHeader && isset($point->{$crashCountHeader})) {
                $point->{$crashCountHeader} = (int)$point->{$crashCountHeader};
            }

            return $point;
        });

        return Inertia::render('DataMap', [
            'initialData' => $enrichedData,
            'filters' => $request->query(),
            'dataType' => $dataType,
            'dataTypeConfig' => $config,
            'allModelConfigurationsForToolbar' => [], // No toolbar needed for this view
            'isReadOnly' => true, // This view is read-only
            'mapConfiguration' => (new DataMapController())->generateMapConfiguration(), // Reuse config generation
            'initialClusterRadius' => $request->input('clusterRadius', 80), // Pass cluster radius
        ]);
    }
}
