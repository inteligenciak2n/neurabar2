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
            'require_table' => ['sometimes', 'boolean'],
            'require_tab' => ['sometimes', 'boolean'],
            'require_location' => ['sometimes', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'require_geolocation' => ['sometimes', 'boolean'],
        ];
    }
}
