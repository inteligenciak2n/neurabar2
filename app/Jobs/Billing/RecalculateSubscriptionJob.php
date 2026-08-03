<?php

namespace App\Jobs\Billing;

use App\Models\Tenant\Venue;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateSubscriptionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly Venue $venue,
        private readonly ?string $period = null,
    ) {}

    public function handle(SubscriptionCalculator $calculator): void
    {
        $period = $this->period ?? now()->format('Y-m');

        $calculator->refreshVenueSnapshot($this->venue, $period);
    }
}
