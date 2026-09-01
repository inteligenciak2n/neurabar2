<?php

namespace App\Http\Controllers\Guest\Delivery;

use App\Actions\Guest\PlaceDeliveryOrderAction;
use App\Enums\ModuleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\StoreDeliveryOrderRequest;
use App\Services\GuestTokenService;
use Illuminate\Http\JsonResponse;

class DeliveryOrderController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function store(string $token, StoreDeliveryOrderRequest $request, PlaceDeliveryOrderAction $action): JsonResponse
    {
        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_unless(in_array(ModuleCode::Delivery->value, $venue->activeModules(), true), 404);

        $order = $action->execute($venue, $request->validated());

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ], 201);
    }
}
