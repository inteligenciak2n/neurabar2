<?php

namespace App\Http\Requests\Guest;

use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', Rule::exists(Product::class, 'id')],
            'items.*.variation_id' => ['nullable', 'uuid', Rule::exists(ProductVariation::class, 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*' => ['uuid', Rule::exists(ModifierOption::class, 'id')],
        ];
    }
}
