<?php

namespace App\Http\Requests\Platform;

use App\Enums\ProfileEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCorporationRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
