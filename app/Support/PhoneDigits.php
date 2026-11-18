<?php

namespace App\Support;

class PhoneDigits
{
    public static function normalize(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    public static function isValid(?string $phone): bool
    {
        $length = strlen(self::normalize($phone));

        return $length === 10 || $length === 11;
    }
}
