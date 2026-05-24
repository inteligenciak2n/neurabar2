<?php

namespace App\Actions\Kitchen;

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

        $readyAt = $item->ready_at;

        if ($status->name === 'Ready' || $this->isLastPendingItem($item)) {
            $readyAt = $readyAt ?? now();
        }

        $item->update([
            'preparation_status_id' => $preparationStatusId,
            'ready_at' => $readyAt,
        ]);

        $item->refresh()->load('order.attendance');

        if ($this->allItemsReady($item)) {
            $item->update(['ready_at' => $item->ready_at ?? now()]);
        }

        event(new ItemStatusUpdated($item));

        return $item;
    }

    private function isLastPendingItem(OrderItem $item): bool
    {
        return OrderItem::where('order_id', $item->order_id)
            ->where('id', '!=', $item->id)
            ->whereNull('ready_at')
            ->doesntExist();
    }

    private function allItemsReady(OrderItem $item): bool
    {
        return OrderItem::where('order_id', $item->order_id)
            ->whereNull('ready_at')
            ->doesntExist();
    }
}
