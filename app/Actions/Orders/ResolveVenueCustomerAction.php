<?php

namespace App\Actions\Orders;

use App\Models\Orders\VenueCustomer;
use App\Models\Tenant\Venue;
use App\Support\PhoneDigits;

class ResolveVenueCustomerAction
{
    public function execute(
        Venue $venue,
        string $name,
        string $phone,
        ?string $address = null,
        ?string $comment = null,
    ): VenueCustomer {
        $customer = VenueCustomer::query()->firstOrNew([
            'venue_id' => $venue->id,
            'phone' => PhoneDigits::normalize($phone),
        ]);

        $customer->name = $name;

        if ($address !== null) {
            $customer->address = $address !== '' ? $address : null;
        }

        if ($comment !== null) {
            $customer->comment = $comment !== '' ? $comment : null;
        }

        $customer->save();

        return $customer;
    }
}
