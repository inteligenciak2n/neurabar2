<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComboRequest extends FormRequest
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

        $productIds = Product::withoutGlobalScopes()
            ->whereHas('category', fn ($q) => $q->whereIn('menu_id', $menuIds))
            ->pluck('id')
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', Rule::in($productIds)],
            'items.*.variation_id' => ['nullable', 'uuid', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
