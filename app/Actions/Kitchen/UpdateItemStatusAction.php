<?php

namespace App\Actions\Kitchen;

use App\Enums\OrderStatus;
use App\Events\Kitchen\ItemStatusUpdated;
use App\Models\Orders\OrderItem;
use App\Models\Settings\PreparationStatus;
use Illuminate\Validation\ValidationException;

class UpdateItemStatusAction
{
    public function execute(OrderItem $item, string $preparationStatusId): OrderItem
    {
        $venueId = app('tenant')->id;

        $attendance = $item->order->attendance()->withoutGlobalScopes()->first();

        if ($attendance === null || $attendance->venue_id !== $venueId) {
            throw ValidationException::withMessages([
                'item' => 'Order item does not belong to the current venue.',
            ]);
        }

        $status = PreparationStatus::findOrFail($preparationStatusId);

        $readyAt = ($status->is_final) ? now() : null;

        $item->update([
            'preparation_status_id' => $preparationStatusId,
            'ready_at' => $readyAt,
        ]);

        $order = $item->order;

        if ($this->isLastPendingItem($item) && $order->status === OrderStatus::InPreparation) {
            $order->update(['status' => OrderStatus::Ready]);
        } else if ($order->status === OrderStatus::Open) {
            $order->update(['status' => OrderStatus::InPreparation]);
        }

        $item->refresh()->load('order.attendance', 'preparationStatus');

        event(new ItemStatusUpdated($item));

        return $item;
    }

    private function isLastPendingItem(OrderItem $item): bool
    {
        return OrderItem::where('order_id', $item->order_id)
            ->whereNull('ready_at')
            ->doesntExist();
    }
}
