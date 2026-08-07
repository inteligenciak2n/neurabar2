<?php

namespace App\Jobs\Billing;

use App\Models\Tenant\VenueUsageRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordModuleUsageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $venueId,
        private readonly string $moduleCode,
        private readonly int $quantity = 1,
    ) {}

    public function handle(): void
    {
        $period = now()->format('Y-m');

        $attributes = [
            'venue_id' => $this->venueId,
            'module_code' => $this->moduleCode,
            'period' => $period,
        ];

        try {
            $record = VenueUsageRecord::firstOrCreate($attributes, ['quantity' => 0]);
        } catch (QueryException) {
            // Outro worker criou o registro concorrentemente (unique constraint em
            // venue_id/module_code/period). Recupera a linha já existente.
            $record = VenueUsageRecord::query()->where($attributes)->firstOrFail();
        }

        // increment() executa "quantity = quantity + ?" atomicamente no banco,
        // evitando lost update quando múltiplos workers processam o mesmo período.
        $record->increment('quantity', $this->quantity);
    }
}
