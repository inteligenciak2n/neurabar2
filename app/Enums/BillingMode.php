<?php

namespace App\Enums;

enum BillingMode: string
{
    case PerVenue = 'per_venue';
    case Unified = 'unified';

    public function label(): string
    {
        return match ($this) {
            self::PerVenue => 'Por Estabelecimento',
            self::Unified => 'Fatura Unificada',
        };
    }
}
