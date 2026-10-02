<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateCategoryAction;
use App\Actions\Menu\ReorderCategoriesAction;
use App\Actions\Menu\UpdateCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\ReorderCategoriesRequest;
use App\Http\Requests\Menu\StoreCategoryRequest;
use App\Http\Requests\Menu\UpdateCategoryRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\Product;
use App\Models\Settings\KitchenStation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
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
            ->with(['products' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')->select('id', 'category_id', 'name')])
            ->get();

        return Inertia::render('Menu/Index', [
            'categories' => $categories,
            'menuId' => $menu->id,
            'products' => Inertia::defer(fn () => Product::query()
                ->whereHas('category', fn ($q) => $q->where('menu_id', $menu->id))
                ->with('category:id,name', 'kitchenStation:id,name', 'variations', 'modifierGroups:id,name,required,multiple_selection')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(), 'catalog'),
            'stations' => Inertia::defer(fn () => KitchenStation::withoutGlobalScopes()
                ->where('venue_id', $venue->id)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']), 'catalog'),
            'modifierGroups' => Inertia::defer(fn () => ModifierGroup::query()
                ->with(['options', 'products:id,name'])
                ->orderBy('name')
                ->get(), 'catalog'),
            'combos' => Inertia::defer(fn () => Combo::query()
                ->with(['items.product:id,name,price', 'items.variation:id,product_id,name,price'])
                ->orderBy('name')
                ->get(), 'catalog'),
        ]);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Category created.');
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $venue = app('tenant');
        $menu = $category->menu()->withoutGlobalScopes()->first();
        abort_if(! $menu || $menu->venue_id !== $venue->id, 404);

        $action->execute($category, $request);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $venue = app('tenant');
        $menu = $category->menu()->withoutGlobalScopes()->first();
        abort_if(! $menu || $menu->venue_id !== $venue->id, 404);

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function reorder(ReorderCategoriesRequest $request, ReorderCategoriesAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request->validated('ids'));

        return back()->with('success', 'Order saved.');
    }
}
