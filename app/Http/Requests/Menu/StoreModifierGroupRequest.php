<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreModifierGroupRequest extends FormRequest
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
            'required' => ['boolean'],
            'multiple_selection' => ['boolean'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['nullable', 'string', 'max:255'],
            'options.*.extra_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
