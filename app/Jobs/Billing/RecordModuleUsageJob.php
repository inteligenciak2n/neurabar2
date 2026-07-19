<?php

namespace App\Jobs\Billing;

use App\Models\Tenant\VenueUsageRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

        $record = VenueUsageRecord::firstOrNew(
            [
                'venue_id' => $this->venueId,
                'module_code' => $this->moduleCode,
                'period' => $period,
            ],
            [
                'quantity' => 0,
            ]
        );

        $record->quantity += $this->quantity;
        $record->save();
    }
}
