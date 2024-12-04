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
});

Route::get('/search-address', [TrashScheduleByAddressController::class, 'search']);


// Route to display the generic map interface
Route::get('/', [GenericMapController::class, 'getRadialMap'])->name('map.index');
Route::post('/', [GenericMapController::class, 'getRadialMap'])->name('map.update');

// Route to fetch data for the map based on filters
Route::post('/api/map-data', [GenericMapController::class, 'getData'])->name('map.data');

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

