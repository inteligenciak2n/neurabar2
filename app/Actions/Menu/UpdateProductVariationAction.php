<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\UpdateProductVariationRequest;
use App\Models\Menu\ProductVariation;

class UpdateProductVariationAction
{
    public function execute(ProductVariation $variation, UpdateProductVariationRequest $request): ProductVariation
    {
        $variation->update($request->validated());

        return $variation;
    }
}
