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
use App\Models\Menu\Menu;
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
            ->with(['products' => fn ($q) => $q->orderBy('name')])
            ->get();

        return Inertia::render('Menu/Index', [
            'categories' => $categories,
            'menuId' => $menu->id,
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
