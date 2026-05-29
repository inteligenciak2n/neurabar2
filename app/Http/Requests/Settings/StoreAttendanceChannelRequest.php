<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_trackable' => ['boolean'],
            'requires_customer_identifier' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
