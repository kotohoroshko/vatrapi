<?php

declare(strict_types=1);

namespace App\SwissEphemerisAPI\Application\Requests;

use App\SwissEphemerisAPI\Application\Support\EphemerisInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NatalChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...EphemerisInput::momentRules(),
            ...EphemerisInput::geoRules(),
            ...EphemerisInput::houseSystemRule(),
            'bodies' => ['sometimes', 'array'],
            'bodies.*' => ['string', Rule::in(EphemerisInput::planetNames())],
        ];
    }
}
