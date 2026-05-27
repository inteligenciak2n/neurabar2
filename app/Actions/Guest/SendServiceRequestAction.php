<?php

namespace App\Actions\Guest;

use App\Enums\AttendanceStatus;
use App\Enums\OrderStatus;
use App\Events\Orders\OrderPlaced;
use App\Http\Requests\Guest\StoreServiceRequestRequest;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SendServiceRequestAction
{
    public function execute(string $slug, StoreServiceRequestRequest $request): Order
    {
        $venue = Venue::where('call_waiter_slug', $slug)->first();

        if ($venue === null) {
            abort(404);
        }

        $validated = $request->validated();

        if ($venue->call_waiter_passphrase !== null && $venue->call_waiter_passphrase !== '') {
            if (! hash_equals($venue->call_waiter_passphrase, $validated['passphrase'] ?? '')) {
                throw ValidationException::withMessages([
                    'passphrase' => 'Invalid passphrase.',
                ]);
            }
        }

        $order = DB::transaction(function () use ($venue, $validated): Order {
            $serviceLocation = ServiceLocation::withoutGlobalScopes()->firstOrCreate(
                [
                    'venue_id' => $venue->id,
                    'name' => $validated['customer_identifier'] ?? 'Guest',
                    'type' => 'other',
                ],
                ['active' => true]
            );

            $attendance = Attendance::withoutGlobalScopes()->create([
                'venue_id' => $venue->id,
                'service_location_id' => $serviceLocation->id,
                'customer_identifier' => $validated['customer_identifier'] ?? null,
                'channel' => 'service_request',
                'status' => AttendanceStatus::Open,
            ]);

            $order = Order::create([
                'attendance_id' => $attendance->id,
                'order_number' => 1,
                'status' => OrderStatus::Open,
                'created_by' => null,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => null,
                'quantity' => 1,
                'unit_price' => 0,
                'notes' => $validated['message'],
                'preparation_status_id' => $venue->initialStatus?->id,
            ]);

            return $order;
        });

        event(new OrderPlaced($order->load('attendance')));

        return $order;
    }
}
