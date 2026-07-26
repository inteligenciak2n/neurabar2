<?php

namespace App\Http\Requests\Settings;

use App\Enums\PaymentSaasMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(PaymentSaasMethod::values())],
            'payment_method_id' => [
                Rule::requiredIf(fn () => $this->input('method') === PaymentSaasMethod::CreditCard->value),
                'nullable',
                'string',
                'exists:user_payment_methods,id',
            ],
            'simulate_failure' => ['sometimes', 'boolean'],
        ];
    }
}
