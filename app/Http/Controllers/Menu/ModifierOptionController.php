<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateModifierOptionAction;
use App\Actions\Menu\UpdateModifierOptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreModifierOptionRequest;
use App\Http\Requests\Menu\UpdateModifierOptionRequest;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\ModifierOption;
use Illuminate\Http\RedirectResponse;

class ModifierOptionController extends Controller
{
    public function store(StoreModifierOptionRequest $request, ModifierGroup $modifierGroup, CreateModifierOptionAction $action): RedirectResponse
    {
        $action->execute($modifierGroup, $request);

        return back()->with('success', 'Option created.');
    }

    public function update(UpdateModifierOptionRequest $request, ModifierGroup $modifierGroup, ModifierOption $option, UpdateModifierOptionAction $action): RedirectResponse
    {
        abort_if($option->modifier_group_id !== $modifierGroup->id, 404);

        $action->execute($option, $request);

        return back()->with('success', 'Option updated.');
    }

    public function destroy(ModifierGroup $modifierGroup, ModifierOption $option): RedirectResponse
    {
        abort_if($option->modifier_group_id !== $modifierGroup->id, 404);

        $option->delete();

        return back()->with('success', 'Option deleted.');
    }
}
