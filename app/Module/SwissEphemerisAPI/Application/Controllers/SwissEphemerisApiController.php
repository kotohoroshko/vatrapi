<?php

declare(strict_types=1);

namespace App\SwissEphemerisAPI\Application\Controllers;

use App\SwissEphemeris\Exception\SwissEphemerisException;
use App\SwissEphemerisAPI\Application\Requests\AyanamsaRequest;
use App\SwissEphemerisAPI\Application\Requests\EclipseRequest;
use App\SwissEphemerisAPI\Application\Requests\FixedStarRequest;
use App\SwissEphemerisAPI\Application\Requests\FromJulianDayRequest;
use App\SwissEphemerisAPI\Application\Requests\HousesRequest;
use App\SwissEphemerisAPI\Application\Requests\NatalChartRequest;
use App\SwissEphemerisAPI\Application\Requests\PlanetPositionRequest;
use App\SwissEphemerisAPI\Application\Requests\ToJulianDayRequest;
use App\SwissEphemerisAPI\Application\Services\SwissEphemerisApiService;
use Illuminate\Http\JsonResponse;

final class SwissEphemerisApiController
{
    public function __construct(private readonly SwissEphemerisApiService $api) {}

    public function toJulianDay(ToJulianDayRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->toJulianDay($request->validated()));
    }

    public function fromJulianDay(FromJulianDayRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->fromJulianDay($request->validated()));
    }

    public function planetPosition(PlanetPositionRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->planetPosition($request->validated()));
    }

    public function houses(HousesRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->houses($request->validated()));
    }

    public function natalChart(NatalChartRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->natalChart($request->validated()));
    }

    public function ayanamsa(AyanamsaRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->ayanamsa($request->validated()));
    }

    public function fixedStar(FixedStarRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->fixedStar($request->validated()));
    }

    public function solarEclipse(EclipseRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->solarEclipse($request->validated()));
    }

    public function lunarEclipse(EclipseRequest $request): JsonResponse
    {
        return $this->respond(fn (): array => $this->api->lunarEclipse($request->validated()));
    }

    /**
     * @param  callable():array<string, mixed>  $callback
     */
    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'ok' => true,
                'data' => $callback(),
            ]);
        } catch (SwissEphemerisException $exception) {
            return response()->json([
                'ok' => false,
                'error' => $exception->getMessage(),
            ], 422);
        }
    }
}
