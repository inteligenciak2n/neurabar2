<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateProductVariationAction;
use App\Actions\Menu\UpdateProductVariationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreProductVariationRequest;
use App\Http\Requests\Menu\UpdateProductVariationRequest;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use Illuminate\Http\RedirectResponse;

class ProductVariationController extends Controller
{
    public function store(StoreProductVariationRequest $request, Product $product, CreateProductVariationAction $action): RedirectResponse
    {
        $venue = app('tenant');
        abort_if($product->category?->menu?->venue_id !== $venue->id, 403);

        $action->execute($product, $request);

        return back()->with('success', 'Variation created.');
    }

    public function update(UpdateProductVariationRequest $request, Product $product, ProductVariation $variation, UpdateProductVariationAction $action): RedirectResponse
    {
        $venue = app('tenant');
        abort_if($product->category?->menu?->venue_id !== $venue->id, 403);
        abort_if($variation->product_id !== $product->id, 403);

        $action->execute($variation, $request);

        return back()->with('success', 'Variation updated.');
    }

    public function destroy(Product $product, ProductVariation $variation): RedirectResponse
    {
        $venue = app('tenant');
        abort_if($product->category?->menu?->venue_id !== $venue->id, 403);
        abort_if($variation->product_id !== $product->id, 403);

        $variation->delete();

        return back()->with('success', 'Variation deleted.');
    }
}
