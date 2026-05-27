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

        $readyAt = ($status->is_final && $this->isLastPendingItem($item)) ? now() : null;

        $item->update([
            'preparation_status_id' => $preparationStatusId,
            'ready_at' => $readyAt,
        ]);

        $item->refresh()->load('order.attendance', 'preparationStatus');

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
}
