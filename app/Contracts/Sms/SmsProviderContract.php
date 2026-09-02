<?php

namespace App\Contracts\Sms;

interface SmsProviderContract
{
    /**
     * Send a plain, one-off SMS message.
     *
     * @return array{sid: string, status: string}
     */
    public function sendSms(string $to, string $message): array;

    /**
     * Request an OTP code to be generated and delivered to the given phone number.
     *
     * @return array{reference_id: string}
     */
    public function requestOtp(string $to, string $appName): array;

    /**
     * Validate an OTP code previously requested for the given phone number.
     */
    public function validateOtp(string $to, string $referenceId, string $otpCode): bool;
}
