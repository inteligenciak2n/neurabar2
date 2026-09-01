<?php

namespace App\Actions\Guest;

use App\Enums\AttendanceStatus;
use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Events\Orders\OrderPlaced;
use App\Models\Menu\ModifierOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariation;
use App\Models\Orders\Attendance;
use App\Models\Orders\DeliveryOrder;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Payment\Payment;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use App\Models\Tenant\Venue;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceDeliveryOrderAction
{
    public function __construct(private readonly PaymentService $paymentService) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Venue $venue, array $validated): Order
    {
        $fulfillmentType = FulfillmentType::from($validated['fulfillment_type']);

        $deliveryFeeZone = null;
        $deliveryFee = 0.0;

        if ($fulfillmentType === FulfillmentType::Delivery) {
            $zipCode = (int) preg_replace('/\D/', '', (string) $validated['address']['zip_code']);

            $deliveryFeeZone = DeliveryFeeZone::withoutGlobalScopes()
                ->where('venue_id', $venue->id)
                ->where('active', true)
                ->where('zip_code_start', '<=', $zipCode)
                ->where('zip_code_end', '>=', $zipCode)
                ->first();

            if ($deliveryFeeZone === null) {
                throw ValidationException::withMessages([
                    'address.zip_code' => 'This address is outside the delivery area.',
                ]);
            }

            $deliveryFee = (float) $deliveryFeeZone->fee;
        }

        $settings = VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first();
        $acceptedMethods = $settings?->acceptedDeliveryPaymentMethods() ?? [];

        foreach ($validated['methods'] as $methodData) {
            if (! in_array($methodData['type'], $acceptedMethods, true)) {
                throw ValidationException::withMessages([
                    'methods' => 'One or more payment methods are not accepted by this venue.',
                ]);
            }
        }

        $order = DB::transaction(function () use ($venue, $validated, $fulfillmentType, $deliveryFeeZone, $deliveryFee): Order {
            $customer = Customer::withoutGlobalScopes()->updateOrCreate(
                ['corporation_id' => $venue->corporation_id, 'phone' => $validated['customer']['phone']],
                ['name' => $validated['customer']['name']]
            );

            $customerAddress = null;

            if ($fulfillmentType === FulfillmentType::Delivery) {
                $addressData = $validated['address'];

                $customerAddress = CustomerAddress::withoutGlobalScopes()->create([
                    'customer_id' => $customer->id,
                    'street' => $addressData['street'],
                    'number' => $addressData['number'],
                    'complement' => $addressData['complement'] ?? null,
                    'neighborhood' => $addressData['neighborhood'],
                    'city' => $addressData['city'],
                    'state' => $addressData['state'],
                    'zip_code' => $addressData['zip_code'],
                    'reference_point' => $addressData['reference_point'] ?? null,
                ]);
            }

            $channelName = $fulfillmentType === FulfillmentType::Delivery ? 'Delivery' : 'Retirada';

            $attendanceChannel = AttendanceChannel::withoutGlobalScopes()->firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $channelName],
                ['is_trackable' => true, 'requires_customer_identifier' => true, 'active' => true, 'sort_order' => 99]
            );

            $attendance = Attendance::withoutGlobalScopes()->create([
                'venue_id' => $venue->id,
                'attendance_channel_id' => $attendanceChannel->id,
                'customer_identifier' => $validated['customer']['name'],
                'status' => AttendanceStatus::Open,
            ]);

            $order = Order::create([
                'attendance_id' => $attendance->id,
                'order_number' => 1,
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

            DeliveryOrder::withoutGlobalScopes()->create([
                'venue_id' => $venue->id,
                'attendance_id' => $attendance->id,
                'fulfillment_type' => $fulfillmentType,
                'customer_id' => $customer->id,
                'customer_address_id' => $customerAddress?->id,
                'delivery_fee_zone_id' => $deliveryFeeZone?->id,
                'delivery_fee' => $deliveryFee,
                'customer_name' => $validated['customer']['name'],
                'customer_phone' => $validated['customer']['phone'],
                'address_street' => $validated['address']['street'] ?? null,
                'address_number' => $validated['address']['number'] ?? null,
                'address_complement' => $validated['address']['complement'] ?? null,
                'address_neighborhood' => $validated['address']['neighborhood'] ?? null,
                'address_city' => $validated['address']['city'] ?? null,
                'address_state' => $validated['address']['state'] ?? null,
                'address_zip_code' => $validated['address']['zip_code'] ?? null,
                'address_reference_point' => $validated['address']['reference_point'] ?? null,
            ]);

            $attendance->refresh()->load('orders.items.modifiers');
            $totals = $this->paymentService->calculateTotal($attendance, 0, $deliveryFee);

            $methodsTotal = collect($validated['methods'])->sum(fn ($m) => (float) $m['amount']);

            if (abs($methodsTotal - $totals['grand_total']) > 0.01) {
                throw ValidationException::withMessages([
                    'methods' => 'The sum of payment methods does not match the grand total.',
                ]);
            }

            $payment = Payment::withoutGlobalScopes()->create([
                'attendance_id' => $attendance->id,
                'items_total' => $totals['items_total'],
                'cover_charge_total' => $totals['cover_charge_total'],
                'service_fee_total' => $totals['service_fee_total'],
                'delivery_fee_total' => $totals['delivery_fee_total'],
                'grand_total' => $totals['grand_total'],
                'party_size' => 0,
            ]);

            foreach ($validated['methods'] as $methodData) {
                $payment->paymentItems()->create([
                    'method' => $methodData['type'],
                    'amount' => $methodData['amount'],
                    'notes' => $methodData['notes'] ?? null,
                ]);
            }

            return $order;
        });

        event(new OrderPlaced($order->load('attendance')));

        return $order;
    }
}
