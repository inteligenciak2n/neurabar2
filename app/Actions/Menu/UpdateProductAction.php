<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\UpdateProductRequest;
use App\Models\Menu\Product;

class UpdateProductAction
{
    public function __construct(private readonly StoreProductImageAction $storeImage) {}

    public function execute(Product $product, UpdateProductRequest $request): Product
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->hasFile('photo')) {
            $data['image_url'] = $this->storeImage->execute(
                $request->file('photo'),
                app('tenant'),
                $product,
            );
        } elseif ($request->boolean('remove_photo')) {
            $this->storeImage->deleteStored($product);
            $data['image_url'] = null;
        }

        if (isset($data['category_id']) && $data['category_id'] !== $product->category_id) {
            $data['sort_order'] = (Product::where('category_id', $data['category_id'])->max('sort_order') ?? 0) + 1;
        }

        $product->update($data);

        return $product->fresh();
    }
}
