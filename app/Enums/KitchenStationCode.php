<?php

namespace App\Enums;

enum KitchenStationCode: string
{
    case WaiterCaller = 'waiter_caller';

    public function defaultName(): string
    {
        return match ($this) {
            self::WaiterCaller => 'Chamador de Garçom (mensagem texto)',
        };
    }
}
