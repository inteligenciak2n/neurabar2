<?php

namespace App\Actions\Guest;

use App\Enums\AttendanceStatus;
use App\Enums\OrderStatus;
use App\Events\Orders\OrderPlaced;
use App\Models\GuestSession;
use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceGuestOrderAction
{
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

        $order = DB::transaction(function () use ($attendance, $venue, $validated): Order {
            $orderNumber = Order::where('attendance_id', $attendance->id)->max('order_number') + 1;

            $order = Order::create([
                'attendance_id' => $attendance->id,
                'order_number' => $orderNumber,
                'status' => OrderStatus::Open,
                'created_by' => null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $product = Product::withoutGlobalScopes()->findOrFail($itemData['product_id']);

                $unitPrice = $product->price;

                if (! empty($itemData['variation_id'])) {
                    $variation = ProductVariation::withoutGlobalScopes()->findOrFail($itemData['variation_id']);
                    $unitPrice = $variation->price;
                }

                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'notes' => $itemData['notes'] ?? null,
                    'preparation_status_id' => $venue->initialStatus?->id,
                ]);

                foreach ($itemData['modifiers'] ?? [] as $modifierOptionId) {
                    $modifierOption = ModifierOption::withoutGlobalScopes()->findOrFail($modifierOptionId);

                    $item->modifiers()->create([
                        'modifier_option_id' => $modifierOptionId,
                        'extra_price_snapshot' => $modifierOption->extra_price,
                    ]);
                }
            }

            return $order;
        });

        event(new OrderPlaced($order->load('attendance')));

        return $order;
    }
}
