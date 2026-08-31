<?php

declare(strict_types=1);

namespace App\SwissEphemerisAPI\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ToJulianDayRequest extends FormRequest
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
            'moment' => ['required_without_all:year,month,day', 'nullable', 'string'],
            'year' => ['required_without:moment', 'nullable', 'integer', 'between:1800,2399'],
            'month' => ['required_without:moment', 'nullable', 'integer', 'between:1,12'],
            'day' => ['required_without:moment', 'nullable', 'integer', 'between:1,31'],
            'hour' => ['sometimes', 'numeric', 'between:0,23.999999'],
        ];
    }
}
