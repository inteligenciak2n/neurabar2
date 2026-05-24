<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreModifierOptionRequest;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\ModifierOption;

class CreateModifierOptionAction
{
    public function execute(ModifierGroup $group, StoreModifierOptionRequest $request): ModifierOption
    {
        return $group->options()->create($request->validated());
    }
}
