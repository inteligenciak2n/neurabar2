<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreProductRequest;
use App\Models\Menu\Product;
use App\Models\Tenant\Venue;

class CreateProductAction
{
    public function execute(Venue $venue, StoreProductRequest $request): Product
    {
        $data = $request->validated();

        return Product::create($data);
    }
}
