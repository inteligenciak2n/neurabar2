<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\UpdateCategoryRequest;
use App\Models\Menu\Category;

class UpdateCategoryAction
{
    public function execute(Category $category, UpdateCategoryRequest $request): Category
    {
        $category->update($request->validated());

        return $category->fresh();
    }
}
