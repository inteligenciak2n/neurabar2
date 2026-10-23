<?php

namespace App\Support;

class TabCode
{
    public static function normalizePrefix(?string $prefix): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $prefix));
    }

    public static function prefixEndsWithDigit(string $prefix): bool
    {
        return $prefix !== '' && preg_match('/\d$/', $prefix) === 1;
    }

    public static function prefixFromCode(string $code): string
    {
        $code = strtoupper(trim($code));

        if (preg_match('/^([A-Z0-9]+)-(\d+)$/', $code, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^([A-Z]*)(\d+)$/', $code, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^[A-Z]+$/', $code) === 1) {
            return $code;
        }

        return '';
    }

    public static function compose(string $prefix, int $number): string
    {
        $prefix = self::normalizePrefix($prefix);

        if ($prefix === '') {
            return (string) $number;
        }

        if (self::prefixEndsWithDigit($prefix)) {
            return $prefix.'-'.$number;
        }

        return $prefix.(string) $number;
    }
}
