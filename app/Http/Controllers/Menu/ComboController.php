<?php

namespace App\Http\Controllers\Menu;

use App\Actions\Menu\CreateComboAction;
use App\Actions\Menu\UpdateComboAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreComboRequest;
use App\Http\Requests\Menu\UpdateComboRequest;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ComboController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        $combos = Combo::with(['items.product:id,name,price', 'items.variation:id,product_id,name,price'])
            ->orderBy('name')
            ->get();

        $menuIds = Menu::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->pluck('id');

        $products = Product::withoutGlobalScopes()
            ->whereHas('category', fn ($q) => $q->whereIn('menu_id', $menuIds))
            ->where('active', true)
            ->with('variations:id,product_id,name,price')
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        return Inertia::render('Menu/Combos', [
            'combos' => $combos,
            'products' => $products,
        ]);
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

    public function destroy(Combo $combo): RedirectResponse
    {
        $combo->delete();

        return back()->with('success', 'Combo deleted.');
    }
}
