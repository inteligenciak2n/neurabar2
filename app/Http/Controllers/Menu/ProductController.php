<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateProductAction;
use App\Actions\Menu\ToggleProductActiveAction;
use App\Actions\Menu\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreProductRequest;
use App\Http\Requests\Menu\SyncProductModifierGroupsRequest;
use App\Http\Requests\Menu\UpdateProductRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\Product;
use App\Models\Settings\KitchenStation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        $menu = Menu::withoutGlobalScopes()->firstOrCreate(
            ['venue_id' => $venue->id],
            ['name' => 'Menu', 'active' => true]
        );

        $categories = Category::where('menu_id', $menu->id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $products = Product::whereHas('category', fn ($q) => $q->where('menu_id', $menu->id))
            ->with('category:id,name', 'kitchenStation:id,name', 'variations', 'modifierGroups:id,name,required,multiple_selection')
            ->when(request('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->orderBy('name')
            ->get();

        $stations = KitchenStation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Menu/Products', [
            'products' => $products,
            'categories' => $categories,
            'stations' => $stations,
            'modifierGroups' => ModifierGroup::orderBy('name')->get(['id', 'name', 'required', 'multiple_selection']),
            'filters' => request()->only('category_id'),
        ]);
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

    public function destroy(Product $product): RedirectResponse
    {
        abort_if($product->category->menu->venue_id !== app('tenant')->id, 403);

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
}
