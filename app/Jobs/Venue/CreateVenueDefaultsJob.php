<?php

namespace App\Jobs\Venue;

use App\Actions\Corporation\CreateVenueDefaultsAction;
use App\Models\Tenant\Venue;
use App\Services\TenantConnectionResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Provisiona os dados iniciais da venue (settings, estações, cardápio, mesas)
 * fora do request.
 *
 * A criação era síncrona dentro da transação de cadastro: dezenas de inserts
 * seguravam o usuário na tela e mantinham a transação aberta.
 */
class CreateVenueDefaultsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    /**
     * @param  array<string, mixed>  $venueSettings
     */
    public function __construct(
        private readonly Venue $venue,
        private readonly array $venueSettings = [],
    ) {
        $this->afterCommit();
    }

    public function venueId(): string
    {
        return (string) $this->venue->id;
    }

    public function handle(CreateVenueDefaultsAction $action, TenantConnectionResolver $resolver): void
    {
        // O worker não passa pelo middleware de contexto: sem isso os models
        // operacionais gravariam na conexão errada.
        $this->venue->load('corporation');

        app()->instance('tenant', $this->venue);
        app()->instance('operational_connection', $resolver->resolve($this->venue));
        app()->instance('operational_is_dedicated', (bool) $this->venue->corporation?->is_dedicated);

        $action->execute($this->venue, $this->venueSettings);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('venue.defaults.failed', [
            'venue_id' => $this->venue->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
