<?php

namespace App\Actions\Orders;

use App\Enums\AttendanceStatus;
use App\Http\Requests\Orders\StoreAttendantOrderRequest;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\VenueCustomer;
use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use App\Support\PhoneDigits;
use Illuminate\Validation\ValidationException;

class TakeAttendantOrderAction
{
    public function __construct(
        private PlaceOrderAction $placeOrder,
        private ResolveVenueCustomerAction $resolveCustomer,
        private PersistVenueCustomerAddressAction $persistAddress,
    ) {}

    public function execute(Venue $venue, StoreAttendantOrderRequest $request): Order
    {
        $attendance = $this->resolveAttendance($venue, $request);
        $this->syncDeliveryAddress($venue, $attendance, $request);

        return $this->placeOrder->execute($attendance, $request);
    }

    private function resolveAttendance(Venue $venue, StoreAttendantOrderRequest $request): Attendance
    {
        $attendanceId = $request->validated('attendance_id');

        if ($attendanceId) {
            $existing = Attendance::query()->open()->whereKey($attendanceId)->first();

            if ($existing) {
                return $existing;
            }
        }

        $channel = AttendanceChannel::query()->findOrFail($request->validated('attendance_channel_id'));
        $tabId = $request->validated('tab_id');

        if ($channel->requires_tab) {
            if (! $tabId) {
                throw ValidationException::withMessages([
                    'tab_id' => 'A tab is required for this channel.',
                ]);
            }

            $openOnTab = Attendance::query()->open()->where('tab_id', $tabId)->first();

            if ($openOnTab) {
                return $openOnTab;
            }
        }

        [$customer, $identifier] = $this->resolveCustomerPayload($venue, $channel, $request);

        return Attendance::query()->create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $channel->id,
            'tab_id' => $channel->requires_tab ? $tabId : null,
            'customer_identifier' => $identifier,
            'venue_customer_id' => $customer?->id,
            'service_location_id' => $request->validated('service_location_id'),
            'party_size' => $request->validated('party_size') ?? 1,
            'status' => AttendanceStatus::Open,
            'created_by' => $request->user()->id,
        ]);
    }

    /**
     * @return array{0: VenueCustomer|null, 1: string|null}
     */
    private function resolveCustomerPayload(
        Venue $venue,
        AttendanceChannel $channel,
        StoreAttendantOrderRequest $request,
    ): array {
        $name = trim((string) ($request->validated('customer_name') ?? ''));
        $phone = PhoneDigits::normalize($request->validated('customer_phone'));

        if ($channel->requires_customer_identifier) {
            if ($name === '') {
                throw ValidationException::withMessages([
                    'customer_name' => 'Name is required for this channel.',
                ]);
            }

            if (! PhoneDigits::isValid($phone)) {
                throw ValidationException::withMessages([
                    'customer_phone' => 'Phone with area code is required for this channel.',
                ]);
            }
        }

        if ($name === '' || ! PhoneDigits::isValid($phone)) {
            $fallback = trim((string) ($request->validated('customer_identifier') ?? ''));

            return [null, $fallback !== '' ? $fallback : null];
        }

        $customer = $this->resolveCustomer->execute($venue, $name, $phone);

        return [$customer, $name];
    }

    private function syncDeliveryAddress(Venue $venue, Attendance $attendance, StoreAttendantOrderRequest $request): void
    {
        $channel = $attendance->attendanceChannel
            ?? AttendanceChannel::query()->find($attendance->attendance_channel_id);

        if (! $channel) {
            return;
        }

        $payload = $request->validated('delivery_address') ?? [];
        $this->assertDeliveryAddress($channel, is_array($payload) ? $payload : []);

        $customer = $attendance->venueCustomer;
        $name = trim((string) ($request->validated('customer_name') ?? ''));
        $phone = PhoneDigits::normalize($request->validated('customer_phone'));

        if (! $customer && $name !== '' && PhoneDigits::isValid($phone)) {
            $customer = $this->resolveCustomer->execute($venue, $name, $phone);
            $attendance->forceFill([
                'venue_customer_id' => $customer->id,
                'customer_identifier' => $name,
            ])->save();
        }

        if (! $customer) {
            return;
        }

        $zip = preg_replace('/\D+/', '', (string) ($payload['zip_code'] ?? '')) ?? '';
        $street = trim((string) ($payload['street'] ?? ''));
        $number = trim((string) ($payload['number'] ?? ''));

        if (strlen($zip) !== 8 || $street === '' || $number === '') {
            return;
        }

        $this->persistAddress->execute($customer, $attendance, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertDeliveryAddress(AttendanceChannel $channel, array $payload): void
    {
        if ($channel->uses_service_location || ! $channel->requires_customer_identifier) {
            return;
        }

        $zip = preg_replace('/\D+/', '', (string) ($payload['zip_code'] ?? '')) ?? '';
        $street = trim((string) ($payload['street'] ?? ''));
        $number = trim((string) ($payload['number'] ?? ''));

        if (strlen($zip) !== 8) {
            throw ValidationException::withMessages([
                'delivery_address.zip_code' => 'ZIP code is required for this channel.',
            ]);
        }

        if ($street === '') {
            throw ValidationException::withMessages([
                'delivery_address.street' => 'Street is required for this channel.',
            ]);
        }

        if ($number === '') {
            throw ValidationException::withMessages([
                'delivery_address.number' => 'Number is required for this channel.',
            ]);
        }

        if (trim((string) ($payload['neighborhood'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'delivery_address.neighborhood' => 'Neighborhood is required for this channel.',
            ]);
        }
    }
}
