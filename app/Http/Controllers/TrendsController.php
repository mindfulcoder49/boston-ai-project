<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Models\Trend;

class TrendsController extends Controller
{
    public function index()
    {
        $trends = Trend::all()->groupBy('model_class');
        $reportsByModel = [];

        foreach ($trends as $modelClass => $analyses) {
            if (!class_exists($modelClass)) {
                continue;
            }

            $modelData = [
                'model_name' => $modelClass::getHumanName(),
                'model_key' => Str::kebab(class_basename($modelClass)),
                'analyses' => [],
            ];

            foreach ($analyses as $analysis) {
                $modelData['analyses'][] = [
                    'trend_id' => $analysis->id,
                    'column_name' => $analysis->column_name,
                    'column_label' => Str::of($analysis->column_name)->replace('_', ' ')->title(),
                    'h3_resolution' => $analysis->h3_resolution,
                    'analysis_weeks_trend' => $analysis->analysis_weeks_trend,
                ];
            }

            $reportsByModel[] = $modelData;
        }

        return Inertia::render('Trends/Index', [
            'reportsByModel' => $reportsByModel,
        ]);
    }
}