<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ThreeOneOneCaseController;
use App\Http\Controllers\CrimeReportsController;
use App\Http\Controllers\CrimeMapController;
use App\Http\Controllers\DataMapController; // Added
use App\Http\Controllers\MetricsController; // Added
use App\Http\Controllers\NewsArticleController; // Added
use App\Http\Controllers\AdminNewsArticleController;

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
use App\Http\Controllers\ReportMapController; // Added
use App\Http\Controllers\ReportIndexController; // Added
use App\Http\Controllers\SavedMapController; // Added
use App\Http\Controllers\AdminController; // Added
use App\Http\Controllers\AdminMapController; // Added
use App\Http\Controllers\AdminLocationController; // Added

use App\Http\Controllers\TrendsController;
use App\Http\Controllers\StatisticalAnalysisReportController;
use App\Http\Controllers\YearlyCountComparisonController;
use App\Http\Controllers\ScoringReportController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\AdminH3GeocodingController;
use App\Http\Controllers\AdminS3BucketController;
use App\Http\Controllers\AdminCacheController;
use App\Http\Controllers\CityLandingController;
use App\Http\Controllers\SitemapController;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/trends', [TrendsController::class, 'index'])->name('trends.index');
Route::middleware(['auth', 'verified'])->post('/trends/refresh', [TrendsController::class, 'refresh'])->name('trends.refresh');
Route::get('/api/trends/{jobId}/summary', [TrendsController::class, 'getSummary'])->name('trends.summary');
Route::get('/hotspots', [HotspotController::class, 'index'])->name('hotspots.index');
Route::get('/hotspots/{citySlug}', [HotspotController::class, 'show'])->name('hotspots.show');
Route::get('/reports/statistical-analysis/{jobId}', [StatisticalAnalysisReportController::class, 'show'])
    ->where('jobId', '[^/]+')
    ->name('reports.statistical-analysis.show');
Route::get('/api/reports/statistical-analysis/{jobId}/group-detail', [StatisticalAnalysisReportController::class, 'groupDetail'])
    ->where('jobId', '[^/]+')
    ->name('reports.statistical-analysis.group-detail');

Route::get('/yearly-comparisons', [YearlyCountComparisonController::class, 'index'])->name('yearly-comparisons.index');
Route::middleware(['auth', 'verified'])->post('/yearly-comparisons/refresh', [YearlyCountComparisonController::class, 'refresh'])->name('yearly-comparisons.refresh');
Route::get('/reports/yearly-comparison/{jobId}', [YearlyCountComparisonController::class, 'show'])
    ->where('jobId', '[\w\-]+')
    ->name('reports.yearly-comparison.show');

// Scoring Report Routes (New)
Route::get('/scoring-reports', [ScoringReportController::class, 'index'])->name('scoring-reports.index');

Route::get('/scoring-reports/{jobId}/{artifactName}', [ScoringReportController::class, 'show'])
    ->where('artifactName', '.*') // Allow dots in filename
    ->name('scoring-reports.show');

Route::get('/api/scoring-reports/{jobId}/{artifactName}', [ScoringReportController::class, 'getReportData'])
    ->where('artifactName', '.*')
    ->name('scoring-reports.report-data');
Route::post('/api/scoring-reports/score-for-location', [ScoringReportController::class, 'getScoreForLocation'])->name('scoring-reports.score-for-location');
Route::get('/api/scoring-reports/source-analysis/{jobId}', [ScoringReportController::class, 'getSourceAnalysisData'])->name('scoring-reports.source-analysis');

// News Article Routes
Route::get('/news', [NewsArticleController::class, 'index'])->name('news.index');
Route::get('/news/{newsArticle:slug}', [NewsArticleController::class, 'show'])->name('news.show');

Route::middleware(['auth'])->group(function () {
    Route::resource('locations', LocationController::class);

    Route::post('/locations/{location}/dispatch-report', [LocationController::class, 'dispatchLocationReportEmail'])->name('locations.dispatch-report');
});

Route::get('/search-address', [TrashScheduleByAddressController::class, 'search']);

// Google Places API proxy routes
Route::post('/api/google-places-autocomplete', [TrashScheduleByAddressController::class, 'googleAutocomplete']); // Or your preferred auth
Route::post('/api/geocode-google-place', [TrashScheduleByAddressController::class, 'geocodeGooglePlace']); // Or your preferred auth


// Serve the Vue component directly
Route::get('/map/{lat?}/{lng?}', function ($lat = null, $lng = null) {
    return Inertia::render('RadialMap', [
        'initialLat' => $lat ? (float)$lat : null,
        'initialLng' => $lng ? (float)$lng : null,
    ]);
})->where([
    'lat' => '[-+]?([0-9]*\.[0-9]+|[0-9]+)',
    'lng' => '[-+]?([0-9]*\.[0-9]+|[0-9]+)'
])->name('map.index');

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

foreach (array_keys(config('cities.cities', [])) as $cityKey) {
    $citySlug = str_replace('_', '-', $cityKey);

    Route::get("/{$citySlug}", function () use ($citySlug) {
        return app(CityLandingController::class)->show($citySlug);
    })
        ->name("city.landing.{$cityKey}");
}

// New API endpoint for fetching map data
Route::post('/api/map-data', [GenericMapController::class, 'getRadialMapData'])->name('map.data');
Route::post('/api/city-landing/translate-record', [CityLandingController::class, 'translateRecord'])->name('city-landing.translate-record');


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

Route::get('/csvreports/map', [ReportIndexController::class, 'index'])->name('reports.map.index'); // New route
Route::get('/csvreports/map/{filename}', [ReportMapController::class, 'show'])->name('reports.map.show');


Route::get('/saved-maps', [SavedMapController::class, 'index'])->name('saved-maps.index');
Route::get('/saved-maps/{savedMap}/view', [SavedMapController::class, 'view'])->name('saved-maps.view'); // Publicly viewable link

// Admin Routes (using controller-based auth check for now)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Cache & Report Import Manager
    Route::prefix('cache-manager')->name('cache-manager.')->group(function () {
        Route::get('/', [AdminCacheController::class, 'index'])->name('index');
        Route::post('/forget', [AdminCacheController::class, 'forgetCache'])->name('forget');
        Route::post('/forget-all-listing', [AdminCacheController::class, 'forgetAllListingCaches'])->name('forget-all-listing');
        Route::post('/forget-all-summaries', [AdminCacheController::class, 'forgetAllSummaryCaches'])->name('forget-all-summaries');
        Route::get('/pull-reports/stream', [AdminCacheController::class, 'pullReportsStream'])->name('pull-reports.stream');
        Route::post('/materialize-hotspots', [AdminCacheController::class, 'materializeHotspots'])->name('materialize-hotspots');
        Route::post('/warm-metrics', [AdminCacheController::class, 'warmMetrics'])->name('warm-metrics');
    });

    Route::get('/h3-geocoding', [AdminH3GeocodingController::class, 'index'])->name('h3-geocoding.index');
    Route::post('/h3-geocoding/geocode', [AdminH3GeocodingController::class, 'geocode'])->name('h3-geocoding.geocode');

    // S3 Bucket Browser
    Route::prefix('s3-bucket')->name('s3-bucket.')->group(function () {
        Route::get('/', [AdminS3BucketController::class, 'index'])->name('index');
        Route::post('/refresh', [AdminS3BucketController::class, 'refresh'])->name('refresh');
        Route::post('/bulk-destroy', [AdminS3BucketController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/{jobId}', [AdminS3BucketController::class, 'destroyDirectory'])->name('destroy-directory');
        Route::delete('/{jobId}/files/{filename}', [AdminS3BucketController::class, 'destroyFile'])
            ->where('filename', '.*')
            ->name('destroy-file');
    });

    Route::post('/scoring-reports/refresh', [ScoringReportController::class, 'refreshIndex'])->name('scoring-reports.refresh');
    Route::delete('/scoring-reports/{jobId}/{artifactName}', [ScoringReportController::class, 'destroy'])
    ->where('artifactName', '.*')
    ->name('scoring-reports.destroy');

    
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/backend-health', [AdminController::class, 'backendHealthIndex'])->name('backend-health.index');

    // Job Runs
    Route::get('/job-runs', [AdminController::class, 'jobRunsIndex'])->name('job-runs.index');

    // Job Dispatcher (New)
    Route::get('/job-dispatcher', [AdminController::class, 'jobDispatcherIndex'])->name('job-dispatcher.index');
    Route::post('/job-dispatcher', [AdminController::class, 'dispatchJob'])->name('job-dispatcher.dispatch');
    Route::post('/job-dispatcher/unique-values', [AdminController::class, 'getUniqueColumnValues'])->name('job-dispatcher.unique-values');

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
    Route::get('/pipeline-file-logs/{runId}/command-log/{logFileName}', [AdminController::class, 'getPipelineCommandFileLogContent'])
        ->where('logFileName', '.*')
        ->name('pipeline.fileLogs.commandLogContent');
    Route::delete('/pipeline-file-logs/{runId}', [AdminController::class, 'deletePipelineFileRun'])->name('pipeline.fileLogs.delete');

    // Remove or comment out old DB-based pipeline routes if they exist
    // Route::get('/pipeline-runs', [AdminController::class, 'pipelineRunsIndex'])->name('pipeline.runs.index');
    // Route::get('/pipeline-runs/{pipelineRun}', [AdminController::class, 'showPipelineRun'])->name('pipeline.runs.show');

    // News Article Generator
    Route::prefix('news-articles')->name('news-articles.')->group(function () {
        Route::get('/',                       [AdminNewsArticleController::class, 'index'])->name('index');
        Route::post('/generate-from-trend',   [AdminNewsArticleController::class, 'generateFromTrend'])->name('generate-from-trend');
        Route::post('/generate-from-hexagon', [AdminNewsArticleController::class, 'generateFromHexagon'])->name('generate-from-hexagon');
        Route::get('/trends/{trend}/configure',        [AdminNewsArticleController::class, 'configureTrend'])->name('trends.configure');
        Route::post('/trends/{trend}/configure',       [AdminNewsArticleController::class, 'saveTrendConfig'])->name('trends.save-config');
        Route::get('/hotspots/{h3}/configure',         [AdminNewsArticleController::class, 'configureHotspot'])->name('hotspots.configure');
        Route::post('/hotspots/{h3}/configure',        [AdminNewsArticleController::class, 'saveHotspotConfig'])->name('hotspots.save-config');
        Route::post('/configs/{config}/generate',       [AdminNewsArticleController::class, 'generateFromConfig'])->name('configs.generate');
        Route::post('/configs/{config}/estimate-tokens',[AdminNewsArticleController::class, 'estimateTokensForConfig'])->name('configs.estimate-tokens');
        Route::post('/estimate-tokens-preview',         [AdminNewsArticleController::class, 'estimateTokensPreview'])->name('estimate-tokens-preview');
    });
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

Route::get('/help', function () {
    return Inertia::render('Help/Index');
})->name('help.index');

Route::get('/help/users', function () {
    return Inertia::render('Help/ForUsers');
})->name('help.users');

Route::get('/help/municipalities', function () {
    return Inertia::render('Help/ForMunicipalities');
})->name('help.municipalities');

Route::get('/help/researchers', function () {
    return Inertia::render('Help/ForResearchers');
})->name('help.researchers');

Route::get('/help/investors', function () {
    return Inertia::render('Help/ForInvestors');
})->name('help.investors');

Route::get('/help-contact', function () {
    return Inertia::render('Support/HelpContact');
})->name('help.contact');

Route::post('/feedback', [EmailController::class, 'store'])
    ->name('feedback.store');

// Data Metrics Page Route (Public)
Route::get('/data-metrics', [MetricsController::class, 'index'])->name('data.metrics'); // Added

// Generalized Data Map Routes (New)
Route::get('/data-map/{dataType}', [DataMapController::class, 'index'])
    //->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data-map.index');

// New Combined Data Map Route
Route::get('/combined-map', [DataMapController::class, 'combinedIndex'])
    //->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data-map.combined');

// API routes for data fetching (can be grouped under api.php if preferred, but kept here for simplicity with web auth)
Route::post('/api/data/{dataType}', [DataMapController::class, 'getData'])
    //->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data.get');

Route::post('/api/natural-language-query/{dataType}', [DataMapController::class, 'naturalLanguageQuery'])
    //->middleware(['auth', 'verified']) // Assuming auth is needed
    ->name('data.natural-language-query');
