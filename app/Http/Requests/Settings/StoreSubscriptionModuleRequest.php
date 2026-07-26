<?php

namespace App\Http\Requests\Settings;

use App\Enums\ModuleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_code' => ['required', 'string', Rule::in(ModuleCode::values())],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
