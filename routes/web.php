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

Route::middleware(['auth'])->group(function () {
    // ... other auth routes ...

    Route::get('/subscribe', function (Request $request) {
        $priceId = 'price_1RNKvODAolObUp5gTKCvh5KB'; // <-- Paste the Price ID you copied from Stripe here!

        // Use the newSubscription builder to create the checkout session
        // This returns a RedirectResponse, which Inertia handles by performing a full page visit
        return $request->user()->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.cancel'),
            ]);

    })->name('subscribe.checkout'); // Give it a name so you can link to it

    // Define success and cancel routes - ADAPTED FOR INERTIA
    Route::get('/subscribe/success', function (Request $request) {
        // Handle successful checkout - webhook is CRUCIAL here
        // You'll typically render an Inertia component
        return Inertia::render('SubscriptionSuccess', [
            // You could fetch session details here if needed, but the webhook is the source of truth
            'sessionId' => $request->get('session_id'), // Passing session ID as prop is common
        ]);
    })->name('subscription.success');

    Route::get('/subscribe/cancel', function (Request $request) {
        // Handle canceled checkout - render an Inertia component
        return Inertia::render('SubscriptionCancel');
    })->name('subscription.cancel');

    Route::get('/billing-portal', function (Request $request) {
        // Redirect the authenticated user to the Stripe Billing Portal
        // Optionally provide a URL for them to return to after they're done
        return $request->user()->redirectToBillingPortal(route('map.index'), [
            'return_url' => route('map.index'),
        ]);
    })->name('billing'); // Name the route for easy linkin
});