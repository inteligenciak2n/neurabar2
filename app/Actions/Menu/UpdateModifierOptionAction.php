<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\UpdateModifierOptionRequest;
use App\Models\Menu\ModifierOption;

class UpdateModifierOptionAction
{
    public function execute(ModifierOption $option, UpdateModifierOptionRequest $request): ModifierOption
    {
        $option->update($request->validated());

        return $option;
    }
}
