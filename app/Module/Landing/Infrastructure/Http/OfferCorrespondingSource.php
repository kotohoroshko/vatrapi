<?php

declare(strict_types=1);

namespace App\Landing\Infrastructure\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class OfferCorrespondingSource
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Link', '<'.url('/source').'>; rel="source"', false);
        $response->headers->set('Link', '<'.url('/license').'>; rel="license"', false);

        return $response;
    }
}
