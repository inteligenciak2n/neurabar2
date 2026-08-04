<?php

namespace Tests\Feature\Subscription;

use App\Exceptions\Subscription\GatewayUnavailableException;
use App\Services\Subscription\GatewayCircuitBreaker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayCircuitBreakerTest extends TestCase
{
    private int $status = 200;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('services.asaas.circuit_breaker.threshold', 3);
        config()->set('services.asaas.circuit_breaker.cooldown', 60);

        // Um único stub dinâmico: chamadas repetidas a Http::fake() apenas
        // acumulam stubs, e o primeiro registrado continuaria respondendo.
        Http::fake(fn () => Http::response([], $this->status));
    }

    public function test_it_opens_after_consecutive_connection_failures(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $breaker = new GatewayCircuitBreaker;

        foreach (range(1, 3) as $ignored) {
            try {
                $breaker->call(fn () => Http::get('https://gateway.test/payments'));
            } catch (ConnectionException) {
                // Esperado: a falha alimenta o contador do disjuntor.
            }
        }

        $this->assertTrue($breaker->isOpen());

        $this->expectException(GatewayUnavailableException::class);

        $breaker->call(fn () => Http::get('https://gateway.test/payments'));
    }

    public function test_server_errors_open_the_circuit_but_client_errors_do_not(): void
    {
        $breaker = new GatewayCircuitBreaker;
        $this->status = 422;

        foreach (range(1, 5) as $ignored) {
            $breaker->call(fn () => Http::get('https://gateway.test/payments'));
        }

        $this->assertFalse($breaker->isOpen());

        $this->status = 500;

        foreach (range(1, 3) as $ignored) {
            $breaker->call(fn () => Http::get('https://gateway.test/payments'));
        }

        $this->assertTrue($breaker->isOpen());
    }

    public function test_a_successful_call_resets_the_failure_counter(): void
    {
        $breaker = new GatewayCircuitBreaker;
        $this->status = 500;

        $breaker->call(fn () => Http::get('https://gateway.test/payments'));
        $breaker->call(fn () => Http::get('https://gateway.test/payments'));

        $this->status = 200;
        $breaker->call(fn () => Http::get('https://gateway.test/payments'));

        $this->status = 500;
        $breaker->call(fn () => Http::get('https://gateway.test/payments'));
        $breaker->call(fn () => Http::get('https://gateway.test/payments'));

        $this->assertFalse($breaker->isOpen());
    }
}
