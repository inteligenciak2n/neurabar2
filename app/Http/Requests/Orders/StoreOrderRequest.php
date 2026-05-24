<?php

namespace App\Http\Requests\Orders;

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /** @var Collection<string, mixed>|null */
    private ?Collection $activeProducts = null;

    /** @var Collection<string, mixed>|null */
    private ?Collection $variationPriceMap = null;

    /** @var Collection<string, string>|null */
    private ?Collection $tenantModifierOptionIds = null;

    /** @var Collection<string, mixed>|null */
    private ?Collection $activeComboIds = null;

    /** @var Collection<string, Collection<int, string>>|null */
    private ?Collection $productRequiredModifierGroupIds = null;

    public function authorize(): bool
    {
        return true;
    }

    private function resolveMenuData(): void
    {
        if ($this->activeProducts !== null) {
            return;
        }

        $venue = app('tenant');

        $menuIds = Menu::withoutGlobalScopes()->where('venue_id', $venue->id)->pluck('id');
        $categoryIds = Category::withoutGlobalScopes()->whereIn('menu_id', $menuIds)->pluck('id');

        $this->activeProducts = Product::withoutGlobalScopes()
            ->whereIn('category_id', $categoryIds)
            ->where('active', true)
            ->get(['id', 'price']);

        $this->variationPriceMap = ProductVariation::whereIn('product_id', $this->activeProducts->pluck('id'))
            ->where('active', true)
            ->get(['id', 'product_id', 'price'])
            ->keyBy('id');

        $venueModifierGroupIds = ModifierGroup::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->pluck('id');

        $this->tenantModifierOptionIds = ModifierOption::whereIn('modifier_group_id', $venueModifierGroupIds)
            ->where('active', true)
            ->pluck('id');

        $this->activeComboIds = Combo::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->pluck('id');

        // Map product_id -> [required modifier_group_ids] via pivot
        $this->productRequiredModifierGroupIds = \Illuminate\Support\Facades\DB::table('product_modifier_group')
            ->join('modifier_groups', 'modifier_groups.id', '=', 'product_modifier_group.modifier_group_id')
            ->where('modifier_groups.venue_id', $venue->id)
            ->where('modifier_groups.required', true)
            ->whereIn('product_modifier_group.product_id', $this->activeProducts->pluck('id'))
            ->select('product_modifier_group.product_id', 'modifier_groups.id as group_id')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->pluck('group_id'));
    }

    public function prepareForValidation(): void
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return;
        }

        $this->resolveMenuData();

        $priceMap = $this->activeProducts->pluck('price', 'id');
        $variationSimpleMap = $this->variationPriceMap->pluck('price', 'id');

        $filled = array_map(function (array $item) use ($priceMap, $variationSimpleMap): array {
            if (! isset($item['unit_price']) || $item['unit_price'] === null) {
                $variationId = $item['variation_id'] ?? null;
                $productId = $item['product_id'] ?? null;

                if ($variationId && $variationSimpleMap->has($variationId)) {
                    $item['unit_price'] = (float) $variationSimpleMap->get($variationId);
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
        $this->resolveMenuData();

        $activeProductIds = $this->activeProducts->pluck('id');
        $priceMap = $this->activeProducts->pluck('price', 'id');
        $variationPriceMap = $this->variationPriceMap;
        $tenantModifierOptionIds = $this->tenantModifierOptionIds;

        return [
            'items' => ['present', 'array'],
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
            'combos' => ['nullable', 'array'],
            'combos.*.combo_id' => ['required', 'uuid', Rule::in($this->activeComboIds)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->resolveMenuData();

        $validator->after(function (Validator $v): void {
            $items = $this->input('items', []);
            $combos = $this->input('combos', []);

            if (empty($items) && empty($combos)) {
                $v->errors()->add('items', 'At least one item or combo is required.');
            }

            foreach ($items as $index => $itemData) {
                $productId = $itemData['product_id'] ?? null;

                if (! $productId || ! $this->productRequiredModifierGroupIds?->has($productId)) {
                    continue;
                }

                $requiredGroupIds = $this->productRequiredModifierGroupIds->get($productId);
                $providedOptionIds = collect($itemData['modifiers'] ?? [])->pluck('modifier_option_id');

                $providedGroupIds = ModifierOption::whereIn('id', $providedOptionIds)
                    ->pluck('modifier_group_id');

                foreach ($requiredGroupIds as $groupId) {
                    if (! $providedGroupIds->contains($groupId)) {
                        $group = ModifierGroup::find($groupId);
                        $v->errors()->add(
                            "items.{$index}.modifiers",
                            'A selection from the required modifier group "'.($group?->name ?? $groupId).'" is missing.'
                        );
                    }
                }
            }
        });
    }
}
