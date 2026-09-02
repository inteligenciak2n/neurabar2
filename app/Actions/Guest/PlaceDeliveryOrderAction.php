<?php

namespace App\Actions\Guest;

use App\Actions\Orders\ResolveOrderItemsAction;
use App\Enums\AttendanceStatus;
use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Events\Orders\OrderPlaced;
use App\Models\Orders\Attendance;
use App\Models\Orders\DeliveryOrder;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerAddress;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceDeliveryOrderAction
{
    public function __construct(private readonly ResolveOrderItemsAction $resolveOrderItemsAction) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(Venue $venue, array $validated): Order
    {
        $fulfillmentType = FulfillmentType::from($validated['fulfillment_type']);
        $settings = VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $deliveryFeeZone = null;
        $deliveryFee = 0.0;

        if ($fulfillmentType === FulfillmentType::Delivery) {
            [$deliveryFeeZone, $deliveryFee] = $this->resolveDeliveryFeeZone($venue, $validated['address']['zip_code']);
        }

        $this->assertAcceptedPaymentMethods($settings, $validated['methods']);

        $resolvedItems = $this->resolveOrderItemsAction->execute(
            $venue,
            $validated['items'],
            fn ($query) => $query->where('available_for_delivery', true)
        );

        $this->assertMethodsMatchTotal($resolvedItems, $settings, $deliveryFee, $validated['methods']);

        $connection = OperationalConnection::current();

        $order = DB::connection($connection)->transaction(function () use (
            $venue, $validated, $fulfillmentType, $deliveryFeeZone, $deliveryFee, $resolvedItems
        ): Order {
            $customer = $this->createCustomer($venue, $validated['customer']);

            $customerAddress = $fulfillmentType === FulfillmentType::Delivery
                ? $this->persistAddressIfRequested($customer, $validated['address'])
                : null;

            $attendanceChannel = $this->createOrRetrieveAttendanceChannel($venue, $fulfillmentType);
            $attendance = $this->createAttendance($venue, $attendanceChannel, $validated['customer']['name']);
            $order = $this->createOrderWithItems($venue, $attendance, $resolvedItems);

            $this->createDeliveryOrderWithPaymentMethods(
                $venue, $attendance, $fulfillmentType, $customer, $customerAddress,
                $deliveryFeeZone, $deliveryFee, $validated
            );

            return $order;
        });

        event(new OrderPlaced($order->load('attendance')));

        return $order;
    }

    /**
     * @param  array{name: string, phone: string}  $customerData
     */
    private function createCustomer(Venue $venue, array $customerData): Customer
    {
        return Customer::withoutGlobalScopes()->updateOrCreate(
            ['corporation_id' => $venue->corporation_id, 'phone' => $customerData['phone']],
            ['name' => $customerData['name']]
        );
    }

    private function createOrRetrieveAttendanceChannel(Venue $venue, FulfillmentType $fulfillmentType): AttendanceChannel
    {
        $channelName = $fulfillmentType === FulfillmentType::Delivery ? 'Delivery' : 'Retirada';

        return AttendanceChannel::withoutGlobalScopes()->firstOrCreate(
            ['venue_id' => $venue->id, 'name' => $channelName],
            ['is_trackable' => true, 'requires_customer_identifier' => true, 'active' => true, 'sort_order' => 99]
        );
    }

    private function createAttendance(Venue $venue, AttendanceChannel $attendanceChannel, string $customerName): Attendance
    {
        return Attendance::withoutGlobalScopes()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $attendanceChannel->id,
            'customer_identifier' => $customerName,
            'status' => AttendanceStatus::Open,
        ]);
    }

    /**
     * @param  array<int, array{product_id: ?string, variation_id: ?string, quantity: int, unit_price: float, notes: ?string, modifiers: array<int, array<string, mixed>>}>  $resolvedItems
     */
    private function createOrderWithItems(Venue $venue, Attendance $attendance, array $resolvedItems): Order
    {
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
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createDeliveryOrderWithPaymentMethods(
        Venue $venue,
        Attendance $attendance,
        FulfillmentType $fulfillmentType,
        Customer $customer,
        ?CustomerAddress $customerAddress,
        ?DeliveryFeeZone $deliveryFeeZone,
        float $deliveryFee,
        array $validated
    ): DeliveryOrder {
        $deliveryOrder = DeliveryOrder::withoutGlobalScopes()->create([
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

        // The charge itself is only recognized once the order is Delivered
        // (see AdvanceDeliveryOrderStatusAction) — here we only record what the
        // guest picked, so it can't be counted as revenue before it's fulfilled.
        foreach ($validated['methods'] as $methodData) {
            $deliveryOrder->paymentMethods()->create([
                'method' => $methodData['type'],
                'amount' => $methodData['amount'],
                'notes' => $methodData['notes'] ?? null,
            ]);
        }

        return $deliveryOrder;
    }

    /**
     * @return array{0: DeliveryFeeZone, 1: float}
     */
    private function resolveDeliveryFeeZone(Venue $venue, string $rawZipCode): array
    {
        $zipCode = (int) preg_replace('/\D/', '', $rawZipCode);

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

        return [$deliveryFeeZone, (float) $deliveryFeeZone->fee];
    }

    /**
     * @param  array<int, array{type: string, amount: float}>  $methods
     */
    private function assertAcceptedPaymentMethods(?VenueSettings $settings, array $methods): void
    {
        $acceptedMethods = $settings?->acceptedDeliveryPaymentMethods() ?? [];

        foreach ($methods as $methodData) {
            if (! in_array($methodData['type'], $acceptedMethods, true)) {
                throw ValidationException::withMessages([
                    'methods' => 'One or more payment methods are not accepted by this venue.',
                ]);
            }
        }
    }

    /**
     * The grand total must be computable from data that isn't written yet (resolved
     * items + delivery fee), so this mirrors PaymentService::calculateTotal()'s
     * formula for a party of 0 (no cover charge) without persisting anything.
     *
     * @param  array<int, array{unit_price: float, quantity: int, modifiers: array<int, array{extra_price_snapshot: float}>}>  $resolvedItems
     * @param  array<int, array{type: string, amount: float}>  $methods
     */
    private function assertMethodsMatchTotal(array $resolvedItems, ?VenueSettings $settings, float $deliveryFee, array $methods): void
    {
        $itemsTotal = collect($resolvedItems)->sum(
            fn (array $item) => ($item['unit_price'] + collect($item['modifiers'])->sum('extra_price_snapshot')) * $item['quantity']
        );

        $serviceFeeTotal = round($itemsTotal * ((float) ($settings?->service_fee_percent ?? 0) / 100), 2);
        $grandTotal = round($itemsTotal + $serviceFeeTotal + round($deliveryFee, 2), 2);

        $methodsTotal = collect($methods)->sum(fn ($m) => (float) $m['amount']);

        if (abs($methodsTotal - $grandTotal) > 0.01) {
            throw ValidationException::withMessages([
                'methods' => 'The sum of payment methods does not match the grand total.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    private function persistAddressIfRequested(Customer $customer, array $addressData): ?CustomerAddress
    {
        if (empty($addressData['save_address'])) {
            return null;
        }

        return CustomerAddress::withoutGlobalScopes()->updateOrCreate(
            [
                'customer_id' => $customer->id,
                'zip_code' => $addressData['zip_code'],
                'number' => $addressData['number'],
            ],
            [
                'street' => $addressData['street'],
                'complement' => $addressData['complement'] ?? null,
                'neighborhood' => $addressData['neighborhood'],
                'city' => $addressData['city'],
                'state' => $addressData['state'],
                'reference_point' => $addressData['reference_point'] ?? null,
            ]
        );
    }
}
