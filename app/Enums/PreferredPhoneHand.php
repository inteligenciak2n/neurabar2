<?php

namespace App\Enums;

enum PreferredPhoneHand: string
{
    case Right = 'right';
    case Left = 'left';

    public static function default(): self
    {
        return self::Right;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
