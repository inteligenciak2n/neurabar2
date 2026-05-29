<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Guest\PlaceGuestOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\StoreGuestOrderRequest;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuestOrderController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function store(string $token, StoreGuestOrderRequest $request, PlaceGuestOrderAction $action): JsonResponse
    {
        ['venue' => $venue] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null || ! $session->hasPin(), 403, 'No active session.');

        $this->tokenService->createAttendanceIfNeeded($session);
        $session->refresh();

        $order = $action->execute($session, $request->validated());

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ], 201);
    }

    public function index(string $token, Request $request): JsonResponse
    {
        $request->validate(['pin' => ['required', 'string', 'digits:4']]);

        ['venue' => $venue] = $this->tokenService->decode($token);

        $session = $this->tokenService->resolveSession($request, $venue);

        abort_if($session === null, 403, 'No active session.');
        abort_if(! Hash::check($request->input('pin'), $session->pin), 403, 'Invalid PIN.');

        $attendance = $session->attendance()->withoutGlobalScopes()
            ->with(['orders.items.product:id,name', 'orders.items.variation:id,name', 'orders.items.preparationStatus:id,name,color,show_to_customer'])
            ->first();

        if (! $attendance) {
            return response()->json(['orders' => []]);
        }

        $orders = $attendance->orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'items' => $order->items
                    ->filter(fn ($item) => $item->preparationStatus?->show_to_customer)
                    ->values()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'product_name' => $item->product?->name,
                        'variation_name' => $item->variation?->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'notes' => $item->notes,
                        'status' => [
                            'name' => $item->preparationStatus?->name,
                            'color' => $item->preparationStatus?->color,
                        ],
                        'ready_at' => $item->ready_at?->toISOString(),
                    ]),
            ];
        });

        return response()->json(['orders' => $orders]);
    }
}
