<?php

namespace App\Http\Requests\Delivery;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliverySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'accepted_delivery_payment_methods' => ['required', 'array', 'min:1'],
            'accepted_delivery_payment_methods.*' => [Rule::enum(PaymentMethod::class)],
            'delivery_enabled' => ['required', 'boolean'],
            'pickup_enabled' => ['required', 'boolean'],
        ];
    }
}
