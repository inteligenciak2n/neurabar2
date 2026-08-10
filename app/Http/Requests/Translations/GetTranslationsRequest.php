<?php

namespace App\Http\Requests\Translations;

use Illuminate\Foundation\Http\FormRequest;

class GetTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'components' => ['required', 'array', 'min:1', 'max:50'],
            'components.*' => ['required', 'string', 'distinct:strict', 'regex:/\A[A-Za-z0-9][A-Za-z0-9._-]{0,119}\z/'],
        ];
    }
}
