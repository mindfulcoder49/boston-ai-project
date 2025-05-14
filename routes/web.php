<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ThreeOneOneCaseController;
use App\Http\Controllers\CrimeReportsController;
use App\Http\Controllers\CrimeMapController;

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


Route::middleware(['auth'])->group(function () {
    Route::resource('locations', LocationController::class);

    Route::post('/locations/{location}/dispatch-report', [LocationController::class, 'dispatchLocationReportEmail'])->name('locations.dispatch-report');
});

Route::get('/search-address', [TrashScheduleByAddressController::class, 'search']);


// Serve the Vue component directly
Route::get('/', function () {
    return Inertia::render('RadialMap', [
        // Optional: pass initial props if needed
    ]);
})->name('map.index');

// New API endpoint for fetching map data
Route::post('/api/map-data', [GenericMapController::class, 'getRadialMapData'])->name('map.data');


Route::post('/api/ai-chat', [AiAssistantController::class, 'handleRequest'])->name('ai.assistant');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //Route::put('/profile', [RoleController::class, 'update'])->name('role.update');

});



Route::middleware(['auth', 'admin'])->group(function () {
    //
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
            ]);
    })->name('subscribe.checkout'); // Name changed slightly to be more generic, plan is a param

    // REMOVE OLD success and cancel routes, as they now point to subscription.index
    // Route::get('/subscribe/success', ...)->name('subscription.success'); // REMOVE
    // Route::get('/subscribe/cancel', ...)->name('subscription.cancel');   // REMOVE

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
