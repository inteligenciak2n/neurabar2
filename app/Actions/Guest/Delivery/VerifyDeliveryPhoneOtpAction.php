<?php

namespace App\Actions\Guest\Delivery;

use App\Facades\Sms;
use App\Models\GuestSession;

class VerifyDeliveryPhoneOtpAction
{
    public function execute(GuestSession $session, string $phone, string $referenceId, string $code): bool
    {
        $valid = Sms::validateOtp($phone, $referenceId, $code);

        if ($valid) {
            $session->update(['verified_phone' => $phone, 'phone_verified_at' => now()]);
        }

        return $valid;
    }
}
