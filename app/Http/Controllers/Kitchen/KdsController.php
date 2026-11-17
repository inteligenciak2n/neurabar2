<?php

namespace App\Http\Controllers\Kitchen;

use App\Actions\Kitchen\AdvanceDeliveryOrderStatusAction;
use App\Actions\Kitchen\EnsureWaiterCallerKitchenStationAction;
use App\Actions\Kitchen\UpdateItemStatusAction;
use App\Enums\AttendanceStatus;
use App\Enums\KitchenStationCode;
use App\Enums\OrderStatus;
use App\Enums\ServiceRequestType;
use App\Http\Resources\ReadyDeliveryOrderResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Orders\ServiceRequest;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\Tab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KdsController
{
    public function index(EnsureWaiterCallerKitchenStationAction $ensureWaiterCaller): Response
    {
        $venue = app('tenant');
        $venueId = $venue->id;

        $ensureWaiterCaller->execute($venue);

        $stations = KitchenStation::query()
            ->where(function ($query): void {
                $query->where('active', true)
                    ->orWhere('code', KitchenStationCode::WaiterCaller);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $preparationStatuses = PreparationStatus::orderBy('sort_order')->get();

        $openItems = OrderItem::with([
            'product',
            'variation',
            'combo:id,name',
            'modifiers.modifierOption',
            'preparationStatus',
            'order.attendance.serviceLocation',
            'order.attendance.tab:id,code',
        ])
            ->whereNull('ready_at')
            ->whereHas('order.attendance', fn ($q) => $q->where('venue_id', $venueId)->where('status', AttendanceStatus::Open))
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($item) => $item->product?->kitchen_station_id ?? 'unassigned');

        // Itens somem do board acima assim que o pedido fica Ready (ready_at preenchido);
        // pedidos de delivery/retirada precisam de uma lane própria para o staff avançar o status.
        $readyDeliveryOrders = Order::with(['attendance.deliveryOrder'])
            ->whereIn('status', [OrderStatus::Ready, OrderStatus::OutForDelivery])
            ->whereHas('attendance', fn ($q) => $q->where('venue_id', $venueId))
            ->whereHas('attendance.deliveryOrder')
            ->orderBy('created_at')
            ->get();

        $waiterCalls = ServiceRequest::query()
            ->open()
            ->ofType(ServiceRequestType::Message)
            ->with(['serviceLocation:id,name'])
            ->latest()
            ->get();

        $occupiedTabIds = Attendance::query()->open()->whereNotNull('tab_id')->pluck('tab_id');

        $availableTabs = Tab::query()
            ->where('venue_id', $venueId)
            ->where('active', true)
            ->whereNotIn('id', $occupiedTabIds)
            ->orderByRaw('length(code), code')
            ->get(['id', 'code']);

        $openTabbedAttendances = Attendance::query()
            ->open()
            ->whereNotNull('tab_id')
            ->with('tab:id,code')
            ->get(['id', 'tab_id', 'service_location_id']);

        return Inertia::render('Kitchen/Kds', [
            'stations' => $stations,
            'preparationStatuses' => $preparationStatuses,
            'openItems' => $openItems,
            'readyDeliveryOrders' => ReadyDeliveryOrderResource::collection($readyDeliveryOrders),
            'waiterCalls' => ServiceRequestResource::collection($waiterCalls),
            'availableTabs' => $availableTabs,
            'openTabbedAttendances' => $openTabbedAttendances,
        ]);
    }

    public function advanceDeliveryStatus(Order $order, AdvanceDeliveryOrderStatusAction $action): RedirectResponse
    {
        // Order has no TenantScope of its own; without this guard the route model
        // binding would accept an order from any venue sharing the same connection.
        abort_unless($order->loadMissing('attendance')->attendance?->venue_id === app('tenant')->id, 404);

        $action->execute($order);

        return back();
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
