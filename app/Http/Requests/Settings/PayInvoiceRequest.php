<?php

namespace App\Http\Requests\Settings;

use App\Enums\PaymentSaasMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', Rule::in(PaymentSaasMethod::values())],
            'payment_method_id' => [
                Rule::requiredIf(fn () => $this->input('method') === PaymentSaasMethod::CreditCard->value),
                'nullable',
                'string',
                // Scoped to the owner: an unscoped `exists` let any tenant pay
                // their own invoice using another user's stored card.
                Rule::exists('user_payment_methods', 'id')->where('user_id', $this->user()?->id),
            ],
            'simulate_failure' => ['sometimes', 'boolean'],
        ];
    }
}
