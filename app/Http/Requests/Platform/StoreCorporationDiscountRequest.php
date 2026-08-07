<?php

namespace App\Http\Requests\Platform;

use App\Enums\ProfileEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorporationDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->profile, [
            ProfileEnum::SuperAdmin,
            ProfileEnum::Finance,
        ], true);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => [
                'required',
                'numeric',
                'min:0',
                Rule::when(
                    $this->input('type') === 'percentage',
                    ['max:100'],
                    ['max:999999999.99']
                ),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'max_months' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
