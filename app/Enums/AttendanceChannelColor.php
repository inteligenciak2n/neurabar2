<?php

namespace App\Enums;

enum AttendanceChannelColor: string
{
    case Navy = '#293b4f';
    case Blue = '#2563eb';
    case Orange = '#ea580c';
    case Green = '#16a34a';
    case Purple = '#7c3aed';
    case Red = '#dc2626';
    case Teal = '#0d9488';
    case Pink = '#db2777';

    public static function default(): self
    {
        return self::Navy;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
