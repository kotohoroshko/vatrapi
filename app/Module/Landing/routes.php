<?php

declare(strict_types=1);

use App\Landing\Application\Controllers\LandingController;
use App\Landing\Application\Controllers\LegalController;
use App\Landing\Application\Controllers\PlaygroundController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::post('/playground/{endpoint}', [PlaygroundController::class, 'run'])
    ->where('endpoint', '[a-z-]+')
    ->middleware('throttle:playground')
    ->name('playground');

Route::get('/license', [LegalController::class, 'license'])->name('license');
Route::get('/notice', [LegalController::class, 'notice'])->name('notice');
Route::get('/source', [LegalController::class, 'source'])->name('source');
Route::get('/source/archive', [LegalController::class, 'archive'])
    ->middleware('throttle:source-archive')
    ->name('source.archive');

Route::get('/landing/assets/{file}', [LandingController::class, 'asset'])
    ->where('file', 'landing\.(css|js)')
    ->name('asset');
