<?php

namespace App\Http\Requests\Orders;

use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $venue = app('tenant');

        $menuIds = Menu::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->pluck('id');

        $categoryIds = Category::withoutGlobalScopes()
            ->whereIn('menu_id', $menuIds)
            ->pluck('id');

        $activeProductIds = Product::withoutGlobalScopes()
            ->whereIn('category_id', $categoryIds)
            ->where('active', true)
            ->pluck('id');

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', Rule::in($activeProductIds)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*.modifier_option_id' => ['required', 'uuid', 'exists:modifier_options,id'],
        ];
    }
}
