<?php

declare(strict_types=1);

namespace App\SwissEphemerisAPI\Application\Requests;

use App\SwissEphemerisAPI\Application\Support\EphemerisInput;
use Illuminate\Foundation\Http\FormRequest;

final class FixedStarRequest extends FormRequest
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
            'star' => ['required', 'string', 'max:100'],
        ];
    }
}
