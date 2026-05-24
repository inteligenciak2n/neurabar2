<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreProductVariationRequest;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;

class CreateProductVariationAction
{
    public function execute(Product $product, StoreProductVariationRequest $request): ProductVariation
    {
        return $product->variations()->create($request->validated());
    }
}
