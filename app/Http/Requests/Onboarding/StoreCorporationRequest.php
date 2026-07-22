<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorporationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],

            'venues' => ['required', 'array', 'min:1'],
            'venues.*.skip' => ['boolean'],
            'venues.*.name' => ['required_if:venues.*.skip,false', 'nullable', 'string', 'max:255'],
            'venues.*.tax_id' => ['nullable', 'string', 'max:20'],
            'venues.*.phone' => ['nullable', 'string', 'max:20'],
            'venues.*.city' => ['nullable', 'string', 'max:100'],
            'venues.*.state' => ['nullable', 'string', 'max:2'],
            'venues.*.timezone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
