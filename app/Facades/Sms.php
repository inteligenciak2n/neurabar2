<?php

namespace App\Facades;

use App\Contracts\Sms\SmsProviderContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendSms(string $to, string $message)
 * @method static array requestOtp(string $to, string $appName)
 * @method static bool validateOtp(string $to, string $referenceId, string $otpCode)
 *
 * @see SmsProviderContract
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsProviderContract::class;
    }
}
