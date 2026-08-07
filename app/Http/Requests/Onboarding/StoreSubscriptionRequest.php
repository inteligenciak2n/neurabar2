<?php

namespace App\Http\Requests\Onboarding;

use App\Rules\ContractableModules;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->onboarding_completed_at === null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'module_codes' => ['array', 'max:20', new ContractableModules],
            'module_codes.*' => ['string', 'exists:saas.module_catalogs,code'],
            'venue_count' => ['required', 'integer', 'min:1', 'max:20'],
            'terms' => ['accepted', 'required'],
        ];
    }
}
