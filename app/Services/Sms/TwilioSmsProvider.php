<?php

namespace App\Services\Sms;

use App\Contracts\Sms\SmsProviderContract;
use Twilio\Rest\Client;

class TwilioSmsProvider implements SmsProviderContract
{
    public function __construct(private readonly Client $client) {}

    public function sendSms(string $to, string $message): array
    {
        $result = $this->client->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message,
        ]);

        return ['sid' => $result->sid, 'status' => $result->status];
    }

    /**
     * $appName is part of the contract for parity with other providers; Twilio
     * Verify scopes the OTP by the configured Verify Service instead.
     */
    public function requestOtp(string $to, string $appName): array
    {
        $verification = $this->client->verify->v2
            ->services(config('services.twilio.verify_sid'))
            ->verifications
            ->create($to, 'sms');

        return ['reference_id' => $verification->sid];
    }

    /**
     * $referenceId is part of the contract for parity with other providers; Twilio
     * Verify matches the check by phone number within the configured service.
     */
    public function validateOtp(string $to, string $referenceId, string $otpCode): bool
    {
        $check = $this->client->verify->v2
            ->services(config('services.twilio.verify_sid'))
            ->verificationChecks
            ->create(['code' => $otpCode, 'to' => $to]);

        return $check->status === 'approved';
    }
}
