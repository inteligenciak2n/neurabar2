<?php

namespace App\Http\Requests\Translations;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'translations' => ['required', 'array', 'min:1', 'max:50'],
            'translations.*.component' => ['required', 'string', 'distinct:strict', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._-]{0,119}\z/'],
            'translations.*.strings' => ['required', 'array', 'min:1', 'max:100'],
            'translations.*.strings.*' => ['required', 'string', 'max:1000', 'distinct:strict'],
        ];
    }
}
