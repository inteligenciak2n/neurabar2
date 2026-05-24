<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\UpdateModifierGroupRequest;
use App\Models\Menu\ModifierGroup;

class UpdateModifierGroupAction
{
    public function execute(ModifierGroup $group, UpdateModifierGroupRequest $request): ModifierGroup
    {
        $group->update($request->validated());

        return $group;
    }
}
