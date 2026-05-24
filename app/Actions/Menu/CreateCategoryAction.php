<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreCategoryRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Tenant\Venue;

class CreateCategoryAction
{
    public function execute(Venue $venue, StoreCategoryRequest $request): Category
    {
        $menu = Menu::withoutGlobalScopes()->firstOrCreate(
            ['venue_id' => $venue->id],
            ['name' => 'Menu', 'active' => true]
        );

        $maxSortOrder = Category::where('menu_id', $menu->id)->max('sort_order') ?? 0;

        return Category::create([
            'menu_id' => $menu->id,
            'name' => $request->validated('name'),
            'sort_order' => $maxSortOrder + 1,
        ]);
    }
}
