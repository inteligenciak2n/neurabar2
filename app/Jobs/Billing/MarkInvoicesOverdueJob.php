<?php

namespace App\Jobs\Billing;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkInvoicesOverdueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        VenueInvoice::query()
            ->where('status', 'open')
            ->where('due_date', '<', today())
            ->where('is_finalized', false)
            ->update(['status' => 'overdue', 'is_finalized' => true]);

        CorporationInvoice::query()
            ->where('status', 'open')
            ->where('due_date', '<', today())
            ->where('is_finalized', false)
            ->update(['status' => 'overdue', 'is_finalized' => true]);
    }
}
