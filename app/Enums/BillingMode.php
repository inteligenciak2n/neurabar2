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

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
