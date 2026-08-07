<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case Base = 'base';
    case Module = 'module';
    case Metered = 'metered';
    case Surcharge = 'surcharge';
    case Discount = 'discount';

    public function label(): string
    {
        return match ($this) {
            self::Base => 'Mensalidade',
            self::Module => 'Módulos',
            self::Metered => 'Consumo medido',
            self::Surcharge => 'Infraestrutura dedicada',
            self::Discount => 'Desconto',
        };
    }
}
