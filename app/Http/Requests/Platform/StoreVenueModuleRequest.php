<?php

namespace App\Http\Requests\Platform;

use App\Enums\ModuleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenueModuleRequest extends FormRequest
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
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
