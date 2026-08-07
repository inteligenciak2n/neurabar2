<?php

namespace App\Jobs\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Notifications\Billing\InvoiceOverdue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class MarkInvoicesOverdueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function handle(): void
    {
        VenueInvoice::query()
            ->where('status', InvoiceStatus::Open->value)
            ->where('due_date', '<', today())
            ->where('is_finalized', false)
            ->with('corporation.owner')
            ->chunkById(100, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $invoice->update(['status' => InvoiceStatus::Overdue->value]);
                    $this->notifyOwner($invoice);
                }
            });

        CorporationInvoice::query()
            ->where('status', InvoiceStatus::Open->value)
            ->where('due_date', '<', today())
            ->where('is_finalized', false)
            ->with('corporation.owner')
            ->chunkById(100, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    $invoice->update(['status' => InvoiceStatus::Overdue->value]);
                    $this->notifyOwner($invoice);
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('billing.mark_invoices_overdue.failed', [
            'message' => $exception->getMessage(),
        ]);
    }

    private function notifyOwner(VenueInvoice|CorporationInvoice $invoice): void
    {
        $owner = $invoice->corporation?->owner;

        if ($owner) {
            Notification::send($owner, new InvoiceOverdue($invoice));
        }
    }
}
