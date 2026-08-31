<?php

use App\Landing\Infrastructure\Http\OfferCorrespondingSource;
use App\SwissEphemeris\Application\Console\InstallSwephpCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
    )
    ->withCommands([
        InstallSwephpCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(OfferCorrespondingSource::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
