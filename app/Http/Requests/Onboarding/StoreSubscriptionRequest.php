<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'module_codes' => ['array'],
            'module_codes.*' => ['string', 'exists:saas.module_catalogs,code'],
            'venue_count' => ['required', 'integer', 'min:1', 'max:20'],
            'terms' => ['accepted', 'required'],
        ];
    }
}
