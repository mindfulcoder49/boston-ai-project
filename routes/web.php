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

Route::get('/search-address', [TrashScheduleByAddressController::class, 'search']);


// Route to display the generic map interface
Route::get('/', [GenericMapController::class, 'getRadialMap'])->name('map.index');
Route::post('/map', [GenericMapController::class, 'getRadialMap'])->name('map.update');

// Route to fetch data for the map based on filters
Route::post('/api/map-data', [GenericMapController::class, 'getData'])->name('map.data');

Route::post('/api/ai-chat', [AiAssistantController::class, 'handleRequest'])->name('ai.assistant');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');
*/

/* Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');  */

/* add route to dashboard that goes through middleware but doesn't require user to be logged in */
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::put('/profile', [RoleController::class, 'update'])->name('role.update');


    //Route::get('/api/AI', [AIController::class, 'handle']);
});



Route::middleware(['auth', 'admin'])->group(function () {
    //
});


require __DIR__.'/auth.php';

Route::get('/cases', [ThreeOneOneCaseController::class, 'index'])->name('cases.index');

Route::get('/scatter', [ThreeOneOneCaseController::class, 'indexnofilter'])->name('cases.indexnofilter');


Route::inertia('/311demo', "ThreeOneOneDemo")->name('311demo');
Route::inertia('/thebostonappdemo', "TheBostonAppDemo")->name('thebostonappdemo');

Route::post('/api/crime-data', [CrimeMapController::class, 'getCrimeData'])->name('crime-data.api');
Route::get('/crime-map', [CrimeMapController::class, 'index'])->name('crime-map');
Route::post('/api/natural-language-query', [CrimeMapController::class, 'naturalLanguageQuery'])->name('crime-map.natural-language-query');

