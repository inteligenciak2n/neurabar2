<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateComboAction;
use App\Actions\Menu\ToggleComboActiveAction;
use App\Actions\Menu\UpdateComboAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreComboRequest;
use App\Http\Requests\Menu\UpdateComboRequest;
use App\Models\Menu\Combo;
use Illuminate\Http\RedirectResponse;

class ComboController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(route('menu.index').'#menu-combos');
    }

    public function store(StoreComboRequest $request, CreateComboAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Combo created.');
    }

    public function update(UpdateComboRequest $request, Combo $combo, UpdateComboAction $action): RedirectResponse
    {
        $action->execute($combo, $request);

        return back()->with('success', 'Combo updated.');
    }

    public function toggleActive(Combo $combo, ToggleComboActiveAction $action): RedirectResponse
    {
        abort_if($combo->venue_id !== app('tenant')->id, 404);

        $action->execute($combo);

        return back()->with('success', 'Combo status updated.');
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        $combo->delete();

        return back()->with('success', 'Combo deleted.');
    }
}
