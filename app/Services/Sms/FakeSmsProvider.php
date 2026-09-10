<?php

namespace App\Services\Sms;

use App\Contracts\Sms\SmsProviderContract;
use Illuminate\Support\Str;

class FakeSmsProvider implements SmsProviderContract
{
    public function sendSms(string $to, string $message): array
    {
        return ['sid' => 'fake_sms_'.Str::uuid(), 'status' => 'sent'];
    }

    public function requestOtp(string $to, string $appName): array
    {
        return ['reference_id' => 'fake_otp_'.Str::uuid()];
    }

    public function validateOtp(string $to, string $referenceId, string $otpCode): bool
    {
        return true;
    }
}
