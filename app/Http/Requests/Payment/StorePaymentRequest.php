<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'party_size' => ['nullable', 'integer', 'min:0'],
            'methods' => ['required', 'array', 'min:1'],
            'methods.*.type' => ['required', Rule::enum(PaymentMethod::class)],
            'methods.*.amount' => ['required', 'numeric', 'min:0.01'],
            'methods.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
