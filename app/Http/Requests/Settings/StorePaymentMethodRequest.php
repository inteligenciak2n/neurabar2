<?php

namespace App\Http\Requests\Settings;

use App\Rules\ValidTaxId;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-subscription') === true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'min:13', 'max:19'],
            'holder_name' => ['required', 'string', 'max:255'],
            // Every field below is mandatory for Asaas card tokenization
            // (creditCardHolderInfo). Sending the request without them makes
            // the gateway reject the card with a validation error.
            'holder_document' => ['required', 'string', 'min:11', 'max:20', new ValidTaxId],
            'holder_email' => ['required', 'email', 'max:255'],
            'holder_postal_code' => ['required', 'string', 'min:8', 'max:20'],
            'holder_address_number' => ['required', 'string', 'max:50'],
            'holder_phone' => ['required', 'string', 'min:10', 'max:20'],
            'expiration_month' => ['required', 'integer', 'between:1,12'],
            'expiration_year' => ['required', 'integer', 'min:'.now()->year],
            'cvv' => ['required', 'string', 'min:3', 'max:4'],
            'billing_address' => ['sometimes', 'array'],
            'billing_address.street' => ['nullable', 'string', 'max:255'],
            'billing_address.number' => ['nullable', 'string', 'max:50'],
            'billing_address.complement' => ['nullable', 'string', 'max:255'],
            'billing_address.neighborhood' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['nullable', 'string', 'max:255'],
            'billing_address.state' => ['nullable', 'string', 'max:255'],
            'billing_address.zip_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Card payload shaped for PaymentGatewayContract::saveCard().
     *
     * The gateway contract uses Asaas' own `ccv` spelling while the form keeps
     * the conventional `cvv`, so the mapping happens here instead of leaking
     * an undefined index into the gateway.
     *
     * @return array{number: string, holder_name: string, holder_document: string, holder_email: string, holder_postal_code: string, holder_address_number: string, holder_phone: string, expiration_month: int, expiration_year: int, ccv: string, remote_ip: string}
     */
    public function cardData(): array
    {
        $validated = $this->validated();

        return [
            'number' => $validated['number'],
            'holder_name' => $validated['holder_name'],
            'holder_document' => $validated['holder_document'],
            'holder_email' => $validated['holder_email'],
            'holder_postal_code' => $validated['holder_postal_code'],
            'holder_address_number' => $validated['holder_address_number'],
            'holder_phone' => $validated['holder_phone'],
            'expiration_month' => $validated['expiration_month'],
            'expiration_year' => $validated['expiration_year'],
            'ccv' => $validated['cvv'],
            'remote_ip' => (string) $this->ip(),
        ];
    }
}
