<?php

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
            'customer_identifier' => ['nullable', 'string', 'max:100'],
            'passphrase' => ['nullable', 'string'],
        ];
    }
}
