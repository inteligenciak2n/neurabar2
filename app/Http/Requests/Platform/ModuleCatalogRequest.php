<?php

namespace App\Http\Requests\Platform;

use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Models\Tenant\ModuleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModuleCatalogRequest extends FormRequest
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
        /** @var ModuleCatalog|null $module */
        $module = $this->route('module');

        return [
            'code' => [
                'required',
                Rule::enum(ModuleCode::class),
                $module
                    ? Rule::in([$module->code])
                    : Rule::unique('module_catalogs', 'code'),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:50'],
            'billing_type' => ['required', Rule::enum(ModuleBillingType::class)],
            'base_monthly_price' => ['required', 'numeric', 'min:0'],
            'unit_of_measure' => ['nullable', 'string', 'max:50'],
            'dependencies' => ['array'],
            'dependencies.*' => [
                'string',
                'distinct',
                Rule::enum(ModuleCode::class),
                Rule::notIn([$this->input('code')]),
                'exists:module_catalogs,code',
            ],
            'required_roles' => ['array'],
            'required_roles.*' => ['string', 'distinct', Rule::enum(UserRole::class)],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
        ];
    }
}
