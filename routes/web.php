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

    Route::put('/profile', [RoleController::class, 'update'])->name('role.update');

});



Route::middleware(['auth', 'admin'])->group(function () {
    //
});


require __DIR__.'/auth.php';


Route::get('/scatter', [ThreeOneOneCaseController::class, 'indexnofilter'])->name('cases.indexnofilter');



Route::post('/api/crime-data', [CrimeMapController::class, 'getCrimeData'])->name('crime-data.api');
Route::get('/crime-map', [CrimeMapController::class, 'index'])->name('crime-map');
Route::post('/api/natural-language-query', [CrimeMapController::class, 'naturalLanguageQuery'])->name('crime-map.natural-language-query');

