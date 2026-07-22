<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Paid = 'paid';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::Overdue => 'Vencida',
            self::Paid => 'Paga',
            self::Canceled => 'Cancelada',
            self::Refunded => 'Reembolsada',
        };
    }

    public function isFinalized(): bool
    {
        return in_array($this, [self::Paid, self::Canceled, self::Refunded], true);
    }

    public function canTransitionTo(self $status): bool
    {
        if ($this === $status) {
            return false;
        }

        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * @return list<self>
     */
    private function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::Overdue, self::Paid, self::Canceled],
            self::Overdue => [self::Paid, self::Canceled],
            self::Paid => [self::Refunded],
            default => [],
        };
    }
}
