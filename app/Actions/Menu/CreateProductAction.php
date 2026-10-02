<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreProductRequest;
use App\Models\Menu\Product;
use App\Models\Tenant\Venue;

class CreateProductAction
{
    public function __construct(private readonly StoreProductImageAction $storeImage) {}

    public function execute(Venue $venue, StoreProductRequest $request): Product
    {
        $data = $request->safe()->except(['photo']);

        $data['sort_order'] = (Product::where('category_id', $data['category_id'])->max('sort_order') ?? 0) + 1;

        if ($request->hasFile('photo')) {
            $data['image_url'] = $this->storeImage->execute($request->file('photo'), $venue);
        }

        return Product::create($data);
    }
}
