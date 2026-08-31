<?php

declare(strict_types=1);

use App\SwissEphemerisAPI\Application\Controllers\SwissEphemerisApiController;
use Illuminate\Support\Facades\Route;

Route::post('/julian-day/to', [SwissEphemerisApiController::class, 'toJulianDay'])->name('julian-day.to');
Route::post('/julian-day/from', [SwissEphemerisApiController::class, 'fromJulianDay'])->name('julian-day.from');
Route::post('/planet-position', [SwissEphemerisApiController::class, 'planetPosition'])->name('planet-position');
Route::post('/houses', [SwissEphemerisApiController::class, 'houses'])->name('houses');
Route::post('/natal-chart', [SwissEphemerisApiController::class, 'natalChart'])->name('natal-chart');
Route::post('/ayanamsa', [SwissEphemerisApiController::class, 'ayanamsa'])->name('ayanamsa');
Route::post('/fixed-star', [SwissEphemerisApiController::class, 'fixedStar'])->name('fixed-star');
Route::post('/eclipses/solar', [SwissEphemerisApiController::class, 'solarEclipse'])->name('eclipses.solar');
Route::post('/eclipses/lunar', [SwissEphemerisApiController::class, 'lunarEclipse'])->name('eclipses.lunar');
