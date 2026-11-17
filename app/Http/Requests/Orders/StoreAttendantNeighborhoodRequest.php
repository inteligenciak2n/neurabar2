<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendantNeighborhoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'zip_code' => preg_replace('/\D+/', '', (string) $this->input('zip_code')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'digits:8'],
            'fee' => ['required', 'numeric', 'min:0'],
        ];
    }
}
