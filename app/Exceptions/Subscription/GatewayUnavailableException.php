<?php

namespace App\Exceptions\Subscription;

/**
 * Lançada quando o circuito do gateway está aberto: o provedor acumulou falhas
 * de infraestrutura e novas chamadas são recusadas de imediato, em vez de
 * segurar o request do usuário até o timeout.
 */
class GatewayUnavailableException extends GatewayRequestException
{
    public function userMessage(): string
    {
        return __('The payment provider is temporarily unavailable. Please try again in a few minutes.');
    }
}
