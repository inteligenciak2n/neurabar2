<?php

namespace App\Http\Controllers\Kitchen;

use App\Actions\Kitchen\UpdateItemStatusAction;
use App\Models\Orders\OrderItem;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Tenant\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KdsController
{
    public function index(): Response
    {
        $venueId = app('tenant')->id;

        $stations = KitchenStation::where('active', true)
            ->orderBy('sort_order')
            ->get();

        $preparationStatuses = PreparationStatus::orderBy('sort_order')->get();

        $openItems = OrderItem::with([
            'product',
            'variation',
            'modifiers.modifierOption',
            'preparationStatus',
            'order.attendance.serviceLocation',
        ])
            ->whereNull('ready_at')
            ->whereHas('order.attendance', fn ($q) => $q->where('venue_id', $venueId)->where('status', 'open'))
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($item) => $item->product?->kitchen_station_id ?? 'unassigned');

        return Inertia::render('Kitchen/Kds', [
            'stations' => $stations,
            'preparationStatuses' => $preparationStatuses,
            'openItems' => $openItems,
        ]);
    }

    public function updateItemStatus(Request $request, OrderItem $item, UpdateItemStatusAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'preparation_status_id' => ['required', 'string', 'exists:preparation_statuses,id'],
        ]);

        $item->load('order.attendance');

        $action->execute($item, $validated['preparation_status_id']);

        return back();
    }

    public function monitor(): Response
    {
        $venues = Venue::all();

        // Monitor is public — load items with show_to_customer statuses across all venues
        // In MLP, we show items for a venue resolved from route or query param
        $venueId = request()->query('venue');

        $openItems = [];

        if ($venueId) {
            $openItems = OrderItem::with([
                'product',
                'preparationStatus',
                'order.attendance.serviceLocation',
            ])
                ->whereNull('ready_at')
                ->whereHas('preparationStatus', fn ($q) => $q->where('show_to_customer', true))
                ->whereHas('order.attendance', fn ($q) => $q->where('venue_id', $venueId)->where('status', 'open'))
                ->orderBy('created_at')
                ->get();
        }

        return Inertia::render('Kitchen/Monitor', [
            'openItems' => $openItems,
        ]);
    }
}
