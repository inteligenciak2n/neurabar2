<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\PlaceOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Menu\MenuPreviewController;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Models\Orders\Attendance;
use Inertia\Response;

class OrderController extends Controller
{
    public function create(Attendance $attendance): Response
    {
        return app(MenuPreviewController::class)->live('order', $attendance);
    }

    public function store(Attendance $attendance, StoreOrderRequest $request, PlaceOrderAction $action)
    {
        $order = $action->execute($attendance, $request);

        return redirect()->route('attendances.show', $attendance->id);
    }
}
