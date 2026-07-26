<?php

namespace App\Actions\Subscription;

use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use InvalidArgumentException;

class UpdateBillingAddressAction
{
    public function execute(Corporation|Venue $billable, array $data): void
    {
        if ($billable instanceof Venue && $billable->corporation === null) {
            throw new InvalidArgumentException('Venue must belong to a corporation.');
        }

        $address = [
            'street' => $data['street'] ?? null,
            'number' => $data['number'] ?? null,
            'complement' => $data['complement'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
            'country' => $data['country'] ?? 'BR',
        ];

        $payload = [
            'billing_address_json' => array_filter($address, fn ($value) => $value !== null),
        ];

        if ($billable instanceof Corporation) {
            $payload['billing_tax_regime'] = $data['billing_tax_regime'] ?? $billable->billing_tax_regime;
            $payload['billing_state_registration'] = $data['billing_state_registration'] ?? $billable->billing_state_registration;
        }

        if ($billable instanceof Venue) {
            $payload['billing_email'] = $data['billing_email'] ?? $billable->billing_email;
            $payload['billing_phone'] = $data['billing_phone'] ?? $billable->billing_phone;
        }

        $billable->update($payload);
    }
}
