<?php

namespace App\Http\Controllers;

// Removed: use App\Models\SavedMap; 
use App\Models\User;
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

class AdminController extends Controller
{
    // Removed: private string $adminEmail = 'alex.g.alcivar49@gmail.com'; // Centralized to config

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
        // This page now serves as a dashboard linking to other admin sections.
        // Map listing is moved to AdminMapController@index and ManageMaps.vue
        return Inertia::render('Admin/Index', [
            // No longer passing mapsForApproval here
        ]);
    }

    // --- FILE-BASED PIPELINE LOGS ---

    public function pipelineFileLogsIndex()
    {
        $historyFilePath = storage_path('logs/pipeline_runs_history.json');
        $runs = [];
        if (File::exists($historyFilePath)) {
            $runs = json_decode(File::get($historyFilePath), true) ?: [];
            // Sort by start_time descending
            usort($runs, function ($a, $b) {
                return strtotime($b['start_time']) - strtotime($a['start_time']);
            });
        }
        return Inertia::render('Admin/PipelineFileLogViewer', [
            'pipelineRuns' => $runs,
        ]);
    }

    public function showPipelineFileLogRun(string $runId)
    {
        // Validate runId format to prevent directory traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', Str::after($runId, '_'))) {
             // A basic check, ensure runId is what you expect (e.g., YYYYMMDDHHMMSS_uuid)
            if (!preg_match('/^\d{14}_[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $runId)) {
                Log::warning("Invalid runId format attempt: {$runId}");
                abort(404, 'Pipeline run not found or invalid ID.');
            }
        }


        $summaryFilePath = storage_path('logs/pipeline_runs/' . $runId . '/run_summary.json');
        if (!File::exists($summaryFilePath)) {
            abort(404, 'Pipeline run summary not found.');
        }
        $runDetails = json_decode(File::get($summaryFilePath), true);

        return Inertia::render('Admin/PipelineFileRunDetail', [
            'runDetails' => $runDetails,
            'runId' => $runId,
        ]);
    }

    public function getPipelineCommandFileLogContent(Request $request, string $runId, string $logFileName)
    {
        // Validate runId and logFileName format to prevent directory traversal
        if (!preg_match('/^\d{14}_[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $runId) ||
            !preg_match('/^cmd_[a-z0-9-]+_\d{14}\.log$/', $logFileName)) {
            Log::warning("Invalid runId or logFileName format attempt: {$runId}, {$logFileName}");
            abort(400, 'Invalid parameters.');
        }

        $logFilePath = storage_path('logs/pipeline_runs/' . $runId . '/' . $logFileName);

        if (!File::exists($logFilePath)) {
            return Response::make('Log file not found.', 404);
        }
        
        // Limit file size to prevent memory issues, e.g., read last 1MB or N lines
        // For simplicity, returning full content. In production, consider streaming or partial reads.
        $content = File::get($logFilePath);
        return Response::make($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function deletePipelineFileRun(Request $request, string $runId)
    {
        if (!preg_match('/^\d{14}_[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $runId)) {
             Log::warning("Invalid runId format for deletion: {$runId}");
            return redirect()->back()->with('error', 'Invalid Run ID format for deletion.');
        }

        $runLogDir = storage_path('logs/pipeline_runs/' . $runId);

        if (File::isDirectory($runLogDir)) {
            File::deleteDirectory($runLogDir);

            // Update history file
            $historyFilePath = storage_path('logs/pipeline_runs_history.json');
            if (File::exists($historyFilePath)) {
                $history = json_decode(File::get($historyFilePath), true) ?: [];
                $history = array_filter($history, fn($run) => $run['run_id'] !== $runId);
                File::put($historyFilePath, json_encode(array_values($history), JSON_PRETTY_PRINT));
            }
            return redirect()->route('admin.pipeline.fileLogs.index')->with('success', 'Pipeline run logs deleted successfully.');
        }
        return redirect()->back()->with('error', 'Pipeline run log directory not found.');
    }

    // User Management
    public function usersIndex()
    {
        $users = User::with(['locations', 'savedMaps' => function ($query) {
                // Optionally eager load related data for saved maps if needed on this page
                // $query->with('user:id,name'); 
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
                        // Add a view URL for convenience
                        'view_url' => route('saved-maps.view', $map->id) 
                    ]),
                ];
            });

        return Inertia::render('Admin/ManageUsers', [
            'users' => $users,
            'subscriptionTiers' => ['free', 'basic', 'pro'], // For dropdowns
            'userRoles' => ['user', 'admin'], // Define available roles
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
            // Provider fields are usually set by OAuth, admin might correct them
            'provider_name' => ['nullable', 'string', 'max:255'],
            'provider_id' => ['nullable', 'string', 'max:255'],
            // email_verified_at can be set to null or a valid date string
            'email_verified_at_action' => ['nullable', Rule::in(['keep', 'verify', 'unverify'])],
        ];

        if ($isSelfEdit) {
            // Admin editing self: restrict role and email change to prevent lockout
            unset($rules['email']); // Cannot change own email via this form
            unset($rules['role']);  // Cannot change own role via this form
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        $validated = $validator->validate();

        $updateData = [
            'name' => $validated['name'],
            'manual_subscription_tier' => $validated['manual_subscription_tier'],
            'provider_name' => $validated['provider_name'] ?? $user->provider_name, // Keep old if not provided
            'provider_id' => $validated['provider_id'] ?? $user->provider_id,     // Keep old if not provided
        ];

        if (!$isSelfEdit) {
            $updateData['email'] = $validated['email'];
            $updateData['role'] = $validated['role'];
            // If email is changed, mark it as unverified
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
                // 'keep' does nothing to email_verified_at
            }
        }


        $user->update($updateData);

        return redirect()->back()->with('success', 'User details updated successfully.');
    }

    public function destroyUser(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === Auth::id() && $user->email === config('admin.email')) {
            return redirect()->back()->with('error', 'Cannot delete the primary admin account.');
        }

        // Consider implications: what happens to user's content?
        // Soft delete if User model supports it, or hard delete.
        // For now, a hard delete.
        try {
            // You might want to handle related data here, e.g., reassign content or delete it.
            // $user->locations()->delete();
            // $user->savedMaps()->delete();
            // $user->reports()->delete();
            // etc.
            
            $userName = $user->name;
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', "User '{$userName}' deleted successfully.");
        } catch (\Exception $e) {
            Log::error("Error deleting user {$user->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete user. Check logs for details.');
        }
    }
}
