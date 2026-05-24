<?php

namespace App\Http\Requests\Orders;

use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return;
        }

        $venue = app('tenant');
        $menuIds = Menu::withoutGlobalScopes()->where('venue_id', $venue->id)->pluck('id');
        $categoryIds = Category::withoutGlobalScopes()->whereIn('menu_id', $menuIds)->pluck('id');
        $activeProducts = Product::withoutGlobalScopes()->whereIn('category_id', $categoryIds)->where('active', true)->get(['id', 'price']);
        $priceMap = $activeProducts->pluck('price', 'id');
        $variationPriceMap = ProductVariation::whereIn('product_id', $activeProducts->pluck('id'))->where('active', true)->get(['id', 'price'])->pluck('price', 'id');

        $filled = array_map(function (array $item) use ($priceMap, $variationPriceMap): array {
            if (! isset($item['unit_price']) || $item['unit_price'] === null) {
                $variationId = $item['variation_id'] ?? null;
                $productId = $item['product_id'] ?? null;

                if ($variationId && $variationPriceMap->has($variationId)) {
                    $item['unit_price'] = (float) $variationPriceMap->get($variationId);
                } elseif ($productId && $priceMap->has($productId)) {
                    $item['unit_price'] = (float) $priceMap->get($productId);
                }
            }

            return $item;
        }, $items);

        $this->merge(['items' => $filled]);
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

        $activeProducts = Product::withoutGlobalScopes()
            ->whereIn('category_id', $categoryIds)
            ->where('active', true)
            ->get(['id', 'price']);

        $activeProductIds = $activeProducts->pluck('id');
        $priceMap = $activeProducts->pluck('price', 'id');

        $variationPriceMap = ProductVariation::whereIn('product_id', $activeProductIds)
            ->where('active', true)
            ->get(['id', 'product_id', 'price'])
            ->keyBy('id');

        $venueModifierGroupIds = ModifierGroup::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->pluck('id');

        $tenantModifierOptionIds = ModifierOption::whereIn('modifier_group_id', $venueModifierGroupIds)
            ->where('active', true)
            ->pluck('id');

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', Rule::in($activeProductIds)],
            'items.*.variation_id' => ['nullable', 'uuid', Rule::in($variationPriceMap->keys()->all())],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($priceMap, $variationPriceMap): void {
                    if ($value === null) {
                        return;
                    }

                    $index = (int) explode('.', $attribute)[1];
                    $productId = $this->input("items.{$index}.product_id");
                    $variationId = $this->input("items.{$index}.variation_id");

                    if ($variationId && $variationPriceMap->has($variationId)) {
                        $expectedPrice = (float) $variationPriceMap->get($variationId)->price;
                        if (abs((float) $value - $expectedPrice) > 0.001) {
                            $fail('The unit price does not match the variation price.');
                        }
                    } elseif ($productId && $priceMap->has($productId)) {
                        $expectedPrice = (float) $priceMap->get($productId);
                        if (abs((float) $value - $expectedPrice) > 0.001) {
                            $fail('The unit price does not match the current product price.');
                        }
                    }
                },
            ],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*.modifier_option_id' => ['required', 'uuid', Rule::in($tenantModifierOptionIds)],
        ];
    }
}
