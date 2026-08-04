<?php

namespace App\Services\Subscription;

use App\Exceptions\Subscription\GatewayUnavailableException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Disjuntor das chamadas síncronas ao gateway de pagamento.
 *
 * Cada chamada espera até 60s pela resposta. Com o provedor fora do ar, todos
 * os usuários ficavam presos nesse timeout e os workers acumulavam conexões
 * abertas. Depois de N falhas seguidas de infraestrutura o circuito abre e as
 * chamadas seguintes falham na hora, até o período de descanso passar.
 */
class GatewayCircuitBreaker
{
    private readonly int $threshold;

    private readonly int $cooldown;

    public function __construct(private readonly string $gateway = 'asaas')
    {
        $this->threshold = max(1, (int) config('services.asaas.circuit_breaker.threshold', 5));
        $this->cooldown = max(1, (int) config('services.asaas.circuit_breaker.cooldown', 60));
    }

    /**
     * @throws GatewayUnavailableException Quando o circuito está aberto.
     */
    public function call(Closure $callback): Response
    {
        if ($this->isOpen()) {
            throw new GatewayUnavailableException("Circuit open for gateway [{$this->gateway}].", 'circuit_open');
        }

        try {
            $response = $callback();
        } catch (ConnectionException $exception) {
            $this->recordFailure();

            throw $exception;
        }

        // Erros 4xx são de negócio (cartão recusado, payload inválido) e não
        // indicam indisponibilidade: só 5xx e falhas de conexão abrem o circuito.
        if ($response->serverError()) {
            $this->recordFailure();

            return $response;
        }

        $this->recordSuccess();

        return $response;
    }

    public function isOpen(): bool
    {
        return Cache::get($this->openKey()) !== null;
    }

    private function recordFailure(): void
    {
        $failures = (int) Cache::get($this->failuresKey(), 0) + 1;

        Cache::put($this->failuresKey(), $failures, $this->cooldown);

        if ($failures >= $this->threshold) {
            Cache::put($this->openKey(), true, $this->cooldown);
            Cache::forget($this->failuresKey());

            Log::error('gateway.circuit.opened', [
                'gateway' => $this->gateway,
                'failures' => $failures,
                'cooldown_seconds' => $this->cooldown,
            ]);
        }
    }

    private function recordSuccess(): void
    {
        Cache::forget($this->failuresKey());
    }

    private function failuresKey(): string
    {
        return "gateway:{$this->gateway}:failures";
    }

    private function openKey(): string
    {
        return "gateway:{$this->gateway}:open";
    }
}
