<?php

namespace App\Actions\Orders;

use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use App\Models\Tenant\Venue;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResolveOrderItemsAction
{
    /**
     * Resolve raw cart items into priced, venue-scoped order items, validating that
     * every product/variation/modifier belongs to the venue's active menu and to
     * each other (variation -> product, modifier option -> product).
     *
     * Runs 3 batched `whereIn` queries regardless of cart size (no N+1).
     *
     * @param  array<int, array{product_id: string, variation_id?: ?string, quantity: int, notes?: ?string, modifiers?: ?array<int, string>}>  $items
     * @param  Closure(Builder<Product>): void|null  $scopeProducts  Extra constraint applied to the product query (e.g. available_for_delivery).
     * @return array<int, array{product_id: string, variation_id: ?string, quantity: int, unit_price: float, notes: ?string, modifiers: array<int, array{modifier_option_id: string, extra_price_snapshot: float}>}>
     */
    public function execute(Venue $venue, array $items, ?Closure $scopeProducts = null): array
    {
        $productIds = collect($items)->pluck('product_id')->unique()->filter()->all();
        $variationIds = collect($items)->pluck('variation_id')->filter()->unique()->all();
        $modifierOptionIds = collect($items)->flatMap(fn (array $item) => $item['modifiers'] ?? [])->unique()->filter()->all();

        $productsQuery = Product::withoutGlobalScopes()
            ->whereIn('id', $productIds)
            ->whereHas('category.menu', fn ($q) => $q->where('venue_id', $venue->id)->where('active', true));

        if ($scopeProducts !== null) {
            $scopeProducts($productsQuery);
        }

        $products = $productsQuery->get()->keyBy('id');
        $variations = ProductVariation::withoutGlobalScopes()->whereIn('id', $variationIds)->get()->keyBy('id');
        $modifierOptions = ModifierOption::withoutGlobalScopes()
            ->whereIn('id', $modifierOptionIds)
            ->with('modifierGroup.products')
            ->get()
            ->keyBy('id');

        return array_map(
            fn (array $itemData) => $this->resolveItem($itemData, $products, $variations, $modifierOptions),
            $items
        );
    }

    /**
     * @param  array{product_id: string, variation_id?: ?string, quantity: int, notes?: ?string, modifiers?: ?array<int, string>}  $itemData
     * @param  Collection<string, Product>  $products
     * @param  Collection<string, ProductVariation>  $variations
     * @param  Collection<string, ModifierOption>  $modifierOptions
     * @return array{product_id: string, variation_id: ?string, quantity: int, unit_price: float, notes: ?string, modifiers: array<int, array{modifier_option_id: string, extra_price_snapshot: float}>}
     */
    private function resolveItem(array $itemData, Collection $products, Collection $variations, Collection $modifierOptions): array
    {
        $product = $products->get($itemData['product_id']);

        if ($product === null) {
            throw ValidationException::withMessages([
                'items' => 'One or more products are not available for this venue.',
            ]);
        }

        $unitPrice = (float) $product->price;
        $variationId = $itemData['variation_id'] ?? null;

        if ($variationId !== null) {
            $variation = $variations->get($variationId);

            if ($variation === null || $variation->product_id !== $product->id) {
                throw ValidationException::withMessages([
                    'items' => 'One or more variations do not belong to the selected product.',
                ]);
            }

            $unitPrice = (float) $variation->price;
        }

        $modifiers = [];

        foreach ($itemData['modifiers'] ?? [] as $modifierOptionId) {
            $modifierOption = $modifierOptions->get($modifierOptionId);

            if ($modifierOption === null || ! $modifierOption->modifierGroup->products->contains('id', $product->id)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more modifiers do not belong to the selected product.',
                ]);
            }

            $modifiers[] = [
                'modifier_option_id' => $modifierOptionId,
                'extra_price_snapshot' => (float) $modifierOption->extra_price,
            ];
        }

        return [
            'product_id' => $product->id,
            'variation_id' => $variationId,
            'quantity' => $itemData['quantity'],
            'unit_price' => $unitPrice,
            'notes' => $itemData['notes'] ?? null,
            'modifiers' => $modifiers,
        ];
    }
}
