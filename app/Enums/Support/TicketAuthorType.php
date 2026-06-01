<?php

namespace App\Enums\Support;

enum TicketAuthorType: string
{
    case User = 'user';
    case PlatformUser = 'platform_user';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Cliente',
            self::PlatformUser => 'Suporte',
        };
    }
}
