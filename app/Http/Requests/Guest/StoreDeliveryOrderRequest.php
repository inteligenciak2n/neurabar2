<?php

namespace App\Http\Requests\Guest;

use App\Enums\FulfillmentType;
use App\Enums\PaymentMethod;
use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],

            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:20'],

            'address' => ['required_if:fulfillment_type,'.FulfillmentType::Delivery->value, 'array'],
            'address.street' => ['required_if:fulfillment_type,'.FulfillmentType::Delivery->value, 'nullable', 'string', 'max:255'],
            'address.number' => ['required_if:fulfillment_type,'.FulfillmentType::Delivery->value, 'nullable', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.neighborhood' => ['required_if:fulfillment_type,'.FulfillmentType::Delivery->value, 'nullable', 'string', 'max:255'],
            'address.city' => ['required_if:fulfillment_type,'.FulfillmentType::Delivery->value, 'nullable', 'string', 'max:255'],
            'address.state' => ['required_if:fulfillment_type,'.FulfillmentType::Delivery->value, 'nullable', 'string', 'max:2'],
            'address.zip_code' => ['required_if:fulfillment_type,'.FulfillmentType::Delivery->value, 'nullable', 'string', 'max:9'],
            'address.reference_point' => ['nullable', 'string', 'max:255'],
            'address.save_address' => ['nullable', 'boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', Rule::exists(Product::class, 'id')->where('available_for_delivery', true)],
            'items.*.variation_id' => ['nullable', 'uuid', Rule::exists(ProductVariation::class, 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*' => ['uuid', Rule::exists(ModifierOption::class, 'id')],

            'methods' => ['required', 'array', 'min:1'],
            'methods.*.type' => ['required', Rule::enum(PaymentMethod::class)],
            'methods.*.amount' => ['required', 'numeric', 'min:0.01'],
            'methods.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
