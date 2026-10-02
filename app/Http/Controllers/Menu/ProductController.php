<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateProductAction;
use App\Actions\Menu\ReorderProductsAction;
use App\Actions\Menu\StoreProductImageAction;
use App\Actions\Menu\ToggleProductActiveAction;
use App\Actions\Menu\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\ReorderProductsRequest;
use App\Http\Requests\Menu\StoreProductRequest;
use App\Http\Requests\Menu\SyncProductModifierGroupsRequest;
use App\Http\Requests\Menu\UpdateProductRequest;
use App\Models\Menu\Product;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(route('menu.index').'#menu-products');
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Product created.');
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $action->execute($product, $request);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product, StoreProductImageAction $storeImage): RedirectResponse
    {
        abort_if($product->category->menu->venue_id !== app('tenant')->id, 403);

        $storeImage->deleteStored($product);
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function toggleActive(Product $product, ToggleProductActiveAction $action): RedirectResponse
    {
        abort_if($product->category->menu->venue_id !== app('tenant')->id, 403);

        $action->execute($product);

        return back()->with('success', 'Product status updated.');
    }

    public function syncModifierGroups(SyncProductModifierGroupsRequest $request, Product $product): RedirectResponse
    {
        $venue = app('tenant');
        abort_if($product->category->menu->venue_id !== $venue->id, 404);

        $product->modifierGroups()->sync($request->validated('modifier_group_ids'));

        return back()->with('success', 'Modifier groups updated.');
    }

    public function reorder(ReorderProductsRequest $request, ReorderProductsAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request->validated('ids'));

        return back()->with('success', 'Order saved.');
    }
}
