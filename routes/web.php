<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\VoterController;
use App\Http\Controllers\ResultsController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::post('/voter/login', [VoterController::class, 'login'])->name('voter.login');

// Voter Registration Routes
Route::get('/voter/register', [VoterController::class, 'showRegistrationForm'])->name('voter.register');
Route::post('/voter/register', [VoterController::class, 'register'])->name('voter.register.submit');

Route::middleware(['voter'])->group(function () {
    Route::get('/voting', [VoterController::class, 'voting'])->name('voting');
    Route::post('/voter/cast-vote', [VoterController::class, 'castVote'])->name('voter.cast-vote');
});
Route::post('/voter/logout', [VoterController::class, 'logout'])->name('voter.logout');

// Election Results Routes
Route::get('/results', [ResultsController::class, 'index'])->name('results');
Route::get('/results/data', [ResultsController::class, 'data'])->name('results.data');
Route::get('/live-results', [ResultsController::class, 'public'])->name('results.public');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
