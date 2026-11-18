<?php

namespace App\Actions\Orders;

use App\Actions\Delivery\CreateDeliveryFeeZoneAction;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Tenant\Venue;
use App\Support\NeighborhoodMatcher;

class RegisterAttendantNeighborhoodAction
{
    public function __construct(private CreateDeliveryFeeZoneAction $createFeeZone) {}

    /**
     * @param  array{name: string, zip_code: string, fee: float|int|string}  $data
     */
    public function execute(Venue $venue, array $data): DeliveryFeeZone
    {
        $name = trim($data['name']);
        $zip = (int) (preg_replace('/\D+/', '', (string) $data['zip_code']) ?? '0');
        $fee = round((float) $data['fee'], 2);

        $existing = $this->findExisting($venue, $name);

        if ($existing) {
            return $existing;
        }

        $sortOrder = (int) DeliveryFeeZone::query()
            ->where('venue_id', $venue->id)
            ->max('sort_order');

        return $this->createFeeZone->execute($venue, [
            'label' => $name,
            'zip_code_start' => $zip,
            'zip_code_end' => $zip,
            'fee' => $fee,
            'active' => true,
            'sort_order' => $sortOrder + 1,
        ]);
    }

    private function findExisting(Venue $venue, string $name): ?DeliveryFeeZone
    {
        $needle = NeighborhoodMatcher::normalize($name);

        if ($needle === '') {
            return null;
        }

        return DeliveryFeeZone::query()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->whereNotNull('label')
            ->where('label', '!=', '')
            ->get()
            ->first(fn (DeliveryFeeZone $zone): bool => NeighborhoodMatcher::normalize($zone->label) === $needle);
    }
}
