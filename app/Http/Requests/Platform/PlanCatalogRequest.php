<?php

namespace App\Http\Requests\Platform;

use App\Enums\ModuleCode;
use App\Enums\ProfileEnum;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanCatalogRequest extends FormRequest
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
        /** @var PlanCatalog|null $plan */
        $plan = $this->route('plan');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('plan_catalogs', 'code')->ignore($plan)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'dedicated_surcharge' => ['nullable', 'numeric', 'min:0'],
            'plan_type' => ['required', Rule::in(['shared', 'dedicated'])],
            'included_modules' => ['array'],
            'included_modules.*' => ['string', 'distinct', Rule::enum(ModuleCode::class), 'exists:module_catalogs,code'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
        ];
    }
}
