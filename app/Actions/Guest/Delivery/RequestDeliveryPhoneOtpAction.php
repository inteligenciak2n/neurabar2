<?php

namespace App\Actions\Guest\Delivery;

use App\Facades\Sms;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Venue;

class RequestDeliveryPhoneOtpAction
{
    /**
     * @return array{reference_id: string}
     */
    public function execute(Venue $venue, string $phone): array
    {
        // Only send a real OTP for numbers already known to the corporation: the
        // frontend only offers this flow after the lookup endpoint said found=true,
        // so this doesn't add a new way to probe for unknown phones.
        $exists = Customer::withoutGlobalScopes()
            ->where('corporation_id', $venue->corporation_id)
            ->where('phone', $phone)
            ->exists();

        abort_unless($exists, 404);

        return Sms::requestOtp($phone, config('app.name'));
    }
}
