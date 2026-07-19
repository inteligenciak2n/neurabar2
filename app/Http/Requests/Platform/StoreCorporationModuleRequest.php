<?php

namespace App\Http\Requests\Platform;

use App\Enums\ModuleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorporationModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'module_code' => ['required', 'string', Rule::in(array_map(fn (ModuleCode $code): string => $code->value, ModuleCode::cases()))],
            'custom_monthly_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
