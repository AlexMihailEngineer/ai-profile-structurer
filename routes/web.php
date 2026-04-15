<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    // The page where we paste the profile
    Route::get('/profiles/create', [ProfileController::class, 'index'])->name('profiles.create');

    // The endpoint that talks to Kimi 2.5
    Route::post('/profiles/parse', [ProfileController::class, 'parse'])->name('profiles.parse');

    Route::get('/profiles/status/{task}', [ProfileController::class, 'checkStatus'])->name('profiles.status');

    // The endpoint to save the final version
    Route::post('/profiles', [ProfileController::class, 'store'])->name('profiles.store');
});

require __DIR__ . '/settings.php';
