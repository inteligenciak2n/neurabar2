<?php

namespace App\Http\Controllers\Kitchen;

use App\Actions\Kitchen\UpdateItemStatusAction;
use App\Enums\AttendanceStatus;
use App\Models\Orders\OrderItem;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'combo:id,name',
            'modifiers.modifierOption',
            'preparationStatus',
            'order.attendance.serviceLocation',
        ])
            ->whereNull('ready_at')
            ->whereHas('order.attendance', fn ($q) => $q->where('venue_id', $venueId)->where('status', AttendanceStatus::Open))
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
            'preparation_status_id' => [
                'required',
                'string',
                Rule::exists(PreparationStatus::class, 'id'),
            ],
        ]);

        $item->load('order.attendance');

        $action->execute($item, $validated['preparation_status_id']);

        return back();
    }

    public function monitor(Request $request): Response
    {
        $venueId = $request->query('venue');

        $openItems = [];

        if ($venueId) {
            $openItems = OrderItem::with([
                'product',
                'preparationStatus',
                'order.attendance.serviceLocation',
            ])
                ->whereNull('ready_at')
                ->whereHas('preparationStatus', fn ($q) => $q->where('show_to_customer', true))
                ->whereHas('order.attendance', fn ($q) => $q->where('venue_id', $venueId)->where('status', AttendanceStatus::Open))
                ->orderBy('created_at')
                ->get();
        }

        return Inertia::render('Kitchen/Monitor', [
            'openItems' => $openItems,
            'venueId' => $venueId,
        ]);
    }
}
