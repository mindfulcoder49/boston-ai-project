<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator; // For conditional password validation
use App\Jobs\RunArtisanCommandJob;
use App\Models\JobRun;
use Illuminate\Support\Facades\Artisan;
use App\Models\ThreeOneOneCase;
use Illuminate\Support\Facades\DB;
use App\Support\AdminPipelineConfig;
use App\Support\BackendHealthSnapshot;
use App\Support\PipelineRunStore;
use App\Support\PipelineRunSummary;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $adminEmail = config('admin.email');
            if (empty($adminEmail)) {
                Log::error('ADMIN_EMAIL is not configured in config/admin.php or .env file.');
                abort(403, 'Admin email not configured.');
            }
            if (!Auth::check() || Auth::user()->email !== $adminEmail) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        return Inertia::render('Admin/Index', []);
    }

    public function backendHealthIndex(BackendHealthSnapshot $backendHealthSnapshot)
    {
        return Inertia::render('Admin/BackendHealth', [
            'snapshot' => $backendHealthSnapshot->build(),
        ]);
    }

    // --- JOB DISPATCHER ---
    public function jobDispatcherIndex()
    {
        $modelDetails = [];
        $modelsPath = app_path('Models');
        if (File::isDirectory($modelsPath)) {
            foreach (File::files($modelsPath) as $file) {
                $className = 'App\\Models\\' . $file->getBasename('.php');
                if (
                    class_exists($className) &&
                    method_exists($className, 'getMappableTraitUsageCheck') && // Check for Mappable trait
                    property_exists($className, 'statisticalAnalysisColumns') &&
                    !empty($className::$statisticalAnalysisColumns)
                ) {
                    $modelName = class_basename($className);
                    $modelDetails[$modelName] = [
                        'class' => $className,
                        'columns' => $className::$statisticalAnalysisColumns ?? [],
                    ];
                }
            }
        }

        $newsReportModels = ['Trend', 'YearlyCountComparison'];
        $newsConfigSets = array_keys(config('news_generation.report_sets', []));

        return Inertia::render('Admin/JobDispatcher', [
            'modelDetails' => $modelDetails,
            'newsReportModels' => $newsReportModels,
            'newsConfigSets' => $newsConfigSets,
            'pipelineStages' => AdminPipelineConfig::getStageNames(),
            'pipelineCitySections' => AdminPipelineConfig::getCitySections(),
            'pipelineGeneralSections' => AdminPipelineConfig::getGeneralSections(),
        ]);
    }

    public function dispatchJob(Request $request)
    {
        $validated = $request->validate([
            'command' => ['required', 'string', Rule::in([
                'app:dispatch-statistical-analysis-jobs',
                'app:dispatch-yearly-count-comparison-jobs',
                'app:dispatch-news-article-generation-jobs',
                'reports:send',
                'app:run-all-data-pipeline',
                'app:dispatch-historical-scoring-jobs',
            ])],
            'parameters' => ['nullable', 'array'],
        ]);

        $command = $validated['command'];
        $parameters = $validated['parameters'] ?? [];

        try {
            if (in_array($command, [
                'app:run-all-data-pipeline',
                'app:dispatch-historical-scoring-jobs',
                'app:dispatch-statistical-analysis-jobs',
            ])) {
                // Dispatch via the queue so the command runs outside the HTTP request lifecycle.
                // This avoids the need for exec()/nohup and works on hosts where exec() is disabled.
                // It also ensures URL generation falls back to APP_URL instead of the current
                // browser request host (e.g. localhost), which fixes analysis export URLs for
                // worker containers that cannot reach the web app at "http://localhost/...".
                RunArtisanCommandJob::dispatch($command, $parameters);

                $message = match ($command) {
                    'app:run-all-data-pipeline' =>
                        "Pipeline job '{$command}' dispatched to run in the background. Check the Pipeline Logs page for progress.",
                    'app:dispatch-statistical-analysis-jobs' =>
                        "Job '{$command}' dispatched to run in the background. Running this outside the web request also ensures worker-accessible export URLs are generated.",
                    default =>
                        "Job '{$command}' dispatched to run in the background. This may take a moment if a data export is required.",
                };

                return redirect()->back()->with('success', $message);
            }

            Artisan::call($command, $parameters);
            $output = Artisan::output();

            return redirect()->back()->with('success', "Job '{$command}' dispatched successfully.")->with('command_output', $output);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch job '{$command}' from admin panel.", [
                'error' => $e->getMessage(),
                'parameters' => $parameters,
            ]);
            return redirect()->back()->with('error', "Failed to dispatch job: " . $e->getMessage());
        }
    }

    public function getUniqueColumnValues(Request $request)
    {
        $validated = $request->validate([
            'model' => 'required|string',
            'column' => 'required|string',
        ]);

        $modelName = $validated['model'];
        $columnName = $validated['column'];

        $modelClass = 'App\\Models\\' . $modelName;

        if (!class_exists($modelClass)) {
            return response()->json(['error' => 'Model not found.'], 404);
        }

        $modelInstance = new $modelClass();
        $tableName = $modelInstance->getTable();
        $connectionName = $modelInstance->getConnectionName() ?? config('database.default');

        if (!DB::connection($connectionName)->getSchemaBuilder()->hasColumn($tableName, $columnName)) {
            return response()->json(['error' => "Column '{$columnName}' not found on table '{$tableName}'."], 400);
        }

        try {
            $uniqueValues = DB::connection($connectionName)
                ->table($tableName)
                ->whereNotNull($columnName)
                ->distinct()
                ->orderBy($columnName)
                ->pluck($columnName);

            return response()->json(['unique_values' => $uniqueValues]);
        } catch (\Exception $e) {
            Log::error("Failed to get unique values for {$modelName}->{$columnName}: " . $e->getMessage());
            return response()->json(['error' => 'Could not retrieve unique values from the database.'], 500);
        }
    }

    // --- JOB RUNS ---
    public function jobRunsIndex()
    {
        $jobRuns = JobRun::with('relatedModel')
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->through(function ($job) {
                $related = null;
                if ($job->relatedModel) {
                    $related = [
                        'type' => class_basename($job->related_model_type),
                        'id' => $job->related_model_id,
                        'name' => $job->relatedModel->title ?? $job->relatedModel->name ?? 'N/A',
                    ];
                }
                return [
                    'id' => $job->id,
                    'job_id' => $job->job_id,
                    'job_class_name' => $job->job_class_name,
                    'status' => $job->status,
                    'related' => $related,
                    'output' => Str::limit($job->output, 150),
                    'created_at' => $job->created_at->diffForHumans(),
                    'started_at' => $job->started_at?->diffForHumans(),
                    'completed_at' => $job->completed_at?->diffForHumans(),
                    'duration' => $job->started_at && $job->completed_at ? $job->completed_at->diffInSeconds($job->started_at) . 's' : null,
                ];
            });

        return Inertia::render('Admin/JobRuns', [
            'jobRuns' => $jobRuns,
        ]);
    }

    // --- FILE-BASED PIPELINE LOGS ---

    public function pipelineFileLogsIndex()
    {
        $runs = array_map(
            fn (array $run) => $this->enrichPipelineHistoryRun($run),
            app(PipelineRunStore::class)->history()
        );
        return Inertia::render('Admin/PipelineFileLogViewer', [
            'pipelineRuns' => $runs,
        ]);
    }

    public function showPipelineFileLogRun(string $runId)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', Str::after($runId, '_'))) {
            if (!preg_match('/^\d{14}_[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $runId)) {
                Log::warning("Invalid runId format attempt: {$runId}");
                abort(404, 'Pipeline run not found or invalid ID.');
            }
        }

        $summaryFilePath = app(PipelineRunStore::class)->summaryPath($runId);
        if (!File::exists($summaryFilePath)) {
            abort(404, 'Pipeline run summary not found.');
        }
        $runDetails = json_decode(File::get($summaryFilePath), true);
        $runDetails = PipelineRunSummary::enrich($runDetails);
        $runDetails['freshness'] = PipelineRunSummary::freshness($runDetails, Carbon::now());

        return Inertia::render('Admin/PipelineFileRunDetail', [
            'runDetails' => $runDetails,
            'runId' => $runId,
        ]);
    }

    public function getPipelineCommandFileLogContent(Request $request, string $runId, string $logFileName)
    {
        if (!preg_match('/^\d{14}_[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $runId) ||
            !preg_match('/^cmd_[a-z0-9-]+_\d{14}(_\d+)?\.log$/', $logFileName)) {
            Log::warning("Invalid runId or logFileName format attempt: {$runId}, {$logFileName}");
            abort(400, 'Invalid parameters.');
        }

        $logFilePath = app(PipelineRunStore::class)->runDirectory($runId) . '/' . $logFileName;

        if (!File::exists($logFilePath)) {
            return Response::make('Log file not found.', 404);
        }

        $content = File::get($logFilePath);
        return Response::make($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function deletePipelineFileRun(Request $request, string $runId)
    {
        if (!preg_match('/^\d{14}_[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $runId)) {
            Log::warning("Invalid runId format for deletion: {$runId}");
            return redirect()->back()->with('error', 'Invalid Run ID format for deletion.');
        }

        $runLogDir = app(PipelineRunStore::class)->runDirectory($runId);

        if (File::isDirectory($runLogDir)) {
            File::deleteDirectory($runLogDir);

            $historyFilePath = (string) config('backend_admin.pipeline_runs.history_path', storage_path('logs/pipeline_runs_history.json'));
            if (File::exists($historyFilePath)) {
                $history = json_decode(File::get($historyFilePath), true) ?: [];
                $history = array_filter($history, fn($run) => $run['run_id'] !== $runId);
                File::put($historyFilePath, json_encode(array_values($history), JSON_PRETTY_PRINT));
            }
            return redirect()->route('admin.pipeline.fileLogs.index')->with('success', 'Pipeline run logs deleted successfully.');
        }
        return redirect()->back()->with('error', 'Pipeline run log directory not found.');
    }

    private function enrichPipelineHistoryRun(array $run): array
    {
        $summary = $this->loadPipelineSummaryFromHistoryEntry($run);
        if ($summary) {
            $run = array_merge($run, PipelineRunSummary::historyEntry($summary));
            $run['freshness'] = PipelineRunSummary::freshness($summary, Carbon::now());
            return $run;
        }

        $run['freshness'] = PipelineRunSummary::freshness($run, Carbon::now());
        return $run;
    }

    private function loadPipelineSummaryFromHistoryEntry(array $run): ?array
    {
        $relativePath = ltrim((string) ($run['summary_file_path'] ?? ''), '/');
        $summaryFilePath = $relativePath !== ''
            ? storage_path($relativePath)
            : app(PipelineRunStore::class)->summaryPath((string) ($run['run_id'] ?? ''));

        if (!File::exists($summaryFilePath)) {
            return null;
        }

        $decoded = json_decode(File::get($summaryFilePath), true);

        return is_array($decoded) ? $decoded : null;
    }

    // User Management
    public function usersIndex()
    {
        $users = User::with(['locations', 'savedMaps' => function ($query) {
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'manual_subscription_tier' => $user->manual_subscription_tier,
                    'effective_tier_details' => $user->getEffectiveTierDetails(),
                    'created_at' => $user->created_at->toFormattedDateString(),
                    'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toFormattedDateString() : 'Not Verified',
                    'locations' => $user->locations->map(fn($loc) => ['id' => $loc->id, 'name' => $loc->name, 'address' => $loc->address]),
                    'saved_maps' => $user->savedMaps->map(fn($map) => [
                        'id' => $map->id, 
                        'name' => $map->name, 
                        'is_public' => $map->is_public,
                        'is_approved' => $map->is_approved,
                        'is_featured' => $map->is_featured,
                        'view_url' => route('saved-maps.view', $map->id) 
                    ]),
                ];
            });

        return Inertia::render('Admin/ManageUsers', [
            'users' => $users,
            'subscriptionTiers' => ['free', 'basic', 'pro'],
            'userRoles' => ['user', 'admin'],
        ]);
    }

    public function updateUserTier(Request $request, User $user)
    {
        $validated = $request->validate([
            'manual_subscription_tier' => ['nullable', Rule::in(['free', 'basic', 'pro'])],
        ]);

        $user->manual_subscription_tier = $validated['manual_subscription_tier'];
        $user->save();

        return redirect()->back()->with('success', 'User subscription tier updated successfully.');
    }

    public function updateUser(Request $request, User $user)
    {
        $isSelfEdit = ($user->id === Auth::id() && $user->email === config('admin.email'));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(config('auth.user_roles', ['user', 'admin']))],
            'manual_subscription_tier' => ['nullable', Rule::in(config('subscriptions.tiers', ['free', 'basic', 'pro']))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'provider_name' => ['nullable', 'string', 'max:255'],
            'provider_id' => ['nullable', 'string', 'max:255'],
            'email_verified_at_action' => ['nullable', Rule::in(['keep', 'verify', 'unverify'])],
        ];

        if ($isSelfEdit) {
            unset($rules['email']);
            unset($rules['role']);
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        $validated = $validator->validate();

        $updateData = [
            'name' => $validated['name'],
            'manual_subscription_tier' => $validated['manual_subscription_tier'],
            'provider_name' => $validated['provider_name'] ?? $user->provider_name,
            'provider_id' => $validated['provider_id'] ?? $user->provider_id,
        ];

        if (!$isSelfEdit) {
            $updateData['email'] = $validated['email'];
            $updateData['role'] = $validated['role'];
            if ($user->email !== $validated['email']) {
                $updateData['email_verified_at'] = null;
            }
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }
        
        if (isset($validated['email_verified_at_action'])) {
            switch ($validated['email_verified_at_action']) {
                case 'verify':
                    $updateData['email_verified_at'] = now();
                    break;
                case 'unverify':
                    $updateData['email_verified_at'] = null;
                    break;
            }
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'User details updated successfully.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === Auth::id() && $user->email === config('admin.email')) {
            return redirect()->back()->with('error', 'Cannot delete the primary admin account.');
        }

        try {
            $userName = $user->name;
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', "User '{$userName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error("Error deleting user {$user->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete user. Check logs for details.');
        }
    }
}
