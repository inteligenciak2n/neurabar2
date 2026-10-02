<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateModifierGroupAction;
use App\Actions\Menu\UpdateModifierGroupAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreModifierGroupRequest;
use App\Http\Requests\Menu\UpdateModifierGroupRequest;
use App\Models\Menu\ModifierGroup;
use Illuminate\Http\RedirectResponse;

class ModifierGroupController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(route('menu.index').'#menu-modifiers');
    }

    public function store(StoreModifierGroupRequest $request, CreateModifierGroupAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Modifier group created.');
    }

    public function update(UpdateModifierGroupRequest $request, ModifierGroup $modifierGroup, UpdateModifierGroupAction $action): RedirectResponse
    {
        $action->execute($modifierGroup, $request);

        return back()->with('success', 'Modifier group updated.');
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        $modifierGroup->delete();

        return back()->with('success', 'Modifier group deleted.');
    }
}
