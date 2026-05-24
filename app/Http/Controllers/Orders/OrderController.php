<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\PlaceOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Orders\Attendance;
use App\Models\Settings\KitchenStation;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function create(Attendance $attendance): Response
    {
        $venue = app('tenant');

        $menu = Menu::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->first();

        $categories = $menu
            ? Category::withoutGlobalScopes()
                ->where('menu_id', $menu->id)
                ->with(['products' => fn ($q) => $q->where('active', true)
                    ->with(['variations' => fn ($q) => $q->where('active', true), 'modifierGroups.options' => fn ($q) => $q->where('active', true)])])
                ->orderBy('sort_order')
                ->get()
            : collect();

        $combos = Combo::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->with(['items.product', 'items.variation'])
            ->get();

        $stations = KitchenStation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->get(['id', 'name']);

        return Inertia::render('Orders/Taker', [
            'attendance' => $attendance->load('serviceLocation'),
            'categories' => $categories,
            'combos' => $combos,
            'stations' => $stations,
        ]);
    }

    public function store(Attendance $attendance, StoreOrderRequest $request, PlaceOrderAction $action)
    {
        $order = $action->execute($attendance, $request);

        return redirect()->route('attendances.show', $attendance->id);
    }
}
