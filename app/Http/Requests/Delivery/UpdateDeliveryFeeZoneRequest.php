<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryFeeZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'zip_code_start' => ['required', 'digits_between:1,8'],
            'zip_code_end' => ['required', 'digits_between:1,8', 'gte:zip_code_start'],
            'fee' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
