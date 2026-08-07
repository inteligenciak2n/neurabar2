<?php

namespace App\Http\Requests\Platform;

use App\Enums\ProfileEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreCorporationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->profile, [
            ProfileEnum::SuperAdmin,
            ProfileEnum::Finance,
            ProfileEnum::Registration,
        ], true);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'timezone' => ['required', 'string', 'max:50'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'plan_catalog_id' => ['nullable', 'uuid', 'exists:plan_catalogs,id'],
        ];
    }
}
