<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cover_charge' => ['nullable', 'numeric', 'min:0'],
            'service_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'table_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
