<?php

namespace App\Http\Requests\Platform;

use App\Enums\ProfileEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualInvoiceRequest extends FormRequest
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
            'invoiceable_type' => ['required', Rule::in(['corporation', 'venue'])],
            'invoiceable_id' => ['required', 'uuid'],
            'period' => ['required', 'string', 'size:7'],
            'base_value' => ['required', 'numeric', 'min:0'],
            'modules_value' => ['nullable', 'numeric', 'min:0'],
            'metered_value' => ['nullable', 'numeric', 'min:0'],
            'dedicated_surcharge' => ['nullable', 'numeric', 'min:0'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ];
    }
}
