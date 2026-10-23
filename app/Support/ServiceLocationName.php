<?php

namespace App\Support;

class ServiceLocationName
{
    public static function compose(string $prefix, int $number): string
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return (string) $number;
        }

        if (preg_match('/\d$/u', $prefix) === 1) {
            return $prefix.'-'.$number;
        }

        return $prefix.' '.$number;
    }
}
