<?php

namespace App\Actions\Orders;

use App\Models\Orders\Attendance;
use App\Models\Orders\AttendanceDeliveryAddress;
use App\Models\Orders\VenueCustomer;
use App\Models\Orders\VenueCustomerAddress;
use App\Models\Settings\DeliveryFeeZone;
use App\Support\DeliveryAddressFormatter;
use App\Support\PhoneDigits;

class PersistVenueCustomerAddressAction
{
    /**
     * @param  array{
     *     zip_code?: string|null,
     *     street?: string|null,
     *     number?: string|null,
     *     complement?: string|null,
     *     delivery_fee_zone_id?: string|null,
     *     neighborhood?: string|null,
     *     city?: string|null,
     *     state?: string|null,
     *     reference_point?: string|null,
     *     delivery_phone?: string|null,
     *     delivery_fee?: float|int|string|null
     * }  $data
     */
    public function execute(VenueCustomer $customer, Attendance $attendance, array $data): AttendanceDeliveryAddress
    {
        $zip = preg_replace('/\D+/', '', (string) ($data['zip_code'] ?? '')) ?? '';
        $street = trim((string) ($data['street'] ?? ''));
        $number = trim((string) ($data['number'] ?? ''));
        $zone = $this->resolveZone($customer->venue_id, $data['delivery_fee_zone_id'] ?? null);
        $neighborhood = trim((string) ($data['neighborhood'] ?? '')) ?: (string) ($zone?->label ?? '');
        $city = trim((string) ($data['city'] ?? ''));
        $state = strtoupper(trim((string) ($data['state'] ?? '')));
        $complement = trim((string) ($data['complement'] ?? '')) ?: null;
        $reference = trim((string) ($data['reference_point'] ?? '')) ?: null;
        $deliveryPhone = PhoneDigits::normalize($data['delivery_phone'] ?? null) ?: null;

        $saved = VenueCustomerAddress::query()->firstOrNew([
            'venue_customer_id' => $customer->id,
            'zip_code' => $zip,
            'number' => $number,
        ]);

        $saved->fill([
            'venue_id' => $customer->venue_id,
            'street' => $street,
            'complement' => $complement,
            'delivery_fee_zone_id' => $zone?->id,
            'neighborhood' => $neighborhood,
            'city' => $city,
            'state' => $state,
            'reference_point' => $reference,
            'delivery_phone' => $deliveryPhone,
        ]);
        $saved->save();

        $snapshot = [
            'venue_customer_address_id' => $saved->id,
            'delivery_fee_zone_id' => $zone?->id,
            'delivery_fee' => array_key_exists('delivery_fee', $data) && $data['delivery_fee'] !== null && $data['delivery_fee'] !== ''
                ? round((float) $data['delivery_fee'], 2)
                : (float) ($zone?->fee ?? 0),
            'zip_code' => $zip,
            'street' => $street,
            'number' => $number,
            'complement' => $complement,
            'neighborhood' => $neighborhood,
            'city' => $city,
            'state' => $state,
            'reference_point' => $reference,
            'delivery_phone' => $deliveryPhone,
        ];

        $customer->forceFill([
            'address' => DeliveryAddressFormatter::line($snapshot),
        ])->save();

        return AttendanceDeliveryAddress::query()->updateOrCreate(
            ['attendance_id' => $attendance->id],
            $snapshot,
        );
    }

    private function resolveZone(string $venueId, mixed $zoneId): ?DeliveryFeeZone
    {
        if (! is_string($zoneId) || $zoneId === '') {
            return null;
        }

        return DeliveryFeeZone::query()
            ->where('venue_id', $venueId)
            ->where('active', true)
            ->whereKey($zoneId)
            ->first();
    }
}
