<?php

namespace App\Enums\Support;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::InProgress => 'Em Atendimento',
            self::Resolved => 'Resolvido',
            self::Closed => 'Encerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::InProgress => 'yellow',
            self::Resolved => 'green',
            self::Closed => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress], true);
    }

    /** @return list<self> */
    public static function openStatuses(): array
    {
        return [self::Open, self::InProgress];
    }
}
