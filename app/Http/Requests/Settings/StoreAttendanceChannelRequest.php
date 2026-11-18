<?php

namespace App\Http\Requests\Settings;

use App\Enums\AttendanceChannelColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('color') && is_string($this->input('color'))) {
            $this->merge([
                'color' => strtolower($this->input('color')),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', Rule::enum(AttendanceChannelColor::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_trackable' => ['boolean'],
            'requires_customer_identifier' => ['boolean'],
            'requires_tab' => ['boolean'],
            'uses_service_location' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
