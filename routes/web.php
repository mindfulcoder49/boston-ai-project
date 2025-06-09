<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ThreeOneOneCaseController;
use App\Http\Controllers\CrimeReportsController;
use App\Http\Controllers\CrimeMapController;
use App\Http\Controllers\DataMapController; // Added
use App\Http\Controllers\MetricsController; // Added

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\GenericMapController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\TrashScheduleByAddressController;

use App\Http\Controllers\LocationController;
use Laravel\Cashier\Cashier;
//User Model for user->subscriptions()
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ReportController; // Added
use App\Http\Controllers\SavedMapController; // Added
use App\Http\Controllers\AdminController; // Added
use App\Http\Controllers\AdminMapController; // Added
use App\Http\Controllers\AdminLocationController; // Added


Route::middleware(['auth'])->group(function () {
    Route::resource('locations', LocationController::class);

    Route::post('/locations/{location}/dispatch-report', [LocationController::class, 'dispatchLocationReportEmail'])->name('locations.dispatch-report');
});

Route::get('/search-address', [TrashScheduleByAddressController::class, 'search']);

// Google Places API proxy routes
Route::post('/api/google-places-autocomplete', [TrashScheduleByAddressController::class, 'googleAutocomplete']); // Or your preferred auth
Route::post('/api/geocode-google-place', [TrashScheduleByAddressController::class, 'geocodeGooglePlace']); // Or your preferred auth


// Serve the Vue component directly
Route::get('/', function () {
    return Inertia::render('RadialMap', [
        // Optional: pass initial props if needed
    ]);
})->name('map.index');

// New API endpoint for fetching map data
Route::post('/api/map-data', [GenericMapController::class, 'getRadialMapData'])->name('map.data');


Route::post('/api/ai-chat', [AiAssistantController::class, 'handleRequest'])->name('ai.assistant');
// New API endpoint for streaming location-based reports
Route::post('/api/stream-location-report', [AiAssistantController::class, 'streamLocationReport'])->name('ai.stream-location-report');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/redeem-code', [ProfileController::class, 'redeemSubscriptionCode'])->name('profile.redeemCode'); // New route

    // Report History Routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

    // Saved Maps Routes
    
    Route::post('/saved-maps', [SavedMapController::class, 'store'])->name('saved-maps.store');
    Route::put('/saved-maps/{savedMap}', [SavedMapController::class, 'update'])->name('saved-maps.update');
    Route::delete('/saved-maps/{savedMap}', [SavedMapController::class, 'destroy'])->name('saved-maps.destroy');
    // Add edit route if you plan an edit page: Route::get('/saved-maps/{savedMap}/edit', [SavedMapController::class, 'edit'])->name('saved-maps.edit');

});

Route::get('/saved-maps', [SavedMapController::class, 'index'])->name('saved-maps.index');
Route::get('/saved-maps/{savedMap}/view', [SavedMapController::class, 'view'])->name('saved-maps.view'); // Publicly viewable link
    
Route::middleware(['auth', 'admin'])->group(function () {
    // This group is currently empty, we'll put admin routes outside if using controller-based auth check
    // Or, define an 'admin' middleware and apply it here if preferred over constructor check
});

// Admin Routes (using controller-based auth check for now)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');

    // Map Management (moved to AdminMapController)
    Route::prefix('maps')->name('maps.')->group(function () {
        Route::get('/', [AdminMapController::class, 'index'])->name('index');
        Route::post('/{savedMap}/approve', [AdminMapController::class, 'approve'])->name('approve');
        Route::post('/{savedMap}/unapprove', [AdminMapController::class, 'unapprove'])->name('unapprove');
        Route::post('/{savedMap}/feature', [AdminMapController::class, 'feature'])->name('feature');
        Route::post('/{savedMap}/unfeature', [AdminMapController::class, 'unfeature'])->name('unfeature');
        Route::put('/{savedMap}', [AdminMapController::class, 'update'])->name('update');
        Route::delete('/{savedMap}', [AdminMapController::class, 'destroy'])->name('destroy');
    });
    
    // User Management
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
    Route::post('/users/{user}/tier', [AdminController::class, 'updateUserTier'])->name('users.updateTier'); // Kept for specific tier updates if still used directly
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

    // Location Management (New)
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [AdminLocationController::class, 'index'])->name('index');
        Route::put('/{location}', [AdminLocationController::class, 'update'])->name('update');
        Route::delete('/{location}', [AdminLocationController::class, 'destroy'])->name('destroy');
    });

    // Pipeline Monitoring (File-based)
    Route::get('/pipeline-file-logs', [AdminController::class, 'pipelineFileLogsIndex'])->name('pipeline.fileLogs.index');
    Route::get('/pipeline-file-logs/{runId}', [AdminController::class, 'showPipelineFileLogRun'])->name('pipeline.fileLogs.show');
    Route::get('/pipeline-file-logs/{runId}/command-log/{logFileName}', [AdminController::class, 'getPipelineCommandFileLogContent'])->name('pipeline.fileLogs.commandLogContent');
    Route::delete('/pipeline-file-logs/{runId}', [AdminController::class, 'deletePipelineFileRun'])->name('pipeline.fileLogs.delete');

    // Remove or comment out old DB-based pipeline routes if they exist
    // Route::get('/pipeline-runs', [AdminController::class, 'pipelineRunsIndex'])->name('pipeline.runs.index');
    // Route::get('/pipeline-runs/{pipelineRun}', [AdminController::class, 'showPipelineRun'])->name('pipeline.runs.show');
});


require __DIR__.'/auth.php';


Route::get('/scatter', [ThreeOneOneCaseController::class, 'indexnofilter'])->name('cases.indexnofilter');



Route::post('/api/crime-data', [CrimeMapController::class, 'getCrimeData'])->name('crime-data.api');
Route::get('/crime-map', [CrimeMapController::class, 'index'])->name('crime-map');
Route::post('/api/natural-language-query', [CrimeMapController::class, 'naturalLanguageQuery'])->name('crime-map.natural-language-query');

Route::get('/api/311-case/live/{case_enquiry_id}', [ThreeOneOneCaseController::class, 'getLiveCaseDetails'])->name('311case.live')->middleware('throttle:boston_311_live_global');
Route::post('api/311-case/live-multiple', [ThreeOneOneCaseController::class, 'getMultipleLiveCaseDetails'])->middleware('throttle:boston_311_live_global');

Route::middleware(['auth'])->group(function () {
    // ... other auth routes ...

    Route::get('/subscribe/{plan}', function (Request $request, $plan) {
        $priceId = null;
        if ($plan === 'basic') {
            $priceId = Config::get('stripe.prices.basic_plan'); // Ensure this is in config/stripe.php
        } elseif ($plan === 'pro') {
            $priceId = Config::get('stripe.prices.pro_plan');   // Add this to config/stripe.php
        }

        if (!$priceId) {
            abort(404, 'Subscription plan not found.');
        }

        return $request->user()->newSubscription('default', $priceId)
            ->checkout([
                // Pass status to the subscription index page
                'success_url' => route('subscription.index', ['status' => 'success', 'session_id' => '{CHECKOUT_SESSION_ID}']),
                'cancel_url' => route('subscription.index', ['status' => 'cancel']),
                'allow_promotion_codes' => true,
            ]);
    })->name('subscribe.checkout'); // Name changed slightly to be more generic, plan is a param

    // Old success and cancel routes are removed, as they now point to subscription.index

    Route::get('/billing-portal', function (Request $request) {
        return $request->user()->redirectToBillingPortal(route('subscription.index')); // Return to subscription page
    })->name('billing');
});

Route::get('/subscription', [SubscriptionController::class, 'index'])
    ->name('subscription.index'); // Page to show pricing plans & success/cancel messages

Route::get('/login/{provider}/redirect', [SocialLoginController::class, 'redirectToProvider'])->name('socialite.redirect');
Route::get('/login/{provider}/callback', [SocialLoginController::class, 'handleProviderCallback'])->name('socialite.callback');

Route::get('/privacy-policy', function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('privacy.policy');

Route::get('/terms-of-use', function () {
    return Inertia::render('Legal/TermsOfUse');
})->name('terms.of.use');

Route::get('/about-us', function () {
    return Inertia::render('Company/AboutUs');
})->name('about.us');

Route::get('/help-contact', function () {
    return Inertia::render('Support/HelpContact');
})->name('help.contact');

Route::post('/feedback', [EmailController::class, 'store'])
    ->name('feedback.store');

// Data Metrics Page Route (Public)
Route::get('/data-metrics', [MetricsController::class, 'index'])->name('data.metrics'); // Added

// Generalized Data Map Routes (New)
Route::get('/map/{dataType}', [DataMapController::class, 'index'])
    ->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data-map.index');

// New Combined Data Map Route
Route::get('/combined-map', [DataMapController::class, 'combinedIndex'])
    ->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data-map.combined');

// API routes for data fetching (can be grouped under api.php if preferred, but kept here for simplicity with web auth)
Route::post('/api/data/{dataType}', [DataMapController::class, 'getData'])
    ->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data.get');

Route::post('/api/natural-language-query/{dataType}', [DataMapController::class, 'naturalLanguageQuery'])
    ->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data.natural-language-query');
