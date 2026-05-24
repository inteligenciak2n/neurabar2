<?php

namespace App\Actions\Menu;

use App\Models\Menu\Product;

class ToggleProductActiveAction
{
    public function execute(Product $product): Product
    {
        $product->update(['active' => ! $product->active]);

        return $product->fresh();
    }
}
