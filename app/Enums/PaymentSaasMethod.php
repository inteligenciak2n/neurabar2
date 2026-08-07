<?php

namespace App\Enums;

enum PaymentSaasMethod: string
{
    case CreditCard = 'credit_card';
    case Pix = 'pix';
    case Boleto = 'boleto';

    public function label(): string
    {
        return match ($this) {
            self::CreditCard => 'Cartão de Crédito',
            self::Pix => 'PIX',
            self::Boleto => 'Boleto Bancário',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $method): array => [
            'value' => $method->value,
            'label' => $method->label(),
        ], self::cases());
    }
}
