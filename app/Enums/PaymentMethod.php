<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case Pix = 'pix';
    case Other = 'other';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
