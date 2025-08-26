<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Trend;

class StatisticalAnalysisReportController extends Controller
{
    public function show($modelKey, $columnName)
    {
        $modelClass = $this->getModelClassFromKey($modelKey);

        if (!$modelClass) {
            abort(404, "Model not found.");
        }

        $trend = Trend::where('model_class', $modelClass)
            ->where('column_name', $columnName)
            ->first();

        if (!$trend) {
            abort(404, "Analysis report not found for this model and column.");
        }

        return Inertia::render('Reports/StatisticalAnalysisViewer', [
            'jobId' => $trend->job_id,
            //'apiBaseUrl' => config('services.analysis_api.url'),
            'apiBaseUrl' => 'http://localhost:8080',
            'reportTitle' => $modelClass::getHumanName() . ' by ' . Str::of($columnName)->replace('_', ' ')->title(),
        ]);
    }

    private function getModelClassFromKey(string $modelKey): ?string
    {
        $modelsPath = app_path('Models');
        $files = File::files($modelsPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . $file->getBasename('.php');
            if (class_exists($className) && Str::kebab(class_basename($className)) === $modelKey) {
                return $className;
            }
        }

        return null;
    }
}