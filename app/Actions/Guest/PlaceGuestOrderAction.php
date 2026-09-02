<?php

namespace App\Actions\Guest;

use App\Actions\Orders\ResolveOrderItemsAction;
use App\Enums\AttendanceStatus;
use App\Enums\OrderStatus;
use App\Events\Orders\OrderPlaced;
use App\Models\GuestSession;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceGuestOrderAction
{
    public function __construct(private readonly ResolveOrderItemsAction $resolveOrderItemsAction) {}

    /**
     * @param  array{items: array<int, array{product_id: string, variation_id: ?string, quantity: int, notes: ?string, modifiers: ?array<int, string>}>}  $validated
     */
    public function execute(GuestSession $session, array $validated): Order
    {
        $attendance = $session->attendance()->withoutGlobalScopes()->first();

        if (! $attendance) {
            abort(422, 'No active attendance for this session.');
        }

        if ($attendance->status !== AttendanceStatus::Open) {
            throw ValidationException::withMessages([
                'attendance' => 'Cannot place order on a closed attendance.',
            ]);
        }

        $venue = $attendance->venue()->withoutGlobalScopes()->with('initialStatus')->first();

        $resolvedItems = $this->resolveOrderItemsAction->execute($venue, $validated['items']);

        $order = DB::transaction(function () use ($attendance, $venue, $resolvedItems): Order {
            $orderNumber = Order::where('attendance_id', $attendance->id)->max('order_number') + 1;

            $order = Order::create([
                'attendance_id' => $attendance->id,
                'order_number' => $orderNumber,
                'status' => OrderStatus::Open,
                'created_by' => null,
            ]);

            foreach ($resolvedItems as $itemData) {
                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'notes' => $itemData['notes'],
                    'preparation_status_id' => $venue->initialStatus?->id,
                ]);

                foreach ($itemData['modifiers'] as $modifier) {
                    $item->modifiers()->create($modifier);
                }
            }

            return $order;
        });

        event(new OrderPlaced($order->load('attendance')));

        return $order;
    }
}
