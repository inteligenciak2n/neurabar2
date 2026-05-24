<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\UpdateProductRequest;
use App\Models\Menu\Product;

class UpdateProductAction
{
    public function execute(Product $product, UpdateProductRequest $request): Product
    {
        $product->update($request->validated());

        return $product->fresh();
    }
}
